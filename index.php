<?php
require("inc/includes.inc.php");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getLanguageCode(); ?>">
<head>
  <meta charset="UTF-8">
  <title><?php echo $lang->page_title; ?></title>
  <link href="assets/bootstrap/css/bootstrap.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/jquery.noty.css">
  <link rel="stylesheet" href="assets/css/jquery.noty_theme_twitter.css">
  <link rel="stylesheet" href="assets/css/jquery.contextMenu.css">
  <!-- <link rel="stylesheet" href="assets/fontawesome/font-awesome.css"> -->
  <link rel="stylesheet" href="assets/css/font-awesome.css">
  <link rel="stylesheet" href="assets/css/styles.css">
  <!--<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>-->
  <script src="assets/js/jquery-1.8.1.min.js"></script>
  <script src="assets/bootstrap/js/bootstrap.min.js"></script>
  <script src="assets/js/jquery.typewatch.js"></script>
  <script src="assets/js/jquery.noty.js"></script>
  <script src="assets/js/jquery.cookie.js"></script>
  <script src="assets/js/jquery.jeditable.js"></script>
  <script src="assets/js/jquery.media.js"></script>
  <script src="assets/js/noty_config.js"></script>
  <script src="assets/js/jquery.contextMenu.js"></script>
  <script src="assets/datatables/media/js/jquery.dataTables.js"></script>
  <script src="assets/datatables/media/js/DT_bootstrap.js"></script>
  <script src="assets/js/src/article_table.js"></script>
  <script src="assets/js/src/notification_handler.js"></script>
  <script src="assets/js/src/language_handler.js"></script>
  <script src="assets/js/jquery.ddslick.js"></script>
  <script src="assets/js/src/main.js"></script>
  <script src="assets/js/jquery.timer.js"></script>
  <script src="assets/js/lang.js.php?lang=<?php echo $lang->getLanguageCode(); ?>"></script>
