<?php
require("categoryreader.class.php");

define('OX_BASE_PATH', $config['oxid_basedir']); // needed for OXID 4.7
// in OXID 4.7 this function is already defined
// information about the base path is provided by the global variable OX_BASE_PATH (must be set separately)
if(!function_exists("getShopBasePath")) {
  function getShopBasePath() {
    global $config;
    return $config['oxid_basedir'];
  }
}

require("oxidextension.class.php");

class CategoryHandler extends CategoryReader {
  private $config;
  private $error_reporting; // temporarily disable error reporting, since OXID throws some notices..
  private $category_sorting_increment = 10;

  public function __construct($caching = false) {
    global $config;
    $this->config = &$config;
    $this->error_reporting = ini_get("error_reporting");
    parent::__construct($caching);

    // IMPORTANT: with PHP reflections it would be possible to access private / protected properties. unfortunately this feature was introduced in PHP 5.3.2, so we have to take another route
    // now use the oxidExtension class
    // access OXID protected methods
    // $this->oxid_db_update = new ReflectionMethod('oxCategory', '_update');
    // $this->oxid_db_update->setAccessible(true);
    // $this->oxid_db_insert = new ReflectionMethod('oxCategory', '_insert');
    // $this->oxid_db_insert->setAccessible(true);
  }

  public function setParentCategory($cat_id, $parent) {
    $this->setErrorReporting(false);
    $cat = oxNew("oxcategory");
    $cat->load($cat_id);
    $cat->oxcategories__oxparentid->setValue($this->db->validate($parent));
    $this->dbUpdate($cat);
    $this->setErrorReporting(true);

    // dont trust OXID, check if parent assignment was successful
    if($this->getParentCategory($cat_id) == $parent) return true;
    else return false;
  }

  /**
  * create a new category with a defined parent category
  *
  * uses OXID functions to create the category
  *
  * @param string $parent the OXID of the parent category
  * @param string $title the OXTITLE for the new category
  * @return mixed[] associative array with the keys (id, sort, active, hidden) containing information about the newly created category
  */
  public function newCategory($parent, $title = "") {
    // default values for OXHIDDEN and OXACTIVE
    $oxhidden = 0;
    $oxactive = 0;
    // calculate the value for the OXSORT field
    // the new category is always inserted at the very end, thus take the OXSORT value + 1 of the last category
    // must be executed BEFORE the new category is inserted!
    $subs_of_parent = $this->getSubCategories($parent);
    if(count($subs_of_parent) == 0) $sort = $this->category_sorting_increment;
    else $sort = $this->getCategorySort(end($subs_of_parent)) + $this->category_sorting_increment;

    $this->setErrorReporting(false);
    $cat = oxNew("oxcategory");
    $cat->oxcategories__oxparentid->value = $this->db->validate($parent); // set the parent category
    $this->dbInsert($cat);
    $this->setErrorReporting(true);

    $cat_id = $cat->oxcategories__oxid->value; // get the OXID of the newly created category
    // dont trust OXID, check
    if(!$this->categoryExists($cat_id)) return false;

    // until now, we have only inserted the blank category into the database
    // now set the OXTITLE
    $this->db->query("UPDATE oxcategories SET OXTITLE='".$this->db->validate($title)."', OXSORT='$sort', OXHIDDEN='$oxhidden', OXACTIVE='$oxactive' WHERE OXID='".$this->db->validate($cat_id)."'");
    return array(
      "id" => $cat_id,
      "sort" => $sort,
      "active" => $oxactive,
      "hidden" => $oxhidden
      );
  }

  public function deleteCategory($cat_id) {
    $this->setErrorReporting(false);
    $cat = oxNew("oxcategory");
    $res = $cat->delete($cat_id);
    $this->setErrorReporting(true);

    // dont trust OXID, check
    if(!$this->categoryExists($cat_id)) return true;
    else return false;
  }

