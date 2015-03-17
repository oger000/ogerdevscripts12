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


include('ext5aliases.inc.php');


// application init
chdir($appJsRoot);
echo "\nCWD=" . getcwd() . "\n\n";


#####################################

// start aliases array with ext aliases
// first char "-" marks "resolved" aliases
$aliases = array();
foreach ($extAliases as $alias) {
	$aliases[$alias] = "-extjs";
}



echo "Read files from $appJsRoot\n";
$fileRegex = '/.*\.js$/';
$classes = getAppClasses($appJsRoot, $fileRegex, $appJsName, $appJsMain);

// sorting
// TODO: sort more structure aware (models before stores, etc)
ksort($classes);

// analyse classes
echo "Analyse classes\n";
$classes = analyseAppClasses($classes, $appJsName);

echo "Sorting dependencies\n";
$classesOrder = sortDeps($classes);


$out = "";
foreach ($classesOrder as $className => $dummy) {
	$out .= $appJsRel . $classes[$className]['shortFileName'] . "\n";
}

if (count($classesOrder) < count($classes) && $params['partial'] == "add") {
	echo "\nAdd " . count($classes) - count($classesOrder) . " unresolved classes.\n";
	$out .= "\n";
	foreach ($classes as $className => $dummy) {
		if (!$classesOrder[$className]) {
			$out .= $appJsRel . $classes[$className]['shortFileName']. "\n";
		}
	}
}  // add unresolved

// finaly add app main js because skipped in get app classes
$out .= $appJsRel . DIRECTORY_SEPARATOR . $appJsMain . "\n";


// update index appjs include
echo "\n";
if ($params['apply']) {

	echo "Write appjs file list {$appJsList}.\n";
	if (file_put_contents($appJsList, $out) === false) {
		echo "ERROR on write to {$appJsList}.\n";
	}

	// create js builds
	if ($params['build']) {

		// write js builds
		if (!file_exists($appJsBuildsDir)) {
			mkdir($appJsBuildsDir);
		}

		$fileName = "app-all-concat.js";
		echo "Write {$fileName} to {$appJsBuildsDir}.\n";
		$out = buildAppJsConcat($out, $webDir);
		//$out = file_get_contents("{$appJsBuildsDir}/{$fileName}");
		$appJsConcat = $out;
		if (file_put_contents("{$appJsBuildsDir}/{$fileName}", $out) === false) {
			echo "ERROR on write to {$appJsBuildsDir}/{$fileName}.\n";
		}

		/*
		$fileName = "app-all-debug-regex.js";
		echo "Write {$fileName} to {$appJsBuildsDir}.\n";
		$out = buildAppJsDebugRegex($appJsConcat);
		if (file_put_contents("{$appJsBuildsDir}/{$fileName}", $out) === false) {
			echo "ERROR on write to {$appJsBuildsDir}/{$fileName}.\n";
		}
		*/

		//$fileName = "app-all-debug-uglifyfs.js";
		$fileName = "app-all-debug.js";
		echo "Write {$fileName} to {$appJsBuildsDir}.\n";
		$out = buildAppJsDebugUglifyJS($appJsConcat);
		if (file_put_contents("{$appJsBuildsDir}/{$fileName}", $out) === false) {
			echo "ERROR on write to {$appJsBuildsDir}/{$fileName}.\n";
		}

		/*
		$fileName = "app-all-debug-yuicompress.js";
		echo "Write {$fileName} to {$appJsBuildsDir}.\n";
		$out = buildAppJsDebugYuiCompressor($appJsConcat);
		if (file_put_contents("{$appJsBuildsDir}/{$fileName}", $out) === false) {
			echo "ERROR on write to {$appJsBuildsDir}/{$fileName}.\n";
		}
		*/

		$fileName = "app-all.js";
		echo "Write {$fileName} to {$appJsBuildsDir}.\n";
		$out = buildAppJsMinifiedUglyFS($appJsConcat);
		if (file_put_contents("{$appJsBuildsDir}/{$fileName}", $out) === false) {
			echo "ERROR on write to {$appJsBuildsDir}/{$fileName}.\n";
		}
	}
	else {
		echo "INFO: No js builds requested.\n";
	}
}
else {
	$tmpName = tempnam("/tmp", "applist");
	if (file_put_contents($tmpName, $out) === false) {
		echo "ERROR on write to $tmpName.\n";
	}
	passthru("diff -u {$appJsList} {$tmpName}");
	unlink($tmpName);
	echo "\n  - Check-only. Do not write file list.\n";
}
echo "\n";






#############################################################



