Database Isolation Extension
============================

Magento 1.x extension which isolates database between [Ecomdev_PHPUnit](https://github.com/EcomDev/EcomDev_PHPUnit)'s tests.

Install
-------

Install the extension using Composer:

	composer require webgriffe/db-isolation-extension

Configuration
-------------

Put an XML file in `app/etc`, for example `app/etc/db_isolation.xml`, with the following content:

```xml
<?xml version="1.0"?>
<config>
    <phpunit>
        <db_isolation>
            <enabled>1</enabled>
            <warning_enabled>1</warning_enabled>
        </db_isolation>
    </phpunit>
</config>

```

When `phpunit/db_isolation/enabled` config is set to `1` every test runs inside a database transaction.

When `phpunit/db_isolation/warning_enabled` config is set to `1` the extension will set tests as risky if the database changes after its execution.

License
-------

This library is under the MIT license. See the complete license in the LICENSE file.

Credits
-------

Developed by [Webgriffe®](http://www.webgriffe.com/). Please, report to us any bug or suggestion by GitHub issues.