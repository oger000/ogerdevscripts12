#!/usr/bin/php
<?PHP

error_reporting((E_ALL | E_STRICT));
error_reporting(error_reporting() ^ E_NOTICE);


require_once("global.inc.php");
require_once("config.localonly.inc.php");
$params = getParams();


// application init and prechecks

chdir($projectRoot);

echo "Write version files.\n";
echo "\nCWD=" . getcwd() . "\n\n";

$cmd = "git log --date=iso | head -5 | grep '^commit\|^Date:'";
echo "$cmd\n";
$revisionStr = shell_exec($cmd);
echo "Version is:\n$revisionStr\n";

echo "Write version to $versionFile.\n";
file_put_contents($versionFile, $revisionStr);
echo "Write version to $distVersionFile.\n";
file_put_contents($distVersionFile, $revisionStr);


?>
