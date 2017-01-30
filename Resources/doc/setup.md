NfqEntityListBundle setup
===============================

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
* ```search``` - search parameter (key inside filters ```filters[search]```).
* ```order_by``` - ordering parameters.
* ```page``` - page number.
* ```page_limit``` - items in page.

**There is also defined:**
* default bundles directory ```%kernel.root_dir%/../src``` for yml driver

You can change default bundle configuration in ``app/config/config.yml``:

    # app/config/config.yml
    nfq_entity_list:
        handler_config:
            bundles_directory: %kernel.root_dir%/../src
            page_nr_param_name: _page
            page_limit_param_name: _limit
            sort_param_name: orderBy
            filters_param_name: _filters
            default_page_limit: 15