/*
* Get app classes from fileregex
*/
function getAppClasses($dirName, $regex, $appName, $appMain, $startDir = null) {

	// if startdir is not given, then use current dirname
	if ($startDir === null) {
		$startDir = $dirName;
	}

	$classes = array();
	$subDirs = array();

	$dh = opendir($dirName);
	if ($dh === false) {
		echo "   *** Cannot open directory $dirName.\n";
		exit;
	}

	while (($fileName = readdir($dh)) !== false) {

		$searchFileName = "{$dirName}/{$fileName}";

		if (is_dir($searchFileName)) {
			if (substr($fileName, 0, 1) == '.') {
				// dont process thisdir, parentdir and other dotdirs
			}
			else {
				$subDirs[] = $searchFileName;
			}
			continue;
		}

		if (!preg_match($regex, $searchFileName)) {
			continue;
		}

		// exclude app main js, because this has no class name
		// and is added as final file anyway
		if (substr($searchFileName, -1 - strlen($appMain)) == DIRECTORY_SEPARATOR . $appMain) {
			continue;
		}

		// make classname out of directory  name
		// remove startdir (leading slash remains) and extension
		$shortFileName = substr($searchFileName, strlen($startDir));
		$className = $appName . str_replace(DIRECTORY_SEPARATOR, '.', substr($shortFileName, 0, -3));

		// check if file contains class name
		// TODO this is very dumb, should be refined
		$content = file_get_contents($searchFileName);
		if (!preg_match('|' . preg_quote($className) . '|', $content)) {
			echo "Warning: Cannot find class '{$className}' in {$searchFileName}.\n";
			exit;
		}

		// add content to class array
		$classes[$className] = array('fileName' => $searchFileName, 'shortFileName' => $shortFileName, 'content' => $content);
	}

	// recursively process subdirs
	foreach ($subDirs as $searchFileName) {
		$classes = array_merge($classes, getAppClasses($searchFileName, $regex, $appName, $appMain, $startDir));
	}

	return $classes;
}  // eo get app classes



