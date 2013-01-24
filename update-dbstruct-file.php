#!/usr/bin/php
<?PHP


require_once("global.inc.php");
require_once("config.localonly.inc.php");
$params = getParams();

// application init
chdir(__DIR__ . "/$webDir");
$skipLogonCheck = true;
require_once("php/init.inc.php");
set_exception_handler(null);


Config::init();
Db::openDbAliasId($dbDefAliasId);

$structChecker = new OgerDbStructMysql(Db::$conn, Config::$dbDefs[$dbDefAliasId]["dbName"]);
$dbStruct = $structChecker->getDbStruct();

$structChecker->setParams(array("dry-run" => true,
                                "log-level" => $structChecker::LOG_NOTICE,
                                "echo-log" => true));

$strucTpl = array();
if (file_exists($dbStructFileName)) {
  $strucTpl = include($dbStructFileName);
}


echo "\n***************************************************\n";
echo "* Database: " . Config::$dbDefs[$dbDefAliasId]["dbName"] . "\n";
echo "* Struct file: dbStructFileName\n";
echo "***************************************************\n";

if ($params["reverse"]) {
  echo "*** Following must be changed in the database to be in sync with the struct file:\n";
}
else {
  echo "*** Following must be changed in the struct file to be in sync with the database:\n";
  // ATTENTION: the normal mode for this script is the reverse mode for the struct checker !!!
  $structChecker->startReverseMode($strucTpl);
}

$structChecker->forceDbStruct($strucTpl);

if ($structChecker->updateCounter) {
  echo "\n";
  if ($params["apply"]) {
    echo "Write structure file.\n";
    file_put_contents($dbStructFileName, "<?PHP\n return\n" . $structChecker->formatDbStruct($dbStruct) . "\n;\n?>\n");
    $cmd = "mysqldump -u " . Config::$dbDefs[$dbDefAliasId]["user"] .
           " " . Config::$dbDefs[$dbDefAliasId]["pass"] .
           " " . Config::$dbDefs[$dbDefAliasId]["dbName"] .
           " --no-data > $dbStructDumpName";
    echo "Dump database structure ($cmd).\n";
    passthru($cmd);
  }
  else {
    echo "Dry-run. Nothing changed.\n";
  }
}
else {
  echo "Nothing to do. Already up to date.\n";
}


echo "\n";
