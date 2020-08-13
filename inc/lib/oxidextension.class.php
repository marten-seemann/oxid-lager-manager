<?php
// needed to get access to protected functions from OXID
class oxidExtension extends oxCategory {
  public function dbUpdate(&$cat) {
    $cat->_update();
  }

  public function dbInsert(&$cat) {
    $cat->_insert();
  }
}
?>
