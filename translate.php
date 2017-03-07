<?php

class setLanguage { 
    
    private $language = null; 
    
    function __construct($language) {
        
        if (file_exists("lang/{$language}.ini")) : 
        
            $this->language = parse_ini_file("lang/{$language}.ini", false, INI_SCANNER_RAW);
        
        else : 
        
            $this->language = parse_ini_file("lang/en.ini", false, INI_SCANNER_RAW);
        
        endif;
        
    } 
    
    public function translate($originalWord) {
        
        $getArg = func_num_args();
        
        if ($getArg > 1) : 
        
            $allWords = func_get_args();
        
            array_shift($allWords); 
        
        else :
        
            $allWords = array(); 
        
        endif;

        $translatedWord = isset($this->language[$originalWord]) ? $this->language[$originalWord] : null; 
    
        if (!$translatedWord) : 
        
            echo ("Translation not found for: $originalWord"); 
        
        endif; 

        $translatedWord = htmlspecialchars($translatedWord, ENT_QUOTES);
        
        return vsprintf($translatedWord, $allWords);
        
    }
    
} 

$getLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : "en"; 
$language = new setLanguage($getLanguage);

?>