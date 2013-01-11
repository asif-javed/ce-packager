<?php

include_once('lib/utils.php');

// get and validate arguments
$usage_string = 'Usage is php '.__FILE__.' <outputdir> <preinstalled> <type> <number> <beta>'.PHP_EOL;
$usage_string .= "<outputdir> = directory in which to create the package (if it does not start with a '/' the running directory is attached".PHP_EOL;
$usage_string .= '<preinstalled> = true / false'.PHP_EOL;
$usage_string .= '<type> = CE / TM'.PHP_EOL;
$usage_string .= '<number> = vX.X.X'.PHP_EOL;
$usage_string .= '<beta> = extra text to include in the version name (like "beta")'.PHP_EOL;

if (count($argv) < 5) {
	die($usage_string);
}

$version_ini = array();
$version_ini['outputdir'] = trim($argv[1]);
$version_ini['preinstalled'] = trim($argv[2]);
$version_ini['type'] = trim($argv[3]);
$version_ini['number'] = trim($argv[4]);
$version_ini['beta'] = '';
if (count($argv) > 5)
	$version_ini['beta'] = trim($argv[5]);

if (strcmp($version_ini['type'], 'CE') != 0 &&  strcmp($version_ini['type'], 'TM') != 0) {
	die($usage_string);		
}

if (strcmp($version_ini['preinstalled'], 'true') != 0 && strcmp($version_ini['preinstalled'], 'false') != 0) {
	die($usage_string);		
}

if (preg_match('/^v[0-9]+\.[0-9]+\.[0-9]+/', $version_ini['number']) != 1) {
	die($usage_string);
}

// set php_ini settings
error_reporting(E_ALL);

ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

echo PHP_EOL.PHP_EOL;

// start packaging
echo "Started packaging\n";

$base_dir = $version_ini['outputdir'];

chdir(__DIR__ . '/../installer/directoryConstructor');
$command = "phing -verbose -DBASE_DIR=$base_dir/package";
$returnedValue = null;
passthru($command, $returnedValue);
if($returnedValue != 0)
	exit($returnedValue);
echo "Created package skeleton\n";

// save version.ini (later used by the installer)
$version_ini_str = 'type = '.$version_ini['type'].PHP_EOL;
$version_ini_str .= 'number = '.$version_ini['number'].' '.$version_ini['beta'].PHP_EOL;
$version_ini_str .= 'preinstalled = '.$version_ini['preinstalled'].PHP_EOL;
file_put_contents($base_dir . '/package/version.ini', $version_ini_str);
echo "Created version.ini\n";

// copy package root
recurse_copy(__DIR__ . '/../installer', "$base_dir/installer");
recurse_copy(__DIR__ . '/package_root/' . $version_ini['type'], "$base_dir/package");
echo "Copied package root\n";
echo "Finished successfully\n";
exit(0);





