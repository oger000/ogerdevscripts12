#!/usr/bin/php
<?PHP

/**
 * Search for definition time dependencies and create
 * dependency-sorted list to load js files
 */

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);

$scriptName = array_shift($argv);
$defFileName = array_shift($argv);

if (!file_exists($defFileName)) {
  echo "*** Cannot find fdf definition file '{$defFile}'.\n";
  exit;
}


$def = array();
$allDefs = array();
$lines = file($defFileName);
foreach ($lines as $line) {

  if (substr($line, 0, 3) == "---") {
    if ($def) {
      $allDefs[$def['fdf']] = $def;
    }
    $def = array('src' => "?");
  }

  list ($name, $value) = explode(":", $line, 2);
  $value = trim($value);

  switch($name) {
  case "FieldName":
    $def['fdf'] = $value;
    break;
  case "FieldType":
    $def['type'] = $value;
    break;
  }

} // eo def line loop
if ($def) {
  $allDefs['fdf'] = $def;
}

$extPos = strrpos($defFileName, ".");
if ($extPos === false) {
  $extPos  = strlen($defFileName);
}

$outFileName = substr($defFileName, 0, $extPos) . ".def_arr";
file_put_contents($outFileName, var_export($allDefs, true));
