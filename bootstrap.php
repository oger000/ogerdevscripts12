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
	doBstrpList();
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
	doBstrpDiff();
	exit;
}

echo "\n\n";
echo "*** Need an action parameter: list, copy or diff.\n";
echo " list: Lists files used for bootstrap package.\n";
echo " copy: Copies missing bootstrap files from 'other' project root.\n";
echo " diff: Creates a unified diff for bootstrap files compared with 'other' project root.\n";
echo "       --no-exclude: Do not exclude known differen files like dbstruct etc\n";
echo "\n\n";
exit;




/*
 * Normalize path (ultrasimple version)
 */
function normalizePath($path) {
	return preg_replace("|/[^/]*/\.\.|", "", $path);
}  // eo normalize path



/*
 * Get bootstrap file names
 */
function getBstrpFileNames($rootDir = "") {

	global $projectRoot, $bstrpConfName;

	if (!$rootDir) {
		$rootDir = normalizePath($projectRoot);
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
 * Create list of bootstrap files
 */
function doBstrpList() {

	global $params;

	$content = implode("\n", getBstrpFileNames()) . "\n";
	file_put_contents("bootstrap.localonly.files", $content);
	if ($params['debug']) {
		echo "\n{$content}\n";
	}
}  // eo list bootstrap files



/*
 * Create diff of bootstrap files between two projects
 */
function doBstrpDiff() {

	global $params, $projectRoot;

	$outFile = "bootstrap.localonly.diff";

	$excludeRegexes = array(
		"|^web/jslist.*\.php|",
		"|^web/config/|",
		"|^web/dbstruct/|",
		"|^web/js/app/view/MainMenu.*\.js|",
	);
	$cmd = "diff -u ";

	$params['other'] = trim($params['other']);
	if (!$params['other']) {
		echo "\n*** Action 'diff' needs parameter: 'other=<other project root>'.\n\n";
		exit;
	}
	if (!file_exists($params['other'])) {
		echo "\n*** Directory '{$params['other']}' existiert nicht.\n\n";
		exit;
	}
	if (!is_dir($params['other'])) {
		echo "\n*** File '{$params['other']}' ist kein Directory.\n\n";
		exit;
	}

	if ($params['--no-exclude']) {
		$excludeRegexes = array();
	}


	// get file names

	$filesThisTmp = getBstrpFileNames();
	$filesOtherTmp = getBstrpFileNames($params['other']);

	$filesThis = array();
	foreach ($filesThisTmp as $v) {
		$k = substr($v, strlen(normalizePath($projectRoot)) + 1 );
		$filesThis[$k] = $v;
	}
	$filesOther = array();
	foreach ($filesOtherTmp as $v) {
		$k = substr($v, strlen(normalizePath($params['other'])) + 1 );
		$filesOther[$k] = $v;
	}

//var_export($filesThis);
//var_export($filesOther);
//exit;

	// unlink outfile
	if (file_exists($outFile)) {
		unlink ($outFile);
	}

	// loop over common files
	$filesBoth = array_intersect_key($filesThis, $filesOther);
	foreach ($filesBoth as $fileKey => $fileName) {

		$fileThis = $filesThis[$fileKey];
		$fileOther = $filesOther[$fileKey];

		// remove filename from file arrays
		unset($filesThis[$fileKey]);
		unset($filesOther[$fileKey]);

		foreach ($excludeRegexes as $regex) {
			if (preg_match($regex, $fileKey)) {
				echo "Exclude {$fileKey}\n";
				continue 2;
			}
		}

		$err = "";
		if (!file_exists($fileThis)) {
			$err .= "*** Datei fehlt: {$fileThis}\n";
		}
		if (!file_exists($fileOther)) {
			$err .= "*** Datei fehlt: {$fileOther}\n";
		}
		if ($err) {
			echo $err;
			continue;
		}

//echo "DODIFF: {$cmd} {$fileThis} {$fileOther} >> {$outFile}\n";
		passthru("{$cmd} {$fileThis} {$fileOther} >> {$outFile}");
	}  // eo intersect loop


	$filesRemain = array_merge(array_values($filesThis), array_values($filesOther));
	foreach ($filesRemain as $fileName) {

		foreach ($excludeRegexes as $regex) {
			if (preg_match($regex, $fileName)) {
				echo "Exclude {$fileName}\n";
				continue 2;
			}
		}

		if (!file_exists($fileName)) {
			echo "*** Unbearbeitete Datei fehlt: {$fileName}\n";
			continue;
		}
		echo "*** Unbearbeitete Datei: {$fileName}\n";
	}  // eo remain loop

	touch($outFile);  // if empty till now

	if ($params['debug']) {
		$content = file_get_contents($outFile);
		echo "\n{$content}\n";
	}

}  // eo diff




