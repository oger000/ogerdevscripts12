<?PHP


function getParams() {
	global $argv;

	// collect all params (including params for php itself)
	$params = array();
	while (count($argv) > 0) {
		$param = array_shift($argv);
		$key = $param;
		$value = null;
		if (strpos($param, "=")) {
			list ($key, $value) = explode("=", $param, 2);
		}
		if (!$value) {
			$value = true;
		}
		else {
			if (strpos($param, ",")) {
				$value = explode(",", $value);
			}
		}

		if ($key == "--") {
			$phpParams = true;
			$value = $key;
		}

		$params[$key] = $value;

	}

	// if php params are present remove them from array
	// otherwise asume all params for this script
	if ($phpParams) {
		foreach ($params as $key => $value) {
			unset($params[$key]);
			if ($key == "--") {
				break;
			}
		}
	}

	return $params;
}  //eo get params



?>
