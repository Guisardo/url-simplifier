<?php
namespace Api\Lib;

/**
 * Static class that consolidates the backups
 */
class Backup
{
    /**
    * Add files and sub-directories in a folder to zip file.
    * @param string $folder
    * @param ZipArchive $zipFile
    * @param int $exclusiveLength Number of text to be exclusived from the file path.
    */
    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    Backup::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    private static function delete($path)
    {
        if (is_dir($path) === true) {
            $files = array_diff(scandir($path), array('.', '..'));

            foreach ($files as $file) {
                Backup::delete(realpath($path) . '/' . $file);
            }

            return rmdir($path);
        } elseif (is_file($path) === true) {
            return unlink($path);
        }

        return false;
    }

    public static function create()
    {
        $bkpDone = false;
        date_default_timezone_set('utc');
        $now = time();
        require_once("lib/Connection.class.php");

        Backup::delete(__DIR__."/bkp/".\Api\Lib\Connection::getDBName());

        exec("mongodump -h ".$_ENV["MONGO_HOSTNAME"]." -p ".$_ENV["MONGO_HOSTPORT"]." --db ".\Api\Lib\Connection::getDBName()." --gzip -o ".__DIR__."/bkp/");

        $maxLoop = 3;
        while (!$bkpDone && $maxLoop > 0) {
            sleep(5);
            $maxLoop = $maxLoop - 1;
            if (file_exists(__DIR__."/bkp/".\Api\Lib\Connection::getDBName())) {
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
        }

        if (file_exists(__DIR__."/bkp/".\Api\Lib\Connection::getDBName())) {
            $pathInfo = pathInfo(__DIR__."/bkp/".\Api\Lib\Connection::getDBName());
            $parentPath = $pathInfo['dirname'];
            $dirName = $pathInfo['basename'];

            $zip = new \ZipArchive;
            $zip->open(__DIR__."/bkp/".\Api\Lib\Connection::getDBName().'.zip', \ZipArchive::CREATE);
            $zip->addEmptyDir($dirName);
            Backup::folderToZip(__DIR__."/bkp/".\Api\Lib\Connection::getDBName(), $zip, strlen("$parentPath/"));
            $zip->close();
            $bkpDone = array('link' => "/api/lib/bkp/".\Api\Lib\Connection::getDBName().'.zip');
        }

        return $bkpDone;
    }

    public static function restore($filePath)
    {
        $restoreDone = false;
        $dirs = array_filter(glob(__DIR__."/bkp/*"), 'is_dir');
        foreach ($dirs as $dir) {
            Backup::delete($dir);
        }

        $zip = new \ZipArchive;
        $zip->open($filePath);
        $zip->extractTo(__DIR__."/bkp/");
        $zip->close();

        $dirs = array_filter(glob(__DIR__."/bkp/*"), 'is_dir');
        foreach ($dirs as $dir) {
            exec("mongorestore -h ".$_ENV["MONGO_HOSTNAME"]." -p ".$_ENV["MONGO_HOSTPORT"]." --db ".\Api\Lib\Connection::getDBName()." --gzip --drop ".$dir);
            $restoreDone = true;
        }
        return $restoreDone;
    }
}
