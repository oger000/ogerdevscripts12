#!/usr/bin/php
<?PHP

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);


require_once("global.inc.php");
require_once("config.localonly.inc.php");
$params = getParams();

// application init
echo "Chdir to $webDir\n";
chdir($webDir);
echo "\nCWD=" . getcwd() . "\n\n";
$skipLogonCheck = true;
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
  }
}
else {
  echo "*** Cannot find structure file $dbStructFileName.\n";
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
      $structer2 = new OgerDbStructMysql($conn, $dbName);
      $structer->setParams(array("dry-run" => false,
                                  "log-level" => $structer::LOG_NOTICE,
                                  "echo-log" => true));
      $structer2->setParams(array());
      $structer2->updateDbStruct($strucTpl);
      $structer2->reorderDbStruct();
    }
    else {  // db -> structfile
      echo "Write structure file.\n";
      file_put_contents($dbStructFileName, "<?PHP\n return\n" . $structer->formatDbStruct($dbStruct) . "\n;\n?>\n");
      $pass = Config::$dbDefs[$dbDefAliasId]['pass'];
      $pass = ($pass ? "-p$pass" : "");
      $cmd = "mysqldump -u " . Config::$dbDefs[$dbDefAliasId]['user'] .
             " $pass" .
             " " . $dbName .
             " --no-data > $dbStructDumpName";
      echo "Dump database structure ($cmd).\n";
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


if ($params['apply'] && !$params['reverse']) {
  echo "\n\n***************************************************\n";
  echo "*** Update models:\n";
  $tplFile = "$appJsRoot/model/template";
  foreach ($dbStruct['TABLES'] as $table) {
    $tableName = $table['TABLE_META']['TABLE_NAME'];
    $modelName = "$appJsName.model." . ucfirst($tableName);
    $modelFile = "$appJsRoot/model/" . ucfirst($tableName) . ".js";
    echo "* $modelName\n";
    if (file_exists($modelFile)) {
      $content = file_get_contents($modelFile);
    }
    else {
      if (!file_exists($tplFile)) {
        echo "  Skip create model (missing template).\n";
        continue;
      }
      $content = file_get_contents($tplFile);
    }
    $fieldList = "";
    foreach ($table['COLUMNS'] as $column) {
      $fieldList .= ($fieldList ? ", " : "") . "'" . $column['COLUMN_NAME'] . "'";
    }
    $search = "^\s*//\s*fields: *";
    if (preg_match("|$search|m", $content, $matches)) {
      $content = preg_replace("/###MODEL_NAME###/", $modelName, $content);
      $replace = $matches[0] . (substr($matches[0], -1) == " " ? "" : " ") . "[ $fieldList ],";
      $content = preg_replace("|$search.*$|m", $replace, $content);
    }
    else {
      $content .= "\n$replace";
    }
    echo "  Write $modelFile\n";
    file_put_contents($modelFile, $content);
  }
}  // eo model files

echo "\n";



?>
