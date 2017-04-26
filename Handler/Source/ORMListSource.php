<?php

namespace Nfq\Bundle\EntityListBundle\Handler\Source;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Nfq\Bundle\EntityListBundle\Mapping\EntityList;
use Nfq\Bundle\EntityListBundle\Mapping\Metadata\ListMetadata;
use Nfq\Bundle\EntityListBundle\ValueObject\Filter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ORMListSource.
 */
class ORMListSource extends AbstractListSource
{
    /**
     * @var EntityRepository
     */
    protected $entityRepository;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var QueryBuilder
     */
    protected $customQueryBuilder;

    /**
     * @var string
     */
    protected $rootEntityAlias;

    /**
     * @var string
     */
    protected $hydrator;

    /**
     * @var array
     */
    protected $joins = [];

    /**
     * @var QueryBuilder
     */
    protected $qbWithFiltersAndSearch;

    /**
     * Constructor.
     *
     * @param ListMetadata      $listMetadata
     * @param array             $listHandlerConfig
     * @param EntityRepository  $entityRepository
     * @param QueryBuilder|null $customQueryBuilder
     * @param string            $rootEntityAlias
     * @param string|int        $hydrator
     */
    public function __construct(
        ListMetadata $listMetadata,
        array $listHandlerConfig,
        EntityRepository $entityRepository,
        $customQueryBuilder = null,
        $rootEntityAlias = self::ROOT_ENTITY_ALIAS,
        $hydrator = Query::HYDRATE_OBJECT
    ) {
        parent::__construct($listMetadata, $listHandlerConfig);
        $this->entityRepository = $entityRepository;
        $this->entityName = $entityRepository->getClassName();
        $this->customQueryBuilder = $customQueryBuilder;
        $this->rootEntityAlias = $rootEntityAlias;
        $this->hydrator = $hydrator;
        $this->initJoins();
    }

    /**
     * Handles entity list data source.
     *
     * Applies:
     *  - sorting
     *  - filters
     *  - search
     *  - grouping
     *
     * @param Request $request
     *
     * @return array
     */
    public function processSource(Request $request)
    {
        $qb = clone $this->createQueryBuilderWithFiltersAndSearch($request);

        $this->addPagination($qb, $request);
        $this->addOrderBy($qb, $request);

        if ($this->listMetadata->getGroupBy()) {
            $qb->addGroupBy(sprintf('%s.%s', $this->rootEntityAlias, $this->listMetadata->getGroupBy()));
        }

        return $qb->getQuery()->getResult($this->hydrator);
    }

