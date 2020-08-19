<?php
namespace API\Controllers;
class Authenticator {
    
    private static $_instance = null;
    private $authenticator;

    private $_supported = ['Base_Authenticator' => true, 'Local_Authenticator' => true];

    public static function instance(){
        if(self::$_instance === null){
            self::$_instance = new \API\Controllers\Authenticator();
        }
        return self::$_instance;
    }

    public function init($authenticator){
        if(isset($this->_supported[$authenticator]) && $this->_supported[$authenticator] === true){
            $authenticator = "\\API\\Controllers\\Authenticators\\{$authenticator}";
            $this->authenticator = new $authenticator();
        }
    }

    public function prepareToSendUsername($username){
        return \API\Controllers\UnsafeCrypto::encrypt($username, AUTH_SALT, true);
    }

    public function prepareToUseUsername($username){
        try {
            return \API\Controllers\UnsafeCrypto::decrypt($username, AUTH_SALT, true);
        } catch (\Exception $e){
            return $username;
        }
    }

    public function storeRefreshToken($refreshtoken, $expiry, $prefix = ''){
        $prefix = (($prefix == '') ? '' : ($prefix. '_'));
        if(PHP_VERSION_ID < 70300) { 
            setcookie(
                $prefix.'refresh_token_auth',
                \API\Controllers\UnsafeCrypto::encrypt($refreshtoken, AUTH_SALT, true),
                time()+$expiry,
                \API\HC_API::instance()->getPath().'; samesite=strict',
                \API\HC_API::instance()->getDomain(),
                \API\HC_API::instance()->getSecure() === true ? true : false, // Only send cookie over HTTPS, never unencrypted HTTP
                TRUE  // Don't expose the cookie to JavaScript
            );

        } else {
            setcookie($prefix.'refresh_token_auth', 
                \API\Controllers\UnsafeCrypto::encrypt($refreshtoken, AUTH_SALT, true), [
                'expires' => time()+$expiry,
                'path' => \API\HC_API::instance()->getPath(),
                'domain' => \API\HC_API::instance()->getDomain(),
                'secure' => \API\HC_API::instance()->getSecure() === true ? true : false,
                'httponly' => true,
                'samesite' => 'strict',
            ]);
        }
    }

    public function retrieveRefreshToken($prefix = ''){
        $prefix = (($prefix == '') ? '' : ($prefix. '_'));
        return \API\Controllers\UnsafeCrypto::decrypt($_COOKIE[$prefix.'refresh_token_auth'], AUTH_SALT, true);
    }
    
    public function canDo($action){
        $this->authenticator->canDo($action);
    }

    public function isAdmin($returnBool = false){
        $result = $this->authenticator->isAdmin();
        if($returnBool === true){
            return ($result === false) ? false : true;
        }

        if($result === false){    
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage('You don\'t have access');
            $response->send();
            exit;
        } else {
            $result['username'] = $this->prepareToSendUsername($result['username']);            
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setData($result);
            $response->send();
            exit;
        }
    }

    public function setUser($user) {
        $this->authenticator->setUser($user);
        return $this;
    }

    public function setSession($session) {
        $this->authenticator->setSession($session);
        return $this;
    }

    public function setRole($role) {
        $this->authenticator->setRole($role);
        $this->_role = $role;
        return $this;
    }
    
    public function getUser() {
        return $this->authenticator->getUser();
    }
    
    public function getRole() {
        return $this->authenticator->getRole();
    }
    
    public function getSession() {
        return $this->authenticator->getSession();
    }


}