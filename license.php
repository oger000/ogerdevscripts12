#!/usr/bin/php
<?PHP

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);


require_once("global.inc.php");
require_once("config.localonly.inc.php");
$params = getParams();


// application init and prechecks

if (!$params['startDir']) {
  echo "Start dir not set on command line.\n\n";
  exit;
}
$startDir = $params['startDir'];

if (!file_exists($startDir)) {
  echo "Start dir $startDir does not exist.\n\n";
  exit;
}


if ($params['delete']) {
  $deleteLicense = true;
}



echo ($deleteLicense ? 'DELETE' : 'Set/Update') . " license info.\n";

if (!$deleteLicense && !file_exists($licenseFile)) {
  echo "   *** License file $licenseFile does not exist.\n";
  exit;
}
if ($deleteLicense) {
  $licenseText = '';
}
else {
  $licenseText = file_get_contents($licenseFile);
}

setLicenseToDir($startDir);

echo "\n*** End of update license.\n\n";


/*
* working function
*/
function setLicenseToDir($dirName) {

  global $licenseText;
  global $licenseExclude;

  $subDirs = array();

  $dh = opendir($dirName);
  if ($dh === false) {
    echo "   *** Cannot open directory $dirName.\n";
  }

  while (($fileName = readdir($dh)) !== false) {

    $fullName = "$dirName/$fileName";

    // dont process thisdir, parentdir, dotdirs and lib subdirs
    if (substr($fileName, 0, 1) == '.') {
      continue;
    }

    $excludeFlag = false;
    foreach ($licenseExclude as $regex) {
      if (preg_match($regex, $fileName) || preg_match($regex, $fullName)) {
        echo "Exclude $fullName\n";
        $excludeFlag = true;
      }
    }
    if ($excludeFlag) {
      continue;
    }

    if (is_dir($fullName)) {
      $subDirs[] = $fullName;
      continue;
    }


    // rewrite file
    $oldText = file_get_contents("$fullName");
    if (strpos($oldText, '#LICENSE BEGIN') === false) {
      echo "  - Skip file $fullName. No LICENSE marker found.\n";
      continue;
    }

    echo "  + Rewrite $fullName\n";
    $search = "/#LICENSE BEGIN.*?#LICENSE END/ms";
    $repl = "#LICENSE BEGIN\n$licenseText#LICENSE END";
    $newText = preg_replace($search, $repl, $oldText);
    if ($newText == $oldText) {
      echo "  - Skip file $fullName - nothing changed.\n";
      continue;
    }

    $result = file_put_contents($fullName, $newText);
    if ($result === false) {
      echo "    *** Error writing new text to $fullName. ***\n";
    }

  }  // eo file loop

  // process subdirs
  foreach ($subDirs as $dirName) {
    setLicenseToDir($dirName);
  }

}  // eo worker func


?>
