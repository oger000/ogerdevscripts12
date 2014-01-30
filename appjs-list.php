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


include('ext4aliases.inc.php');


// application init
chdir($appJsRoot);
echo "\nCWD=" . getcwd() . "\n\n";


#####################################


echo "Read files from $appJsRoot\n";
$fileRegex = '/.*\.js$/';
$classes = getAppClasses($appJsRoot, $fileRegex, $appJsName, $appJsMain);
ksort($classes);

echo "Sorting dependencies\n";
$classesNew = sortDeps($classes, $appJsName, $appJsMain);


$out = "";
foreach ($classesNew as $className => $dummy) {
  $out .= $appJsRel .
           str_replace('.', DIRECTORY_SEPARATOR, substr($className, strlen($appJsName))) . ".js\n";
}

if (count($classesNew) < count($classes) && $params['partial'] == "add") {
  echo "\nAdd " . count($classes) - count($classesNew) . " unresolved classes.\n";
  $out .= "\n";
  foreach ($classes as $className => $dummy) {
    if (!$classesNew[$className]) {
      $out .= $appJsRoot .
               str_replace('.', DIRECTORY_SEPARATOR, substr($className, strlen($appJsName))) . ".js\n";
    }
  }
}  // add unresolved


// update index appjs include
echo "\n";
if ($params['apply']) {
  echo "Write appjs file list $appJsList.\n";
  if (file_put_contents($appJsList, $out) === false) {
    echo "ERROR on write to $appJsList.\n";
  }
}
else {
  $tmpName = tempnam("/tmp", "applist");
  if (file_put_contents($tmpName, $out) === false) {
    echo "ERROR on write to $tmpName.\n";
  }
  passthru("diff -u {$appJsList} {$tmpName}");
  unlink($tmpName);
  echo "  - Check-only. Do not write file list.\n";
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

    $searchFileName = "$dirName/$fileName";

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

    // make classname out of directory  name
    // remove startdir (leading slash remains) and extension
    $shortFileName = substr($searchFileName, strlen($startDir));
    $className = $appName . str_replace(DIRECTORY_SEPARATOR, '.', substr($shortFileName, 0, -3));

    // check if file contains class name
    $content = file_get_contents($searchFileName);

    // exclude app main js, because this has no class name
    if ($shortFileName != DIRECTORY_SEPARATOR . $appMain) {
      // TODO this is very dumb, should be refined
      if (!preg_match('|' . preg_quote($className) . '|', $content)) {
        echo "Warning: Cannot find class '{$className}' in $dirName/$fileName.\n";
        exit;
      }
    }

    // add content to class array
    $classes[$className] = $content;
  }

  // process subdirs
  foreach ($subDirs as $searchFileName) {
    $classes = array_merge($classes, getAppClasses($searchFileName, $regex, $appName, $appMain, $startDir));
  }

  return $classes;
}  // eo get app classes



/*
* Detect dependencies and sort those.
*/
function sortDeps($classes, $appName, $appJs) {

  global $debug;
  global $extAliases;
  global $params;

  $deps = array();
  $xtypeDeps = array();
  $aliases = array();

  $storeIdDefs = array();
  $storeIdDeps = array();

  // may be we should differ between explicit dependencies like
  // Ext.require method and the "requires" property
  // and implicit dependencies (all other)?

  // collect dependencies on other classes, xtypes and store ids for all classes
  foreach ($classes as $className => $content) {

    $deps[$className] = array();   // force each class to the deps array

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

    // collect class name dependencies

    // search for "Ext.require(...)"
    if (preg_match_all("#Ext\.require\s*\((.*?)\)#s", $content, $matches)) {
      foreach ($matches[1] as $depMatch) {
        foreach (explodeDeps($depMatch, $appName) as $depClass) {
          $deps[$className][$depClass] = $depClass;
        }
      }
    }
    // search for "requires: [...]"
    // depends on [] delimiter, but I am not shure if those are mandatory
    if (preg_match_all("#requires\s*:\s*\[(.*?)\]#s", $content, $matches)) {
      foreach ($matches[1] as $depMatch) {
        foreach (explodeDeps($depMatch, $appName) as $depClass) {
          $deps[$className][$depClass] = $depClass;
        }
      }
    }
    // search for "extend:"
    if (preg_match_all("#extend\s*:\s*(.*?),#s", $content, $matches)) {
      foreach ($matches[1] as $depMatch) {
        foreach (explodeDeps($depMatch, $appName) as $depClass) {
          $deps[$className][$depClass] = $depClass;
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
        $deps[$className][$depClass] = $depClass;
      }
    }
    */

    // collect xtype alias (asume one alias per class/file ?)
    if (preg_match_all("|alias\s*:\s*['\"]widget\.(\w+)['\"]|", $content, $matches)) {
      foreach ($matches[1] as $xtype) {
        if (array_key_exists($xtype, $aliases)) {
          echo "Warning: Duplicate xtype alias $xtype in:\n";
          echo " - " . $aliases[$xtype] . "\n";
          echo " - $className\n";
        }
        $aliases[$xtype] = $className;
      }
    }

    // collect xtype dependencies
    if (preg_match_all("|xtype\s*:\s*['\"](\w+)['\"]|", $content, $matches)) {
      foreach ($matches[1] as $xtype) {
        $xtypeDeps[$className][$xtype] = $xtype;
      }
    }

    // collect store ids
    if (preg_match_all("|storeId\s*:\s*['\"](\w+)['\"]|", $content, $matches)) {
      foreach ($matches[1] as $storeId) {
        if (array_key_exists($storeId, $storeIdDefs)) {
          echo "Warning: Duplicate storeId $storeId in:\n";
          echo " - " . $storeIdDefs[$storeId] . "\n";
          echo " - $className\n";
        }
        $storeIdDefs[$storeId] = $className;
      }
    }

    // collect storeId dependencies
    if (preg_match_all("|store\s*:\s*['\"](\w+)['\"]|", $content, $matches)) {
      foreach ($matches[1] as $storeId) {
        $storeIdDeps[$className][$storeId] = $storeId;
      }
    }

  }  // collect deps


  // map xtype dependencies to classnames
  foreach ($xtypeDeps as $className => $xtypeDep) {
    foreach ($xtypeDep as $xtype) {
      // we need not handle aliases already defined in ext
      if (in_array($xtype, $extAliases)) {
        continue;
      }
      $depClass = $aliases[$xtype];
      if (!$depClass) {
        echo "Warning: Cannot find class for xtype $xtype required in $className.\n";
        continue;
      }
      elseif ($debug) {
        echo "Xtype $xtype found in $depClass.\n";
      }
      $deps[$className][$depClass] = $depClass;
    }
  }


  // map store dependencies by storeId to classnames
  foreach ($storeIdDeps as $className => $storeDep) {
    foreach ($storeDep as $storeId) {
      $depClass = $storeIdDefs[$storeId];
      if (!$depClass) {
        echo "Warning: Cannot find class for storeId $storeId required in $className.\n";
        continue;
      }
      elseif ($debug) {
        echo "StoreId $storeId found in $depClass.\n";
      }
      $deps[$className][$depClass] = $depClass;
    }
  }

  // check for missing class dependencies
  $missing = array();
  foreach ($deps as $className => $classDeps) {
    foreach ($classDeps as $depName) {
      if (!isset($deps[$depName])) {
        echo "* $className depends on $depName which does not exist.\n";
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




?>
