<?php 

namespace API\Controllers\Authenticators;
class Local_Authenticator extends Base_Authenticator {

    public function __construct(){
        parent::__construct();
    }

    /* DO CHECK BASED ON ACTUALL FRAMEWORK USED */
    public function isAdmin(){
        return [
            'username' => 'lesley@hellogoodday.nl',
            'name' => 'Lesley Taihitu'
        ];
    }


}