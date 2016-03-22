<?php

// Configuration
$dbhost = 'db';
$dbname = 'db.redirects';
// Connect to test database
$manager = new MongoDB\Driver\Manager("mongodb://$dbhost");

include_once ("api/models/Settings.class.php");
$settings = new Settings($manager, $dbname, 'global');
$settings->load();

$defaultUrl = $settings->getProperty('defaultUrl');

if (isset($_GET["alias"])) {
    $orgQuery = '?'.$_SERVER['QUERY_STRING'];
    $orgQuery = preg_replace('/alias=.+?(?:&|$)/', '', $orgQuery);
    $orgQuery = preg_replace('/^\?$/', '', $orgQuery);

    include_once ("api/models/Redirect.class.php");
    $redirect = new Redirect($manager, $dbname);
    $redirect->load($_GET["alias"]);

    $cid = $_COOKIE["cid"];
    if($cid === null) {
        $cid = rand(0, 2147483647);
        setcookie("cid",$cid,time()+(86400*31*12));
    }
    $currProtocol = 'http://';
    if (isset($_SERVER['HTTPS'])) {
        $currProtocol = 'https://';
    }
    $data_json = json_encode([
            "qs" => $_SERVER["QUERY_STRING"],
            "charset" => $_SERVER["HTTP_ACCEPT_CHARSET"],
            "encoding" => $_SERVER["HTTP_ACCEPT_ENCODING"],
            "lang" => $_SERVER["HTTP_ACCEPT_LANGUAGE"],
            "ref" => $_SERVER["HTTP_REFERER"],
            "ua" => $_SERVER["HTTP_USER_AGENT"],
            "ip" => $_SERVER["REMOTE_ADDR"],
            "usr" => $_SERVER["PHP_AUTH_USER"],
            "dl" => $currProtocol.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"],
            "tid" => $settings->getProperty('analytics'),
            "cid" => $cid
        ]);
    $url = 'http://localhost/api/redirect.php?hit=me&alias='.$redirect->getProperty("alias");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_json)));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    $data = curl_exec($ch);
    curl_close($ch);


    if ($redirect->isNew() || $redirect->isExpired()) {
        $redirect = new Redirect($manager, $dbname);
        $redirect->setProperties([
            "method" => "temporary",
            "destination" => $defaultUrl
            ]);
    }
    $username = $redirect->getProperty('username');
    if ($username === '') {
        $username = null;
    }
    $password = $redirect->getProperty('password');
    if ($password === '') {
        $password = null;
    }
    if ($username !== null || $password !== null) {
        $realm = 'UrlShorter Realm';
        if (($username !== null && $username !== $_SERVER['PHP_AUTH_USER'])
            || ($password !== null && $password !== $_SERVER['PHP_AUTH_PW'])) {
            header('WWW-Authenticate: Basic realm="'.$realm.'"');
            header('HTTP/1.0 401 Unauthorized');
            die ("Not authorized");
            exit;
        }
    }
    if ($redirect->getProperty('method') === 'permanent') {
        header( "HTTP/1.1 301 Moved Permanently" );
    } else if ($redirect->getProperty('method') === 'temporary') {
        header( "HTTP/1.1 302 Moved Temporary" );
    }
    //var_dump($redirect->getProperty('destination').$orgQuery);
    header( "Location: ".$redirect->data->destination.$orgQuery );
}
