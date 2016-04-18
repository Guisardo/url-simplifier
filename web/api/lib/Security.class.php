<?php
namespace Api\Lib;

/**
* Static class that consolidates the security methods
*/
class Security
{
    /**
     * Build a daily token from a password.
     * @param  [type] $password Original password
     * @return [type]           Tokenized password
     */
    public static function tokenizePwd($password)
    {
        return md5(trim(strtolower($password.date('d M Y'))));
    }
    /**
     * Validate the control panel user.
     * @return [void] The process is cut when unauthorized
     */
    public static function validateAdminUser()
    {
        require_once(__DIR__."/../models/Settings.class.php");
        $security = new \Api\Models\Settings('sec');
        $security->load();
        $username = $security->getProperty('username');
        $password = $security->getProperty('password');
        var_dump($username);
        var_dump($password);
        \Api\Lib\Security::httpBasicAuth($username, $password);
    }
    /**
     * Apply basic authentication verification.
     * @param  [String] $username Username
     * @param  [String] $password Password
     * @return [void]           The process is cut when unauthorized
     */
    public static function httpBasicAuth($username, $password)
    {
        if ($username === '') {
            $username = null;
        }
        if ($password === '') {
            $password = null;
        }
        if ($username !== null || $password !== null) {
            $realm = 'UrlSimplifier Realm';
            if (($username !== null && $username !== $_SERVER['PHP_AUTH_USER'])
                || ($password !== null && $password !== $_SERVER['PHP_AUTH_PW'])) {
                header('WWW-Authenticate: Basic realm="'.$realm.'"');
                header('HTTP/1.0 401 Unauthorized');
                die("Not authorized");
                exit;
            }
        }
    }
}
