<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors","1");
header("Content-type: text/javascript");
require('../../inc/lib/language.class.php');
$language = $_GET['lang'];
$lang = new LanguageHelper($language, "../../", true);

$strings = $lang->getDefaultLangKeys();


?>

var lang = {
<?php
foreach($strings as $string) {
  echo "'$string': '{$lang->$string}', \n";
}
?>
}