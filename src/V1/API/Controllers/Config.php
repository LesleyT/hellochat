<?php 
namespace API\Controllers;
class Config {

    private static $instance = null;
    private $config;

    public static function instance(){
        if(is_null(self::$instance)){
            self::$instance = new Config();
            self::$instance->fetch();
        }
        return self::$instance;
    }

    private function fetch(){
        if(!realpath(API_ROOT.'/Config/config.json')){
            throw new \Exception('Config file not found');
            return;
        }
        $file = json_decode(file_get_contents(API_ROOT.'/Config/config.json'));
        if(json_last_error() == JSON_ERROR_NONE){
            self::instance()->config = $file;
            return;
        } else {
            throw new \Exception('Invalid JSON file');
            return;
        }
    }

    public static function get($key = null){
        if(is_null($key)){ return self::instance()->config; }
        
        if(isset(self::instance()->config->{$key})){
            return self::instance()->config->{$key};
        } return false;
    }

}