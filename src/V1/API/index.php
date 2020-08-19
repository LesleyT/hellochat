<?php 

namespace API;

define('API_ROOT', dirname(__FILE__));

include dirname(__DIR__).'/autoload.php';

class HC_API {

    private static $_instance = null;
    private $config;

    private $domain;
    private $secure;

    public static function instance (){
        if(self::$_instance === null){
            self::$_instance = new HC_API();
        }
        return self::$_instance;
    }

    public function __construct(){
        $this->config = \API\Controllers\Config::instance();

        $this->domain = $this->config->get('domain');
        $this->path = $this->config->get('path');
        $this->secure = $this->config->get('secure');
        define('AUTH_SALT', $this->config->get('salt'));

        //checks if a routes exist in config outerwise throw error
        if(!$this->config->get('routes')){        
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('No routes could be found');
            $response->send();
            exit;
        }

        $this->router = new \API\Controllers\Router($this->config->get('routes'));
        $this->router->addRoute('authorization', '/');
    }
    

    public function run(){
        if(!isset($_GET['module']) || strlen($_GET['module']) <= 0){        
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            if(!isset($_GET['module'])){
                $response->addMessage('No module supplied to request');
            } if(strlen($_GET['module']) <= 0){
                $response->addMessage('Module cannot be blank');
            }
            $response->send();
            exit;
        }

        $module = $_GET['module'];
        if(!$this->router->routeExist($module)){        
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage('Could not find route to supplied module');
            $response->send();
            exit;
        }
        
        \API\Controllers\Authenticator::instance()->init($this->config->get('authenticator'));
        if($module === 'authorization'){
            \API\Controllers\Authenticator::instance()->isAdmin();
        }
        
        $module = $this->router->getRoute($module);
        $module = new $module();

        if(isset($_GET['collectionId']) && is_numeric($_GET['collectionId'])){
            $module->setCollection($_GET['collectionId']);
        } 
        if(isset($_GET['entityId']) && is_numeric($_GET['entityId'])){
            $module->setEntity($_GET['entityId']);
        }
        if(isset($_GET['action']) && !is_string($_GET['action'] && strlen($_GET['action']) > 0)){
            $module->setAction($_GET['action']);
        }
        $module->run();
    }

    public function getDomain(){
        return $this->domain;
    }

    public function getSecure(){
        return $this->secure;
    }

    public function getPath(){
        return $this->path;
    }

}
HC_API::instance()->run();