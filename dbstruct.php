#!/usr/bin/php
<?PHP

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);


require_once("global.inc.php");
require_once("config.localonly.inc.php");
$params = getParams();


// prechecks
$dbStructDirName = dirname($dbStructFileName);
if (!file_exists($dbStructDirName)) {
  mkdir($dbStructDirName);
}
if (!file_exists($dbStructFileName)) {
  file_put_contents($dbStructFileName, "<?PHP\nreturn array(true);\n?>\n");
}


// application init
echo "Chdir to $webDir\n";
chdir($webDir);
echo "\nCWD=" . getcwd() . "\n\n";
$skipLogonCheck = true;
$skipSessionStart = true;
require_once("php/init.inc.php");
set_exception_handler(null);

if ($params["dbDefAliasId"]) {
  $dbDefAliasId = $params["dbDefAliasId"];
};


Config::init();
if (class_exists("Dbw")) {
  if (Config::$dbDefs[$dbDefAliasId]['dsnConnect']) {
    Dbw::openDbAliasId($dbDefAliasId);
  }
  else {   // fallback to pre12 config
    Dbw::openDbAliasId($dbDefAliasId, array("compat" => true));
  }

  $conn = Dbw::$conn;
  $dbName = Dbw::$dbDef['dbName'];
}
else {  // fallback to pre12
  throw new Exeption("Need Dbw class to process.");
  $dbDef = Config::$dbDefs[$dbDefAliasId];
  Db::init($dbDef['dbName'], $dbDef['dbUser'], $dbDef['dbPass'], $dbDef['dbAttributes']);
  $conn = Db::getConn();

  list($dbDriver, $tmp) = explode(':', $dbDef['dbName'], 2);
  preg_match("/.*dbname=(.*?);/", $tmp, $matches);
  $dbName = $matches[1];
}


$structer = new OgerDbStructMysql($conn, $dbName);
$dbStruct = $structer->getDbStruct();

$structer->setParams(array("dry-run" => true,
                            "log-level" => $structer::LOG_NOTICE,
                            "echo-log" => true));

$strucTpl = array();
if (file_exists($dbStructFileName)) {
  $strucTpl = include($dbStructFileName);
  if (!$strucTpl) {
    echo "*** No structure found in file $dbStructFileName.\n";
    exit;
  }
}
else {
  echo "*** Cannot find structure file $dbStructFileName.\n";
  exit;
}


echo "\n***************************************************\n";
echo "* Database: " . $dbName . "\n";
echo "* Struct file: $dbStructFileName\n";
echo "***************************************************\n";

if ($params['reverse']) {
  echo "*** Following must be changed in the database to be in sync with the struct file:\n";
}
else {
  echo "*** Following must be changed in the struct file to be in sync with the database:\n";
  // ATTENTION: the normal mode for this script is the reverse mode for the struct checker !!!
  $structer->startReverseMode($strucTpl);
}

$structer->forceDbStruct($strucTpl);

if ($structer->changeCount) {
  echo "\n";
  if ($params['apply']) {
    if ($params['reverse']) {  // structfile -> db
      if (class_exists("Dbw")) {  // update with log
        Dbw::$struct = $strucTpl;
        Dbw::checkStruct();
      }
      else {  // plain update
        $structer2 = new OgerDbStructMysql($conn, $dbName);
        $structer->setParams(array("dry-run" => false,
                                    "log-level" => $structer::LOG_NOTICE,
                                    "echo-log" => true));
        $structer2->setParams(array());
        $structer2->updateDbStruct($strucTpl);
        $structer2->reorderDbStruct();
      }
    }
    else {  // db -> structfile
      // Dbw compat-open should have converted the config from pre12 format
      $dbDef = Dbw::$dbDef;

      echo "Write structure file.\n";
      file_put_contents($dbStructFileName, "<?PHP\n return\n" . $structer->formatDbStruct($dbStruct) . "\n;\n?>\n");

      $cmd = "mysqldump -u {$dbDef['user']} \$pass {$dbDef['dbName']} --no-data " .
             " | sed -e 's/ AUTO_INCREMENT=[0-9]*\b//' " .
             " > $dbStructDumpName";
      echo "Dump database structure ($cmd).\n";
      $pass = Config::$dbDefs[$dbDefAliasId]['pass'];
      if (!$pass) {
        echo "INFO: No password found in db config file.\n";
      }
      $pass = ($pass ? "-p$pass" : "");
      $cmd = str_replace("\$pass", $pass, $cmd);
      passthru($cmd);
    }
  }
  else {
    echo "Dry-run. Nothing changed.\n";
  }
}
else {
  echo "Nothing to do. Already up to date.\n";
}


