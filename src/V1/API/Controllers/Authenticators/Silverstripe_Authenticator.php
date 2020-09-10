<?php 

namespace API\Controllers\Authenticators;
class Silverstripe_Authenticator extends Base_Authenticator {

    public function __construct(){
        parent::__construct();
    }

    public function isAdmin(){
        if(!isset($_COOKIE['hc_ss'])){ return false; }
        $data = unserialize($_COOKIE['hc_ss']);
        
        if(!is_array($data)){ return false; }
        if(!isset($data['mail']) && strlen($data['mail']) <= 0){ return false; }
        if(!isset($data['firstname']) && strlen($data['firstname']) <= 0){ return false; }

        return [
            'username' => $data['mail'],
            'name' => $data['firstname']
        ];
    }


}