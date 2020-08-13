<?php
if(file_exists($basedir."/bootstrap.php")) { // we are dealing with a OXID 4.7
  $oConfig = oxRegistry::get("oxConfigFile");
  $config['dbServer'] = $oConfig->getVar( 'dbHost' );
  $config['dbName'] = $oConfig->getVar( 'dbName' );
  $config['dbUser'] = $oConfig->getVar( 'dbUser' );
  $config['dbPw'] = $oConfig->getVar( 'dbPwd' );
}
else {
  $oConfig = oxConfig::getInstance();
  $config['dbServer'] = $oConfig->getConfigParam( 'dbHost' );
  $config['dbName'] = $oConfig->getConfigParam( 'dbName' );
  $config['dbUser'] = $oConfig->getConfigParam( 'dbUser' );
  $config['dbPw'] = $oConfig->getConfigParam( 'dbPwd' );
}
