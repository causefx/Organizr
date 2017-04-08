<?php
require_once("user.php");

$errors         = array();
$data           = array();

    if (empty($_POST['registerPasswordValue']))
        $errors['registerPasswordValue'] = 'Password is required.';
    
    if ($_POST['registerPasswordValue'] != REGISTERPASSWORD)
        $errors['registerPasswordValue'] = 'Password does not match.';

    if ( ! empty($errors)) {

        $data['success'] = false;
        $data['errors']  = $errors;
        
    } else {

        $data['success'] = true;
        $data['message'] = 'Success!';
        
    }

    echo json_encode($data);

?>