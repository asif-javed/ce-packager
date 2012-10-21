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
echo "Started packaging";

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
// get all svn files (including the installer)
$revisions =  svn_export_group($manifest['server_core'], $manifest['global'], $base_dir);
$server_onprem_all = $manifest['server_onprem'];
$server_onprem_all['svn_path'] = $server_onprem_all['svn_path'] . 'all/';
$revisions += svn_export_group($server_onprem_all, $manifest['global'], $base_dir);
$server_onprem_specific = $manifest['server_onprem'];
$server_onprem_specific['svn_path'] = $server_onprem_specific['svn_path'] . $version_ini['type'] . '/';
$revisions += svn_export_group($server_onprem_specific, $manifest['global'], $base_dir);

// get from kConf.php the latest versions of kmc
$kconf = file_get_contents($base_dir."/". $manifest['server_core']['local_path'] . "configurations/base.ini");
$kmcVersion = getVersionFromKconf($kconf,"kmc_version");
$revisions += svn_export_group($manifest['flash'], $manifest['global'], $base_dir,"kmc/".$kmcVersion);

// get kdp kClip versions that are working with kmc version.
// get it from kmc config file 
$kmcConf = parse_ini_file($base_dir."/".$manifest['flash']['local_path']."kmc/".$kmcVersion."/config.ini", true);

//echo "kdp: " . $kmcConf['defaultKdp']['widgets.kdp1.version'];
$revisions += svn_export_group($manifest['flash'], $manifest['global'], $base_dir,"kdp3/".$kmcConf['defaultKdp']['widgets.kdp1.version']);
$revisions += svn_export_group($manifest['flash'], $manifest['global'], $base_dir,"kclip/".$kmcConf['kClip']['widgets.kClip1.version']);

$revisions += svn_export_group($manifest['flash'], $manifest['global'], $base_dir);
$revisions += svn_export_group($manifest['uiconf'], $manifest['global'], $base_dir);
$revisions += svn_export_group($manifest['dwh'], $manifest['global'], $base_dir);
$revisions += svn_export_group($manifest['dwh_upgrade'], $manifest['global'], $base_dir);
$revisions += svn_export_group($manifest['apps'], $manifest['global'], $base_dir);
$revisions += svn_export_group($manifest['installer'], $manifest['global'], $base_dir);
$revisions += github_export_group($manifest['html5'], $base_dir);
$revisions_str = implode(PHP_EOL, $revisions);
file_put_contents($base_dir . 'package/revisions.ini', $revisions_str);

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





