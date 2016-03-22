<?php
if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    // buffer all upcoming output
    ob_start();

    echo json_encode("ACK");

    // get the size of the output
    $size = ob_get_length();

    // send headers to tell the browser to close the connection
    header("Content-Length: $size");
    header('Connection: close');

    // flush all output
    ob_end_flush();
    ob_flush();
    flush();
}


// Configuration
$dbhost = 'db';
$dbname = 'db.redirects';
// Connect to test database
$manager = new MongoDB\Driver\Manager("mongodb://$dbhost");

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