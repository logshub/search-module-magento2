# Mageneto 2 integration with LogsHub.com Search

BETA version of Magento 2 integration module. It's not ready for production yet, but can be useful for your integration.

## What module is doing?

* Admin's configuration (LogsHub API credentials)
* Send product and categories by Magento's indexers

## Requirements

* Magento 2.3

## Installation

```
composer require logshub/search-module-magento2
bin/magento module:enable Logshub_SearchModule
bin/magento setup:upgrade
bin/magento setup:static-content:deploy -f
```

Noty sure why, but without it, strange error occures:

```
INSERT INTO indexer_state VALUES (12,'logshub_products', 'valid', '2019-03-11 10:56:33','');
INSERT INTO indexer_state VALUES (13,'logshub_categories', 'valid', '2019-03-11 10:56:33','');
```

## Manual execution

```
bin/magento indexer:reindex logshub_products
bin/magento indexer:reindex logshub_categories
```

## More details

* How indexation works in Magento 2: https://devdocs.magento.com/guides/v2.3/extension-dev-guide/indexing.html

## TODO

* timeouts in configuration
* add block for frontend integration
* test other versions of Magento 2
* travis build

## License

MIT
