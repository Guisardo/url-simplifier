<?php
namespace Api\Models;

/**
 * Representation of a single redirect.
 */
class Redirect
{
    public function __construct()
    {
        require_once(__DIR__."/../lib/Connection.class.php");
        $this->manager = \Api\Lib\Connection::getManager();
        $this->dbname = \Api\Lib\Connection::getDBName();
        $this->data = new \stdClass();
        $this->data->created = gmmktime();
        $this->data->active = true;
        require_once("Settings.class.php");
        $settings = new Settings('global');
        $settings->load();
        $this->setProperties([
            "method" => "shareable",
            "title" => $settings->getProperty('title'),
            "description" => $settings->getProperty('description'),
            "image" => $settings->getProperty('image'),
            "destination" => $settings->getProperty('defaultUrl')
            ]);
    }
    public function setProperties($properties)
    {
        foreach ($properties as $key => $value) {
            if ($key != '_id') {
                if (gettype($value) === 'object' && get_class($value) === 'MongoDB\BSON\UTCDateTime') {
                    $this->data->$key = (int)date($value);
                } else {
                    $this->data->$key = $value;
                }
            }
        }
    }
    public function getProperty($property)
    {
        $result = $this->data->$property;
        if ($result === null) {
            $result = '';
        } elseif ($property === 'method' && $result === 'shareable' &&
                !preg_match("/bot|spider|crawl|facebook/", $_SERVER["HTTP_USER_AGENT"])) {
            // shareable is only for crawlers
            $result = 'temporary';
        }
        return $result;
    }
    public function isNew()
    {
        return $this->new;
    }
    public function isExpired()
    {
        $this->expired = false;
        if (!$this->getProperty('active')) {
            $this->expired = true;
        } elseif (!isset($this->expired)) {
            $this->expired = false;
            if ($this->getProperty('method') !== 'permanent' && $this->getProperty('expiration') !== null) {
                $this->expired =
                        (gmmktime() - $this->getProperty('modified')) >= ($this->getProperty('expiration') * 60000);
            }
        }
        return $this->expired;
    }
    public function load($alias)
    {
        $this->new = true;
        $q_currentredirect = new \MongoDB\Driver\Query(["alias" => $alias]);
        $cursor = $this->manager->executeQuery($this->dbname.".redirects", $q_currentredirect);
        // Iterate over all matched documents
        foreach ($cursor as $document) {
            $this->new = false;
            $this->old = $document;

            $this->setProperties($this->old);
        }
    }
    public function save()
    {
        $result = false;
        $this->data->created = new \MongoDB\BSON\UTCDateTime($this->getProperty('created'));
        $this->data->modified = new \MongoDB\BSON\UTCDateTime(gmmktime());
        // Specify the search criteria and update operations (or replacement document)
        $filter = ["alias" => $this->getProperty('alias')];
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
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->update($filter, $newObj, $options);

        try {
            /* Specify the full namespace as the first argument, followed by the bulk
             * write object and an optional write concern. MongoDB\Driver\WriteResult is
             * returned on success; otherwise, an exception is thrown. */
            $result = $this->manager->executeBulkWrite($this->dbname.".redirects", $bulk);
            $this->new = false;
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            $result = $e;
        }
        $this->callHooks('save');

        return $result;
    }

    public function hit($data)
    {
        $result = false;
        // Create a bulk write object and add our insert operation
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->insert([
            "alias" => $this->getProperty('alias'),
            "t" => new \MongoDB\BSON\UTCDateTime($_SERVER["REQUEST_TIME"]),
            "data" => $data
            ]);

        try {
            /* Specify the full namespace as the first argument, followed by the bulk
             * write object and an optional write concern. MongoDB\Driver\WriteResult is
             * returned on success; otherwise, an exception is thrown. */
            $result = $this->manager->executeBulkWrite($this->dbname.".hits", $bulk);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            $result = $e;
        }

        if ($data->tid !== null) {
            $url = 'https://www.google-analytics.com/collect?v=1&tid='.
                    urlencode($data->tid).'&ds=web&z='.time().'&cid='.urlencode($data->cid).
                    '&uip='.urlencode($data->ip).'&ua='.urlencode($data->ua).
                    '&dr='.urlencode($data->ref).'&ul='.urlencode($data->lang).
                    '&t=pageview&dl='.urlencode($data->dl);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $data = curl_exec($ch);
            curl_close($ch);
        }
        return $result;
    }
    public function remove()
    {
        $result = false;
        if (!$this->isNew()) {
            if ($this->getProperty('active')) {
                $this->setProperties([
                    'active' => false
                    ]);
                $this->save();
            } else {
                // Specify the search criteria
                $filter = ["alias" => $this->getProperty('alias')];

                /* Specify some command options for the update:
                 *
                 *  * limit (integer): Deletes all matching documents when 0 (false). Otherwise,
                 *    only the first matching document is deleted. */
                $options = ["limit" => 1];

                // Create a bulk write object and add our delete operation
                $bulk = new \MongoDB\Driver\BulkWrite;
                $bulk->delete($filter, $options);

                try {
                    /* Specify the full namespace as the first argument, followed by the bulk
                     * write object and an optional write concern. MongoDB\Driver\WriteResult is
                     * returned on success; otherwise, an exception is thrown. */
                    $result = $this->manager->executeBulkWrite($this->dbname.".redirects", $bulk);
                } catch (\MongoDB\Driver\Exception\Exception $e) {
                    $result = $e;
                }
            }
            $this->callHooks('remove');
        }

        return $result;
    }

    public function callHooks($action) {
        $currProtocol = 'http://';
        if (isset($_SERVER['HTTPS'])) {
            $currProtocol = 'https://';
        }
        $host = $currProtocol.$_SERVER['HTTP_HOST'];

        @file_get_contents("http://www.google.com/webmasters/sitemaps/ping?sitemap=$host/sitemap.xml");
        @file_get_contents("http://www.bing.com/webmaster/ping.aspx?siteMap=$host/sitemap.xml");
    }
}
