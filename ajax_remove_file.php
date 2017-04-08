<?php
if(isset($_POST['file'])){
    $file = 'images/' . $_POST['file'];
    if(file_exists($file)){
        unlink($file);
    }
}
?>