/*
* Analyse app classes
*/
function analyseAppClasses($classes, $appName) {

	global $debug;
	global $aliases;
	global $params;

	// may be we should differ between explicit dependencies like
	// Ext.require method and the "requires" property
	// and implicit dependencies (all other)?

	// collect dependencies on other classes and aliases (xtypes, storeIds, ...)
	foreach ($classes as $className => $classPack) {

		$deps = array();
		$aliasDeps = array();

		$content = $classPack['content'];
		// remove comments
		$content = preg_replace('|/\*.*?\*/|s', '', $content);
		// following can produce unwanted matches e.g. if in string
		$content = preg_replace('|//.*$|m', '', $content);

		// remove functions (inside listeners and elsewhere, because those are
		// hopefully called after loading all js files)
		// see: http://stackoverflow.com/questions/2300939/find-matching-brackets-using-regular-expression
		// see: http://stackoverflow.com/questions/2348547/pcre-find-matching-brace-for-code-block
		// see: http://stackoverflow.com/questions/2300939/find-matching-brackets-using-regular-expression
		/*
		// THIS DOES NOT WORK AS EXPECTED, SO DISABLED
		// and even if works maybe a wrong solution - better use Ext.require and
		// disable looking for application class files "somewhere"
		$content = preg_replace("#function\s*\([^\)]*\)\s*\{((?>[^\{\}]+)|(?R))*\}#x", "", $content);
		*/


		// ----------------------------------------------------------------------

		// collect alias (asume one alias per class/file ?)
		if (preg_match_all("|alias\s*:\s*['\"]([\w\.]+)['\"]|", $content, $matches)) {
			foreach ($matches[1] as $alias) {
				$aliasX = $alias;
				if (array_key_exists($aliasX, $aliases)) {
					echo "Warning: Duplicate alias {$alias} in:\n";
					echo " - " . $aliases[$aliasX] . "\n";
					echo " - $className\n";
				}
				$aliases[$aliasX] = $className;
			}
		}


		// collect store ids
		if (preg_match_all("|storeId\s*:\s*['\"](\w+)['\"]|", $content, $matches)) {
			foreach ($matches[1] as $alias) {
				$aliasX = "storeId:{$alias}";
				if (array_key_exists($aliasX, $aliases)) {
					echo "Warning: Duplicate storeId {$alias} in:\n";
					echo " - " . $aliases[$aliasX] . "\n";
					echo " - $className\n";
				}
				$aliases[$aliasX] = $className;
			}
		}


		// ----------------------------------------------------------------------

		// collect class name dependencies

		// beside the recommended usage of Ext.require we look for creating of stores
		// because this is normally only used in grids and for combos
		if (preg_match_all("#Ext\.create\s*\([\s'\"]*({$appName}\.store\..*?)\)#s", $content, $matches)) {
			foreach ($matches[1] as $depMatch) {
				foreach (explodeDeps($depMatch, $appName) as $depClass) {
					$deps[$depClass] = $depClass;
					//echo "$depClass from $className\n";
				}
			}
		}


		// search for "Ext.require(...)"
		if (preg_match_all("#Ext\.require\s*\((.*?)\)#s", $content, $matches)) {
			foreach ($matches[1] as $depMatch) {
				foreach (explodeDeps($depMatch, $appName) as $depClass) {
					$deps[$depClass] = $depClass;
				}
			}
		}
		// search for "requires: [...]"
		// depends on [] delimiter, but I am not shure if those are mandatory
		if (preg_match_all("#requires\s*:\s*\[(.*?)\]#s", $content, $matches)) {
			foreach ($matches[1] as $depMatch) {
				foreach (explodeDeps($depMatch, $appName) as $depClass) {
					$deps[$depClass] = $depClass;
				}
			}
		}

		// search for "extend:"
		if (preg_match_all("#extend\s*:\s*(.*?),#s", $content, $matches)) {
			foreach ($matches[1] as $depMatch) {
				foreach (explodeDeps($depMatch, $appName) as $depClass) {
					$deps[$depClass] = $depClass;
				}
			}
		}

		// collect mixin dependencies
		// depends on [] delimiter, but I am not shure if those are mandatory
		if (preg_match_all("#mixins\s*:\s*\[(.*?)\]#s", $content, $matches)) {
			foreach ($matches[1] as $depMatch) {
				foreach (explodeDeps($depMatch, $appName) as $depClass) {
					$deps[$depClass] = $depClass;
				}
			}
		}

		// search for application classes mentioned somewhere
		// disabled: use explicit require statements (see above)
		/*
		if (preg_match_all("|($appName\..*?)['\"]|", $content, $matches)) {
			foreach ($matches[1] as $depClass) {
				// the own class is not a dependency
				if ($depClass == $className) {
					continue;
				}
				$deps[$depClass] = $depClass;
			}
		}
		*/

		// collect xtype dependencies
		/* DISABLED: not needed at laod time
		if (preg_match_all("|xtype\s*:\s*['\"](\w+)['\"]|", $content, $matches)) {
			foreach ($matches[1] as $alias) {
				$alias = "widget.{$alias}";
				$aliasDeps[$alias] = $alias;
			}
		}
		*/


		// collect storeId dependencies
		/* DISABLED: not needed at load time
		if (preg_match_all("|store\s*:\s*['\"](\w+)['\"]|", $content, $matches)) {
			foreach ($matches[1] as $alias) {
				$alias = "storeId:{$alias}";
				$aliasDeps[$alias] = $alias;
			}
		}
		*/


		// collect view controller dependencies
		/*
		 * we need view contoller only at runtime, but not at loadtime - so comment out
		if (preg_match_all("|controller\s*:\s*['\"](\w+)['\"]|", $content, $matches)) {
			foreach ($matches[1] as $alias) {
				$alias = "controller.{$alias}";
				$aliasDeps[$alias] = $alias;
			}
		}
		*/


		// remember deps
		$classes[$className]['deps'] = $deps;
		$classes[$className]['aliasDeps'] = $aliasDeps;

	}  // collect deps for all classes


	// map alias dependencies to classnames
	// this was not possible before, because we collected the alias just in the step above
	foreach ($classes as $className => $classPack) {

		foreach ($classPack['aliasDeps'] as $alias) {

			$depClass = $aliases[$alias];

			// we need not handle aliases already defined in ext
			// or otherwise marked "resolved"
			if (substr($depClass, 0, 1) == "-") {
				continue;
			}

			if (!$depClass) {
				echo "Warning: Cannot find class for alias '{$alias}' required in $className.\n";
				continue;
			}
			elseif ($debug) {
				echo "Alias {$alias} resolved to {$depClass}.\n";
			}
			$classes[$className]['deps'][$depClass] = $depClass;
		}

		unset($classes[$className]['aliasDeps']);
	}  // eo resolve alias classes

	return $classes;
}  // eo analyse classes



