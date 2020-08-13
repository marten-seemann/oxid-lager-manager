<?php
/**
* include various php-files needed on almost every page
*
* @author Marten Seemann <martenseemann@gmail.com>
* @package OXID Stock Manager
*/
if ( !defined('__DIR__') ) define('__DIR__', dirname(__FILE__)); // PHP 5.2 polyfill

if(isset($_GET['debug']) AND $_GET['debug']) {
  error_reporting(E_ERROR | E_WARNING | E_PARSE);
  ini_set("display_errors","1");
}

require(__DIR__."/config.inc.php");

$config['oxid_basedir_orig'] = $config['oxid_basedir'];
$config['oxid_basedir'] = realpath(dirname(__FILE__)."/../".$config['oxid_basedir'])."/";
$basedir = $config['oxid_basedir'];
require("lib/oxid_bootstrap.php");
require("lib/db_connector.inc.php");

require("lib/db.inc.php");
require("lib/timer.class.php");
require("lib/language.class.php");
require("get_language.inc.php");
require("lib/functions.inc.php");
require("version.inc.php");


require('lib/json_encode_oldphp/jsonwrapper.php'); // add json_encode for PHP version before PHP 5.2

if(!isset($config['disable_auth_check']) OR !$config['disable_auth_check']) {
  require("lib/enforce_http_auth.inc.php");
  if(!isAuthenticatedUser()) {
    $dir = realpath(__DIR__."/..");
    echo "
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset='UTF-8'>
        </head>
        <body>
          <div style='color:#CE272A; width: 50%; margin: 50px auto; text-align: center; font-size: 19px;'>".str_replace("%DIR%", $dir, $lang->no_auth_user_error)."</div>
        </body>
      </html>
    ";
    die;
  }
}
?>
