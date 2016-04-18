<?php
include_once("lib/Security.class.php");
\Api\Lib\Security::validateAdminUser();

date_default_timezone_set('utc');
$now = time();
require_once("lib/Connection.class.php");

function delete($path)
{
    if (is_dir($path) === true) {
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file) {
            Delete(realpath($path) . '/' . $file);
        }

        return rmdir($path);
    } elseif (is_file($path) === true) {
        return unlink($path);
    }

    return false;
}
delete(__DIR__."/bkp/".\Api\Lib\Connection::getDBName());

exec("mongodump -h ".$_ENV["MONGO_HOSTNAME"]." -p ".$_ENV["MONGO_HOSTPORT"]." --db ".\Api\Lib\Connection::getDBName()." --gzip -o ".__DIR__."/bkp/");

$maxLoop = 3;
while (!$bkpDone && $maxLoop > 0) {
    sleep(5);
    $maxLoop = $maxLoop - 1;
    $bkpDone = true;
    $files = scandir(__DIR__."/bkp/".\Api\Lib\Connection::getDBName());
    foreach ($files as $file) {
        $bkpTime = filemtime(__DIR__."/bkp/".\Api\Lib\Connection::getDBName()."/".$file);
        if ($file != "." && $file != ".." && $now - 10 > $bkpTime) {
            $bkpDone = false;
            break;
        }
    }
}

if (!$bkpDone) {
    echo 'ERROR';
} else {
    echo 'ACK';
}
