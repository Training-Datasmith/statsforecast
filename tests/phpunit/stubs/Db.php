<?php

class Db
{
    /** @var Db */
    private static $instance;

    /** @var mysqli */
    private $link;

    private function __construct()
    {
        $host = getenv('SF_DB_HOST') ? getenv('SF_DB_HOST') : '127.0.0.1';
        $user = getenv('SF_DB_USER') ? getenv('SF_DB_USER') : 'sf';
        $pass = getenv('SF_DB_PASSWORD') ? getenv('SF_DB_PASSWORD') : 'sf';
        $name = getenv('SF_DB_NAME') ? getenv('SF_DB_NAME') : 'statsforecast';

        $this->link = new mysqli($host, $user, $pass, $name);
        if ($this->link->connect_error) {
            throw new RuntimeException('mysqli connect failed: ' . $this->link->connect_error);
        }
        $this->link->query("SET NAMES utf8");
        $this->link->query("SET SESSION sql_mode=''");
        $this->link->query("SET time_zone='+00:00'");
    }

  public static function getInstance()
  {
      if (!self::$instance) {
          self::$instance = new self();
      }

      return self::$instance;
    }

    public static function resetInstance()
    {
        self::$instance = null;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function escape($str)
    {
        return $this->link->real_escape_string($str);
    }

    public function query($sql)
    {
        return $this->link->query($sql);
    }

    public function nextRow($result)
    {
        if (!is_object($result)) {
            return false;
        }

        return $result->fetch_assoc();
    }

    public function executeS($sql)
    {
        $result = $this->query($sql);
        if ($result === false) {
            return false;
        }
        if ($result === true) {
            return array();
        }
        $rows = array();
        while ($row = $this->nextRow($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getRow($sql)
    {
        $rows = $this->executeS($sql);
        if ($rows === false || count($rows) === 0) {
            return false;
        }

        return $rows[0];
    }

    public function getValue($sql)
    {
        $row = $this->getRow($sql);
        if ($row === false) {
            return false;
        }
        $values = array_values($row);

        return $values[0];
    }
}

function pSQL($string, $htmlOK = false)
{
    if (!is_numeric($string)) {
        $string = Db::getInstance()->escape($string);
    }
    if (!$htmlOK) {
        $string = strip_tags($string);
    }

    return $string;
}
