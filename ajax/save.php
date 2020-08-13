<?php
require("../inc/includes.inc.php");

$id = $_POST['id'];
$field = $_POST['field'];
$value = str_replace(",", ".", $_POST['value']);

if(strpos($field, "sorting") !== false) $field = trim(substr($field, 0, strpos($field, "sorting")));

switch($field) {
  case "active":
    $col = "OXACTIVE";
    break;
  case "stock":
    $col = "OXSTOCK";
    break;
  case "price":
    $col = "OXPRICE";
    break;
  case "tprice":
    $col = "OXTPRICE";
    break;
  case "bprice":
    $col = "OXBPRICE";
    break;
  case "stock_remindamount":
    $col = "OXREMINDAMOUNT";
    break;
  case "stock_available_date":
    $col = "OXDELIVERY";
    $language = $lang->getLanguageCode();
    // handle date inputs with missing year
    $year_added = false;
    if($language == "de") {
      if(strlen($value) <= 6 AND (substr($value, -1) == "." OR is_numeric(substr($value, -1)))) {
        if(is_numeric(substr($value, -1))) $value.=".";
        $value.=date("Y");
        $year_added = true;
      }
    }
    else if($language == "en") {
      if(strlen($value) <= 5 AND is_numeric(substr($value, -1))) {
        $value.="-".date("Y");
        $year_added = true;
      }
    }
    $date = date_parse_from_format($lang->date_format, $value);
    if(strlen($value) <= 6 && mktime(0,0,0,$date["month"], $date["day"], $date["year"]) < time()) $date["year"] = $date["year"] + 1;
    $value = $date["year"]."-".$date["month"]."-".$date["day"];
    break;
  default:
    die("false");
}

$db->query("UPDATE oxarticles SET $col='".$db->validate($value)."' WHERE OXID='".$db->validate($id)."'");

// if an article is a variant, then the OXVARSTOCK of the parent has to be updated when the OXSTOCK of the variant is changed
// this is best done using the onChange() function from OXID
if($col == "OXSTOCK") {
  $oArticle = oxNew("oxarticle");
  $oArticle->load($id);
  $oArticle->onChange();
}

// check
$result = $db->query("SELECT $col FROM oxarticles WHERE OXID='".$db->validate($id)."'");
$data = $result->fetch_object();
switch($field) {
  case "price":
    echo $lang->formatPrice($data->OXPRICE);
    break;
  case "stock_available_date":
    if($data->$col == "0000-00-00") die("false");
    else echo $value;
    break;
  default:
    echo $data->$col;
}
