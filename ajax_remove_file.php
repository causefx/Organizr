<?php
if(isset($_POST['file'])){
    $file = 'icons/' . $_POST['file'];
    if(file_exists($file)){
        unlink($file);
    }
}
?>
