NfqEntityListBundle request structure
===============================

Entity list handler tries to find sort, pagination, filters and/or search **GET** parameters in request. Request should be in **strict structure**:
* **filters and search parameters:**
    * ```filters[search]``` - global search value
    * ```filters[0][field]``` - 1st filter field
    * ```filters[0][operator]``` - 1st filter operator
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
    * ```filters[0][value]``` - 1st filter value
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