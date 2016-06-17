<?php

include_once("api/models/RedirectCollection.class.php");
$redirects = new Api\Models\RedirectCollection();
$redirects->loadPublic();
$template = file_get_contents('html/sitemap_container.xml');
$templateItem = file_get_contents('html/sitemap_content.xml');
$currProtocol = 'http://';
if (isset($_SERVER['HTTPS'])) {
    $currProtocol = 'https://';
}
$host = $currProtocol.$_SERVER['HTTP_HOST'];
$templateItems = str_replace(
    array(
        '{{url}}'
    ),
    array(
        $host.'/'
    ),
    $templateItem
);

foreach ($redirects->getList() as $redirectData) {
    include_once("api/models/Redirect.class.php");
    $redirect = new Api\Models\Redirect();
    $redirect->setProperties($redirectData);
    if (!$redirect->isExpired()) {
        $templateItems = $templateItems.str_replace(
            array(
                '{{url}}'
            ),
            array(
                $host.'/'.$redirect->getProperty('alias')
            ),
            $templateItem
        );
    }
}

header('Content-Type: application/xml');
echo str_replace(
    array(
        '{{content}}'
    ),
    array(
        $templateItems
    ),
    $template
);
