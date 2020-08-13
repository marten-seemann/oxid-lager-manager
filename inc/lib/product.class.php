<?php
/**
* Handle OXID articles
*
* covers especially the category handling, but also provides additional function like getting the name of the manufacturer
*
* @author Marten Seemann <martenseemann@gmail.com>
* @package OXID Category Master
*/
class Product {
  private $db;
  /**
  * OXID of the product
  * @var string
  */
  private $id;

  /**
  * Constructor
  * @param string OXID of the product
  */
  public function __construct($id) {
    global $db;
    $this->db = $db;
    $this->id = $id;
  }

  /**
  * get all categories the product is assigned to
  *
  * the main category is the first value of the returned array (see below)
  *
  * @return string[] OXIDs of the categories the product is assigned to, the first element of the array is the main category
  */
  public function getCategories() {
    $result = $this->db->query("SELECT * FROM oxobject2category WHERE OXOBJECTID='".$this->db->validate($this->id)."' ORDER BY OXTIME"); // ORDER BY oxtime for getting the main category. main category is the one with the lowest oxtime
    if($result->num_rows == 0) return array();
    $cat_ids = array();
    while($data = $result->fetch_object()) {
      $cat_ids[] = $data->OXCATNID;
    }
    return $cat_ids;
  }

  /**
  * get the name of the manufacturer of this product
  *
  * the name of the manufacturer is stored as <i>oxmanufacturers.OXTITLE</i>
  *
  * this function performs a database query to get this value
  *
  * @return string the name of the manufacturer
  */
  public function getManufacturer() {
    $result = $this->db->query("SELECT oxarticles.OXID,oxarticles.OXMANUFACTURERID,oxmanufacturers.OXTITLE FROM oxarticles LEFT JOIN oxmanufacturers ON oxarticles.OXMANUFACTURERID = oxmanufacturers.OXID
      WHERE oxmanufacturers.OXTITLE IS NOT NULL AND oxarticles.OXID='".$this->db->validate($this->id)."'");
    if($result->num_rows == 0) return "";
    $data = $result->fetch_object();
    return $data->OXTITLE;
  }

  /**
  * remove the product from a category
  *
  * @param string $cat_id the OXID of the category from which the product should be removed
  * @return int the number of affected_rows
  */
  public function removeFromCategory($cat_id) {
    $this->db->query("DELETE FROM oxobject2category WHERE OXOBJECTID='".$this->db->validate($this->id)."' AND OXCATNID='".$this->db->validate($cat_id)."'");
    return $this->db->affected_rows;
  }

  /**
  * remove the product from all categories
  *
  * after this function the product is assigned to 0 categories!
  *
  * @return int the number of affected_rows
  */
  public function removeFromAllCategories() {
    $this->db->query("DELETE FROM oxobject2category WHERE OXOBJECTID='".$this->db->validate($this->id)."'");
    return $this->db->affected_rows;
  }

  /**
  * assign the product to a certain category
  *
  * @param string $cat_id the OXID of the category this product should be assigned to
  * @param bool $main_category should this category be the (new) main category of this product?
  * @return int the number of affected_rows
  */
  public function setCategory($cat_id, $main_category = false) {
    $has_category = $this->hasCategory($cat_id);
    // if this $cat_id should be the main category, we have to make sure that the other categories are NOT the main categories
    if($main_category) $this->db->query("UPDATE oxobject2category SET OXTIME='".time()."' WHERE OXOBJECTID='".$this->db->validate($this->id)."' AND OXTIME='0'");
    // if the product already has THIS category $cat_id assigned, we must treat it differently (update the database instead of inserting)
    if($has_category) {
      if(!$main_category) return 1; // if the category is already set and we shouldnt make it the default category, we are done here
      else {
        // set this product as the main category
        $this->db->query("UPDATE oxobject2category SET OXTIME='0' WHERE OXOBJECTID='".$this->db->validate($this->id)."' AND OXCATNID='".$this->db->validate($cat_id)."'");
        if($this->db->errno) return 0;
        return 1;
      }
    }
    else { // the product does not yet have this category $cat_id assigned. we must create a new record in oxobject2category to assign the category
      $primary_key = substr(time()."_".$this->id, 0, 32);
      if($main_category) $oxtime = 0;
      else $oxtime = time();
      $this->db->query("INSERT INTO oxobject2category(OXID, OXOBJECTID, OXCATNID, OXPOS, OXTIME) VALUES('".$this->db->validate($primary_key)."','".$this->db->validate($this->id)."','".$this->db->validate($cat_id)."', 0, '$oxtime')");
      return $this->db->affected_rows;
    }
  }

  /**
  * checks if the product is assigned to a certian category
  *
  * @param string $cat_id the category to be checked
  * @return bool is the product in this category?
  */
  private function hasCategory($cat_id) {
    $result = $this->db->query("SELECT COUNT(*) AS num FROM oxobject2category WHERE OXOBJECTID='".$this->db->validate($this->id)."' AND OXCATNID='".$this->db->validate($cat_id)."'");
    $data = $result->fetch_object();
    if($data->num > 0) return true;
    else return false;
  }
}
