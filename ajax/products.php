<?php
require("../inc/includes.inc.php");

function addToWhere($sWhere, $query) {
  if(strlen($sWhere) == 0) $sWhere = $query;
  else $sWhere = "$query AND (".substr($sWhere, 6).")";
  return $sWhere;
}

$sTable = "view_oxarticles_stock";
$sIndexColumn = "OXID";
$aColumns = array('OXID', 'OXTITLE', 'OXARTNUM', 'OXEAN', 'manufacturer_OXTITLE', 'OXMPN', 'OXDISTEAN', 'vendor_OXTITLE', 'OXPRICE', 'OXREMINDAMOUNT', 'OXDELIVERY', 'OXSTOCK', 'OXVARSTOCK', 'OXSTOCKFLAG', 'OXACTIVE', 'OXVARSELECT', 'OXPARENTID', 'OXTITLE_COMBINED', 'OXREMINDACTIVE', 'OXREMINDAMOUNT_SELF', 'NUM_VARIANTS', 'OXBPRICE', 'OXTPRICE', 'OXTIMESTAMP', 'OXINSERT' );
$aSortColumns = array(
  3 => "OXARTNUM",
  4 => "OXEAN",
  5 => "manufacturer_OXTITLE",
  6 => "OXMPN",
  7 => "OXDISTEAN",
  8 => "vendor_OXTITLE",
  9 => "OXPRICE",
  10 => "OXBPRICE",
  11 => "OXTPRICE",
  12 => "OXREMINDAMOUNT_SELF",
  13 => "OXDELIVERY",
  14 => "OXSTOCK",
  16 => "OXVARSTOCK",
  17 => "OXTIMESTAMP",
  18 => "OXINSERT",
);



// debug mode
if(isset($_GET['debug'])) $debug = true;
else $debug = false;

if($debug) {
  var_dump(setlocale(LC_ALL, 0));
  $timer = new Timer();
  var_dump($_GET);
}

if($debug) $timer->start("query_creation");

/*
 * Paging
 */
$sLimit = "";
if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )  {
  $sLimit = "LIMIT ".$db->validate( $_GET['iDisplayStart'] ).", ".($db->validate( $_GET['iDisplayLength'] ));
}


/*
 * Ordering
 */
if ( isset( $_GET['iSortCol_0'] ) ) {
  $sOrder = "ORDER BY  ";
  for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ ) {
    if($_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true") {
      $sortcol = intval( $_GET['iSortCol_'.$i] );
      $sOrder .= $aSortColumns[$sortcol]." ".$db->validate( $_GET['sSortDir_'.$i] ) .", ";
    }
  }

  $sOrder .= "OXTITLE, OXPARENTID, OXVARSELECT";
  // $sOrder = substr_replace( $sOrder, "", -2 );
  // if ( $sOrder == "ORDER BY" ) $sOrder = "";
}


/*
 * Filtering
 * NOTE this does not match the built-in DataTables filtering which does it
 * word by word on any field. It's possible to do here, but concerned about efficiency
 * on very large tables, and MySQL's regex functionality is very limited
 */
$sWhere = "";
if ( $_GET['sSearch'] != "" ) {
  $sWhere = "WHERE (";
  for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
    $sWhere .= $aColumns[$i]." LIKE '%".$db->validate( $_GET['sSearch'] )."%' OR ";
  }
  $sWhere = substr_replace( $sWhere, "", -3 );
  $sWhere .= ')';
}

/* Individual column filtering */
for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
  if ( isset($_GET['bSearchable_'.$i]) AND $_GET['bSearchable_'.$i] == "true" &&  $_GET['sSearch_'.$i] != '' ) {
    $search_string = $_GET['sSearch_'.$i];
    if ( $sWhere == "" ) $sWhere = "WHERE ";
    else $sWhere .= " AND ";
    if($i == 0) $col = "OXTITLE_COMBINED";
    else $col = $aColumns[$i+1];
    if($col == "OXSTOCK") {
      $sWhere .= $col." < ".$db->validate($search_string);
    }
    else {
      $sWhere .= "(";
      foreach(explode(" ", $search_string) as $word) {
        $sWhere .= $col." LIKE '%".$db->validate($word)."%' AND ";
      }
      $sWhere = substr($sWhere, 0, -4);
      $sWhere .= ")";
    }
  }
}

$sJoin = "";
$sGroup = "";
// filter products that are marked as inactive
if($_GET['hide_inactive_articles'] == "true") {
  $query = "WHERE {$sTable}.OXACTIVE = 1";
  $sWhere = addToWhere($sWhere, $query);
}

// filter products that are marked as active
if($_GET['hide_active_articles'] == "true") {
  $query = "WHERE {$sTable}.OXACTIVE = 0";
  $sWhere = addToWhere($sWhere, $query);
}

// show only products with low stock
if($_GET['show_only_low_stock'] == "true") {
  $query = "WHERE {$sTable}.OXSTOCK < {$sTable}.OXREMINDAMOUNT";
  $sWhere = addToWhere($sWhere, $query);
}

