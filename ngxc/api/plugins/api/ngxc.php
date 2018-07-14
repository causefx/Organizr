<?php
if(isset($_POST['data']['plugin'])){
    switch ($_POST['data']['plugin']) {
        case 'ngxc/settings/get':
            if(qualifyRequest(1)){
                $result['status'] = 'success';
                $result['statusText'] = 'success';
                $result['data'] = NGXCGetSettings();
            }else{
                $result['status'] = 'error';
                $result['statusText'] = 'API/Token invalid or not set';
                $result['data'] = null;
            }
            break;
        case 'ngxc/settings/save':
            if(qualifyRequest(1)){
                $save = NGXCWriteConfig();
                if ($save == true) {
                    $result['status'] = 'success';
                    $result['statusText'] = 'success';
                    $result['data'] = true;
                }
                else { 
                    $result['status'] = 'error';
                    $result['statusText'] = 'There was an error with saving the configuration';
                    $result['data'] = null;
                }
            }else{
                $result['status'] = 'error';
                $result['statusText'] = 'API/Token invalid or not set';
                $result['data'] = null;
            }
            break;
        default:
            //DO NOTHING!!
            break;
    }
}
