#!/usr/bin/php
<?PHP


require_once("global.inc.php");
require_once("config.localonly.inc.php");
$params = getParams();


include('ext4aliases.inc.php');


// application init
chdir(__DIR__ . "/$webRoot");
echo "CWD=" . getcwd() . "\n\n";


#####################################
# settings
#####################################

$appJsMain = "app.js";
$indexFile = "index.php";




echo "Read files from $searchDir\n";
$fileRegex = '/.*\.js$/';
$classes = getAppClasses($appJsRoot, $fileRegex, $appJsName, $appJsMain);
ksort($classes);

echo "Sorting dependencies\n";
$classes = sortDeps($classes, $appJsName, $appJsMain);


$classList = '';
foreach ($classes as $className => $content) {
  $fileName = $appJsRoot . $className;
  $fileBase = basename($fileName);
  $dirName = dirname($fileName) . DIRECTORY_SEPARATOR;
  $filesText .= "$indent{ \"path\": \"$dirName\",\n$indent  \"name\": \"$fileBase\",\n$indent},\n";
  echo "$searchDir$fileNameBare\n";
}

  echo "Create $jsbFile\n";
  if ($applyFlag) {
    $oldText = file_get_contents($jsbTmpl);
    $newText = preg_replace('/#FILES#/', $filesText, $oldText);
    $result = file_put_contents($jsbFile, $newText);
    if ($result === false) {
      echo "    *** Error writing new text to $jsbFile. ***\n";
    }
  }
  else {
    echo "  - Check-only. Jsb file not created.\n";
  }
  echo "\n";


  // update index file
  echo "Update indexfile $indexFile\n";
  if ($applyFlag) {
    $indent = "  ";
    $scriptTpl = "<script type=\"text/javascript\" src=\"js/app#JSFILE#\"></script>\n";
    $newText = "";
    foreach ($files as $fileNameBare => $content) {
      $newText .= $indent . str_replace('#JSFILE#', $fileNameBare, $scriptTpl);
    }

    $oldText = file_get_contents($indexFile);
    $search = "|" . preg_quote("<!-- #APPJS BEGIN -->") . ".*?" . preg_quote("<!-- #APPJS END -->") . "|ms";
    //$search = "|<!-- #APPJS BEGIN -->.*?<!-- #APPJS END -->|ms";
    $repl = "<!-- #APPJS BEGIN -->\n$newText<!-- #APPJS END -->";
    $newText = preg_replace($search, $repl, $oldText);
    $result = file_put_contents($indexFile, $newText);
    if ($result === false) {
      echo "    *** Error writing new text to $indexFile. ***\n";
    }
  }
  else {
    echo "  - Check-only. Changes NOT applied to the index file.\n";
  }
  echo "\n";

}  // eo create command



#####################################
# process build command
#####################################

if ($buildFlag) {
  if ($applyFlag) {
    system("$jsbuildCmd -p $jsbFile -d $buildDir -v > $logFile 2>&1");
    system("cat $logFile");
  }
  else {
    echo "  - Build without apply not possible.\n";
  }
  echo "\n";
}  // eo build command



#############################################################



/*
* Get app classes from fileregex
*/
function getAppClasses($dirName, $regex, $appName, $appMain, $startDir == null) {

  // if startdir is not given, then use current dirname
  if ($startDir === null) {
    $startDir = $dirName;
  }

  $classes = array();
  $subDirs = array();

  $dh = opendir($dirName);
  if ($dh === false) {
    echo "   *** Cannot open directory $dirName.\n";
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

    // exclude app main js
    if ($shortFileName != DIRECTORY_SEPARATOR . $appMain) {
      // TODO this is very dumb, should be refined
      if (!preg_match('|' . preg_quote($className) . '|', $content)) {
        echo "Warning: Cannot find class $className in $appDirRel$fileName.\n";
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
* Sort dependencies
*/
function sortDeps($classes, $appName, $appJs) {

  global $debug;
  global $extAliases;

  $deps = array();
  $xtypeDeps = array();
  $aliases = array();

  $storeIdDefs = array();
  $storeIdDeps = array();

  // may be we should differ between explicit dependencies like
  // Ext.require method and the "requires" property
  // and implicit dependencies (all other)?

  // search all files for dependencies infos
  foreach ($classes as $className => $content) {

    $deps[$className] = array();   // force each class to the deps array

    // remove comments
    $content = preg_replace('|/\*.*?\*/|s', '', $content);
    $content = preg_replace('|^\s*//.*$|m', '', $content);

    // collect class name dependencies
    // search for application classes mentioned somewhere
    if (preg_match_all("|($appName\..*?)['\"]|", $content, $matches)) {
      foreach ($matches[1] as $depClass) {
        // the own class is not a dependency
        if ($depClass == $className) {
          continue;
        }
        $deps[$className][$depClass] = $depClass;
      }
    }

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


  // resolve xtype dependencies to classnames
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


  // resolve store dependencies by storeId to classnames
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

  // order by pushing those classes to the stack that have no dependences
  // or where the dependencies are already on the stack (resolved)
  // and remove the pushed class from all remaining classes from the (open) dependency array
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
      // maybe sort by dep count?
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
      exit;
    }

  }  // eo while deps (files)

  return $classesNew;
}  // eo check js names




?>
