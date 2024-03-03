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
ob_start();
echo "Chdir to $webDir\n";
chdir($webDir);
echo "\nCWD=" . getcwd() . "\n\n";
$skipLogonCheck = true;
$skipSessionStart = true;
require_once("php/init.inc.php");
ob_end_flush();
set_exception_handler(null);

if ($params["dbDefAliasId"]) {
	$dbDefAliasId = $params["dbDefAliasId"];
};


// open database and read structure
Config::init();
Dbw::openDbAliasId($dbDefAliasId);
$conn = Dbw::$conn;
$dbName = Dbw::$dbDef['dbName'];

$structer = new OgerDbStructMysql($conn, $dbName);
$structer->setparam("ignoreCollate", true);
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

if ($params['struct-to-db']) {
	echo "*** Following must be changed in the database to be in sync with the struct file:\n";
}
else {
	echo "*** Following must be changed in the struct file to be in sync with the database:\n";
	$structer->startDbToTplMode($strucTpl);
}

$structer->forceDbStruct($strucTpl);

if ($structer->changeCount) {
	echo "\n";
	if ($params['apply']) {
		if ($params['struct-to-db']) {  // structfile -> db
			if (class_exists("Dbw")) {  // update with log
				Dbw::$struct = $strucTpl;
				Dbw::checkStruct(array("ignoreCollate" => true));
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


if ($params['apply'] && !$params['no-models'] && !$params['struct-to-db']) {

	echo "\n\n***************************************************\n";
	echo "*** Create/update models in {$appJsRoot}/model:\n";
	$tplFile = "$appJsRoot/model/Model.tpl";

	foreach ($dbStruct['TABLES'] as $table) {

		$tableName = $table['TABLE_META']['TABLE_NAME'];
		$tplOutFile = "$appJsRoot/model/" . ucfirst($tableName) . ".js";

		if (file_exists($tplOutFile)) {
			$content = file_get_contents($tplOutFile);
			$contentBak = $content;
		}
		else {
			if (!file_exists($tplFile)) {
				echo "* {$tableName} - Skip create model (missing template).\n";
				continue;
			}
			$content = file_get_contents($tplFile);
			$contentBak = "";
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
				$fieldList .= "{ name: '{$colName}', type: 'date' },\n\t\t";
			}
			/*
			 * add only date type - otherwise numbertype (e.g. for combo field keys)
			 * conflicts with string-types (all numbers from db are string type!)
			 * because combobox entries are searched by === comparision
			elseif ($column['DATA_TYPE'] == "decimal") {
				$fieldList .= "{ name: '{$colName}', type: 'float' },\n\t\t";
			}
			elseif ($column['DATA_TYPE'] == "int" ||
							$column['DATA_TYPE'] == "tinyint") {
				$fieldList .= "{ name: '{$colName}', type: 'int' },\n\t\t";
			}
			*/
			else {
				$fieldList .= "'{$colName}',\n\t\t";
			}
		}
		$fieldList = trim($fieldList);

		$search = "(//\s*autogen-begin>[ \t]*)(.*?)(//\s*<autogen-end)";
		if (preg_match("|$search|s", $content, $matches)) {
			$matches[1] = trim($matches[1]);
			$search = preg_quote($matches[0], "|");
			$replace = "{$matches[1]}\n\t\t{$fieldList}\n\t{$matches[3]}";
			$content = preg_replace("|$search|s", $replace, $content);
		}
		else {
			echo "* {$tableName} - No field marker found.\n";
			//$content .= "\n$replace";
		}

		$content = preg_replace("/###TABLE_NAME###/", $tableName, $content);
		$content = preg_replace("/###TABLE_NAME_UC1###/", ucfirst($tableName), $content);

		if ($content != $contentBak) {
			echo "* {$tableName} - Write model file.\n";
			file_put_contents($tplOutFile, $content);
		}
		else {
			//echo "*** {$tplOutFile} unchanged.\n";
		}
	}
	echo "*** Update models finished.\n";
}  // eo model files

echo "\n";


if ($params['apply'] && !$params['no-stores'] && !$params['struct-to-db']) {

	echo "\n\n***************************************************\n";
	echo "*** Create/update stores in {$appJsRoot}/store:\n";
	$tplFile = "$appJsRoot/store/Store.tpl";

	foreach ($dbStruct['TABLES'] as $table) {

		$tableName = $table['TABLE_META']['TABLE_NAME'];
		$tplOutFile = "$appJsRoot/store/" . ucfirst($tableName) . ".js";

		if (file_exists($tplOutFile)) {
			$content = file_get_contents($tplOutFile);
			$contentBak = $content;
		}
		else {
			if (!file_exists($tplFile)) {
				echo "* {$tableName} - Skip create store (missing template).\n";
				continue;
			}
			$content = file_get_contents($tplFile);
			$contentBak = "";
		}

		$content = preg_replace("/###TABLE_NAME###/", $tableName, $content);
		$content = preg_replace("/###TABLE_NAME_UC1###/", ucfirst($tableName), $content);

		if ($content != $contentBak) {
			echo "* {$tableName} - Write store file.\n";
			file_put_contents($tplOutFile, $content);
		}
		else {
			//echo "*** {$tplOutFile} unchanged.\n";
		}
	}
	echo "*** Update stores finished.\n";
}  // eo store files

echo "\n";
