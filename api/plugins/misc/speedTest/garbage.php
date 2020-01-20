<?php
// Disable Compression
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');
// Headers
header('HTTP/1.1 200 OK');
// Download follows...
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=random.dat');
header('Content-Transfer-Encoding: binary');
// Never cache me
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
// Generate data
$data=openssl_random_pseudo_bytes(1048576);
// Deliver chunks of 1048576 bytes
$chunks=isset($_GET['ckSize']) ? intval($_GET['ckSize']) : 4;
if(empty($chunks)){$chunks = 4;}
if($chunks>100){$chunks = 100;}
for($i=0;$i<$chunks;$i++){
    echo $data;
    flush();
}
?>
