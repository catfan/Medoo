<?php

if(!function_exists('config')){
    function config($key, $default = null,$type = 'database'){
        $config = \Symfony\Component\Yaml\Yaml::parse(
            file_get_contents(__DIR__.'/../env.yml')
        );
        if(!empty($config[$type][$key])){
            return $config[$type][$key];
        } else {
            return $default;
        }
    }
}

if(!function_exists('str_rand')){
    function str_rand($lengh = 10){
        $characters = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $lengh; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return (String)$randomString;
    }
}

if(!function_exists('bcrypt')){
    function bcrypt($password){
        $HashingPass = "$2y$11$"."n3i0TKQ6eUaNXPXnQhkpL9";
        $hashed = crypt($password,$HashingPass);
        return (String)$hashed;
    }
}

if(!function_exists('getMigrations')){
    function getMigrations(){
        $Migrations = scandir(__DIR__."/../database/migrations/");
        $i = 0;
        while ($i <= count($Migrations) - 1){
            if($Migrations[$i] == '.' || $Migrations[$i] == '..'){
                unset($Migrations[$i]);
            }
            $i++;
        }
        sort($Migrations);
        $b = 0; $classes = [];
        while ($b <= count($Migrations) - 1){
            $file = file_get_contents(__DIR__."/../database/migrations/".$Migrations[$b]);
            $length = strpos($file,"class ");
            $fileAnalyts = substr($file,$length);
            $findcol = strpos($fileAnalyts,"extends");
            $class = substr($fileAnalyts,0,$findcol);
            $className = str_replace('class ','',trim($class));
            array_push($classes,trim($className));
            $b++;
        }
        return $classes;
    }
}