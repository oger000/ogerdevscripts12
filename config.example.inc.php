<?PHP

// This file has to be included into a config.localonly.inc.php file.
// Most devscripts will not work without a valid config.localonly.inc.php file
// within this directory.


// global settings (needed for for creating dist files)
$projectName = "dummy";  // override in config.localonly.inc.php

// all directories are relative to script location

$devScriptsRoot = __DIR__;
$projectRoot = __DIR__ . "/..";
$webDir = "{$projectRoot}/web";


// for dbstruct
$dbDefAliasId = "default";
$dbStructFileName = "{$webDir}/dbstruct/dbStruct.inc.php";
$dbStructDumpName = "{$webDir}/dbstruct/dbStruct.sql";

// for appjs-list
$appJsRel = "js/app";
$appJsRoot = "{$webDir}/{$appJsRel}";
$appJsName = "App";
$appJsMain = "app.js";
$appJsList = "{$webDir}/jslist-app.inc.php";
$appJsBuildsDir = "{$webDir}/js/builds";


// for prepare dist
$distRoot = "$projectRoot";
$distDir = "{$projectRoot}/../dist";

// maybe split into distExcludeFile and distExcludeDir ???
$distExclude = array(
	"/.*localonly.*/i",
	"|devdocu\$|",
	"|/autobackup/.*sqldump|",
	"|devdata\$|",
	"|devscripts\$|",
	"|devscripts12\$|",
	"|/lib/extjs4/examples|",
	"|/lib/extjs4/docs|",
	"|/lib/extjs4/jsbuilder|",
	"|/lib/ogerlibphp/obsolete|",
	"|/lib/ogerlibphp12/docu|",
);


// for update-shown-version (relative to projectroot)
$versionFile = "{$webDir}/config/version.inc.php";
$distVersionFile = "{$projectRoot}/DISTVERSION";


// for licence
$licenseFile = "{$projectRoot}/LICENSE";

$licenseExclude = array(
	"|/lib/extjs|",
	"|/lib/extjs4|",
	"|/lib/extjs5|",
	"|/lib/tcpdf|",
);


// for bootstrap
$bstrpConfName = "bootstrap.conf";
$bstrpConfThis = "{$projectRoot}/{$bstrpConfName}";

?>
