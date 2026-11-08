<?php

class Module
{
    public $name;
    public $context;

    public static $parentInstallResult = true;

    public $registeredHooks = array();
    public $unregisteredHooks = array();

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public static function resetForTests()
    {
        self::$parentInstallResult = true;
    }

    public function install()
    {
        return self::$parentInstallResult;
    }

    public function registerHook($name)
    {
        $this->registeredHooks[] = $name;

        return true;
    }

    public function unregisterHook($name)
    {
        $this->unregisteredHooks[] = $name;

        return true;
    }

    public function trans($id)
    {
        return $id;
    }
}
