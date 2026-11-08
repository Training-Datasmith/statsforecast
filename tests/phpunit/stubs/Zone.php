<?php

class Zone
{
    public static function getZones()
    {
        return Db::getInstance()->executeS(
            'SELECT id_zone, name FROM ' . _DB_PREFIX_ . 'zone ORDER BY name'
        );
    }
}
