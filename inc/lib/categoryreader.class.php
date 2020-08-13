<?php
/**
* handle the tree structure of the OXID categories
*
* provides functions to reconstruct the tree-like strucure of OXID categories
* also makes some category attributes (name, hidden, active) accessible
*
* @author Marten Seemann <martenseemann@gmail.com>
* @package OXID Category Master
*/
class CategoryReader {
  protected $db;
  /**
  * determine if category names will be cached
  * @var bool
  */
  private $caching;
  /**
  * array containing the category data if caching is enabled
  * @var string[]
  * @see createCategoryNameCache()
  */
  private $categories;

  /**
  * Constructor
  *
  * prepared statement is need by getSubCategories()
  * @param bool $caching should category name caching be enabled. useful if one can assume that the names of many categories will be needed
  */
  public function __construct($caching = false) {
    global $db;
    $this->db = $db;
    $this->caching = $caching;
    if($this->caching) $this->createCategoryNameCache();
    // this is the prepared statement to get the subcategories. will be executed by getSubCategories()
    $this->subcat_stmt = $this->db->prepare("SELECT OXID FROM oxcategories WHERE OXPARENTID=? ORDER BY OXSORT");
  }

  /**
  * Destructor
  *
  * closes the prepared statement
  */
  public function __destruct() {
    $this->subcat_stmt->close();
  }

  /**
  * get all categories located directly under oxrootid
  */
  public function getRootCategories() {
    return $this->getSubCategories('oxrootid');
  }

  public function getParentCategory($cat_id) {
    $result = $this->db->query("SELECT OXPARENTID FROM oxcategories WHERE OXID='".$this->db->validate($cat_id)."'");
    if($result->num_rows == 0) return false;
    $data = $result->fetch_object();
    return $data->OXPARENTID;
  }

  // works, but is not needed anywhere
  public function getAllParentCategories($cat_id) {
    $cats = array();
    $cat = $cat_id;
    do {
      $cats[] = $this->getParentCategory($cat);
      $cat = end($cats);
    } while($cat != "oxrootid");
    return $cats;
  }

  /**
  * get all categories located directly unter a category
  *
  * uses the prepared statement prepared by the constructor
  *
  * @param  string  $cat_id OXID of the category
  * @return string[] array containing the OXIDs of the categories
  */
  public function getSubCategories($cat_id) {
    $this->subcat_stmt->bind_param("s", $cat_id);
    $this->subcat_stmt->execute();
    $this->subcat_stmt->bind_result($id);
    $ids = array();
    while($this->subcat_stmt->fetch()) {
      $ids[] = $id;
    }
    return $ids;
  }

  /**
  * get all categories located somewhere under a category
  *
  * @param  string  $cat_id OXID of the category
  * @return string[] array containing the OXIDs of all subcategories
  */
  private function getAllSubCategories($cat_id) {
    $sub_cats = $this->getSubCategories($cat_id);
    $ids = array();
    if(count($sub_cats) > 0) {
      $ids = array_merge($ids, $sub_cats);
      foreach($sub_cats as $cat) $ids = array_merge($ids, $this->getAllSubCategories($cat));
    }
    return $ids;
  }

  /**
  * get all categories located somewhere under a category, including this category itself
  *
  * @param  string  $cat_id OXID of the category
  * @return string[] array containing the OXIDs of all subcategories
  */
  public function getAllSubCategoriesFromHere($cat_id) {
    return array_merge(array($cat_id), $this->getAllSubCategories($cat_id));
  }

  /**
  * get the name of a category
  *
  * this functions makes use the category caching, if enabled
  * @param  string  $cat_id OXID of the category
  * @return string name of the category
  */
  public function getCategoryName($cat_id) {
    if($this->caching) return $this->categories[$cat_id]['name'];
    else {
      $result=$this->db->query("SELECT OXID,OXTITLE FROM oxcategories WHERE OXID='".$this->db->validate($cat_id)."'");
      if($result->num_rows == 0) return false;
      $data = $result->fetch_object();
      return $data->OXTITLE;
    }
  }

  public function getCategorySort($cat_id) {
    if($this->caching) return $this->categories[$cat_id]['sort'];
    else {
      $result=$this->db->query("SELECT OXID,OXSORT FROM oxcategories WHERE OXID='".$this->db->validate($cat_id)."'");
      if($result->num_rows == 0) return false;
      $data = $result->fetch_object();
      return $data->OXSORT;
    }
  }

  /**
  * determine if a category is active
  *
  * just ask the database attribute 'active'
  *
  * this function does not yet work without the category caching
  *
  * @param  string  $cat_id OXID of the category
  * @return   bool  is the category active?
  */
  public function isCategoryActive($cat_id) {
    if($this->caching) return $this->categories[$cat_id]['active'];
    else throw new Exception("Not yet implemented");
  }

  /**
  * determine if a category is hidden
  *
  * just ask the database attribute 'hidden'
  *
  * this function does not yet work without the category caching
  *
  * @param  string  $cat_id OXID of the category
  * @return   bool  is the category hidden?
  */
  public function isCategoryHidden($cat_id) {
    if($this->caching) return $this->categories[$cat_id]['hidden'];
    else throw new Exception("Not yet implemented");
  }

  /**
  * check if category exists
  *
  * this function does not make use of the cache
  * @param  string  $cat_id OXID of the category
  * @return bool does the category exist?
  */
  public function categoryExists($cat_id) {
    $result = $this->db->query("SELECT COUNT(*) AS num FROM oxcategories WHERE OXID='".$this->db->validate($cat_id)."'");
    $data = $result->fetch_object();
    if($data->num < 1) return false;
    else return true;
  }

  // public function getCategoryData($cat_id) {
  //   $result = $this->db->query("SELECT * FROM oxcategories WHERE OXID='".$this->db->validate($cat_id)."'");
  //   $data = $result->fetch_object();
  //   if($data->num < 1) return false;
  //   else return true;
  // }


  /**
  * create the category name cache
  *
  * gets *all* categories from the database
  *
  * fields queried: OXID of the category, the title, active and hidden
  *
  * the result will be saved to @var $categories
  */
  private function createCategoryNameCache() {
    $result=$this->db->query("SELECT OXID,OXTITLE,OXACTIVE,OXHIDDEN,OXSORT FROM oxcategories ORDER BY OXID");
    $this->categories = array();
    while($data=$result->fetch_object()) {
      $this->categories[$data->OXID]=array(
        "name" => $data->OXTITLE,
        "active" => $data->OXACTIVE,
        "hidden" => $data->OXHIDDEN,
        "sort" => $data->OXSORT
        );
    }
  }
}

?>