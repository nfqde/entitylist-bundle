NfqEntityListBundle
==================
The **NfqEntityListBundle** allows you to generate doctrine entities lists.
## Features
+ pagination
+ sorting
+ filtering by one or more fields and different operators
+ global search

# Installation
---
## Step 1: Download the Bundle
Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

    $ composer require nfqde/entity-list-bundle

This command requires you to have Composer installed globally, as explained
in the `installation chapter`_ of the Composer documentation.

## Step 2: Enable the Bundle
Then, enable the bundle by adding it to the list of registered bundles
in the ``app/AppKernel.php`` file of your project:

    <?php
    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...
                new Nfq\EntityListBundle\NfqEntityListBundle(),
            );

            // ...
        }

        // ...
    }

## Step 3: Configure the Bundle (optional step)
By default bundle tries to get following parameters from request:
* ```filters``` - filters parameter.
* ```search_param_name``` - search parameter (key inside filters ```filters[search]```).
* ```order_by``` - ordering parameters.
* ```page``` - page number.
* ```page_limit``` - items in page.

**There is also defined:**.
* default items in page ```10```
* default bundles directory ```%kernel.root_dir%/../src``` for yml driver

You can change default bundle configuration in ``app/config/config.yml``:

    # app/config/config.yml
    nfq_entity_list:
        handler_config:
            bundles_directory: %kernel.root_dir%/../src
            page_nr_param_name: page
            page_limit_param_name: page_limit
            sort_param_name: order_by
            filters_param_name: filters
            default_page_limit: 10

# Usage
## Request structure
Entity list handler tries to find sort, pagination, filters and/or search **GET** parameters in request. Request should be in **strict structure**:
* **filters and search parameters:**
    * ```filters[search]``` - global search value
    * ```filters[1][field]``` - 1st filter field
    * ```filters[1][operator]``` - 1st filter operator
        * eq - equal (```=```)
        * neq - not equal (```!=```)
        * lt - less than (```<```)
        * lte - less than or equal to (```<=```)
        * gt - greater than (```>```)
        * gte - greater than or equal to (```>=```)
        * btw - BETWEEN
        * like - LIKE  (```LIKE '%value%'```)
        * nlike - NOT LIKE  (```NOT LIKE '%value%'```)
        * rlike - starts with (```LIKE 'value%'```)
        * llike - ends with (```LIKE '%value'```)
        * in - IN
        * notIn - NOT IN
        * isNull - IS NULL
        * isNotNull - IS NOT NULL
    * ```filters[1][value]``` - 1st filter value
    * ```filters[n][field]``` - nth filter field
    * ```filters[n][operator]``` - nth filter operator
    * ```filters[n][value]``` - nth filter value
* **sort parameters:**
    * ```order_by[field]``` - order field
    * ```order_by[direction]```
        * ASC - default
        * DESC
* **paging parameters:**
    * ```page``` - page number (default 1)
    * ```page_limit``` - items in page (default 10)

## Configure entity
**NfqEntityListBundle** requires to configure entities in order to enable possibility to generate list. You can do that with annotations. It possible to define:
* sortable fields
* filterable fields
* global search fields
* group by field.

Minumum requirement is to add ```@Nfq\EntityListBundle\Mapping\Source``` annotation. In this case you will have paging possibility. But if you want to enable sorting, filtering and/or global search, then you need to add extra parameters to annotations:
+ **sortableFields** - defines fields by with list can be sorted
    + key should be field name
    + value can be array with following parameters:
        + target - ```direct``` (if field belongs to main entity) or ```relation``` (if field belongs to -TO-ONE relation).
        + joinType - required if target is **relation** (leftJoin, rightJoin, innerJoin or join).
        + joinField - required if target is **relation**, relation field, which should be joined.
    
    You can skip all parameters, then target will be ```direct```.
+ **filterFields** - defines fields by which list can be filtered
    + key should be field name
    + value can be array with following parameters:
        + opearators - **required** filter operators (array).
        + target - ```direct``` (if field belongs to main entity) or ```relation``` (if field belongs to -TO-ONE relation).
        + joinType - required if target is **relation** (leftJoin, rightJoin, innerJoin or join).
        + joinField - required if target is **relation**, relation field, which should be joined.
        + globalSearch - ```true``` or ```false```, if ```true``` then this field will be used in global search.
+ **searchFields** - fields which will be used in global search
    + parameters are same as for **sortableFields**
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
        factory_service: nfq_entity_list.handler_factory
        factory_method: getEntityListHandler
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

TODO
------------
+ Write tests.
+ Better documentation.
