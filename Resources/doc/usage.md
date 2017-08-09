NfqEntityListBundle usage
===============================

## Configure entity
**NfqEntityListBundle** requires to configure entities in order to enable possibility to generate list. You can do that with annotations. It's possible to define:
* sortable fields
* filterable fields
* global search fields
* group by field.

Minimum requirement is to add ```@Nfq\EntityListBundle\Mapping\EntityList``` annotation. In this case you will have only paging functionality. But if you want to enable sorting, filtering and/or global search, then you need to add extra parameters to annotations:
+ **sortableFields** - defines fields by with list can be sorted
    + definition should be object with following parameters:
        + target - ```relation``` (if field belongs to -TO-ONE relation) or ```direct``` (if field belongs to main entity);
        + joinType - required if target is **relation** (leftJoin, rightJoin, innerJoin or join);
        + joinField - required if target is **relation**, relation field, which should be joined;
        + name - sortable field name (default is same as key);
        + derived - is field derived or not, e.g. COUNT(id) as total (default false);
    + if field belongs to same entity, then fields can be defined as simple array of string items (names of fields);
+ **filterFields** - defines fields by which list can be filtered
    + definition should be object with following parameters:
        + operators - **required** filter operators (array);
        + target - ```direct``` (if field belongs to main entity) or ```relation``` (if field belongs to relation);
        + joinType - required if target is **relation** (leftJoin, rightJoin, innerJoin or join);
        + joinField - required if target is **relation**, relation field, which should be joined;
        + name - filter field name (default is same as key);
        + globalSearch - ```true``` or ```false```, if ```true``` then this field will be used in global search.
+ **searchFields** - fields which will be used in global search
    + definition should be object with following parameters:
        + target - ```direct``` (if field belongs to main entity) or ```relation``` (if field belongs to relation);
        + joinType - required if target is **relation** (leftJoin, rightJoin, innerJoin or join);
        + joinField - required if target is **relation**, relation field, which should be joined;
    + if field belongs to same entity, then fields can be defined as simple array of string items (names of fields);
+ **groupBy** - if one of filter or search fields is -TO-MANY relation, then result should be grouped.
```
<?php

namespace Acme\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Nfq\EntityListBundle\Mapping as EntityList;

/**
 * Class Product.
 *
 * @ORM\Entity
 * @ORM\Table(name="products")
 *
 * @EntityList\Source(
 *     sortableFields={
 *         "id",
 *         "name",
 *         "price"
 *     },
 *     filterFields={
 *         "name"={
 *             "operators"={"eq", "neq", "like", "nlike"},
 *             "globalSearch"=true
 *         },
 *         "price"={
 *             "operators"={"eq", "neq", "gt", "gte", "lt", "lte"},
 *             "globalSearch"=true
 *         },
 *         "categories.name"={
 *             "target"="relation",
 *             "operators"={"eq", "neq", "like", "nlike"},
 *             "joinType"="join",
 *             "joinField"="categories"
 *         },
 *         "categories.id"={
 *             "target"="relation",
 *             "operators"={"in", "notIn"},
 *             "joinType"="join",
 *             "joinField"="categories"
 *         },
 *     },
 *     searchFields={
 *         "categories.name"={
 *             "target"="relation",
 *             "joinType"="join",
 *             "joinField"="categories"
 *         },
 *     },
 *     groupBy="id"
 * )
 */
class Product
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @var float
     * @ORM\Column(type="float")
     */
    protected $price;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Category", inversedBy="products", cascade={"persist"})
     * @ORM\JoinTable(
     *      name="products_mm_categories",
     *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $categories;
}
```
Create list handler
------------
The easiest way to create list handle is using list handler factory (**requires repository as a service**):

    # services.yml
    # ...
    products.entity_list_handler:
        class: Nfq\EntityListBundle\Handler\EntityListHandler
        factory: 'nfq.entity_list.handler.factory:getEntityListHandler'
        arguments:
            - "@products_repository"

## Use in controller
```
<?php
// ...
public function listAction()
{
    $listHandler = $this->get('products.entity_list_handler');
    $products = $listHandler->getResults();

    // ...
}

// ...
```