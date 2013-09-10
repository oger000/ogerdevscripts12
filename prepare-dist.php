#!/usr/bin/php
<?PHP

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);


require_once("global.inc.php");
require_once("config.localonly.inc.php");
$params = getParams();


// application init and prechecks

$timeStamp = date("YmdHis");

if (!$projectName) {
  echo "Project name not set in config file.";
  exit;
}
if (!$webDir) {
  echo "Web directory not set in config file.";
  exit;
}
if (!$distRoot) {
  echo "Distribution root not set in config file.";
  exit;
}
if (!$distDir) {
  echo "Distribution dir not set in config file.";
  exit;
}

chdir($distRoot);
echo "\nCWD=" . getcwd() . "\n\n";

if (!file_exists($distRoot)) {
  echo "Distribution root $distRoot does not exist.\n\n";
  exit;
}
if (!file_exists($distDir)) {
  echo "Distribution dir $distDir does not exist.\n\n";
  exit;
}



// check if git status is clean (do not distribute uncommented changes)
$cmd = "git status --porcelain";
echo "Check for clean repository.\n";
echo "$cmd\n";
$out = shell_exec($cmd);
echo "$out";
if (trim($out)) {
  echo "*** Repository is dirty.\n";
  if ($params["dirty"]) {
    echo "*** Dirty repository forced - continue.\n";
  }
  else {
    exit;
  }
}
else {
  echo "Repository is clean.\n";
}


$tmpDistDir = sys_get_temp_dir() . "/{$projectName}_$timeStamp/$projectName";
if (file_exists($tmpDistDir)) {
  echo "\n*** Temp dist dir $tmpDistDir already exists.\n\n";
  exit;
}
if (!mkdir($tmpDistDir, 0777, true)) {
  echo "\n*** Cannot create temp dist dir $tmpDistDir.\n\n";
  exit;
}
echo "\nTemporary dist dir is $tmpDistDir.\n\n";



// write dist file
$cmd = __DIR__ . "/version-out.php";
echo "$cmd\n";
passthru($cmd);



// make shure we are back to dist root
chdir($distRoot);

echo "\nCopy files from " . getcwd() . " to $tmpDistDir\n";
$copyCount = copyFiles("", "$tmpDistDir/");
echo "$copyCount files copied.\n\n";


// apply license
$cmd = __DIR__ . "/license.php startDir=$tmpDistDir";
echo "$cmd\n";
passthru($cmd);


// create zip and sfx
$zipFile = "$distDir/{$projectName}-dist@$timeStamp.zip";
echo "\nZip from $tmpDistDir to $zipFile\n";
zipIt($tmpDistDir, $zipFile, true);

$sfxUnzipFile = __DIR__ . "/tools/unzipsfx.exe";
$sfxFile = str_replace(".zip", ".sfx", $zipFile);
echo "\nCreate self extracting zip $sfxFile.\n";
$content = file_get_contents($sfxUnzipFile);
$content .= file_get_contents($zipFile);
file_put_contents($sfxFile, $content);

$cmd = "zip -A $sfxFile";
echo "$cmd\n";
passthru($cmd);


/*
# self extracting zipfile
SFXEXT=".sfx"
cat tools/unzipsfx.exe "$DISTFILE_FULL$ZIPEXT" > "$DISTFILE_FULL$SFXEXT"
zip -A "$DISTFILE_FULL$SFXEXT"
cp -a "$DISTFILE_FULL$SFXEXT" "$DISTFILE_FULL@$TODAY$SFXEXT"
*/



/*
* Copy files
*/
function copyFiles($dirName, $targetRoot) {
  global $distExclude;

  if ($dirName) {
    $dirName .= "/";
  }

  $subDirs = array();

  $tmpDirName = $dirName;
  if (!$tmpDirName) {
    $tmpDirName = ".";   // empty current dir for start
  }
  $dh = opendir($tmpDirName);
  if ($dh === false) {
    echo "*** Cannot open directory $tmpDirName.\n";
    exit;
  }

  while (($fileName = readdir($dh)) !== false) {

    $fullName = "$dirName$fileName";

    // dont process thisdir, parentdir and other dotdirs and dotfiles
    if (substr($fileName, 0, 1) == '.') {
      continue;
    }

    $excludeFlag = false;
    foreach ($distExclude as $regex) {
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

    $targetName = "$targetRoot$fullName";
    $targetDir = dirname($targetName);
    if (!file_exists($targetDir)) {
      //echo "Create $targetDir\n";
      mkdir($targetDir, 0777, true);
      //echo "Copy " . dirname($fullName) . " to $targetDir\n";
    }
    //echo "Copy $fullName to $targetRoot$fullName\n";
    copy($fullName, $targetName);
    $copyCount++;
  }

  // process subdirs
  foreach ($subDirs as $dirName) {
    $copyCount += copyFiles($dirName, $targetRoot);
  }

  return $copyCount;
}  // eo copy files



/*
* Create zip archive
* see: <http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php>
*/
function zipIt($source, $destination, $include_dir = false, $additionalIgnoreFiles = array())
{
    // Ignore "." and ".." folders by default
    $defaultIgnoreFiles = array('.', '..');

    // include more files to ignore
    $ignoreFiles = array_merge($defaultIgnoreFiles, $additionalIgnoreFiles);

    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    /*
    if (file_exists($destination)) {
        unlink ($destination);
    }
    */

    $zip = new ZipArchive();
    if ($zip->open($destination, ZIPARCHIVE::CREATE) !== true) {
      echo "Cannot open $destination.\n";
      return false;
    }
    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        if ($include_dir) {

            $arr = explode("/",$source);
            $maindir = $arr[count($arr)- 1];

            $source = "";
            for ($i=0; $i < count($arr) - 1; $i++) {
                $source .= '/' . $arr[$i];
            }

            $source = substr($source, 1);

            $zip->addEmptyDir($maindir);
        }

        foreach ($files as $file) {

            $file = str_replace('\\', '/', $file);

            // purposely ignore files that are irrelevant
            if( in_array(substr($file, strrpos($file, '/')+1), $ignoreFiles) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}  // eo zip it




?>