/*
* Detect dependencies and sort those.
*/
function sortDeps($classes) {

	global $debug;
	global $aliases;
	global $params;

	$deps = array();


	// copy deps to working array
	foreach ($classes as $className => $classPack) {
		$deps[$className] = $classPack['deps'];
	}

	// precheck for missing class dependencies
	$missing = array();
	foreach ($deps as $className => $classDeps) {
		foreach ($classDeps as $depName) {
			if (!isset($deps[$depName])) {
				echo "* {$className} depends on {$depName} which does not exist.\n";
				$missing[$depName] = $depName;
				if ($params['missingdep'] == "cont") {
					unset($deps[$className][$depName]);
					echo "  - Remove dependency.\n";
				}
			}
		}
	}  // eo missing check
	if (count($missing)) {
		switch ($params['missingdep']) {
		case "cont":
			// nothing to do here - already removed
			break;
		default:  // abort
			echo "\nAbort.\n\n";
			exit;
		}
	}  // eo missing

	// order by pushing those classes to the stack that have no dependences
	// or where the dependencies are already on the stack (resolved)
	$classesNew = array();
	while (count($deps)) {

		$resolvedCount = 0;

		foreach ($deps as $className => $classDeps) {
			// if no open dependencies than push to stack and remove from deps
			if (count($classDeps) == 0) {
				$resolvedCount++;
				$classesNew[$className] = $className;
				unset($deps[$className]);
				// remove from other classes dependencies
				foreach ($deps as $otherClassName => $otherClassDeps) {
					foreach ($otherClassDeps as $depName) {
						if ($depName == $className) {
							unset($deps[$otherClassName][$depName]);
						}
					}
				}
			}
			elseif ($debug) {
				echo "$className has " . count($classDeps) . " dependencies.\n";
			}

		}  // eo one dep loop

		if ($resolvedCount == 0) {
			// - sort by dep count of a class or by
			//   count of references TO a class?
			echo "Warning: Unresolved dependencies.\n";
			foreach ($deps as $className => $classDeps) {
				echo " - $className (" . count($classDeps) . ")\n";
				foreach ($classDeps as $depName) {
					echo "   * $depName";
					if (!$classes[$depName]) {
						echo " (not found)";
					}
					echo "\n";
				}
			}
			echo "\n";

			// - circular check (direct backpointer at least)
			foreach ((array)$deps as $className => $classDeps) {
				foreach ((array)$classDeps as $depName) {
					foreach ((array)$deps[$depName] as $otherDeps) {
						foreach ((array)$otherDeps as $otherDep) {
							if ($otherDep == $className) {
								echo "* Curcular dependency $className <-> $depName\n";
							}
						}
					}
				}
			}
			echo "\n";

			switch ($params['partial']) {
			case "ok":
			case "add":
				return $classesNew;
				break;
			default:  // abort
				exit;
			}
		}

	}  // eo while deps (files)

	return $classesNew;
}  // eo check js names



/*
* Explode classes given as string or json array in string
*/
function explodeDeps($str, $app) {

	$str = trim($str);
	$appPrefix = "{$app}.";

	// handle json array []
	if (substr(str, 0, 1) == "[") {
		$str = trim(substr(str, 1));
		$pos = strpos(str, "]");
		if ($pos !== false) {
			$str = trim(substr($str, 0, $pos));
		}
	}

	// remove delimiter (simple)
	$str = str_replace("'", "", $str);
	$str = str_replace('"', "", $str);

	$classList = array();
	foreach (explode(",", $str) as $str) {
		// only handle app dependencies
		if (strpos($str, $appPrefix) !== 0) {
			continue;
		}
		$classList[] = trim($str);
	}

	return($classList);
}  // eo explode deps




/*
* Build app js concat from js file list
*/
function buildAppJsConcat($jsFileList, $webRoot) {

	$out = "";

	$fileList = explode("\n", $jsFileList);
	foreach ($fileList as $fileName) {
		$fileName = trim($fileName);
		// skip comments
		if (substr($fileName, 0, 2) == "//") {
			continue;
		}
		// skip empty lines
		if (!$fileName) {
			continue;
		}
		$out .= file_get_contents("{$webRoot}/{$fileName}") . "\n";
	}

	// get first license text
	$search = "|/\*\s*#LICENSE BEGIN.*?#LICENSE END\s*\*/|ms";
	preg_match($search, $out, $matches);
	$license = $matches[0];
	// remove all license text and reinsert first one
	$out = preg_replace($search, "", $out);
	$out = "{$license}{$out}";

	return $out;
}  // eo build app js concat



