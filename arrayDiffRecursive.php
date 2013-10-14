#!/usr/bin/php
<?PHP

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);


$json1 = file_get_contents("array.localonly.1");
$json2 = file_get_contents("array.localonly.2");

$arr1 = json_decode($json1, true);
$arr2 = json_decode($json2, true);

echo "\n\nin array.localonly.1 but not else\n";
var_export(arrayRecursiveDiff($arr1, $arr2));

echo "\n\nin array.localonly.2 but not else\n";
var_export(arrayRecursiveDiff($arr2, $arr1));




function arrayRecursiveDiff($aArray1, $aArray2) {
  $aReturn = array();

  foreach ($aArray1 as $mKey => $mValue) {
    if (array_key_exists($mKey, $aArray2)) {
      if (is_array($mValue)) {
        $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
        if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
      } else {
        if ($mValue != $aArray2[$mKey]) {
          $aReturn[$mKey] = $mValue;
        }
      }
    } else {
      $aReturn[$mKey] = $mValue;
    }
  }

  return $aReturn;
}
?>




?>
