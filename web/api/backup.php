<?php
//ini_set("display_errors", false);

include_once("lib/Security.class.php");
\Api\Lib\Security::validateAdminUser();

include_once("lib/Backup.class.php");

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $bkpDone = \Api\Lib\Backup::restore($_FILES['file']['tmp_name']);
} else {
    $bkpDone = \Api\Lib\Backup::create();
}

if (!$bkpDone) {
    echo json_encode('ERROR');
} else {
    echo json_encode($bkpDone);
}
