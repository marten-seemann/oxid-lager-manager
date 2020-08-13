<?php
// this script reads the "index.php" as it is in the development environment, thus it has many single js and css files. then it does various replacements to include the merged and compressed js and css versions created by the post_process.sh script
// outputs a file "index.new.php". the old "index.php" can and should be replaced by this new file

// if a line contains one of the following strings it will be completely ignored
// useful for files that are merged with other files
$ignore = array(
  "assets/js/jquery.",
  "assets/css/jquery.",
  "assets/js/src/",
  "font-awesome.css",
  "demo_table.css",
  "noty_config.js"
  );
// if a line contains an element of $search, it will be replaced by the corresponding element of $replace
// useful for files that where compressed
$search = array(
  "js/sorter.js",
  "css/styles.css",
  "assets/datatables/media/js/jquery.dataTables.js",
  "assets/datatables/media/js/DT_bootstrap.js",
  "assets/bootstrap/css/bootstrap.css"
  );
$replace = array(
  "js/sorter.min.js",
  "css/styles.min.css",
  "assets/datatables/media/js/jquery.dataTables.min.js",
  "assets/datatables/media/js/DT_bootstrap.min.js",
  "assets/bootstrap/css/bootstrap.min.css"
  );
$output = "index.new.php";

$handle = @fopen("index.php", "r");
if ($handle) {
  while (($buffer = fgets($handle, 4096)) !== false) {
      // handle the ignores
      $will_continue = false;
      foreach($ignore as $string) {
        if(strpos($buffer, $string)!== false) {
          $will_continue = true;
          break;
        }
      }
      if($will_continue) continue;
      // end handling of the ignores

      // add the merge products at the end of the header
      if(strpos($buffer, "</head>")!== false) {
        addJS($output,"assets/js/jquery_plugins.min.js");
        addJS($output,"assets/js/main.min.js");
        addCSS($output,"assets/css/jquery_plugins_and_other.min.css");
      }
      $buffer = str_replace($search, $replace, $buffer);
    file_put_contents($output, $buffer, FILE_APPEND);
  }
  if (!feof($handle)) {
    echo "Error: unexpected fgets() fail\n";
  }
  fclose($handle);
}
else throw new Exception("Could not open index.php");


function addJS($output,$filename) {
  file_put_contents($output, '<script src="'.$filename.'"></script>', FILE_APPEND);
  file_put_contents($output, "\n", FILE_APPEND);
}

function addCSS($output,$filename) {
  file_put_contents($output, '<link rel="stylesheet" href="'.$filename.'">', FILE_APPEND);
  file_put_contents($output, "\n", FILE_APPEND);
}

?>
