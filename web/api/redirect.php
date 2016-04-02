<?php

include_once("lib/Security.class.php");
\Api\Lib\Security::validateAdminUser();

if (isset($_GET["hit"])) {
    ignore_user_abort(true);
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

if (isset($_GET["alias"])) {
    include_once("models/Redirect.class.php");
    $redirect = new Api\Models\Redirect();
    $redirect->load($_GET["alias"]);
}

if ($_SERVER['REQUEST_METHOD'] === "PUT") {
    $rawInput = file_get_contents("php://input");
    $putData = json_decode($rawInput);
    if (isset($_GET["hit"])) {
        $redirect->hit($putData);
    } else {
        $redirect->setProperties($putData);
        $redirect->save();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === "DELETE") {
    if (isset($redirect)) {
        if ($redirect->getProperty('active')) {
            echo json_encode("deactivated");
        } else {
            echo json_encode("removed");
        }
        $redirect->remove();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === "GET") {
    if (!isset($redirect)) {
        include_once("models/RedirectCollection.class.php");
        $redirects = new Api\Models\RedirectCollection();
        $redirects->load();
        echo json_encode($redirects->getList());
    } else {
        echo json_encode($redirect->data);
    }
}
