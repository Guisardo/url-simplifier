<?php
namespace Api\Models;

class Settings
{
    public function __construct($type)
    {
        include_once("../lib/Connection.class.php");
        $this->manager = Lib\Connection::getManager();
        $this->dbname = Lib\Connection::getDBName();
        $this->data = new stdClass();
        $this->data->type = $type;
    }
    public function setProperties($properties)
    {
        foreach ($properties as $key => $value) {
            if ($key != '_id') {
                if (gettype($value) === 'object' && get_class($value) === 'MongoDB\BSON\UTCDateTime') {
                    $this->data->$key = (int)date($value);
                } elseif ($key == 'password' && $value !== $this->getProperty('password')) {
                    $this->data->$key = $value;
                } else {
                    $this->data->$key = $value;
                }
            }
        }
    }
    public function getProperty($property)
    {
        $result = $this->data->$property;
        if ($property === 'password' && $result !== '' && $result !== null) {
            return md5(trim(strtolower($result.date('d M Y'))));
        }
        return $result;
    }
    public function load()
    {
        $q_currentredirect = new MongoDB\Driver\Query(["type" => $this->getProperty('type')]);
        $cursor = $this->manager->executeQuery($this->dbname.".settings", $q_currentredirect);
        // Iterate over all matched documents
        foreach ($cursor as $document) {
            $this->old = $document;

            $this->setProperties($this->old);
        }

    }
    public function save()
    {
        $result = false;
        $this->data->modified = new MongoDB\BSON\UTCDateTime(gmmktime());
        // Specify the search criteria and update operations (or replacement document)
        $filter = ["type" => $this->getProperty('type')];
        $newObj = ['$set' => $this->data];

        /* Specify some command options for the update:
         *
         *  * multi (boolean): Updates all matching documents when true; otherwise, only
         *    the first matching document is updated. Defaults to false.
         *  * upsert (boolean): If there is no matching document, create a new document
         *    from $filter and $newObj. Defaults to false.
         */
        $options = ["multi" => false, "upsert" => true];

        // Create a bulk write object and add our update operation
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update($filter, $newObj, $options);

        try {
            /* Specify the full namespace as the first argument, followed by the bulk
             * write object and an optional write concern. MongoDB\Driver\WriteResult is
             * returned on success; otherwise, an exception is thrown. */
            $result = $this->manager->executeBulkWrite($this->dbname.".settings", $bulk, $wc);
            $this->new = false;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $result = $e;
        }

        return $result;
    }
}
