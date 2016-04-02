<?php
namespace Api\Models;

class RedirectCollection
{
    public function __construct($manager, $dbname)
    {
        $this->manager = $manager;
        $this->dbname = $dbname;
        $this->list = [];
    }
    public function load()
    {
        $q_currentredirect = new MongoDB\Driver\Query([]);
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
