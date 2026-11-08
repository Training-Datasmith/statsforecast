<?php

class Link
{
    public static $overrideAdminLink = null;
    public $lastAdminLinkArgs = array();

    public static function resetForTests()
    {
        self::$overrideAdminLink = null;
    }

    public function getAdminLink($controller, $withToken = true, $sfRouteParams = array(), $params = array())
    {
        $this->lastAdminLinkArgs = array(
            'controller' => $controller,
            'withToken' => $withToken,
            'sfRouteParams' => $sfRouteParams,
            'params' => $params,
        );

        if (self::$overrideAdminLink !== null) {
            return self::$overrideAdminLink;
        }

        $query = array_merge(array('controller' => $controller), $params);

        return 'http://shop.test/admin/' . $controller . '.php?' . http_build_query($query);
    }
}
