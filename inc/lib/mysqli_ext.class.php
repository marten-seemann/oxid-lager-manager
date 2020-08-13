<?php
/**
* extend the default PHP MySQLi class
*
* adds additional functionality
*
* - error messages on failed database connections
* - error messages on failed database queries
* - validation (SQL escaping)
* - stores the name of the database it is connected to
*
* @author Marten Seemann <martenseemann@gmail.com>
* @package OXID Category Master
*/

// disable the behaviour introduced by php magic_quotes
// why magic_quotes are bad: see http://www.php.net/manual/en/security.magicquotes.whynot.php
// validation will be done by the validate() function defined in the mysqli_ext class. that is all we need
if (get_magic_quotes_gpc()) {
  function stripslashes_gpc(&$value) {
      $value = stripslashes($value);
  }
  array_walk_recursive($_GET, 'stripslashes_gpc');
  array_walk_recursive($_POST, 'stripslashes_gpc');
  array_walk_recursive($_COOKIE, 'stripslashes_gpc');
  array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}

class mysqli_ext extends mysqli {
  private $db_server;
  private $db_user;
  private $db_name;

  /**
  * Constructor
  *
  * handles the connection to the MySQL database, "normal" ones as well as socket connections
  *
  * if we need to connect via socket is determined by the $db_server parameter. Possible formats:
  *
  * - localhost:port/path/to/socket
  * - localhost:/path/to/socket
  * - /path/to/socket
  *
  * outputs a human readable error if database connection fails
  * @param string $db_server MySQL server name, OR MySQL socket
  * @param string $db_user MySQL user
  * @param string $db_password MySQL password
  * @param string $db_name MySQL database name
  */
  public function __construct($db_server,$db_user,$db_password,$db_name) {
    $this->db_server=$db_server;
    $this->db_user=$db_user;
    $this->db_name=$db_name;
    // detect from the given MySQL server if we have to handle a "normal" or a socket connection
    if(strpos($db_server, "sock") === false) {
      $parts = explode(":", $db_server);
      $server = $parts[0];
      if(count($parts) > 1) {
        $port = $parts[1];
        parent::__construct($server, $db_user, $db_password, $db_name, $port); // "normal connection"
      }
      else parent::__construct($server, $db_user, $db_password, $db_name); // "normal connection"
    }
    else { // socket connection
      if(preg_match("/(.*?)\:([0-9]*)(.*)/", $db_server, $matches)) {       // handle localhost:port/path/to/socket and localhost:/path/to/socket
        $server = $matches[1];
        $port = $matches[2];
        $socket = $matches[3];
      }
      else { // handle /path/to/socket
        $server = "localhost";
        $socket = $db_server;
      }

      if(!isset($port) OR strlen($port) == 0) $port = 3306; // set default port, if not given
      parent::__construct($server, $db_user, $db_password, $db_name, $port, $socket);
    }
    if(mysqli_connect_errno()) die('Connect Error (' . mysqli_connect_errno() . '): <strong>' . mysqli_connect_error()).'</strong>';
  }

  /**
  * execute MySQL query
  *
  * outputs the MySQL error number and error message if the query fails
  * @param string $query the query to be executed
  * @return mixed the MySQL result
  */
  public function query($query) {
    $result=parent::query($query);
    if($this->errno) {
      throw new Exception("MySQL-Fehler: ".$this->errno.": ".$this->error.", Query: $query");
    }
    return $result;
  }


  /**
  * get the name of the selected database
  * @return string name of selected database
  */
  public function selected_db() {
    $result=$this->query("SELECT DATABASE() AS name");
    $data=$result->fetch_object();
    return $data->name;
  }

  /**
  * SQL escape string
  *
  * to prevent SQL injections, see
  * @link http://php.net/manual/de/function.mysqli-escape-string.php
  * @param string $string the string to be escaped
  * @return string the SQL escaped string
  */
  public function validate($string) {
    return parent::escape_string($string);
  }

  //Magic functions
  public function __get($prop) {
    if(!isset($this->$prop)) return false;
    else return $this->$prop;
  }

}
?>
