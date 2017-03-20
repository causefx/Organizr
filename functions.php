<?php

function registration_callback($username, $email, $userdir){
    
    global $data;
    
    $data = array($username, $email, $userdir);

}

function printArray($arrayName){
    
    $messageCount = count($arrayName);
    
    $i = 0;
    
    foreach ( $arrayName as $item ) :
    
        $i++; 
    
        if($i < $messageCount) :
    
            echo "<small class='text-uppercase'>" . $item . "</small> & ";
    
        elseif($i = $messageCount) :
    
            echo "<small class='text-uppercase'>" . $item . "</small>";
    
        endif;
        
    endforeach;
    
}

function write_ini_file($content, $path) { 
    
    if (!$handle = fopen($path, 'w')) {
        
        return false; 
    
    }
    
    $success = fwrite($handle, trim($content));
    
    fclose($handle); 
    
    return $success; 

}

function getTimezone(){

    $regions = array(
        'Africa' => DateTimeZone::AFRICA,
        'America' => DateTimeZone::AMERICA,
        'Antarctica' => DateTimeZone::ANTARCTICA,
        'Arctic' => DateTimeZone::ARCTIC,
        'Asia' => DateTimeZone::ASIA,
        'Atlantic' => DateTimeZone::ATLANTIC,
        'Australia' => DateTimeZone::AUSTRALIA,
        'Europe' => DateTimeZone::EUROPE,
        'Indian' => DateTimeZone::INDIAN,
        'Pacific' => DateTimeZone::PACIFIC
    );
    
    $timezones = array();

    foreach ($regions as $name => $mask) {
        
        $zones = DateTimeZone::listIdentifiers($mask);

        foreach($zones as $timezone) {

            $time = new DateTime(NULL, new DateTimeZone($timezone));

            $ampm = $time->format('H') > 12 ? ' ('. $time->format('g:i a'). ')' : '';

            $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1) . ' - ' . $time->format('H:i') . $ampm;

        }
        
    }   
    
    print '<select name="timezone" id="timezone" class="form-control material" required>';
    
    foreach($timezones as $region => $list) {
    
        print '<optgroup label="' . $region . '">' . "\n";
    
        foreach($list as $timezone => $name) {
            
            print '<option value="' . $timezone . '">' . $name . '</option>' . "\n";
    
        }
    
        print '</optgroup>' . "\n";
    
    }
    
    print '</select>';
    
}

function explosion($string, $position){
    
    $getWord = explode("|", $string);
    return $getWord[$position];
    
}

function getServerPath() {
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') { 
        
        $protocol = "https://"; 
    
    } else {  
        
        $protocol = "http://"; 
    
    }
    
    return $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
      
}

function get_browser_name() {
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
    
    return 'Other';
    
}

function getPlexRecent($url, $port, $type, $token, $size, $header){
    
    $address = $url.":".$port;
    
    $api = simplexml_load_file($address."/library/recentlyAdded?X-Plex-Token=".$token);
    
    $i = 0;
    
    $gotPlex = '<div class="col-lg-'.$size.'"><h5 class="text-center">'.$header.'</h5><div id="carousel-'.$type.'" class="carousel slide box-shadow white-bg" data-ride="carousel"><div class="carousel-inner" role="listbox">';
        
    foreach($api AS $child) {
     
        if($child['type'] == $type){
            
            $i++;
            
            if($i == 1){ $active = "active"; }else{ $active = "";}
            
            $thumb = $child['thumb'];
            if($type == "movie"){ 
                
                $title = $child['title']; 
                $summary = $child['summary'];
            
            }elseif($type == "season"){ 
                
                $title = $child['parentTitle'];
                $summary = $child['parentSummary'];
            
            }elseif($type == "album"){
                
                $title = $child['parentTitle']; 
                $summary = $child['title'];
            
            }
            
            
            $gotPlex .= '<div class="item '.$active.'"><img class="carousel-image '.$type.'" src="image.php?img='.$address.$thumb.'"><div class="carousel-caption '.$type.'" style="overflow:auto"><h4>'.$title.'</h4><small><em>'.$summary.'</em></small></div></div>';

        }
        
    }
    
    $gotPlex .= '</div>';
    
    if ($i > 1){ 

        $gotPlex .= '<a class="left carousel-control '.$type.'" href="#carousel-'.$type.'" role="button" data-slide="prev"><span class="fa fa-chevron-left" aria-hidden="true"></span><span class="sr-only">Previous</span></a><a class="right carousel-control '.$type.'" href="#carousel-'.$type.'" role="button" data-slide="next"><span class="fa fa-chevron-right" aria-hidden="true"></span><span class="sr-only">Next</span></a>';
        
    }

    $gotPlex .= '</div>';

    $gotPlex .= '</div>';
    
    if ($i != 0){ return $gotPlex; }

}

function getPlexStreams($url, $port, $token, $size){
    
    $address = $url.":".$port;
    
    $api = simplexml_load_file($address."/status/sessions?X-Plex-Token=".$token);
    
    $i = 0;
    
    $gotPlex = '<div class="col-lg-'.$size.'">';
    $gotPlex .= '<div id="carousel-streams" class="carousel slide box-shadow white-bg" data-ride="carousel">';
    $gotPlex .= '<div class="carousel-inner" role="listbox">';
        
    foreach($api AS $child) {
     
        $type = $child['type'];
            
        $i++;

        if($i == 1){ $active = "active"; }else{ $active = "";}

        
        if($type == "movie"){ 

            $title = $child['title']; 
            $summary = $child['summary'];
            $thumb = $child['thumb'];
            $image = "movie";

        }elseif($type == "episode"){ 

            $title = $child['grandparentTitle'];
            $summary = $child['summary'];
            $thumb = $child['parentThumb'];
            $image = "season";


        }elseif($type == "track"){

            $title = $child['grandparentTitle'] . " - " . $child['parentTitle']; 
            $summary = $child['title'];
            $thumb = $child['thumb'];
            $image = "album";

        }

        $gotPlex .= '<div class="item '.$active.'">';

        $gotPlex .= "<img class='carousel-image $image' src='image.php?img=$address$thumb'>";

        $gotPlex .= '<div class="carousel-caption '. $image . '" style="overflow:auto">';

        $gotPlex .= '<h4>'.$title.'</h4>';

        $gotPlex .= '<small><em>'.$summary.'</em></small>';

        $gotPlex .= '</div>';

        $gotPlex .= '</div>';

        
    }
    
    $gotPlex .= '</div>';
    
    if ($i > 1){ 

        $gotPlex .= '<a class="left carousel-control streams" href="#carousel-streams" role="button" data-slide="prev">';

        $gotPlex .= '<span class="fa fa-chevron-left" aria-hidden="true"></span>';
        $gotPlex .= '<span class="sr-only">Previous</span>';

        $gotPlex .= '</a>';

        $gotPlex .= '<a class="right carousel-control streams" href="#carousel-streams" role="button" data-slide="next">';

        $gotPlex .= '<span class="fa fa-chevron-right" aria-hidden="true"></span>';
        $gotPlex .= '<span class="sr-only">Next</span>';

        $gotPlex .= '</a>';
        
    }

    $gotPlex .= '</div>';

    $gotPlex .= '</div>';
    
    if ($i != 0){ return $gotPlex; }

}

?>