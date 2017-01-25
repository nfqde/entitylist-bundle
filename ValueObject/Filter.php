<?php

namespace Nfq\Bundle\EntityListBundle\ValueObject;

/**
 * Class Filter.
 */
class Filter
{
    const OPERATOR_EQ = 'eq';
    const OPERATOR_NEQ = 'neq';
    const OPERATOR_LT = 'lt';
    const OPERATOR_LTE = 'lte';
    const OPERATOR_GT = 'gt';
    const OPERATOR_GTE = 'gte';
    const OPERATOR_BTW = 'btw';
    const OPERATOR_LIKE = 'like';
    const OPERATOR_NLIKE = 'nlike';
    const OPERATOR_RLIKE = 'rlike';
    const OPERATOR_LLIKE = 'llike';
    const OPERATOR_IN = 'in';
    const OPERATOR_NOT_IN = 'notIn';
    const OPERATOR_ISNULL = 'isNull';
    const OPERATOR_ISNOTNULL = 'isNotNull';

    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var string|array
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param string       $field
     * @param string       $operator
     * @param string|array $value
     */
    public function __construct($field, $operator, $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * Getter for field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Getter for operator.
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Getter for value.
     *
     * @return string|array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns array of all acceptable filter operators.
     *
     * @return array
     */
    public static function getAcceptableOperators()
    {
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_NEQ,
            self::OPERATOR_LT,
            self::OPERATOR_LTE,
            self::OPERATOR_GT,
            self::OPERATOR_GTE,
            self::OPERATOR_BTW,
            self::OPERATOR_LIKE,
            self::OPERATOR_NLIKE,
            self::OPERATOR_RLIKE,
            self::OPERATOR_LLIKE,
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
            self::OPERATOR_ISNULL,
            self::OPERATOR_ISNOTNULL,
        ];
    }

    /**
     * Returns array of all acceptable filter operators.
     *
     * @return array
     */
    public static function getElasticAcceptableOperators()
    {
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_NEQ,
            self::OPERATOR_LT,
            self::OPERATOR_LTE,
            self::OPERATOR_GT,
            self::OPERATOR_GTE,
            self::OPERATOR_LIKE,
            self::OPERATOR_NLIKE,
        ];
    }
}
