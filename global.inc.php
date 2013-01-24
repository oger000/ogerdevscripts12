<?PHP


function getParams() {
  global $argv;
  while (count($argv) > 0 && $argv[0] !== "--") {
    array_shift($argv);
  }
  array_shift($argv);

  $params = array();
  while (count($argv) > 0) {
    $param = array_shift($argv);
    $key = $param;
    $value = null;
    if (strpos($param, "=")) {
      list ($key, $value) = explode("=", $param, 2);
    }
    if (!$value) {
      $value = true;
    }
    else {
      if (strpos($param, ",")) {
        $value = explode(",", $value);
      }
    }
    $params[$key] = $value;
  }

  return $params;
}  //eo get params



?>
