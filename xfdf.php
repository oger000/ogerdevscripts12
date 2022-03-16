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
      $allDefs[$def['fdfField']] = $def;
    }
    $def = array('srcField' => "");
  }

  list ($name, $value) = explode(":", $line, 2);
  $value = trim($value);

  switch($name) {
  case "FieldName":
    $def['fdfField'] = $value;
    break;
  case "FieldType":
    $def['fdfType'] = $value;
    if ($value == "Button") {
      $def['fdfStates'] = array();
    }
    break;
  case "FieldStateOption":
    $def['fdfStates'][] = $value;
    break;
  case "FieldFlags":
    if ($value != 0) {
      $def['fdfFlags'] = $value;
    }
    break;
  }

} // eo def line loop
if ($def) {
  $allDefs[$def['fdfField']] = $def;
}

ksort($allDefs);

$outText = "<?PHP\nreturn\narray(\n";
foreach($allDefs as $key => $def) {
  $outText .= "  '" . $key . "' => " . str_replace("\n", "", preg_replace("/^\s+/m", "", var_export($def, true))) . ",\n";
}
$outText .= ");\n";

$outFileName = $fieldsFileName . ".inc.php";
echo "write $outFileName\n";
file_put_contents($outFileName, $outText);