    /**
     * Returns found list items count.
     *
     * @param Request $request
     *
     * @return int
     */
    public function getItemsCount(Request $request)
    {
        $qb = clone $this->createQueryBuilderWithFiltersAndSearch($request);

        // Reset default select part and add select count.
        $qb->resetDQLPart('select');
        $qb->resetDQLPart('orderBy');
        $qb->resetDQLPart('groupBy');
        $qb->select($qb->expr()->count(sprintf('DISTINCT %s.id', $this->rootEntityAlias)));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Creates query builder with filter and search condition.
     *
     * @param Request $request
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilderWithFiltersAndSearch(Request $request)
    {
        if (!$this->qbWithFiltersAndSearch) {
            $this->qbWithFiltersAndSearch = $this->getQueryBuilder();

            $this->addFilterConditions($this->qbWithFiltersAndSearch, $request);
            $this->addSearchConditions($this->qbWithFiltersAndSearch, $request);
        }

        return $this->qbWithFiltersAndSearch;
    }

    /**
     * Returns query builder for entity list.
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        if ($this->customQueryBuilder) {
            return $this->customQueryBuilder;
        }

        return $this->entityRepository->createQueryBuilder($this->rootEntityAlias);
    }

    /**
     * Initializes possibles joins.
     */
    protected function initJoins()
    {
        foreach ($this->listMetadata->getSortFieldsMappings() as $sortFieldMappings) {
            if ($sortFieldMappings['target'] != EntityList::FIELD_TARGET_RELATION) {
                continue;
            }
            $this->joins[$sortFieldMappings['joinField']] = [
                'field' => $this->rootEntityAlias . '.' . $sortFieldMappings['joinField'],
                'joinType' => $sortFieldMappings['joinType'],
            ];
        }

        foreach ($this->getFilterFieldsMappings() as $filterFieldMappings) {
            if ($filterFieldMappings['target'] != EntityList::FIELD_TARGET_RELATION) {
                continue;
            }
            $this->joins[$filterFieldMappings['joinField']] = [
                'field' => $this->rootEntityAlias . '.' . $filterFieldMappings['joinField'],
                'joinType' => $filterFieldMappings['joinType'],
            ];
        }

        foreach ($this->getSearchFieldsMappings() as $searchFieldMappings) {
            if ($searchFieldMappings['target'] != EntityList::FIELD_TARGET_RELATION) {
                continue;
            }
            $this->joins[$searchFieldMappings['joinField']] = [
                'field' => $this->rootEntityAlias . '.' . $searchFieldMappings['joinField'],
                'joinType' => $searchFieldMappings['joinType'],
            ];
        }
    }

    /**
     * Adds filter conditions to query builder.
     *
     * @param QueryBuilder $qb
     * @param Request      $request
     *
     * @return void
     */
    protected function addFilterConditions(QueryBuilder $qb, Request $request)
    {
        $filters = $this->extractFilters($request);
        if (!$filters) {
            return;
        }

        $filterFieldsMappings = $this->getFilterFieldsMappings();
        $condition = $qb->expr()->andx();
        $parameters = [];
        $paramIndex = 0;
        foreach ($filters as $filter) {
            $this->validateFilter($filter, $filterFieldsMappings);

            $filterMetadata = $filterFieldsMappings[$filter->getField()];
            $fieldName = $this->getFilterFieldName($filter, $qb);
            $operator = $this->normalizeOperator($filter->getOperator());
            if ($operator == Filter::OPERATOR_BTW) {
                // If on valueFrom given then greater than or equal to.
                if ($filter->getValue()['from'] && !$filter->getValue()['to']) {
                    $value = $filter->getValue()['from'];
                    $operator = Filter::OPERATOR_GTE;
                    $this->addFilterCondition($qb, $condition, $filter, $value, $paramIndex, $parameters, $operator);
                    $paramIndex++;
                    continue;
                }

                // If on valueTo given then less than or equal to.
                if (!$filter->getValue()['from'] && $filter->getValue()['to']) {
                    $value = $filter->getValue()['to'];
                    $operator = Filter::OPERATOR_LTE;
                    $this->addFilterCondition($qb, $condition, $filter, $value, $paramIndex, $parameters, $operator);
                    $paramIndex++;
                    continue;
                }

                $bindIndexFromPlaceholder = sprintf('?%s', $paramIndex);
                $bindIndexToPlaceholder = sprintf('?%s', $paramIndex + 1);
                $q = $qb->expr()->between($fieldName, $bindIndexFromPlaceholder, $bindIndexToPlaceholder);
                $condition->add($q);

                $parameters[$paramIndex] = $this->normalizeValue(
                    $operator,
                    $filter->getValue()['from'],
                    $filterMetadata
                );
                $parameters[$paramIndex + 1] = $this->normalizeValue(
                    $operator,
                    $filter->getValue()['to'],
                    $filterMetadata
                );
                $paramIndex += 2;
            } else {
                $value = $filter->getValue()['from'];
                $this->addFilterCondition($qb, $condition, $filter, $value, $paramIndex, $parameters, $operator);
                $paramIndex++;
            }
        }

        $qb->andWhere($condition);
        foreach ($parameters as $paramKey => $paramValue) {
            $qb->setParameter($paramKey, $paramValue);
        }
    }

    /**
     * Returns filter field name and adds join if field target is relation.
     *
     * @param Filter       $filter
     * @param QueryBuilder $qb
     *
     * @return string
     */
    protected function getFilterFieldName($filter, $qb)
    {
        $filterFieldsMappings = $this->getFilterFieldsMappings();
        $filterMetadata = $filterFieldsMappings[$filter->getField()];

        $filterTarget = $filterMetadata['target'];
        $fieldName = sprintf('%s.%s', $this->rootEntityAlias, $filter->getField());
        if ($filterTarget == EntityList::FIELD_TARGET_RELATION) {
            $joinField = $filterMetadata['joinField'];
            $this->addJoin($qb, $joinField);
            $fieldName = $filterMetadata['name'];
        }

        return $fieldName;
    }

    /**
     * Adds filter condition.
     *
     * @param QueryBuilder $qb
     * @param Expr\Andx    $whereCondition
     * @param Filter       $filter
     * @param string       $value
     * @param int          $paramIndex
     * @param array        $allParameters
     * @param string       $normalizedOperator
     */
    protected function addFilterCondition(
        $qb,
        $whereCondition,
        $filter,
        $value,
        $paramIndex,
        &$allParameters,
        $normalizedOperator
    ) {
        $filterFieldsMappings = $this->getFilterFieldsMappings();
        $filterMetadata = $filterFieldsMappings[$filter->getField()];

        $fieldName = $this->getFilterFieldName($filter, $qb);

        $bindIndexPlaceholder = sprintf('?%s', $paramIndex);
        $q = $qb->expr()->$normalizedOperator($fieldName, $bindIndexPlaceholder);
        if ($filter->getOperator() == Filter::OPERATOR_NLIKE) {
            $q = $qb->expr()->not($q);
        }

        $whereCondition->add($q);
        $allParameters[$paramIndex] = $this->normalizeValue($normalizedOperator, $value, $filterMetadata);
    }

    /**
     * Adds search conditions to query builder.
     *
     * @param QueryBuilder $qb
     * @param Request      $request
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addSearchConditions(QueryBuilder $qb, Request $request)
    {
        $filtersData = $request->query->get($this->listHandlerConfig['filters_param_name'], []);
        if (!isset($filtersData[$this->listHandlerConfig['search_param_name']])) {
            return;
        }

        if (!$this->getSearchFieldsMappings()) {
            throw new \InvalidArgumentException('This list has not search feature');
        }

        $parameters = [];
        // Start from previous parameters.
        $paramIndex = count($qb->getParameters());
        $searchValueFull = (string)$filtersData[$this->listHandlerConfig['search_param_name']];
        $whereCondition = $qb->expr()->andX();
        foreach (explode(self::SEARCH_TERM_SEPARATOR, $searchValueFull) as $searchValue) {
            $fieldCondition = $qb->expr()->orX();
            foreach ($this->getSearchFieldsMappings() as $fieldName => $fieldData) {
                $searchWords = preg_split('/\s+/', $searchValue);
                $searchWordCondition = $qb->expr()->andX();
                foreach ($searchWords as $searchWord) {
                    $field = sprintf('%s.%s', $this->rootEntityAlias, $fieldName);
                    if (strpos($fieldName, '.') !== false) {
                        list($assocField, $searchField) = explode('.', $fieldName);
                        $this->addJoin($qb, $assocField);
                        $field = sprintf('%s.%s', $assocField, $searchField);
                    }

                    $bindIndexPlaceholder = sprintf('?%s', $paramIndex);
                    $searchWordCondition->add($qb->expr()->like($field, $bindIndexPlaceholder));
                    $parameters[$paramIndex] = $this->normalizeValue(Filter::OPERATOR_LIKE, $searchWord);
                    $paramIndex++;
                }
                $fieldCondition->add($searchWordCondition);
            }
            $whereCondition->add($fieldCondition);
        }

        $qb->andWhere($whereCondition);
        foreach ($parameters as $paramKey => $paramValue) {
            $qb->setParameter($paramKey, $paramValue);
        }
    }

    /**
     * Adds pagination to query builder.
     *
     * @param QueryBuilder $qb
     * @param Request      $request
     *
     * @return void
     */
    protected function addPagination(QueryBuilder $qb, Request $request)
    {
        $paginationData = $this->extractPagination($request);
        $pageNr = $paginationData['pageNr'];
        $pageLimit = $paginationData['pageLimit'];

        if ($pageNr > 1) {
            $qb->setFirstResult(($pageNr - 1) * $pageLimit);
        }

        if ($pageLimit) {
            $qb->setMaxResults($pageLimit);
        }
    }

    /**
     * Adds order by condition to query builder.
     *
     * @param QueryBuilder $qb
     * @param Request      $request
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addOrderBy(QueryBuilder $qb, Request $request)
    {
        $orderBy = $this->extractOrderBy($request);
        if (!count($orderBy)) {
            return;
        }

        // Reset default order by before applying from request.
        $qb->resetDQLPart('orderBy');
        foreach ($orderBy as $orderByData) {
            if (!in_array($orderByData['field'], $this->getSortableFields())) {
                throw new \InvalidArgumentException(
                    sprintf('Field "%s" is not sortable', $orderByData['field'])
                );
            }
            $sortFieldMetadata = $this->listMetadata->getSortFieldsMappings()[$orderByData['field']];
            $field = $sortFieldMetadata['name'];
            $derived = $sortFieldMetadata['derived'];

            if (!$derived) {
                $field = sprintf('%s.%s', $this->rootEntityAlias, $field);
            }

            if (!$derived && strpos($sortFieldMetadata['name'], '.') !== false) {
                list($assocField, $sortField) = explode('.', $sortFieldMetadata['name']);
                $this->addJoin($qb, $assocField);
                $field = sprintf('%s.%s', $assocField, $sortField);
            }

            $direction = 'ASC';
            if (isset($orderByData['direction'])) {
                $direction = $orderByData['direction'];
            }

            if (!in_array($direction, ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException(
                    sprintf('Order direction "%s" is not acceptable. It should be "ASC" or "DESC"', $direction)
                );
            }

            $qb->addOrderBy($field, $direction);
        }
    }

    /**
     * Joins given association if root entity has it and it is not already joined.
     *
     * @param QueryBuilder $qb
     * @param string       $joinField
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addJoin(QueryBuilder $qb, $joinField)
    {
        if (!isset($this->joins[$joinField])) {
            throw new \InvalidArgumentException(
                sprintf('Association "%s" does not exists in "%s"', $joinField, $this->entityName)
            );
        }

        $alreadyJoined = false;
        $joinsAdded = $qb->getDQLPart('join');
        if (isset($joinsAdded[$this->rootEntityAlias])) {
            $fullJoinField = sprintf('%s.%s', $this->rootEntityAlias, $joinField);
            /** @var Join $join */
            foreach ($joinsAdded[$this->rootEntityAlias] as $join) {
                if ($join->getJoin() == $fullJoinField) {
                    $alreadyJoined = true;

                    break;
                }
            }
        }

        if (!$alreadyJoined) {
            $joinData = $this->joins[$joinField];
            $joinType = $joinData['joinType'];
            $qb->$joinType($joinData['field'], $joinField);
        }
    }
}