if ($params['apply'] && !$params['no-models'] && !$params['reverse']) {

  echo "\n\n***************************************************\n";
  echo "*** Create/update models in $appJsRoot/model:\n";
  $tplFile = "$appJsRoot/model/Model.tpl";

  foreach ($dbStruct['TABLES'] as $table) {

    $tableName = $table['TABLE_META']['TABLE_NAME'];
    $modelName = "$appJsName.model." . ucfirst($tableName);
    $modelFile = "$appJsRoot/model/" . ucfirst($tableName) . ".js";

    if (file_exists($modelFile)) {
      $content = file_get_contents($modelFile);
      $contentBak = $content;
    }
    else {
      if (!file_exists($tplFile)) {
        echo "* $modelName - Skip create model (missing template).\n";
        continue;
      }
      $content = file_get_contents($tplFile);
    }

    $excludeCols = array();
    $search = "^\s*//\s*autogen-exclude:\s*(.*)$";
    if (preg_match("|$search|m", $content, $matches)) {
      $tmp = explode(",", $matches[1]);
      foreach ($tmp as &$column) {
        $column = trim($column);
        $excludeCols[$column] = $column;
      }
    }

    $fieldList = "";
    foreach ($table['COLUMNS'] as $column) {
      $colName = $column['COLUMN_NAME'];
      if ($excludeCols[$colName]) {
        continue;
      }
      // add type to fire the appropriate reader
      if ($column['DATA_TYPE'] == "date") {
        $fieldList .= "{ name: '{$colName}', type: 'date' },\n    ";
      }
      /*
       * add only date type - otherwise numbertype (e.g. for combo field keys)
       * conflicts with string-types (all numbers from db are string type!)
       * because combobox entries are searched by === comparision
      elseif ($column['DATA_TYPE'] == "decimal") {
        $fieldList .= "{ name: '{$colName}', type: 'float' },\n    ";
      }
      elseif ($column['DATA_TYPE'] == "int" ||
              $column['DATA_TYPE'] == "tinyint") {
        $fieldList .= "{ name: '{$colName}', type: 'int' },\n    ";
      }
      */
      else {
        $fieldList .= "'{$colName}',\n    ";
      }
    }
    $fieldList = trim($fieldList);

    $search = "(//\s*autogen-begin>[ \t]*)(.*?)(//\s*<autogen-end)";
    if (preg_match("|$search|s", $content, $matches)) {
      $matches[1] = trim($matches[1]);
      $search = preg_quote($matches[0], "|");
      $replace = "{$matches[1]}\n    {$fieldList}\n  {$matches[3]}";
      $content = preg_replace("|$search|s", $replace, $content);
    }
    else {
      echo "* $modelName - No field marker found.\n";
      //$content .= "\n$replace";
    }

    $content = preg_replace("/###MODEL_NAME###/", $modelName, $content);

    if ($content != $contentBak) {
      echo "* $modelName - Write file.\n";
      file_put_contents($modelFile, $content);
    }
    else {
      //echo "*** {$modelFile} unchanged.\n";
    }
  }
  echo "*** Update models finished.\n";
}  // eo model files

echo "\n";


// sync pre12 dbstruct
if (!$params['no-pre12']) {

  $oldDir = getcwd();
  $oldDevScriptsDir = __DIR__ . "/../devscripts";
  $oldDevStructUpd = "update-dbinfo.sh";

  if (file_exists("$oldDevScriptsDir/$oldDevStructUpd")) {

    echo "\n\n";
    echo "***************************************************\n";
    echo "* Sync pre12 database struct\n";
    echo "***************************************************\n";

    echo "Chdir $oldDevScriptsDir\n";
    chdir($oldDevScriptsDir);
    $cmd = "$oldDevScriptsDir/$oldDevStructUpd -- --suppress-missing-class --da default";
    if ($params['apply-pre12'] && !$params['reverse']) {
      $cmd .= " apply";
    }
    passthru($cmd);
    chdir($oldDir);
  }
  else {
    // be silent - echo "No pre12 dbstruct updater found ($oldDevScriptsDir/$oldDevStructUpd).\n";
  }

}  // eo pre12

?>
