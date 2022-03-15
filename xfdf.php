#!/usr/bin/php
<?PHP

/**
 * Search for definition time dependencies and create
 * dependency-sorted list to load js files
 */

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);

$scriptName = array_shift($argv);
$fieldsFileName = array_shift($argv);

if (!file_exists($fieldsFileName)) {
  echo "*** Cannot find fdf definition file '{$fieldsFileName}'.\n";
  exit;
}


$def = array();
$allDefs = array();
$lines = file($fieldsFileName);
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

$extPos = strrpos($fieldsFileName, ".");
if ($extPos === false) {
  $extPos  = strlen($fieldsFileName);
}

ksort($allDefs);

$outText = var_export($allDefs, true);
// file_put_contents("x", $outText);exit;

$outText = preg_replace("/^\s+/m", "", $outText);
$outText = str_replace("\n", "", $outText) . "\n";
$outText = str_replace("),", "),\n  ", $outText);
$arr = explode("(", $outText, 2);
$outText = implode("(\n  ", $arr);

$outFileName = $fieldsFileName . ".inc.php";
echo "write $outFileName\n";
file_put_contents($outFileName, $outText);
