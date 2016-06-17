<?php
namespace Api\Models;

/**
 * Representation of a list of redirects.
 */
class RedirectCollection
{
    public function __construct()
    {
        require_once(__DIR__."/../lib/Connection.class.php");
        $this->manager = \Api\Lib\Connection::getManager();
        $this->dbname = \Api\Lib\Connection::getDBName();
        $this->list = [];
    }
    public function load()
    {
        $q_currentredirect = new \MongoDB\Driver\Query([]);
        $cursor = $this->manager->executeQuery($this->dbname.".redirects", $q_currentredirect);
        // Iterate over all matched documents
        foreach ($cursor as $document) {
            array_push($this->list, $document);
        }
    }
    public function loadPublic()
    {
        $q_currentredirect = new \MongoDB\Driver\Query([
            "username" => "",
            "password" => ""
        ]);
        $cursor = $this->manager->executeQuery($this->dbname.".redirects", $q_currentredirect);
        // Iterate over all matched documents
        foreach ($cursor as $document) {
            array_push($this->list, $document);
        }
    }
    public function getList()
    {
        return $this->list;
    }
}
