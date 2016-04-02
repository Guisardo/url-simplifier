<?php

include_once("lib/Security.class.php");
\Api\Lib\Security::validateAdminUser();

include_once("models/Settings.class.php");
$settings = new Api\Models\Settings($_GET["type"]);
$settings->load();

if ($_SERVER['REQUEST_METHOD']==="PUT") {
    $rawInput = file_get_contents("php://input");
    $putData = json_decode($rawInput);
    $settings->setProperties($putData);
    $settings->save();
    echo json_encode("ACK");
} elseif ($_SERVER['REQUEST_METHOD']==="GET") {
    echo json_encode($settings->data);
}
