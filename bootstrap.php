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


if ($params['list']) {
	$content = implode("\n", getBstrpFileNames()) . "\n";
	file_put_contents("bootstrap.localonly.files", $content);
	if ($params['debug']) {
		echo "\n{$content}\n";
	}
	exit;
}


if ($params['copy']) {
	if (!$params['other']) {
		echo "\n*** Action 'copy' needs parameter: 'other=<other project root>'.\n\n";
		exit;
	}
	$filesOther = getBstrpFileNames($params['other']);
	echo "Copy not implemented yet.\n";
	exit;
}

if ($params['diff']) {
	if (!$params['other']) {
		echo "\n*** Action 'diff' needs parameter: 'other=<other project root>'.\n\n";
		exit;
	}
	$filesThis = getBstrpFileNames();
	$filesOther = getBstrpFileNames($params['other']);
	echo "Diff not implemented yet.\n";
	bstrpDiff();
	exit;
}

echo "\n\n";
echo "*** Need an action parameter: list, copy or diff.\n";
echo " list: Lists files used for bootstrap package.\n";
echo " copy: Copies missing bootstrap files from 'other' project root.\n";
echo " diff: Creates a unified diff for bootstrap files compared with 'other' project root.\n";
echo "\n\n";
exit;




/*
 * Get bootstrap file names
 */
function getBstrpFileNames($rootDir = "") {

	global $projectRoot, $bstrpConfName;

	if (!$rootDir) {
		$rootDir = preg_replace("|/[^/]*/\.\.|", "", $projectRoot);
	}


	if (!file_exists($bstrpConfName)) {
		echo "*** Cannot find bootstrap conf file '{$bstrpConfName}'.\n";
		exit;
	}

	$files = array();
	$lineStack = file($bstrpConfName);

	while ($lineStack) {

		$lines = $lineStack;
		// linesbuffer is refilled by recursion handling
		$lineStack = array();

		foreach ($lines as $line) {

			$doGlob = false;
			$doSubStar = false;
			$doSubGlob = false;

			list($ctrl, $fileName) = preg_split("/\s+/", trim($line), 2);

			$plain = (strpos($ctrl, "=") !== false);
			$doGlob = (strpos($ctrl, "~") !== false);
			$doSubGlob = (strpos($ctrl, "+") !== false);
			$doSubStar = (strpos($ctrl, "*") !== false);

			if (substr($fileName, 0, 1) != "/") {
				$fileName = "{$rootDir}/{$fileName}";
			}

			if ($plain) {
				$files[$fileName] = $fileName;
				continue;
			}

			$subDirs = array();
			$subGlob = "";
			if ($doGlob) {
				$fileParts = explode("/", $fileName);
				$subGlob = array_pop($fileParts);
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

			// push subdirectories to line stack
			if ($doSubStar || $doSubGlob) {
				foreach ($subDirs as $subDir) {
					if ($doSubGlob) {
						$subDir = "~+ {$subDir}/{$subGlob}";
					}
					else {
						$subDir = "~* {$subDir}/*";
					}
					$lineStack[] = $subDir;
				}
			}

		}  // eo conf line loop
	}


	sort($files);
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


