<?php

define("CHATDB", "chat.db");

if (!file_exists(CHATDB)) {
    
    try{
        $db = new PDO('sqlite:'.CHATDB);

        $sql ="
          CREATE TABLE IF NOT EXISTS MESSAGES 
          (ID INTEGER PRIMARY KEY AUTOINCREMENT,
          USER TEXT NOT NULL,
          EMAIL TEXT NOT NULL,
          MESSAGE TEXT NOT NULL,
          TIME TIMESTAMP NOT NULL DEFAULT((julianday('now') - 2440587.5)*86400.0))";

        $ret = $db->exec($sql);
        sleep(0.5);
        $ret = $db->exec('PRAGMA journal_mode = wal;');
        sleep(0.5);
        

        header("Refresh:0");
    }
    catch(PDOException $e){
       die('Failed to execute query:'. $e->getMessage());
    }

   $db=null;
    
}elseif(file_exists(CHATDB)) {
    
   try{
        $db = new PDO('sqlite:'.CHATDB);
    }
    catch(PDOException $e){
        die('Failed to connect:'. $e->getMessage());
    }
}

//check to see if the ajax call was to update db

if (isset($_POST['text'])){

    $msg=$_POST['text'];
    $us=$_POST['user'];
    $email=$_POST['email'];

    $sql ="INSERT INTO MESSAGES (USER,MESSAGE,EMAIL) VALUES(?, ?, ?)";

    try{
        $ret = $db->prepare($sql);
        $ret->execute([$us,$msg, $email]);
    }
    catch(PDOException $e){
        console.log('Failed to update db:'. $e->getMessage());
    }
}else{
    //the script will run for 20 seconds after the initial ajax call
    $time=time()+20;

    while(time()<$time){
        if ($_POST['time']){
            $prevtime=$_POST['time'];
        }
        else {
            $prevtime=0;
        }
        //query to see if there are new messages

        $sql ="SELECT TIME,USER,MESSAGE,EMAIL FROM MESSAGES WHERE TIME>? ORDER BY TIME ASC";

        try{
            $ret = $db->prepare($sql);
            $ret->execute([$prevtime]);

            $resarr = $ret->fetchAll(PDO::FETCH_ASSOC);

            //if there are no new messages in the db, sleep for half a second and then run loop again
            if (!$resarr)
                sleep(0.5);
            else{
                echo json_encode($resarr);
                break;
            }
        }
        catch(PDOException $e){
            console.log('Failed to get messages:'. $e->getMessage());
        }
    }
}

$db=null;

?>