<?php
namespace API\Controllers\Authenticators;
class Base_Authenticator {

    private $_user;
    private $_role;
    private $_session;

    public function __construct(){

    }
    
    public function isAdmin(){
        return false;
    }

    public function setUser($user) {
        $this->_user = $user;
        return $this;
    }

    public function setRole($role) {
        $this->_role = $role;
        return $this;
    }

    public function setSession($session) {
        $this->_session = $session;
        return $this;
    }
    
    public function getUser() {
        return $this->_user;
    }
    
    public function getRole() {
        return $this->_role;
    }
    
    public function getSession() {
        return $this->_session;
    }

}