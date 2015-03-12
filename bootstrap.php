#!/usr/bin/php
<?PHP

/**
 * Search for definition time dependencies and create
 * dependency-sorted list to load js files
 */

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);


require_once("global.inc.php");
require_once("config.localonly.inc.php");
$params = getParams();


if ($params['create']) {
	$filesThis = getBstrpFileNames();
	file_put_contents("bootstrap.localonly.files", implode("\n", $filesThis));
	exit;
}


if ($params['diff']) {
	if (!$params['other']) {
		echo "*** Diff action needs parameter: 'other='.\n";
		exit;
	}
	$filesThis = getBstrpFileNames();
	$filesOther = getBstrpFileNames($params['other']);
	bstrpDiff();
	exit;
}

echo "*** Need an action parameter: create or diff.\n";
exit;




/*
 * Get bootstrap file names
 */
function getBstrpFileNames($rootDir = "") {

	global $projectRoot, $bstrpConfName, $bstrpConfThis;

	if ($rootDir) {
		$bstrpCf = "{$rootDir}/{$bstrpConfName}";
	}
	else {
		$rootDir = preg_replace("|/[^/]*/\.\.|", "", $projectRoot);
		//$rootDir = $projectRoot;
		$bstrpCf = $bstrpConfThis;
	}


	if (!file_exists($bstrpCf)) {
		echo "*** Cannot find bootstrap conf file '{$bstrpCf}'.\n";
		exit;
	}

	$files = array();
	$linesBuffer = file($bstrpCf);

	while ($linesBuffer) {

		$lines = $linesBuffer;
		// linesbuffer is refilled by recursion handling
		$linesBuffer = array();

		foreach ($lines as $line) {

			$glob = false;
			$recurseStar = false;
			$recurseGlob = false;

			list($ctrl, $fileName) = preg_split("/\s+/", trim($line), 2);

			$plain = (strpos($ctrl, "=") !== false);
			$glob = (strpos($ctrl, "~") !== false);
			$recurseGlob = (strpos($ctrl, "+") !== false);
			$recurseStar = (strpos($ctrl, "*") !== false);

			$fileName = "{$rootDir}/{$fileName}";

			if ($plain) {
				$files[$fileName] = $fileName;
				continue;
			}

			if ($glob) {
				$subDirs = array();
				$tmpFiles = glob($fileName);
				foreach ($tmpFiles as $tmpFile) {
					if (strpos($tmpFile, "localonly") !== false) {
						continue;
					}
					if (is_dir($tmpFile)) {
						$subDirs[$tmpFile] = $tmpFile;
						continue;
					}
					$files[$tmpFile] = $tmpFile;
					continue;
				}
			}

			// CONTINUE HERE

		}  // eo conf line loop
	}


	return $files;
}  // eo get files

/*

$baseDir = "/home/gerhard/src";
$dir1 = "{$baseDir}/ogeramstools/repo";
$dir2 = "{$baseDir}/ogerfibs/repo";

$inFile = "./bootstrap.package.localonly";
$outFile = "{$inFile}.diff";

$excludeRegexes = array(
  "/jslist.*\.php/",
  "|/web/config/|",
  "|/web/dbstruct/|",
);


$cmd = "diff -u ";


// allow overwrite of settings
$confFile = "bootstrap.package.localonly.conf";
if ($confFile) {
  include($confFile);
}


// create diff
if ($outFile) {
  unlink ($outFile);
}
$fileNames = explode("\n", file_get_contents($inFile));
foreach ($fileNames as $fileName) {

  $fileName = trim($fileName);
  if (!$fileName) {
    continue;
  }

  $file1Name = "{$dir1}/{$fileName}";
  $file2Name = "{$dir2}/{$fileName}";

  foreach ($excludeRegexes as $regex) {
    if (preg_match($regex, $file1Name)) {
      echo "Exclude {$fileName}\n";
      continue 2;
    }
  }

  passthru("{$cmd} {$file1Name} {$file2Name} >> {$outFile}");

}

*/


