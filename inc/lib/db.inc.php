<?php
require('mysqli_ext.class.php');
require('mysqli_ext_oxid.class.php');

$db=new mysqli_ext_oxid($config['dbServer'],$config['dbUser'],$config['dbPw'],$config['dbName']);
$db->set_charset("utf8");
unset($config['dbUser'],$config['dbServer'],$config['dbPw'],$config['dbName']);

/* check connection */
if (mysqli_connect_errno()) {
  printf("Connect failed: %s\n", mysqli_connect_error());
  exit();
}
?>
