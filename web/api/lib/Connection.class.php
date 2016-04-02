<?php
namespace Api\Lib;

/**
* Singleton that consolidates the database connection configurations
*/
class Connection
{
    private static $manager;

    public static function getDBName()
    {
        return 'db.redirects';
    }
    public static function getManager()
    {
        if (null === static::$manager) {
            static::$manager = new \MongoDB\Driver\Manager("mongodb://".$_ENV["MONGO_HOSTNAME"]);
        }

        return static::$manager;
    }
}
