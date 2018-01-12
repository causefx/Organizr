<?php
if(isset($_POST['data']['plugin'])){
    switch ($_POST['data']['plugin']) {
        case 'PHPMailer/settings/get':
            if(qualifyRequest(1)){
                $result['status'] = 'success';
                $result['statusText'] = 'success';
                $result['data'] = phpmGetSettings();
            }else{
                $result['status'] = 'error';
                $result['statusText'] = 'API/Token invalid or not set';
                $result['data'] = null;
            }
            break;
        case 'PHPMailer/send/test':
            if(qualifyRequest(1)){
                $result['status'] = 'success';
                $result['statusText'] = 'success';
                $result['data'] = phpmSendTestEmail();
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
