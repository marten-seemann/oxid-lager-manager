<?php
/**
* add functionality to the MySQLi class needed to access OXID translations
*
* provide functions to rewrite queries just before they are executed in order to access the translation fields of the OXID database
*
* @author Marten Seemann <martenseemann@gmail.com>
* @package OXID Category Master
*/
class mysqli_ext_oxid extends mysqli_ext {
  /**
  * an instance of the Language class
  * @var object
  */
  private $lang;


  /**
  * Constructor
  *
  * @param string $db_server MySQL server name
  * @param string $db_name MySQL database name
  * @param string $db_user MySQL user
  * @param string $db_passwort MySQL password
  */
  public function __construct($db_server,$db_user,$db_passwort,$db_name) {
    $this->language = false; // can be set to a meaningful value by calling setLanguage()
    parent::__construct($db_server,$db_user,$db_passwort,$db_name);
  }

  /**
  * execute MySQL query
  *
  * if necessary (that means if $this->language is set), replace fields to get the desired language from the database
  * @param string $query the query to be executed
  * @return mixed the MySQL result
  */
  public function query($query) {
    if(!!$this->lang->getOxidLanguage()) {
      $query = $this->replaceQuery($query);
    }
    return parent::query($query);
  }

  /**
  * pass a Language object to this class
  * @param object $lang an instance of the Language class
  * @return void
  */
  public function setLanguage(&$lang) {
    $this->lang = $lang;
  }

  /**
  * do the query replacement
  *
  * this function is called by the query() just before the query is executed, thus we rewrite the complete MySQL query
  *
  * which language code we have to use was determined by detectOxidLanguageCode(), thus we know that the <i>OXTITLE</i> value is stored in <i>OXTITLE_1</i> in the corresponding language
  *
  * replaces all occurences of a field with the corresponding translated field
  * @param string $query the query that must be rewritten
  * @return string the query that was rewritten
  */
  private function replaceQuery($query) {
    // echo getFormattedSQL($query)."<br>";
    if($this->lang->getOxidLangNumber() === false) return $query; // if for example OXTITLE is OXTITLE in the desired language, we do not have to rewrite the query
    $number = $this->lang->getOxidLangNumber();
    $search = array(
      "OXTITLE",
      "OXDESC",
      "OXURLDESC",
      "OXSHORTDESC",
      "OXLONGDESC",
      "OXSTOCKTEXT",
      "OXTHUMB",
      "OXNOSTOCKTEXT",
      "OXSEARCHKEYS",
      "OXVARNAM",
      "OXVARSELECT"
    );
    // TODO: change replace when in ORDER query
    $replace = array();
    foreach($search as $val)  $replace[] = "{$val}_{$number}";
    $from_pos = strpos($query, " FROM ");
    if(strpos($query, "SELECT") !== false) { // deal with a SELECT query
      $offset = strpos($query, "SELECT ")+7;
      if($offset === false) return $query; // query replacement only possible for SELECT queries till now
      // remove the SELECT from the beginning of the query
      $query_part1 = substr($query, $offset, $from_pos-$offset); // this is the part between SELECT ... FROM, where only ... is contained (SELECT and FROM are removed from the string)
      unset($offset);
      // var_dump($query_part1);
      $fields = explode(", ", $query_part1); // MySQL table fields that should be queried
      if(count($fields) == 1) $fields = explode(",", $query_part1);
      $new_query_part1 = "";
      foreach($fields as $field) { // iterate over all table fields and do replacements, if necessary
        $tmp = $field;
        for($i=0; $i<count($search); $i++) {
          $search_string = $search[$i];
          $replace_string = $replace[$i];
          if(strpos($field, $search_string) !== false) { // if a replacement has to be performed in the table field
            $tmp = str_replace($search_string, $replace_string, $tmp);
            $tmp .= " AS ";
            $offset = strpos($field, ".");
            // cut away the table name for the AS-part, e.g. "view_xyz.OXTITLE" => "view_xyz.OXTITLE_1 AS OXTITLE"
            if($offset === false) $tmp .= $field;
            else $tmp .= substr($field, $offset+1);
          }
        }
        $new_query_part1.= $tmp.", ";
      }
      $new_query_part1 = "SELECT ".substr($new_query_part1, 0, -2); // put the SELECT here again, which was removed earlier
      $new_query = $new_query_part1.str_replace($search, $replace, substr($query, $from_pos));
      // var_dump($new_query);
      return $new_query;
    }
    else if(strpos($query, "UPDATE") !== false OR strpos($query, "INSERT") !== false) { // deal with an UPDATE or INSERT queries
      $new_query = str_replace($search, $replace, $query);
      return $new_query;
      // echo getFormattedSQL($new_query);
    }
    else return $query; // if there is no FROM in the query, then do no replacement at all, then we are ready here
  }
}
