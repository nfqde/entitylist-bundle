NfqEntityListBundle custom query builder
===============================

By default bundle is prepared to work with doctrine ORM entity lists, but it also lets to use customized lists. You can change following things:
* doctrine query builder;
* doctrine results hydrator;
* list source:
    * ORMListSource - default, uses doctrine query builder to fetching, sorting, filtering
    * ArrayListSource - you can pass your own array of objects
    * ElasticListSource - entities source is elasticsearch server
* result converter

In order to customize your list, first of all you need to create custom list handler factory where you can pass your custom arguments to list source:

        # services.yml
        # ...
        custom_list_handler_factory:
            class: Acme\MyCustomBundle\Handler\MyEntityListHandlerFactory
            arguments:
                - "@nfq.entity_list.mapping.metadata.annotation_manager"
                - "%list_handler_config%"

Set custom query builder
-------------------------------------

By default ORM list source calls ```$entityRepository->createQueryBuilder('e')``` in order to create query builder. If you want to pass custom query builder to list source, then you need to use your list handler factory:

    ```
    <?php
    class MyEntityListHandlerFactory
    {
        // ...
        public function getEntityListHandler($entityRepository)
        {
            $metadata = $this->listMetadataManager->getListMetadata($entityRepository->getClassName());
            $listSource = new ORMListSource(
                $metadata,
                $this->listHandlerConfig,
                $entityRepository,
                $entityRepository->getMyCustomQueryBuilder()
            );

            return new EntityListHandler($listSource);
        }
    }
    ```

Change result hydrator
------------------------
Default result hydrator is ```Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT```. You can change it in custom list handler factory:

    ```
    <?php
    class MyEntityListHandlerFactory
    {
        // ...
        public function getEntityListHandler($entityRepository)
        {
            $metadata = $this->listMetadataManager->getListMetadata($entityRepository->getClassName());
            $listSource = new ORMListSource(
                $metadata,
                $this->listHandlerConfig,
                $entityRepository,
                null,
                'e', // alias of root entity
                'myCustomHydrator'
            );

            return new EntityListHandler($listSource);
        }
    }
    ```

Change list source
------------------------

    ```
    <?php
    class MyEntityListHandlerFactory
    {
        // ...
        public function getEntityListHandler($entityRepository)
        {
            $data = $this->getDataForList();
            $sortableFields = [
                'id' => ['target' => EntityList::FIELD_TARGET_DIRECT],
                'name' => ['target' => EntityList::FIELD_TARGET_DIRECT],
                'description' => ['target' => EntityList::FIELD_TARGET_DIRECT],
            ];

            $metadata = new ListMetadata(
                $sortableFields,
                [],
                [
                    'name' => ['target' => EntityList::FIELD_TARGET_DIRECT],
                    'description' => ['target' => EntityList::FIELD_TARGET_DIRECT],
                ]
            );
            $listSource = new ArrayListSource($metadata, $this->listHandlerConfig, $data);

            return new EntityListHandler($listSource);
        }

        protected function getDataForList()
        {
            return [
                [
                    'id' => 1,
                    'name' => 'foo',
                    'descrition' => 'foo bar',
                ],
                [
                    'id' => 1,
                    'name' => 'test',
                    'descrition' => 'foo bar baz',
                ],
                // ...
                [
                    'id' => 999,
                    'name' => 'foo',
                    'descrition' => 'foo bar',
                ],
            ];
        }
    ```

Change result converter
------------------------

Result converter lets to convert your list handler results to custom object. It is useful when you are using custom query builder with MySql COUNT, because in this case you result looks like:

    ```
    array:2 [
        0 => YourEntity {...}
        "total" => 500
    ]
    ```

Lets say that at the end you want to have array of MyCustomListItem instances. Then you need:
* Create your result converter which implements ```Nfq\Bundle\EntityListBundle\Handler\Result\ListResultConverterInterface```:

    ```
    <?php
    class MyListResultConverter implements ListResultConverterInterface
    {
        // ...
        public function convert(array $items)
        {
            $result = [];
            foreach ($items as $item) {
                $entity = $item[0];

                $result[] = new MyCustomListItem($entity->getFoo(), $entity->getBar());
            }

            return $result;
        }
    }
    ```