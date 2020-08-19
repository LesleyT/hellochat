<?php 
namespace API\Controllers;

class User extends Request {

    public function __construct(){
        parent::__construct();
    }

    public function run(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $this->POST();
        } elseif($_SERVER['REQUEST_METHOD'] == 'GET'){
            if($this->getAction() === 'ping'){
                $this->GET();
            } else {
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage('Request method not allowed');
                $response->send();
                exit;
            }
        } else {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(405);
            $response->setSuccess(false);
            $response->addMessage('Request method not allowed');
            $response->send();
            exit;
        }
    }

    protected function GET(){ 
        $this->validateAuthenticationData();
        $this->setAccessToken($_SERVER['HTTP_AUTHORIZATION']);

        try {
            /* GET FROM DB */
            $id = $this->getEntity();
            $userId = Authenticator::instance()->getUser();
            
            if($id != $userId){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('You cannot ping another user');
                $response->send();
                exit;
            }


            /* GET MESSAGES FROM CONVERSATION */
                
            $query = $this->getReadDB()->prepare('SELECT id, fullname, username FROM gdhc_users WHERE id = :userId');
            $query->bindParam(':userId', $id, \PDO::PARAM_INT);
            $query->execute();
            
            $rowCount = $query->rowCount();

            /* BUILD RESPONS */
            $userArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $userArray[] = [
                    'id' => $row['id'],
                    'name' => $row['fullname'],
                    'email' => $row['username'],
                ];
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['users'] = $userArray;
            
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(false);
            $response->setData($returnData);
            $response->send();
            exit;
        } catch(\API\Models\Exceptions\ConversationException $e) {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($e->getMessage());
            $response->send();
            exit;
        } catch(\PDOException $e) {
            error_log("Database query error - " . $e, 0);
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get User');
            $response->send();
            exit;
        }
    }

    protected function POST(){
        $this->validateRequestData();
        $jsonData = $this->fetchBody();
        
        $this->validateData($jsonData);
        $returnData = $this->createUser($jsonData);

        if($returnData){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(201);
            $response->setSuccess(true);
            $response->addMessage('User created');
            $response->setData($returnData);
            $response->send();
        } else {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue creating a user account - please try again');
            $response->send();
            exit;
        }
    }

    protected function PATCH(){ }

    protected function DELETE(){ }

    protected function OPTIONS(){ }

    private function createUser(&$jsonData){
        $fullname = trim($jsonData->fullname);
        $username = trim($jsonData->username);
        $password = $jsonData->password;
        $role = (isset($jsonData->role)) ? $jsonData->role : null;
        if($role !== null && \API\Controllers\Authenticator::instance()->isAdmin(true) === false){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage('You don\'t have access');
            $response->send();
            exit;
        }

        if(strtoupper($role) === 'ADMIN' && \API\Controllers\Authenticator::instance()->isAdmin(true) === true){
            try {
                $username = Authenticator::instance()->prepareToUseUsername($username);
            } catch(\Exception $e){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('There was an issue creating a user account - please try again');
                $response->send();
                exit;
            }
        }

        try {
            if($this->userExists($username)){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(409);
                $response->setSuccess(false);
                $response->addMessage('Username already exists');
                $response->send();
                exit;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = $this->getWriteDB()->prepare('insert into gdhc_users (fullname, username, password, role) VALUES (:fullname, :username, :password, :role)');
            $query->bindParam(':fullname', $fullname, \PDO::PARAM_STR);
            $query->bindParam(':username', $username, \PDO::PARAM_STR);
            $query->bindParam(':password', $hashed_password, \PDO::PARAM_STR);
            
            if($role === null){ 
                $role = 'GEBRUIKER'; 
            } else { $role = strtoupper($role); }

            $query->bindParam(':role', $role, \PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();
            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('There was an issue creating a user account - please try again');
                $response->send();
                exit;
            }

            $lastUserId = $this->getWriteDB()->lastInsertId();

            $returnData = [];
            $returnData['id'] = $lastUserId;
            $returnData['fullname'] = $fullname;
            $returnData['username'] = Authenticator::instance()->prepareToSendUsername($username);
            return $returnData;
        } catch(\PDOException $e){
            error_log("Connection error - " . $e, 0);
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue creating a user account - please try again');
            $response->send();
            exit;
        }
        return false;
    }
    
    private function userExists(&$username){
        try {
            $query = $this->getWriteDB()->prepare('SELECT id FROM gdhc_users WHERE username = :username');
            $query->bindParam(':username', $username, \PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            return ($rowCount !== 0) ? true : false;
        } catch(\PDOException $e){
            error_log("Connection error - " . $e, 0);
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue creating a user account - please try again');
            $response->send();
            exit;
        }
    }

    private function validateData(&$jsonData){
        if(isset($jsonData->username) || !isset($jsonData->password)){
            $jsonData->password = $this->fetchPasswordFromData($jsonData);
        }

        if(!isset($jsonData->fullname) || !isset($jsonData->username) || !isset($jsonData->password)){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            if(!isset($jsonData->fullname)){
                $response->addMessage('Full name not supplied');
            } if(!isset($jsonData->username)){
                $response->addMessage('Username not supplied');
            } if(!isset($jsonData->password)){
                $response->addMessage('Password not supplied');
            }
            $response->send();
            exit;
        }

        if(strlen($jsonData->fullname) <= 0 || strlen($jsonData->fullname) > 100 || strlen($jsonData->username) <= 0 || strlen($jsonData->username) > 255 || strlen($jsonData->password) <= 0 || strlen($jsonData->password) > 255){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            if(strlen($jsonData->fullname) <= 0){
                $response->addMessage('Full name cannot be empty supplied');
            } elseif(strlen($jsonData->fullname) > 100){
                $response->addMessage('Full name cannot be greater than 100 characters');
            } 
        
            if(strlen($jsonData->username) <= 0){
                $response->addMessage('Username cannot be empty supplied');
            } elseif(strlen($jsonData->username) > 255){
                $response->addMessage('Username cannot be greater than 255 characters');
            } 
        
            if(strlen($jsonData->password) <= 0){
                $response->addMessage('Password cannot be empty supplied');
            } elseif(strlen($jsonData->password) > 255){
                $response->addMessage('Password cannot be greater than 100 characters');
            } 
            $response->send();
            exit;
        }
    }

    private function fetchPasswordFromData(&$jsonData){
        return AUTH_SALT.Authenticator::instance()->prepareToUseUsername($jsonData->username);
    }

}