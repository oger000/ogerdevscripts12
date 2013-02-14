<?PHP

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);



$skipLogonCheck = true;
//require_once("php/init.inc.php");
session_start();




if ($_REQUEST['clear']) {
  session_destroy();
}


// move to var to do not manipulate the original
$sess = $_SESSION;

if (!$_REQUEST['errorLog']) {
  unset($sess['errorLog']);
}

var_export($sess);



?>
