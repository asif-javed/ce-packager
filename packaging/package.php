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
//$includeWindowsBinaries = false;
if (count($argv) > 5){
	$version_ini['beta'] = trim($argv[5]);
//	if((count($argv) > 6) && (strcmp($argv[6],'-w') == 0)){
//		$includeWindowsBinaries = true;
//	}
}

if (strcmp($version_ini['type'], 'CE') != 0 &&  strcmp($version_ini['type'], 'TM') != 0) {
	die($usage_string);		
}

if (strcmp($version_ini['preinstalled'], 'true') != 0 && strcmp($version_ini['preinstalled'], 'false') != 0) {
	die($usage_string);		
}

if (preg_match('/v[0-9]\.[0-9]\.[0-9]/', $version_ini['number']) != 1) {
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

$base_dir = $version_ini['outputdir'].'/';
if (substr($base_dir,0,1) !== '/') {
	$base_dir = getcwd().'/'.$base_dir;
}

// set the manifest file name
// the manifest file will set the directories that will be exported from SVN
$manifestFileName = 'manifest.ini';
$manifest = parse_ini_file($manifestFileName, true);

mkdir($base_dir, 0777, true);
recurse_copy('./package_skeleton', $base_dir . 'package/'); // copy package skeleton to a package output dir

echo "Created package skeleton\n";

// save version.ini (later used by the installer)
$version_ini_str = 'type = '.$version_ini['type'].PHP_EOL;
$version_ini_str .= 'number = '.$version_ini['number'].' '.$version_ini['beta'].PHP_EOL;
$version_ini_str .= 'preinstalled = '.$version_ini['preinstalled'].PHP_EOL;
file_put_contents($base_dir . 'package/version.ini', $version_ini_str);

echo "Created version.ini\n";
$revisions =  array();
// get all files from git submodules (including the installer)
$revisions = recurse_copy($manifest['server_core']['local_git_path'], $base_dir.$manifest['server_core']['local_path']);
$revisions += recurse_copy($manifest['server_onprem']['local_git_path'], $base_dir.$manifest['server_onprem']['local_path']);
$revisions += recurse_copy($manifest['server_ce']['local_git_path'], $base_dir.$manifest['server_ce']['local_path']);

// get from kConf.php the latest versions of kmc
$kconf = file_get_contents($base_dir."/". $manifest['server_core']['local_path'] . "configurations/base.ini");
$kmcVersion = getVersionFromKconf($kconf,"kmc_version");
$revisions += recurse_copy(realpath($manifest['flash']['local_git_path']."kmc/".$kmcVersion), futurepath($base_dir.$manifest['flash']['local_path']."kmc/".$kmcVersion));
foreach($manifest['flash']['get'] as $current) {
echo "Installing compiled $current\n";
	$revision[$manifest['flash']['local_path'] . $current] = recurse_copy(realpath($manifest['flash']['local_git_path'].$current), futurepath($base_dir.$manifest['flash']['local_path'].$current));
}
// FIXME: I don't know what this one, vv,  does; looks like it copies too much stuff
//$revisions += svn_export_group($manifest['flash'], $manifest['global'], $base_dir);

// get kdp kClip versions that are working with kmc version.
// get it from kmc config file 
$kmcConf = parse_ini_file($base_dir."/".$manifest['flash']['local_path']."kmc/".$kmcVersion."/config.ini", true);
//echo "kdp: " . $kmcConf['defaultKdp']['widgets.kdp1.version'];

// copy flash binaries
$revisions += recurse_copy(realpath($manifest['flash']['local_git_path'].'kdp3/'.$kmcConf['defaultKdp']['widgets.kdp1.version']), futurepath($base_dir.$manifest['flash']['local_path'].'kdp3/'.$kmcConf['defaultKdp']['widgets.kdp1.version']));
$revisions += recurse_copy(realpath($manifest['flash']['local_git_path'].'kclip/'.$kmcConf['kClip']['widgets.kClip1.version']), futurepath($base_dir.$manifest['flash']['local_path'].'kclip/'.$kmcConf['kClip']['widgets.kClip1.version']));

// copy uiconfs
foreach($manifest['uiconf']['get'] as $current) {
	$revision[$manifest['uiconf']['local_path'] . $current] = recurse_copy(realpath($manifest['uiconf']['local_git_path'].$current), futurepath($base_dir.$manifest['uiconf']['local_path'].$current));
}

// copy dwh
$revisions += recurse_copy(realpath($manifest['dwh']['local_git_path']), futurepath($base_dir.$manifest['dwh']['local_path']));

// copy apps
foreach($manifest['apps']['get'] as $current) {
	print('COPY '.$current."\n");
	$revision[$manifest['apps']['local_path'] . $current] = recurse_copy(realpath($manifest['apps']['local_git_path'].$current), futurepath($base_dir.$manifest['apps']['local_path'].$current));
}
$revisions += recurse_copy(realpath('../installer'), futurepath($base_dir));

// copy html5
mkdir($base_dir."package/app/html5");
mkdir($base_dir."package/app/html5/html5lib");
$revisions_git = github_export_group($manifest['html5'], futurepath($base_dir));

$revisions_str = implode(PHP_EOL, $revisions);
file_put_contents($base_dir . 'package/revisions.ini', $revisions_str);
#
echo "Exported all svn code\n";

// copy package root
recurse_copy('./package_root/' . $version_ini['type'], $base_dir);
recurse_copy('./package_root/' . $version_ini['type'], $base_dir . 'package/app/');

echo "Copied package root\n";

// manipulate uiconfs (add disableUrlHashing)
manipulateUiConfs($base_dir . 'package/app/web/content/uiconf');
manipulateUiConfs($base_dir . 'package/app/web/content/templates/uiconf');

echo "Manipulated ui confs\n";


// delete windows binaries (we currently do not support windows installation
//if($includeWindowsBinaries == true){
//	echo "keep windows binaries\n";
//} else {
//	echo "delete windows binaries\n";
	recursive_remove_directory($base_dir . 'package/bin/windows');
//}

// strip all binaries in package/bin/linux directory
exec("find " . $base_dir . 'package/bin/linux' . ' -type f -exec strip {} \;');
// handle files committed from Windows.
exec("find " . $base_dir . 'package/app' . ' -type f -name "*.php" -exec dos2unix {} \;');
exec("find " . $base_dir . 'package/app' . ' -type f -name "*template*" -exec dos2unix {} \;');
exec("find " . $base_dir . 'package/bin/linux' . ' -type f -name "*.sh" -exec dos2unix {} \;');
echo "Finished successfully\n";
exit(0);
