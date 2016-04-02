<?php
// we are the parent
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/redirect.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
$username = $_SERVER['PHP_AUTH_USER'];
if ($username === null) {
    $username = '';
}
$password = $_SERVER['PHP_AUTH_PW'];
if ($password === null) {
    $password = '';
}
if ($username !== '' || $password !== '') {
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $username.":".md5(trim(strtolower($password.date('d M Y')))));
}
$data = curl_exec($ch);
$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);
$data = substr($data, $header_len);
$data_header = substr($data, 0, $header_len);
if (strpos($data_header, 'Not authorized') !== false) {
    $realm = 'UrlShorter Realm Admin';
    header('WWW-Authenticate: Basic realm="'.$realm.'"');
    header('HTTP/1.0 401 Unauthorized');
    die("Not authorized");
    exit;
}

if (isset($_GET["alias"]) || isset($_GET["settings"])) {
    if (isset($_GET["settings"])) {
        $url = 'http://localhost/api/settings.php?type='.$_GET["settings"];
    } elseif ($_GET["alias"] === "*") {
        $url = 'http://localhost/api/redirect.php';
    } else {
        $url = 'http://localhost/api/redirect.php?alias='.$_GET["alias"];
    }
    // we are the parent
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
    $username = $_SERVER['PHP_AUTH_USER'];
    if ($username === null) {
        $username = '';
    }
    $password = $_SERVER['PHP_AUTH_PW'];
    if ($password === null) {
        $password = '';
    }
    if ($username !== '' || $password !== '') {
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username.":".md5(trim(strtolower($password.date('d M Y')))));
    }
    if ($_SERVER['REQUEST_METHOD'] === "PUT") {
        $data_json = file_get_contents("php://input");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_json)
            ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    } elseif ($_SERVER['REQUEST_METHOD'] === "DELETE") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $data = curl_exec($ch);
    $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $data = substr($data, $header_len);

    header('Content-Type: application/json');
    echo $data;
} else {
    $html = $_GET["html"];
    if ($html === null) {
        $html = 'list';
    }
    echo file_get_contents('html/'.$html.'.html');
}
