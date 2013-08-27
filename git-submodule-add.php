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

// process submodule add
function addSubmodule($submodule) {

  if (!$submodule) {
    return;
  }

  if (!$submodule['path']) {
    echo "Missing path info.";
    return;
  }
  if (!$submodule['url']) {
    echo "Missing url info.";
    return;
  }

  $cmd = "git submodule add {$submodule['url']} {$submodule['path']}";
  echo "$cmd\n";
  //return;
  passthru($cmd);

}  // eo add submodule


?>
