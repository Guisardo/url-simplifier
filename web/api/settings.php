<?php
// Configuration
$dbhost = 'db';
$dbname = 'db.redirects';
// Connect to test database
$manager = new MongoDB\Driver\Manager("mongodb://$dbhost");

include_once ("models/Settings.class.php");
$security = new Settings($manager, $dbname, 'sec');
$security->load();
$username = $security->getProperty('username');
if ($username === '') {
    $username = null;
}
$password = $security->getProperty('password');
if ($password === '') {
    $password = null;
}
if ($username !== null || $password !== null) {
    $realm = 'UrlShorter Realm API';
    if (($username !== null && $username !== $_SERVER['PHP_AUTH_USER'])
        || ($password !== null && $password !== $_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="'.$realm.'"');
        header('HTTP/1.0 401 Unauthorized');
        die ("Not authorized");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    echo json_encode("ACK");
}

include_once ("models/Settings.class.php");
$settings = new Settings($manager, $dbname, $_GET["type"]);
$settings->load();

if ($_SERVER['REQUEST_METHOD']==="PUT") {
    $rawInput = file_get_contents("php://input");
    $putData = json_decode($rawInput);
    $settings->setProperties($putData);
    $settings->save();
} else if ($_SERVER['REQUEST_METHOD']==="GET") {
    echo json_encode($settings->data);
}