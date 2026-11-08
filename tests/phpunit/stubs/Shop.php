<?php

class Shop
{
    const CONTEXT_SHOP = 1;
    const SHARE_ORDER = 'share_order';
    const SHARE_CUSTOMER = 'share_customer';

    private static $context = self::CONTEXT_SHOP;
    private static $contextShopId = 1;
    private static $shareOrder = false;
    private static $shareCustomer = false;

    private static $assoTables = array(
        'product' => array('type' => 'shop'),
        'lang' => array('type' => 'shop'),
    );

    public static function resetForTests()
    {
        self::$context = self::CONTEXT_SHOP;
        self::$contextShopId = 1;
        self::$shareOrder = false;
        self::$shareCustomer = false;
    }

    public static function setShareOrder($on)
    {
        self::$shareOrder = (bool) $on;
    }

    public static function setShareCustomer($on)
    {
        self::$shareCustomer = (bool) $on;
    }

    public static function getContext()
    {
        return self::$context;
    }

    public static function getContextShopID()
    {
        return self::$contextShopId;
    }

    public static function getContextShopGroupID()
    {
        return 1;
    }

    public static function getSharedShops($shopId, $share)
    {
        if ($share === self::SHARE_ORDER && self::$shareOrder) {
            return array(1, 2);
        }
        if ($share === self::SHARE_CUSTOMER && self::$shareCustomer) {
            return array(1, 2);
        }

        return array((int) $shopId);
    }

    public static function getContextListShopID($share = false)
    {
        if (!$share) {
            return array(1);
        }

        return self::getSharedShops(self::$contextShopId, $share);
    }

    public static function getGroupFromShop($idShop, $cache = true)
    {
        return array(
            'share_customer' => self::$shareCustomer ? 1 : 0,
            'share_order' => self::$shareOrder ? 1 : 0,
        );
    }

    public static function addSqlRestriction($share = false, $alias = null)
    {
        $prefix = '';
        if ($alias) {
            $prefix = $alias . '.';
        }

        $group = self::getGroupFromShop(self::getContextShopID(), false);
        if ($share === self::SHARE_CUSTOMER && self::getContext() == self::CONTEXT_SHOP && $group['share_customer']) {
            return ' AND ' . $prefix . 'id_shop_group = ' . (int) self::getContextShopGroupID() . ' ';
        }

        $ids = self::getContextListShopID($share);

        return ' AND ' . $prefix . 'id_shop IN (' . implode(', ', $ids) . ') ';
    }

    public static function getAssoTable($table)
    {
        if (isset(self::$assoTables[$table])) {
            return self::$assoTables[$table];
        }

        return false;
    }

    public static function addSqlAssociation($table, $alias, $inner_join = true, $on = null, $force_not_default = false)
    {
        $asso = self::getAssoTable($table);
        if ($asso === false || $asso['type'] !== 'shop') {
            return '';
        }
        $table_alias = $table . '_shop';
        $join = $inner_join ? ' INNER' : ' LEFT';

        $sql = $join . ' JOIN ' . _DB_PREFIX_ . $table . '_shop ' . $table_alias . '
 ON (' . $table_alias . '.id_' . $table . ' = ' . $alias . '.id_' . $table;
        $sql .= ' AND ' . $table_alias . '.id_shop = 1';
        $sql .= (($on) ? ' AND ' . $on : '') . ')';

        return $sql;
    }

    public static function addSqlRestrictionOnLang($alias = null)
    {
        $prefix = $alias ? $alias . '.' : '';

        return ' AND ' . $prefix . 'id_shop = 1 ';
    }
}
