<?php

ini_set("display_errors", false);
include_once("api/models/Settings.class.php");
$isConnected = false;
try {
    $settings = new Api\Models\Settings('global');
    $settings->load();
    $isConnected = true;
} catch (Exception $e) {
    $isConnected = $e->getMessage();
}

if (isset($_GET["healthcheck"])) {
    if ($isConnected === true) {
        die("WORKING");
    } else {
        die($isConnected);
    }
}

$defaultUrl = $settings->getProperty('defaultUrl');

$orgQuery = '?'.$_SERVER['QUERY_STRING'];
$orgQuery = preg_replace('/alias=.*?(?:&|$)/', '', $orgQuery);
$orgQuery = preg_replace('/^\?$/', '', $orgQuery);

include_once("api/models/Redirect.class.php");
$redirect = new Api\Models\Redirect();
if (isset($_GET["alias"]) && $_GET["alias"] !== '') {
    $redirect->load($_GET["alias"]);
}

$cid = $_COOKIE["cid"];
if ($cid === null) {
    $cid = rand(0, 2147483647);
    setcookie("cid", $cid, time() + (86400 * 31 * 12));
}
$currProtocol = 'http://';
if (isset($_SERVER['HTTPS'])) {
    $currProtocol = 'https://';
}
$ip = $_SERVER['REMOTE_ADDR'];
//check ip from share internet
if (isset($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
}
//to check ip is pass from proxy
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
}
$data_json = json_encode([
        "qs" => $_SERVER["QUERY_STRING"],
        "charset" => $_SERVER["HTTP_ACCEPT_CHARSET"],
        "encoding" => $_SERVER["HTTP_ACCEPT_ENCODING"],
        "lang" => $_SERVER["HTTP_ACCEPT_LANGUAGE"],
        "ref" => $_SERVER["HTTP_REFERER"],
        "ua" => $_SERVER["HTTP_USER_AGENT"],
        "ip" => $ip,
        "usr" => $_SERVER["PHP_AUTH_USER"],
        "dl" => $currProtocol.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"],
        "tid" => $settings->getProperty('analytics'),
        "cid" => $cid
    ]);
$url = $currProtocol.$_SERVER['HTTP_HOST'].'/api/redirect.php?hit=me&alias='.$redirect->getProperty("alias");
$security = new Api\Models\Settings('sec');
$security->load();
$username = $security->getProperty('username');
if ($username === null) {
    $username = '';
}
$password = $security->getProperty('password');
if ($password === null) {
    $password = '';
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
if ($username !== '' || $password !== '') {
    curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
}
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_json)));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
curl_exec($ch);
curl_close($ch);

if ($redirect->isNew() || $redirect->isExpired() || $redirect->getProperty('destination') === '') {
    $redirect = new Api\Models\Redirect();
}
if ($redirect->getProperty('method') === 'shareable') {
    $template = file_get_contents('html/shareable.html');
    $metaRedirect = '';
    $extraTags = $settings->getProperty('extraTags');
    if ($extraTags === null) {
        $extraTags = '';
    }
    $template = str_replace(
        array(
            '{{title}}',
            '{{description}}',
            '{{image}}',
            '{{destination}}',
            '{{defaultUrl}}',
            '{{extraTags}}'
        ),
        array(
            $redirect->getProperty('title'),
            $redirect->getProperty('description'),
            $redirect->getProperty('image'),
            $redirect->getProperty('destination'),
            $defaultUrl,
            $metaRedirect,
            $extraTags
        ),
        $template
    );
    echo $template;
} else {
    $username = $redirect->getProperty('username');
    $password = $redirect->getProperty('password');
    include_once("api/lib/Security.class.php");
    \Api\Lib\Security::httpBasicAuth($username, $password);

    if ($redirect->getProperty('method') === 'permanent') {
        header("HTTP/1.1 301 Moved Permanently");
    } elseif ($redirect->getProperty('method') === 'temporary') {
        header("HTTP/1.1 302 Moved Temporary");
    }
    header("Location: ".$redirect->getProperty('destination').$orgQuery);
    //var_dump($redirect->getProperty('destination').$orgQuery);
}
