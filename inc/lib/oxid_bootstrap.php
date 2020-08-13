<?php
function getShopBasePath() {
  global $basedir;
  return $basedir;
}

if(file_exists($basedir."/bootstrap.php")) { // we are dealing with a OXID 4.7
  require_once($basedir."/bootstrap.php");
}
else {  // older OXID, max. 4.6.5
  require_once($basedir."/modules/functions.php");
  require_once($basedir."/core/oxfunctions.php");
}
