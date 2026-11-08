<?php

class Context
{
    /** @var Context */
    private static $instance;

    public $cookie;
    public $employee;
    public $currency;
    public $language;
    public $link;
    private $locale;

    public static function getContext()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function resetForTests()
    {
        self::$instance = null;
    }

    public function __construct()
    {
        $this->cookie = new stdClass();
        $this->employee = new stdClass();
        $this->employee->stats_date_from = '2020-01-01';
        $this->employee->stats_date_to = '2020-01-07';
        $this->currency = new stdClass();
        $this->currency->iso_code = 'EUR';
        $this->language = new stdClass();
        $this->language->id = 1;
        $this->link = new Link();
        $this->locale = new Locale();
    }

    public function getCurrentLocale()
    {
        return $this->locale;
    }

    public function getLocaleLog()
    {
        return $this->locale->formatPriceLog;
    }

    public function clearLocaleLog()
    {
        $this->locale->formatPriceLog = array();
    }
}
