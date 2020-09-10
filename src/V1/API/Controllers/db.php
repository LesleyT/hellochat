<?php 
namespace API\Controllers;

class DB {


    private static $writeDBConnection;
    private static $readDBConnection;

    private static function getDatabaseConnector(){
        $config = null;
        if(!defined('HELLO_CHAT_DB_CONNECTOR') && defined('HELLO_CHAT_ROOT')){
            $connector = \API\Controllers\Config::instance()->get('db_connector');
            if($connector != false){
                define('HELLO_CHAT_DB_CONNECTOR', $connector);
            }
        } 

        if(!defined('HELLO_CHAT_DB_CONNECTOR')){ return null; }

        if(HELLO_CHAT_DB_CONNECTOR === 'SilverStripe'){
            return self::getSilverStripeConnector();
        }
        return $config;
    }

    public static function connectWriteDB(){
        if(self::$writeDBConnection === null){
            $config = self::getDatabaseConnector();

            try {
                self::$writeDBConnection = new \PDO($config['server'], $config['username'], $config['password']);
                self::$writeDBConnection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$writeDBConnection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            } catch(\Exception $e){
                die('<h2>Failed to establish a database connection</h2>');
            }
            unset($config);
        }
        return self::$writeDBConnection;
    }

    public static function connectReadDB(){
        if(self::$readDBConnection === null){
            $config = self::getDatabaseConnector();

            try {
                self::$readDBConnection = new \PDO($config['server'], $config['username'], $config['password']);
                self::$readDBConnection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$readDBConnection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            } catch(\Exception $e){
                die('<h2>Failed to establish a database connection</h2>');
            }
            unset($config);
        }
        return self::$readDBConnection;
    }


    private static function getSilverStripeConnector(){
        if(file_exists(dirname(dirname(dirname(HELLO_CHAT_ROOT))).'/.env')){
            $enviornment = file_get_contents(dirname(dirname(dirname(HELLO_CHAT_ROOT))).'/.env');
            $lines = explode("\n", $enviornment);
            $temp = [];
            foreach($lines as &$line){
                $line = explode('=', $line);
                if(isset($line[0]) && isset($line[1])){
                    $temp[$line[0]] = str_replace('"', '',trim($line[1]));
                    
                    if($temp[$line[0]] === ''){ $temp[$line[0]] = null; }
                }
            }
            unset($lines);
            unset($enviornment);

            $config = [
                'server' => "mysql:host={$temp['SS_DATABASE_SERVER']};dbname={$temp['SS_DATABASE_NAME']};utf-8",
                'username' => $temp['SS_DATABASE_USERNAME'],
                'password' => $temp['SS_DATABASE_PASSWORD']
            ];
            unset($temp);
        }
        return $config;
    }

}