<?php
$url = 'http://api/settings.php?type=global';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$settings = json_decode(curl_exec($ch));
curl_close($ch);

$password = $settings->password;
if ($password !== null) {
    $realm = 'UrlShorter Realm';
    if (('admin' !== $_SERVER['PHP_AUTH_USER'])
        || ($password !== md5(''.date('d M Y')) && $password !== md5($_SERVER['PHP_AUTH_PW'].date('d M Y')))) {
        header('WWW-Authenticate: Basic realm="'.$realm.'"');
        header('HTTP/1.0 401 Unauthorized');
        die ("Not authorized");
        exit;
    }
}


if (isset($_GET["alias"]) || isset($_GET["settings"])) {
    if (isset($_GET["settings"])) {
        $url = 'http://api/settings.php?type='.$_GET["settings"];
    } else if ($_GET["alias"] === "*") {
        $url = 'http://api/redirect.php';
    } else {
        $url = 'http://api/redirect.php?alias='.$_GET["alias"];
    }
    // we are the parent
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if ($_SERVER['REQUEST_METHOD'] === "PUT") {
        $data_json = file_get_contents("php://input");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_json)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    } else if ($_SERVER['REQUEST_METHOD'] === "DELETE") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $data = curl_exec($ch);
    curl_close($ch);

    header('Content-Type: application/json');
    echo $data;
} else {
    $html = $_GET["html"];
    if ($html === null) {
        $html = 'list';
    }
    echo file_get_contents('html/'.$html.'.html');
}