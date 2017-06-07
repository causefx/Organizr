<?php

$data = $_POST["datavars"];
$dataarray = explode("###", $data);
$user = $dataarray[0];
$avatar = $dataarray[1];
$imagename = $_FILES["image"]["name"];
$size = $_FILES["image"]["size"];
$tempname = $_FILES["image"]["tmp_name"];
$type = $_FILES["image"]["type"];
$endtemp = explode(".", $_FILES["image"]["name"]);
$ending = end($endtemp);

// unique image filename
                
$unique = md5($imagename . $tempname . time());
$filename = $unique . "." . $ending;
                
// thumbnail

$thumbname = $unique . "t" . "." . $ending;
            
// path

$uploaddir = "../uploads";
$uploaddirexists = false;

if( !is_dir($uploaddir) )  // check if upload directory exists
{
    if( mkdir($uploaddir, 0705, true) )  // create upload directory
    {
        $uploaddirexists = true;
    }
}
else
{
    $uploaddirexists = true;
}

$path = "../uploads/" . $filename;
$thumbpath = "../uploads/" . $thumbname;

// upload

if( strlen($user) > 0 && strlen($imagename) > 0 && $size > 0 && $uploaddirexists )
{
    if( ($type == "image/gif") || ($type == "image/jpeg") || ($type == "image/jpg") || ($type == "image/png") )
    {   
        if( $size < 5000000 )
        {   
            if( $_FILES["image"]["error"] == 0 )
            {   
                if( !file_exists($path) )
                {   
                    if( copy($tempname, $path) )  // upload image
                    {   
                        // thumbnail	

                        $sizedata = getimagesize($tempname);

                        if( $type == "image/gif" )
                        {
                            $imagetoupload = @imagecreatefromgif($tempname);
                        }
                        elseif( $type == "image/jpeg" || $type == "image/jpg" )
                        {   
                            $imagetoupload = @imagecreatefromjpeg($tempname);
                        }
                        elseif( $type == "image/png" )
                        {
                            $imagetoupload = @imagecreatefrompng($tempname);
                        }

                        if( $imagetoupload )  // imagecreatefromX
                        {
                            $width = imagesx($imagetoupload);
                            $height = imagesy($imagetoupload);
                            $div = $width / $height;
                            $newwidth = 150;
                            $newheight = 150 / $div;

                            $newimage = @imageCreateTrueColor($newwidth, $newheight);

                            if( $newimage )  // imagecreatetruecolor
                            {   
                                // upload thumbnail

                                $imagecopy = @imagecopyresized($newimage, $imagetoupload, 0, 0, 0, 0,
                                                               $newwidth, $newheight, $sizedata[0], $sizedata[1]);

                                if( $imagecopy )  // imagecopyresized
                                {   
                                    if( $type == "image/gif" )
                                    {
                                        $img = @imagegif($newimage, $thumbpath);
                                    }
                                    elseif( $type == "image/jpeg" || $type1 == "image/jpg" )
                                    {
                                        $img = @imagejpeg($newimage, $thumbpath);
                                    }
                                    elseif( $type == "image/png" )
                                    {
                                        $img = @imagepng($newimage, $thumbpath);
                                    }

                                    if( $img )  // imageX
                                    {   
                                        @imagedestroy($newimage);

                                        // db entry

                                        include("connect.php");
                                        
                                        $timestamp = time();
                                        $message = "specialcharimg" . $thumbname;

                                        if( !$db->exec("INSERT INTO chatpack_log (timestamp, user, avatar, message)
                                                        VALUES ('$timestamp', '$user', '$avatar', '$message')") )
                                        {
                                            cleanup($path, $thumbpath, $filename);  // clean up on error
                                        }

                                        $db->close();
                                    }
                                }
                            }
                        }
                    }
                    else  // error upload
                    {
                        cleanup($path, $thumbpath, $filename);
                    }
                }
                else  // error exists
                {
                    cleanup($path, $thumbpath, $filename);
                }
            }
        }
        else  // error size
        {
            cleanup($path, $thumbpath, $filename);
        }
    }
    else  // error type
    {
        cleanup($path, $thumbpath, $filename);
    }
}

function cleanup($path, $thumbpath, $filename)
{	
    // delete image

    if( file_exists($path) )
    {
        unlink($path);
    }

    // delete thumbnail

    if( file_exists($thumbpath) )
    {
        unlink($thumbpath);
    }
    
    // delete db entry
    
    include("connect.php");
    
    $message = "specialcharimg" . $thumbname;
    $db->exec("DELETE FROM chatpack_log WHERE message='$message'");

    $db->close();
}

?>