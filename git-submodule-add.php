#!/usr/bin/php
<?PHP


// read .gitmodules file
$lines = file('.gitmodules');
if (!$lines) {
	exit;
}

$submodule = array();

foreach ($lines as $line) {

	$line = trim($line);

	if (preg_match('/^\[submodule /', $line)) {
		// if submodule info is cached than process
		addSubmodule($submodule);
		$submodule = array();
	}

	if (preg_match('/^path = /', $line)) {
		$submodule['path'] = preg_replace('/^path = /', '', $line);
	}

	if (preg_match('/^url = /', $line)) {
		$submodule['url'] = preg_replace('/^url = /', '', $line);
	}

}  // eo .gitmodules loop

// process last submodule info
addSubmodule($submodule);

$cmd = "git submodule foreach git checkout master";
echo "$cmd\n";
passthru($cmd);


//echo "\nIf content of a submodule is missing, then \"git submodule update --init <path>\"\n";
//echo " will init, clone the content and leave the submodule into detatched state.\n\n";



// process submodule add
function addSubmodule($submodule) {

	if (!$submodule) {
		return;
	}

	echo "\n";

	if (!$submodule['path']) {
		echo "Missing path info.";
		return;
	}
	if (!$submodule['url']) {
		echo "Missing url info.";
		return;
	}

	//$cmd = "mkdir -p {$submodule['path']}";
	//$cmd = "rm -rf {$submodule['path']}";
	//echo "\nOger call: $cmd\n";
	//passthru($cmd);

	$cmd = "git submodule add --depth 3 {$submodule['url']} {$submodule['path']}";
	echo "\nOger call: $cmd\n";
	passthru($cmd);

	$cmd = "git submodule update --init {$submodule['path']}";
	echo "\nOger call: $cmd\n";
	passthru($cmd);

}  // eo add submodule


?>