  // @return the path where the picture was saved (in a form that it can be requested from the server in an <img>)
  public function savePicture($cat_id, $from, $role) {
    if(!$this->categoryExists($cat_id)) return false;
    $role = preg_replace("/[^a-zA-Z0-9_-]/", "", $role);

    // move the file
    $oxid_path = "out/pictures/master/category/$role/"; // path of the picture folder inside the OXID directory
    $path = $this->config['oxid_basedir'].$oxid_path;
    $filename = strtolower(str_replace(" ", "", pathinfo($from, PATHINFO_FILENAME)));
    $ext = strtolower(pathinfo($from, PATHINFO_EXTENSION));

    // create a filename which does not yet exist
    $filepath = $path.$filename.".".$ext;
    $counter = 1;
    while(file_exists($filepath)) {
      $filepath = $path.$filename."_".$counter.".".$ext;
      $counter++;
    }
    if(rename($from, $filepath)) {
      $field = $this->getPicturesRoleField($role);
      if($field === false) return false;
      $this->db->query("UPDATE oxcategories SET $field='".$this->db->validate(pathinfo($filepath, PATHINFO_BASENAME))."' WHERE OXID='".$this->db->validate($cat_id)."'");
      return array("filesystem" => $filepath, "url" => $this->config['oxid_basedir_orig'].$oxid_path.pathinfo($filepath, PATHINFO_BASENAME));
    }
    else return false;
  }

  public function deletePicture($cat_id, $role) {
    if(!$this->categoryExists($cat_id)) return false;

    $role = preg_replace("/[^a-zA-Z0-9_-]/", "", $role);
    $path = $this->config['oxid_basedir']."out/pictures/master/category/$role/";
    // this must stand outside the if. if it returns false, the pic will not be deleted
    $field = $this->getPicturesRoleField($role);
    if($field === false) return false;
    $filename = $this->getPictureName($cat_id, $role);
    if(strlen($filename) == 0) return false;
    if(unlink($path.$filename)) { // now delete the file and save to database
      $this->db->query("UPDATE oxcategories SET $field='' WHERE OXID='".$this->db->validate($cat_id)."'");
      return true;
    }
    else return false;
  }

  private function getPictureName($cat_id, $role) {
    $result = $this->db->query("SELECT OXTHUMB, OXICON, OXPROMOICON FROM oxcategories WHERE OXID='".$this->db->validate($cat_id)."'");
    $data = $result->fetch_object();
    $key = $this->getPicturesRoleField($role);
    if($key === false) return false;
    return $data->$key;
  }

  private function getPicturesRoleField($role) {
    switch($role) {
      case "thumb": return "OXTHUMB"; break;
      case "icon": return "OXICON"; break;
      case "promo_icon": return "OXPROMOICON"; break;
      default: return false;
    }
  }

  public function reorder($order) {
    $parent = $this->getParentCategory($order[0]);
    $subcat = $this->getSubCategories($parent);
    // check if we are dealing with *all* children of the parent category
    // if not, something really strange went wrong
    if(count(array_diff($order, $subcat)) != 0 OR count(array_diff($subcat, $order)) != 0) return false;

    $res = array();
    $counter = $this->category_sorting_increment;
    foreach($order as $cat) {
      $res[$cat] = $counter;
      $this->db->query("UPDATE oxcategories SET OXSORT='$counter' WHERE OXID='".$this->db->validate($cat)."'");
      $counter += $this->category_sorting_increment;
    }
    return $res;
  }

  // private function updateCategoryTree() {
  //   $this->setErrorReporting(false);
  //   $o = oxNew("oxcategorylist");
  //   $o->updateCategoryTree();
  //   $this->setErrorReporting(true);
  // }

  private function setErrorReporting($state) {
    if($state) return ini_set("error_reporting", $this->error_reporting);
    else return ini_set("error_reporting", "E_ALL ^ E_NOTICE");
  }

  private function dbUpdate($cat) {
    $a = new oxidExtension();
    $a->dbUpdate($cat);
  }

  private function dbInsert($cat) {
    $a = new oxidExtension();
    $a->dbInsert($cat);
  }
}

?>
