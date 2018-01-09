<?php


// Upgrade the installation
function upgradeInstall($branch = 'v2-master') {
    $url = 'https://github.com/causefx/Organizr/archive/'.$branch.'.zip';
    $file = "upgrade.zip";
    $source = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'upgrade'.DIRECTORY_SEPARATOR.'Organizr-'.str_replace('v2','2',$branch).DIRECTORY_SEPARATOR;
    $cleanup = dirname(__DIR__,2) .DIRECTORY_SEPARATOR."upgrade".DIRECTORY_SEPARATOR;
    $destination = dirname(__DIR__,2).DIRECTORY_SEPARATOR;
	echo 'URL: ',$url, '<br/></br/>FILENAME: ',$file,'<br/></br/>SOURCLEFILE: ', $source,'<br/></br/>DELETE DIR: ', $cleanup,'<br/></br/>OVERWRITE: ', $destination;
	//writeLog("success", "starting organizr upgrade process");
    if(downloadFile($url, $file)){
		echo 'downloaded file';
	}else{
		echo 'error! download';
	}
	if(unzipFile($file)){
		echo 'unzipped file';
	}else{
		echo 'error! zip';
	}
	if(rcopy($source, $destination)){
		echo 'copied file';
	}else{
		echo 'error! copy';
	}
	if(rrmdir($cleanup)){
		echo 'removed file';
	}else{
		echo 'error! remove';
	}
    //;
    //writeLog("success", "new organizr files copied");
    //;
    //writeLog("success", "organizr upgrade folder removed");
	//writeLog("success", "organizr has been updated");
	return true;
}
function downloadFile($url, $path){
	ini_set('max_execution_time',0);
	$folderPath = "upgrade".DIRECTORY_SEPARATOR;
	if(!mkdir($folderPath)){
		//writeLog("error", "organizr could not create upgrade folder");
	}
	$newfname = $folderPath . $path;
	$file = fopen ($url, 'rb');
	if ($file) {
		$newf = fopen ($newfname, 'wb');
		if ($newf) {
			while(!feof($file)) {
				fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
			}
		}
	}else{
		//writeLog("error", "organizr could not download $url");
	}

	if ($file) {
		fclose($file);
		//writeLog("success", "organizr finished downloading the github zip file");
	}else{
		//writeLog("error", "organizr could not download the github zip file");
	}

	if ($newf) {
		fclose($newf);
		//writeLog("success", "organizr created upgrade zip file from github zip file");
	}else{
		//writeLog("error", "organizr could not create upgrade zip file from github zip file");
	}
	return true;
}
function unzipFile($zipFile){
	$zip = new ZipArchive;
	$extractPath = "upgrade/";
	if($zip->open($extractPath . $zipFile) != "true"){
		//writeLog("error", "organizr could not unzip upgrade.zip");
	}else{
		//writeLog("success", "organizr unzipped upgrade.zip");
	}

	/* Extract Zip File */
	$zip->extractTo($extractPath);
	$zip->close();
	return true;
}
// Function to remove folders and files
function rrmdir($dir) {
	if (is_dir($dir)) {
		$files = scandir($dir);
		foreach ($files as $file)
			if ($file != "." && $file != "..") rrmdir("$dir/$file");
		rmdir($dir);
	}
	else if (file_exists($dir)) unlink($dir);
	return true;
}
// Function to Copy folders and files
function rcopy($src, $dst) {
	if (is_dir ( $src )) {
		if (!file_exists($dst)) : mkdir ( $dst ); endif;
		$files = scandir ( $src );
		foreach ( $files as $file )
			if ($file != "." && $file != "..")
				rcopy ( "$src/$file", "$dst/$file" );
	} else if (file_exists ( $src ))
		copy ( $src, $dst );
	return true;
}
