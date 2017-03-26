<?php

require_once("user.php");

isset($_GET['downloader']) ? $downloader = $_GET['downloader'] : die("Error");
isset($_GET['list']) ? $list = $_GET['list'] : die("Error");
    
if($downloader == "nzbget"){
    
    $url = NZBGETURL;
    $port = NZBGETPORT;
    $username = NZBGETUSERNAME;
    $password = NZBGETPASSWORD;

    $urlCheck = stripos($url, "http");

    if ($urlCheck === false) {

        $url = "http://" . $url;

    }

    if($port !== ""){ $url = $url . ":" . $port; }

    $address = $url;

    $api = file_get_contents("$url/$username:$password/jsonrpc/$list");

    $api = json_decode($api, true);

    $i = 0;

    $gotNZB = "";

    foreach ($api['result'] AS $child) {

        $i++;
        //echo '<pre>' . var_export($child, true) . '</pre>';
        $downloadName = $child['NZBName'];
        $downloadStatus = $child['Status'];
        $downloadCategory = $child['Category'];
        if($list == "history"){ $downloadPercent = "100"; $progressBar = ""; }
        if($list == "listgroups"){ $downloadPercent = (($child['FileSizeMB'] - $child['RemainingSizeMB']) / $child['FileSizeMB']) * 100; $progressBar = "progress-bar-striped active"; }
        if($child['Health'] <= "750"){ 
            $downloadHealth = "danger"; 
        }elseif($child['Health'] <= "900"){ 
            $downloadHealth = "warning"; 
        }elseif($child['Health'] <= "1000"){ 
            $downloadHealth = "success"; 
        }

        $gotNZB .= '<tr>

                        <td>'.$downloadName.'</td>
                        <td>'.$downloadStatus.'</td>
                        <td>'.$downloadCategory.'</td>

                        <td>

                            <div class="progress">

                                <div class="progress-bar progress-bar-'.$downloadHealth.' '.$progressBar.'" role="progressbar" aria-valuenow="'.$downloadPercent.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$downloadPercent.'%">

                                    <p class="text-center">'.round($downloadPercent).'%</p>
                                    <span class="sr-only">'.$downloadPercent.'% Complete</span>

                                </div>

                            </div>

                        </td>

                    </tr>';


    }

    if($i > 0){ echo $gotNZB; }
    if($i == 0){ echo '<tr><td colspan="4"><p class="text-center">No Results</p></td></tr>'; }
    
}
    
if($downloader == "sabnzbd"){
    
    $url = SABNZBDURL;
    $port = SABNZBDPORT;
    $key = SABNZBDKEY;
    
    $urlCheck = stripos($url, "http");

    if ($urlCheck === false) {
        
        $url = "http://" . $url;
    
    }
    
    if($port !== ""){ $url = $url . ":" . $port; }
    
    $address = $url;

    $api = file_get_contents("$url/api?mode=$list&output=json&apikey=$key&limit=40");
                    
    $api = json_decode($api, true);
    
    $i = 0;
    
    $gotNZB = "";
    
    foreach ($api[$list]['slots'] AS $child) {
        
        $i++;
        if($list == "queue"){ $downloadName = $child['filename']; $downloadCategory = $child['cat']; $downloadPercent = (($child['mb'] - $child['mbleft']) / $child['mb']) * 100; $progressBar = "progress-bar-striped active"; } 
        if($list == "history"){ $downloadName = $child['name']; $downloadCategory = $child['category']; $downloadPercent = "100"; $progressBar = ""; }
        $downloadStatus = $child['status'];
        
        if($child['missing'] > "400"){ 
            $downloadHealth = "danger"; 
        }elseif($child['missing'] <= "400"){ 
            $downloadHealth = "warning"; 
        }elseif($child['missing'] <= "200"){ 
            $downloadHealth = "success"; 
        }
        
        if(!isset($child['missing'])){
            $downloadHealth = "success";
        }
        
        
        $gotNZB .= '<tr>

                        <td>'.$downloadName.'</td>
                        <td>'.$downloadStatus.'</td>
                        <td>'.$downloadCategory.'</td>

                        <td>

                            <div class="progress">

                                <div class="progress-bar progress-bar-'.$downloadHealth.' '.$progressBar.'" role="progressbar" aria-valuenow="'.$downloadPercent.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$downloadPercent.'%">

                                    <p class="text-center">'.round($downloadPercent).'%</p>
                                    <span class="sr-only">'.$downloadPercent.'% Complete</span>

                                </div>

                            </div>

                        </td>

                    </tr>';
        
        
    }
    
    if($i > 0){ echo $gotNZB; }
    if($i == 0){ echo '<tr><td colspan="4"><p class="text-center">No Results</p></td></tr>'; }

}

?>