/*
* Build app js debug from js concat file
* Remove comments but preserve line numbers
* TODO: refine methods. Currently marker (//, /* * /)
* inside of text strings result in corrupt script.
*/
function buildAppJsDebugRegex($jsConcat) {

	$out = $jsConcat;

	// get and remove first license text
	$search = "|/[ \t]*\*\s*#LICENSE BEGIN.*?#LICENSE END\s*\*/[ \t]*|s";
	preg_match($search, $out, $matches);
	$license = $matches[0];
	$out = preg_replace($search, "", $out, 1);

	// remove all single line comments
	// this DOES NOT WORK:
	//$out = preg_replace("|\s*//.*$|mU", "", $out);
	// looks like php 5.3 includes line-breaks into whitespace collecting
	// after "^" and before "$" so try following workaround
	$out = preg_replace("|[ \t]*//.*?$|m", "", $out);

	// remove multiline comments with fitting count of line breaks
	$matches2 = array();
	preg_match_all("|[ \t]*/\*.*?\*/[ \t]*|s", $out, $matches);
	// preserve multiple maches only once by using as key
	foreach ($matches[0] as $match) {
		$matches2[$match] = $match;
	}
	foreach ($matches2 as $match) {
		$repl = preg_replace("/[^\n\r]+/", "", $match);
		$match = preg_quote($match, "|");
		$out = preg_replace("|{$match}|", $repl, $out);
	}


	$out = "{$license}{$out}";
	return $out;
}  // eo build app js debug


/*
* Build app js debug from js concat file
* Remove comments with uglifyjs
*/
function buildAppJsDebugUglifyJS($jsConcat) {

	$out = $jsConcat;
	//$out = buildAppJsDebug($jsConcat);

	$cmd = "/usr/bin/uglifyjs";
	if (file_exists($cmd)) {

		$f1nam = tempnam("/tmp", "uglify");
		file_put_contents($f1nam, $out);

		$f2nam = tempnam("/tmp", "uglify");
		$cmd .= " {$f1nam} -o {$f2nam} --comments '/#LICENSE/' -b indent-level=2 ";
		echo "   Call: {$cmd}\n";
		passthru($cmd);
		$out = file_get_contents($f2nam);
		unlink($f2nam);

		unlink($f1nam);
	}
	else {  // no uglifyjs
		//echo "UglifyFs not found - remove comments by regex.\n";
		//$out = buildAppJsDebugRegex($out);
		echo "*** UglifyFs not found - do nothing.\n";
}

	return $out;
}  // eo build app js debug



/*
* Build app js debug from js concat file
* Remove comments with yui compressor
*/
function buildAppJsDebugYuiCompressor($jsConcat) {

	global $devScriptsRoot;

	$out = $jsConcat;
	//$out = buildAppJsDebug($jsConcat);

	$jar = "{$devScriptsRoot}/tools/yuicompressor.jar";
	$cmd = "java -jar {$jar}";
	if (file_exists($jar)) {

		$f1nam = tempnam("/tmp", "yuicomp");
		file_put_contents($f1nam, $out);

		$f2nam = tempnam("/tmp", "yuicomp");
		$cmd .= " --nomunge --preserve-semi --disable-optimizations --type js -o {$f2nam} {$f1nam} ";
		echo "   Call: {$cmd}\n";
		passthru($cmd);
		$out = file_get_contents($f2nam);
		unlink($f2nam);

		unlink($f1nam);
	}
	else {  // no yuicompressor
		//$cwd = getcwd();
		//echo "Yuicompressor not found at {$jar} - remove comments by regex.\n";
		//$out = buildAppJsDebugRegex($out);
		echo "*** Yuicompressor not found - do nothing.\n";
	}

	return $out;
}  // eo build app js debug




/*
* Build minified app js
*/
function buildAppJsMinifiedUglyFS($jsConcat) {

	$out = $jsConcat;
	//$out = buildAppJsDebugRegex($jsConcat);

	$cmd = "/usr/bin/uglifyjs";
	if (file_exists($cmd)) {

		$f1nam = tempnam("/tmp", "uglify");
		file_put_contents($f1nam, $out);

		$f2nam = tempnam("/tmp", "uglify");
		$cmd .= " {$f1nam} -o {$f2nam} --comments '/#LICENSE/' -c warnings=false -m";
		echo "   Call: {$cmd}\n";
		passthru($cmd);
		$out = file_get_contents($f2nam);
		unlink($f2nam);

		unlink($f1nam);
	}
	else {  // no uglifyjs
		//echo "UglifyFs not found - remove empty lines only.\n";
		// remove empty lines to do anything ;-)
		//$out = preg_replace("/^\s*$/ms", "", $out);
		echo "*** UglifyFs not found - do nothing.\n";
	}

	return $out;
}  // eo build minified app js




?>
