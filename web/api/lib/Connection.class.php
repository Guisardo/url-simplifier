<?php
namespace Api\Lib;

/**
* Static class that consolidates the database connection configurations
*/
class Connection
{
    private static $manager;

    /**
     * Get the database name.
     * @return [String] Database name
     */
    public static function getDBName()
    {
        return 'db.redirects';
    }
    /**
     * Get the connection manager.
     * @return [\MongoDB\Driver\Manager] Connection manager
     */
    public static function getManager()
    {
        if (null === static::$manager) {
            static::$manager = new \MongoDB\Driver\Manager("mongodb://".$_ENV["MONGO_HOSTNAME"].":".$_ENV["MONGO_HOSTPORT"]);
        }

        return static::$manager;
    }
}
