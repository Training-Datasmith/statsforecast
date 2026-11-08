<?php

class Fixture
{
    private static $orderId = 1;
    private static $cartId = 1;
    private static $customerId = 1;
    private static $guestId = 1;
    private static $connectionId = 1;
    private static $paymentId = 1;

    public static function resetCounters()
    {
        self::$orderId = 1;
        self::$cartId = 1;
        self::$customerId = 1;
        self::$guestId = 1;
        self::$connectionId = 1;
        self::$paymentId = 1;
    }

    public static function setEmployeeRange($from, $to)
    {
        $ctx = Context::getContext();
        $ctx->employee->stats_date_from = $from;
        $ctx->employee->stats_date_to = $to;
    }

    public static function seedCatalog()
    {
        $db = Db::getInstance()->getLink();
        $db->query("INSERT INTO " . _DB_PREFIX_ . "currency (id_currency, name) VALUES (1, 'Euro')");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "lang (id_lang, iso_code, active) VALUES (1, 'en', 1)");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "lang_shop (id_lang, id_shop) VALUES (1, 1)");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "zone (id_zone, name) VALUES (1, 'Europe'), (2, 'America')");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "country (id_country, id_zone) VALUES (1, 1)");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "address (id_address, id_country) VALUES (1, 1), (9, 1)");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "product (id_product, id_shop_default) VALUES (1, 1), (2, 1)");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "product_shop (id_product, id_shop, id_category_default) VALUES (1, 1, 1), (2, 1, 2)");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "category_lang (id_category, id_lang, id_shop, name) VALUES (1, 1, 1, 'Home'), (2, 1, 1, 'Garden')");
    }

    public static function addLanguage($idLang, $iso, $active, $shop = true)
    {
        $db = Db::getInstance()->getLink();
        $db->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'lang (id_lang, iso_code, active) VALUES ('
            . (int) $idLang . ", '" . $db->real_escape_string($iso) . "', " . (int) $active . ')'
        );
        if ($shop) {
            $db->query('INSERT INTO ' . _DB_PREFIX_ . 'lang_shop (id_lang, id_shop) VALUES (' . (int) $idLang . ', 1)');
        }
    }

    public static function addCurrency($id, $name)
    {
        $db = Db::getInstance()->getLink();
        $db->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'currency (id_currency, name) VALUES ('
            . (int) $id . ", '" . $db->real_escape_string($name) . "')"
        );
    }

    public static function addCountryZone($idCountry, $idZone)
    {
        $db = Db::getInstance()->getLink();
        $zone = $idZone === null ? 'NULL' : (int) $idZone;
        $db->query('INSERT INTO ' . _DB_PREFIX_ . 'country (id_country, id_zone) VALUES (' . (int) $idCountry . ', ' . $zone . ')');
    }

    public static function addAddress($idAddress, $idCountry)
    {
        Db::getInstance()->getLink()->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'address (id_address, id_country) VALUES (' . (int) $idAddress . ', ' . (int) $idCountry . ')'
        );
    }

    public static function insertOrder($opts)
    {
        $defaults = array(
            'reference' => 'REF' . self::$orderId,
            'id_shop' => 1,
            'id_shop_group' => 1,
            'id_lang' => 1,
            'id_currency' => 1,
            'id_address_invoice' => 1,
            'valid' => 1,
            'conversion_rate' => 1,
            'total_paid_tax_excl' => 10,
            'total_paid_tax_incl' => 12,
            'invoice_date' => '2020-01-06 12:00:00',
            'date_add' => '2020-01-06 12:00:00',
            'lines' => array(),
        );
        $o = array_merge($defaults, $opts);
        $id = self::$orderId++;
        $db = Db::getInstance()->getLink();
        $db->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'orders (id_order, reference, id_shop, id_shop_group, id_lang, id_currency, '
            . 'id_address_invoice, valid, conversion_rate, total_paid_tax_excl, total_paid_tax_incl, invoice_date, date_add) VALUES ('
            . (int) $id . ", '" . $db->real_escape_string($o['reference']) . "', "
            . (int) $o['id_shop'] . ', ' . (int) $o['id_shop_group'] . ', '
            . (int) $o['id_lang'] . ', ' . (int) $o['id_currency'] . ', '
            . (int) $o['id_address_invoice'] . ', ' . (int) $o['valid'] . ', '
            . (float) $o['conversion_rate'] . ', ' . (float) $o['total_paid_tax_excl'] . ', '
            . (float) $o['total_paid_tax_incl'] . ", '"
            . $db->real_escape_string($o['invoice_date']) . "', '"
            . $db->real_escape_string($o['date_add']) . "')"
        );
        foreach ($o['lines'] as $line) {
            $ld = array_merge(array(
                'product_id' => 1,
                'product_attribute_id' => 0,
                'product_quantity' => 1,
                'unit_price_tax_excl' => 10,
                'product_price' => 99,
            ), $line);
            $db->query(
                'INSERT INTO ' . _DB_PREFIX_ . 'order_detail (id_order, product_id, product_attribute_id, product_quantity, unit_price_tax_excl, product_price) VALUES ('
                . (int) $id . ', ' . (int) $ld['product_id'] . ', ' . (int) $ld['product_attribute_id'] . ', '
                . (int) $ld['product_quantity'] . ', ' . (float) $ld['unit_price_tax_excl'] . ', ' . (float) $ld['product_price'] . ')'
            );
        }

        return $id;
    }

    public static function insertConnection($date, $idShop = 1, $idGuest = null)
    {
        if ($idGuest === null) {
            $idGuest = self::$guestId++;
        }
        $db = Db::getInstance()->getLink();
        $db->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'connections (id_shop, id_shop_group, id_guest, date_add) VALUES ('
            . (int) $idShop . ', 1, ' . (int) $idGuest . ", '" . $db->real_escape_string($date) . "')"
        );
    }

    public static function insertGuest($idGuest, $idCustomer = 0)
    {
        Db::getInstance()->getLink()->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'guest (id_guest, id_customer) VALUES (' . (int) $idGuest . ', ' . (int) $idCustomer . ')'
        );
    }

    public static function insertCustomer($date, $idShop = 1)
    {
        $id = self::$customerId++;
        Db::getInstance()->getLink()->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'customer (id_shop, id_shop_group, date_add) VALUES ('
            . (int) $idShop . ', 1, \'' . Db::getInstance()->escape($date) . '\')'
        );

        return $id;
    }

    public static function insertCart($opts)
    {
        $defaults = array(
            'id_shop' => 1,
            'id_address_invoice' => 0,
            'date_add' => '2020-08-10 10:00:00',
            'date_upd' => '2020-08-10 10:00:00',
            'products' => array(),
        );
        $c = array_merge($defaults, $opts);
        $id = self::$cartId++;
        $db = Db::getInstance()->getLink();
        $db->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'cart (id_cart, id_shop, id_shop_group, id_address_invoice, date_add, date_upd) VALUES ('
            . (int) $id . ', ' . (int) $c['id_shop'] . ', 1, ' . (int) $c['id_address_invoice'] . ", '"
            . $db->real_escape_string($c['date_add']) . "', '" . $db->real_escape_string($c['date_upd']) . "')"
        );
        foreach ($c['products'] as $product) {
            $pid = isset($product['id_product']) ? $product['id_product'] : 1;
            $qty = isset($product['quantity']) ? $product['quantity'] : 1;
            $db->query(
                'INSERT INTO ' . _DB_PREFIX_ . 'cart_product (id_cart, id_product, quantity) VALUES ('
                . (int) $id . ', ' . (int) $pid . ', ' . (int) $qty . ')'
            );
        }

        return $id;
    }

    public static function insertPayment($reference, $method, $amount, $rate, $date)
    {
        $db = Db::getInstance()->getLink();
        $db->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'order_payment (order_reference, payment_method, amount, conversion_rate, date_add) VALUES ('
            . "'" . $db->real_escape_string($reference) . "', '" . $db->real_escape_string($method) . "', "
            . (float) $amount . ', ' . (float) $rate . ", '" . $db->real_escape_string($date) . "')"
        );
    }

    public static function setCategoryDefault($productId, $categoryId)
    {
        Db::getInstance()->getLink()->query(
            'UPDATE ' . _DB_PREFIX_ . 'product_shop SET id_category_default = ' . (int) $categoryId
            . ' WHERE id_product = ' . (int) $productId . ' AND id_shop = 1'
        );
    }

    public static function addCategoryLang($categoryId, $name)
    {
        Db::getInstance()->getLink()->query(
            'INSERT INTO ' . _DB_PREFIX_ . 'category_lang (id_category, id_lang, id_shop, name) VALUES ('
            . (int) $categoryId . ', 1, 1, \'' . Db::getInstance()->escape($name) . '\')'
        );
    }

    public static function seedAttributes()
    {
        $db = Db::getInstance()->getLink();
        $db->query('INSERT INTO ' . _DB_PREFIX_ . 'attribute (id_attribute, id_attribute_group) VALUES (10, 1), (11, 2)');
        $db->query("INSERT INTO " . _DB_PREFIX_ . "attribute_group_lang (id_attribute_group, id_lang, name) VALUES (1, 1, 'Size'), (1, 2, 'WRONG'), (2, 1, 'Color'), (2, 2, 'WRONG')");
        $db->query("INSERT INTO " . _DB_PREFIX_ . "attribute_lang (id_attribute, id_lang, name) VALUES (10, 1, 'Large'), (10, 2, 'WRONG'), (11, 1, 'Red'), (11, 2, 'WRONG')");
        $db->query('INSERT INTO ' . _DB_PREFIX_ . 'product_attribute_combination (id_product_attribute, id_attribute) VALUES (100, 10), (101, 11)');
    }
}
