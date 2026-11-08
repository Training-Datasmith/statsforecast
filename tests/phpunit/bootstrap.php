<?php

error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
date_default_timezone_set('UTC');

if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.6.5');
}
if (!defined('_PS_CREATION_DATE_')) {
    define('_PS_CREATION_DATE_', '2010-01-01');
}

$stubDir = dirname(__FILE__) . '/stubs';
require $stubDir . '/Exceptions.php';
require $stubDir . '/Db.php';
require $stubDir . '/Shop.php';
require $stubDir . '/ModuleGraph.php';
require $stubDir . '/Module.php';
require $stubDir . '/Tools.php';
require $stubDir . '/Link.php';
require $stubDir . '/Locale.php';
require $stubDir . '/Context.php';
require $stubDir . '/Zone.php';

$db = Db::getInstance()->getLink();
$schema = array(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'orders (
        id_order INT NOT NULL,
        reference VARCHAR(64) NOT NULL,
        id_shop INT NOT NULL DEFAULT 1,
        id_shop_group INT NOT NULL DEFAULT 1,
        id_lang INT NOT NULL DEFAULT 1,
        id_currency INT NOT NULL DEFAULT 1,
        id_address_invoice INT NOT NULL DEFAULT 0,
        valid TINYINT NOT NULL DEFAULT 1,
        conversion_rate DECIMAL(13,6) NOT NULL DEFAULT 1,
        total_paid_tax_excl DECIMAL(20,6) NOT NULL DEFAULT 0,
        total_paid_tax_incl DECIMAL(20,6) NOT NULL DEFAULT 0,
        invoice_date DATETIME NOT NULL,
        date_add DATETIME NOT NULL,
        PRIMARY KEY (id_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'order_detail (
        id_order INT NOT NULL,
        product_id INT NOT NULL DEFAULT 1,
        product_attribute_id INT NOT NULL DEFAULT 0,
        product_quantity INT NOT NULL DEFAULT 0,
        unit_price_tax_excl DECIMAL(20,6) NOT NULL DEFAULT 0,
        product_price DECIMAL(20,6) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'connections (
        id_connection INT NOT NULL AUTO_INCREMENT,
        id_shop INT NOT NULL DEFAULT 1,
        id_shop_group INT NOT NULL DEFAULT 1,
        id_guest INT NOT NULL DEFAULT 0,
        date_add DATETIME NOT NULL,
        PRIMARY KEY (id_connection)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'customer (
        id_customer INT NOT NULL AUTO_INCREMENT,
        id_shop INT NOT NULL DEFAULT 1,
        id_shop_group INT NOT NULL DEFAULT 1,
        date_add DATETIME NOT NULL,
        PRIMARY KEY (id_customer)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'guest (
        id_guest INT NOT NULL,
        id_customer INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id_guest)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'cart (
        id_cart INT NOT NULL,
        id_shop INT NOT NULL DEFAULT 1,
        id_shop_group INT NOT NULL DEFAULT 1,
        id_address_invoice INT NOT NULL DEFAULT 0,
        date_add DATETIME NOT NULL,
        date_upd DATETIME NOT NULL,
        PRIMARY KEY (id_cart)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'cart_product (
        id_cart INT NOT NULL,
        id_product INT NOT NULL DEFAULT 1,
        quantity INT NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'order_payment (
        id_order_payment INT NOT NULL AUTO_INCREMENT,
        order_reference VARCHAR(64) NOT NULL,
        payment_method VARCHAR(128) NOT NULL,
        amount DECIMAL(20,6) NOT NULL DEFAULT 0,
        conversion_rate DECIMAL(13,6) NOT NULL DEFAULT 1,
        date_add DATETIME NOT NULL,
        PRIMARY KEY (id_order_payment)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'address (
        id_address INT NOT NULL,
        id_country INT NOT NULL DEFAULT 1,
        PRIMARY KEY (id_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'country (
        id_country INT NOT NULL,
        id_zone INT NULL,
        PRIMARY KEY (id_country)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'zone (
        id_zone INT NOT NULL,
        name VARCHAR(128) NOT NULL,
        PRIMARY KEY (id_zone)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'currency (
        id_currency INT NOT NULL,
        name VARCHAR(128) NOT NULL,
        PRIMARY KEY (id_currency)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'lang (
        id_lang INT NOT NULL,
        iso_code VARCHAR(8) NOT NULL,
        active TINYINT NOT NULL DEFAULT 1,
        PRIMARY KEY (id_lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'lang_shop (
        id_lang INT NOT NULL,
        id_shop INT NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'product (
        id_product INT NOT NULL,
        id_shop_default INT NOT NULL DEFAULT 1,
        PRIMARY KEY (id_product)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'product_shop (
        id_product INT NOT NULL,
        id_shop INT NOT NULL DEFAULT 1,
        id_category_default INT NULL,
        PRIMARY KEY (id_product, id_shop)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'category_lang (
        id_category INT NOT NULL,
        id_lang INT NOT NULL DEFAULT 1,
        id_shop INT NOT NULL DEFAULT 1,
        name VARCHAR(128) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'product_attribute_combination (
        id_product_attribute INT NOT NULL,
        id_attribute INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'attribute (
        id_attribute INT NOT NULL,
        id_attribute_group INT NOT NULL,
        PRIMARY KEY (id_attribute)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'attribute_group_lang (
        id_attribute_group INT NOT NULL,
        id_lang INT NOT NULL,
        name VARCHAR(128) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'attribute_lang (
        id_attribute INT NOT NULL,
        id_lang INT NOT NULL,
        name VARCHAR(128) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8',
);

foreach ($schema as $sql) {
    if (!$db->query($sql)) {
        throw new RuntimeException('schema failed: ' . $db->error);
    }
}

require dirname(dirname(__DIR__)) . '/statsforecast.php';
require dirname(dirname(__DIR__)) . '/upgrade/upgrade-2.0.4.php';

if (class_exists('PHPUnit_Framework_TestCase')) {
    require dirname(__FILE__) . '/Fixture.php';
    require dirname(__FILE__) . '/DatabaseCase.php';
}