</head>
<body>

  <div class="container-fluid">
    <div class="row">
      <header>
        <h1><?php echo $lang->page_header; ?><span id="help"><i class="icon-question-sign icon-small"></i></span></h1>
        <div id="header_functions_right">
          <div><a class="btn btn-default" id="refresh"><i class="icon-refresh"></i> <?php echo $lang->refresh_button; ?></a></div>
          <div id="stock_manager_language_switcher_wrapper">
            <div><?php echo $lang->stock_manager_language; ?>:</div>
            <div id="stock_manager_language_switcher" class="language_switcher">
              <form action="#">
                <select>
                <?php
                $languages = LanguageHelper::availableLanguages();
                foreach($languages as $key) {
                  echo "<option value='$key' data-imagesrc='assets/flags/".$lang->getFlagCode($key).".png' ";
                  if($key == $lang->getLanguageCode()) echo ' selected="selected"';
                  echo ">".$lang->getLanguageName($key)."</option>\n";
                }
                ?>
                </select>
              </form>
            </div>
          </div>
          <div id="oxid_language_switcher_wrapper">
          <?php
          $oxid_languages = $lang->getOxidLanguages();
          if(count($oxid_languages) > 0 ) {
          echo "<div>{$lang->oxid_language}:</div>\n";
          echo "<div id=\"oxid_language_switcher\">\n";
          echo "<form action=\"#\">\n<select>\n";
          foreach($oxid_languages as $key) {
            echo "<option value='$key' data-imagesrc=\"assets/flags/".$lang->getFlagCode($key).".png\" ";
            if(isset($_COOKIE['oxid_language']) AND $key == $_COOKIE['oxid_language']) echo " selected=\"selected\"";
            echo ">".$lang->getLanguageName($key)."</option>\n";
          }
          echo "</select></form>\n";
          echo "</div>";
          }
          ?>
          </div>
        </div>
      </header>
      <div class="modal fade" id="modal_help">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal">x</button>
              <h4 class="modal-title"><?php echo $lang->help_modal_legend; ?></h4>
            </div>
            <div class="modal-body">
              <?php echo str_replace('%VERSION%', $version, $lang->help_modal_data); ?>
            </div>
            <div class="modal-footer">
              <a href="#" class="btn btn-default" data-dismiss="modal"><?php echo $lang->modal_close; ?></a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="main" class="row">
      <div class="col-sm-12">
        <?php if(file_exists("inc/demo_note.inc.php")) { require("inc/demo_note.inc.php"); } ?>
        <?php if(!isset($config['disable_backup_notice']) OR !$config['disable_backup_notice']) {
          echo "<div class=\"alert alert-warning alert-dismissable\">
            <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>
              {$lang->backup_notice}
          </div>";
          }
        ?>
        <div id="hidden_articles_proto" style="display:hidden">
          <?php echo $lang->article_hide_inactive; ?>: <input type="checkbox" name="hide_inactive_articles" id="hide_inactive_articles" checked="checked" />&nbsp;
          <?php echo $lang->article_hide_active; ?>: <input type="checkbox" name="hide_active_articles" id="hide_active_articles" />
        </div>
        <div id="show_only_low_stock_proto" style="display:hidden"><?php echo $lang->show_only_low_stock; ?>: <input type="checkbox" name="show_only_low_stock" id="show_only_low_stock" /></div>
        <div id="hide_parents_proto" style="display: hidden"><?php echo $lang->hide_parents; ?>: <input type="checkbox" name="hide_parents" id="hide_parents"></div>
        <table class="table table-striped table-bordered" id="products">
          <thead>
            <tr>
              <td></td> <!-- active -->
              <td style="border-right: 0;">
                <input type="text" class="form-control search_init" placeholder="<?php echo $lang->article_table_search; ?>" />
              </td> <!-- title -->
              <td style="border-left: 0;"></td> <!-- variant -->
              <td><input type="text" class="form-control search_init" style="width:80px" placeholder="<?php echo $lang->article_table_search; ?>" /></td> <!-- artnum -->
              <td><input type="text" class="form-control search_init" style="width:100px" placeholder="<?php echo $lang->article_table_search; ?>" /></td> <!-- ean -->
              <td><input type="text" class="form-control search_init" placeholder="<?php echo $lang->article_table_search; ?>" style="width:80px" /></td> <!-- manufacturer -->
              <td><input type="text" class="form-control search_init" placeholder="<?php echo $lang->article_table_search; ?>" style="width:110px" /></td> <!-- manufacturer artnum -->
              <td><input type="text" class="form-control search_init" style="width:110px" placeholder="<?php echo $lang->article_table_search; ?>" /></td> <!-- manufacturer ean -->
              <td><input type="text" class="form-control search_init" style="width:100px" placeholder="<?php echo $lang->article_table_search; ?>" /></td> <!-- vendor -->
              <td><input type="text" class="form-control search_init" style="width:100px; display:none;" placeholder="smaller than <?php echo $lang->article_table_search; ?>" /></td> <!-- price -->
              <td></td> <!-- oxbprice -->
              <td></td> <!-- oxtprice -->
              <td></td> <!-- reorder level -->
              <td></td> <!-- available date -->
              <td colspan="1"> <!-- stock -->
                <?php echo $lang->article_table_filter_stock; ?>
                <input type="hidden" class="search_init" />
                <input type="hidden" class="search_init" />
                <input type="text" class="form-control search_init" name="search_stock" style="width:80px" placeholder="<?php echo $lang->article_table_search; ?>" />
              </td>
              <td></td> <!-- stockflag -->
              <td></td> <!-- varstock -->
              <td></td> <!-- oxtimestamp -->
              <td></td> <!-- oxinsert -->
            </tr>
            <tr>
              <th style="width: 4%;"><?php echo $lang->article_table_heading_active; ?></th>
              <th style="width:20%"><?php echo $lang->article_table_heading_title; ?></th>
              <th style="width:9%"><?php echo $lang->article_table_heading_variant; ?></th>
              <th><?php echo $lang->article_table_heading_artnum; ?></th>
              <th><?php echo $lang->article_table_heading_ean; ?></th>
              <th style="width:8%"><?php echo $lang->article_table_heading_manufacturer; ?></th>
              <th><?php echo $lang->article_table_heading_manufacturer_artnum; ?></th>
              <th><?php echo $lang->article_table_heading_dist_ean; ?></th>
              <th style="width:8%"><?php echo $lang->article_table_heading_vendor; ?></th>
              <th style="width:7%"><?php echo $lang->article_table_heading_price; ?></th>
              <th style="width:7%"><?php echo $lang->article_table_heading_bprice; ?></th>
              <th style="width:7%"><?php echo $lang->article_table_heading_tprice; ?></th>
              <th style="width:7%"><?php echo $lang->article_table_heading_stock_remindamount; ?></th>
              <th style="width:7%"><?php echo $lang->article_table_heading_stock_delivery; ?></th>
              <th style="width:7%"><?php echo $lang->article_table_heading_stock; ?></th>
              <th style="width:9%"><?php echo $lang->article_table_heading_stockflag; ?></th>
              <th style="width:7%"><?php echo $lang->article_table_heading_varstock; ?></th>
              <th style="width:8%"><?php echo $lang->article_table_heading_oxtimestamp; ?></th>
              <th style="width:8%"><?php echo $lang->article_table_heading_oxinsert; ?></th>
            </tr>
          </thead>
          <tbody>

          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php if(file_exists("inc/tracker.inc.php")) { require("inc/tracker.inc.php"); } ?>
</body>
</html>