// hide parent articles
if($_GET['hide_parents'] == "true") {
  $query = "WHERE ({$sTable}.OXPARENTID<>'' OR ({$sTable}.OXPARENTID ='' AND {$sTable}.NUM_VARIANTS = 0))";
  $sWhere = addToWhere($sWhere, $query);
}

if($debug) {
  $timer->stop("query_creation");
  $timer->start("query_execution");
}
// var_dump($)

$sGroup = "GROUP BY OXPARENTID, OXID";

/*
 * SQL queries
 * Get data to display
 */
$sQuery = "
  SELECT {$sTable}.".str_replace(" , ", " ", implode(", {$sTable}.", $aColumns))."
  FROM   $sTable
  $sJoin
  $sWhere
  $sGroup
  $sOrder
  $sLimit
";
if($debug) echo getFormattedSQL($sQuery);
$rResult = $db->query($sQuery) or die(mysql_error());
if($debug) $timer->stop("query_execution");

/* Data set length after filtering */
$sQuery = "
  SELECT COUNT(DISTINCT {$sTable}.OXID)
  FROM   $sTable
  $sJoin
  $sWhere
";
if($debug) echo getFormattedSQL($sQuery);

$rResultFilterTotal = $db->query( $sQuery) or die(mysql_error());
$aResultFilterTotal = $rResultFilterTotal->fetch_array();
$iFilteredTotal = $aResultFilterTotal[0];

/* Total data set length */
$sQuery = "
  SELECT COUNT(".$sIndexColumn.")
  FROM   $sTable
";
$rResultTotal = $db->query($sQuery) or die(mysql_error());
$aResultTotal = $rResultTotal->fetch_array();
$iTotal = $aResultTotal[0];

/*
 * Output
 */
$output = array(
  "sEcho" => intval($_GET['sEcho']),
  "iTotalRecords" => $iTotal,
  "iTotalDisplayRecords" => $iFilteredTotal,
  "aaData" => array()
);

while($data = $rResult->fetch_object()) {
  $id = $data->OXID;
  $title = $data->OXTITLE;
  $row_class = "";
  $is_variant = !empty($data->OXPARENTID);
  if($data->OXACTIVE == 0) $row_class = "article-inactive";
  if($is_variant) $row_class .=" variant";

  // check for remindamount
  if($data->OXSTOCK < $data->OXREMINDAMOUNT) $row_class .=" remind";

  // format remindamount
  $remindamount = "<span class='amount'>".$data->OXREMINDAMOUNT."</span>";
  if($data->OXREMINDAMOUNT_SELF != $data->OXREMINDAMOUNT) $remindamount = "{$lang->stock_remindamount_inherited}: ".$remindamount;

  // format available date
  if($data->OXDELIVERY == 0) $available_date = str_replace(array("d", "m", "Y"), array("dd", "mm", "yyyy"), $lang->date_format);
  else $available_date = date($lang->date_format, strtotime($data->OXDELIVERY));

  // format oxtimestamp
  if($data->OXTIMESTAMP == 0) $oxtimestamp = "";
  else $oxtimestamp = date($lang->date_format, strtotime($data->OXTIMESTAMP));

  // format oxinsert
  if($data->OXINSERT == 0) $oxinsert = "";
  else $oxinsert = date($lang->date_format, strtotime($data->OXINSERT));

  // create active checkbox
  $active_checkbox = "<input type='checkbox'";
  if($data->OXACTIVE) $active_checkbox .= "checked = 'checked'";
  $active_checkbox .= " />";

  // determine stockflag
  $stockflag = '';
  switch($data->OXSTOCKFLAG) {
    case 1: $stockflag = $lang->stockflag_1; break;
    case 2: $stockflag = $lang->stockflag_2; break;
    case 3: $stockflag = $lang->stockflag_3; break;
    case 4: $stockflag = $lang->stockflag_4; break;
  }

  // determine varstock
  $varstock = '';
  if(!$is_variant) $varstock = $data->OXVARSTOCK;

  $row = array(
    "DT_RowId" => $id,
    "DT_RowClass" => $row_class,
    0 => $active_checkbox,
    1 => trim($title),
    2 => trim($data->OXVARSELECT),
    3 => $data->OXARTNUM,
    4 => $data->OXEAN,
    5 => $data->manufacturer_OXTITLE,
    6 => $data->OXMPN, //manufacturer article number
    7 => $data->OXDISTEAN,
    8 => $data->vendor_OXTITLE,
    9 => $lang->formatPrice($data->OXPRICE),
    10 => $lang->formatPrice($data->OXBPRICE),
    11 => $lang->formatPrice($data->OXTPRICE),
    12 => $remindamount,
    13 => $available_date,
    14 => $data->OXSTOCK,
    15 => $stockflag,
    16 => $varstock,
    17 => $oxtimestamp,
    18 => $oxinsert,
  );
  $output['aaData'][] = $row;
}


if($debug) {
  var_dump($timer->getAll());
  var_dump($output);
}
else echo json_encode($output);
