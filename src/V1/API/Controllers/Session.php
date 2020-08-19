<?php 
namespace API\Controllers;

class Session extends Request {

    public function __construct(){
        parent::__construct();
    }

    public function run(){
        if($this->getEntity() !== null){
            $this->validateRequestHeader();
            
            $this->setAccessToken($_SERVER['HTTP_AUTHORIZATION']);

            if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
                $this->DELETE();
            } elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){
                $this->PATCH();
            } else {
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage('Request method not allowed');
                $response->send();
                exit;
            }
        } elseif($this->getEntity() === null) {
            if($_SERVER['REQUEST_METHOD'] !== 'POST'){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage('Request method not allowed');
                $response->send();
                exit;
            }

            $this->POST();
            
        } else {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage('Endpoint not found');
            $response->send();
            exit;
        }
    }

    protected function POST(){
        sleep(1);

        $this->validateRequestData();
        $jsonData = $this->fetchBody();
        if(isset($jsonData->username)){
            $jsonData->username = Authenticator::instance()->prepareToUseUsername($jsonData->username);
        }

        $this->validateCredentialsInput($jsonData);
        $data = $this->validateUserCredentials($jsonData);

        $this->createSession($data);
    }

    protected function GET() {}

    protected function PATCH(){
        $this->validateRequestData();
        $jsonData = $this->fetchBody();

        $sessionId = $this->getEntity();
        $accessToken = $this->getAccessToken();

        $query = $this->getWriteDB()->prepare('SELECT gdhc_sessions.userid as userId FROM gdhc_sessions, gdhc_users WHERE gdhc_sessions.userid = gdhc_users.id AND gdhc_sessions.id = :sessionId AND gdhc_sessions.accesstoken = :accesstoken');
        $query->bindParam(':sessionId', $sessionId, \PDO::PARAM_INT);
        $query->bindParam(':accesstoken', $accessToken, \PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage('No refresh token has been provided');
            $response->send();
            exit;
        }

        $row = $query->fetch(\PDO::FETCH_ASSOC);
        Authenticator::instance()->setUser($row['userId']);

        if(isset($_COOKIE[Authenticator::instance()->getUser().'_refresh_token_auth'])){
            $jsonData->refresh_token = Authenticator::instance()->retrieveRefreshToken(Authenticator::instance()->getUser());
        } else {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('No refresh token has been provided');
            $response->setData(Authenticator::instance()->getUser().'_refresh_token_auth');
            $response->send();
            exit;
        }

        $this->refreshSession($jsonData);
    }

    protected function DELETE(){
        try {
            $userId = $this->getEntity();
            $accessToken = $this->getAccessToken();

            $query = $this->getWriteDB()->prepare('DELETE FROM gdhc_sessions WHERE id = :sessionid AND accesstoken = :accesstoken');
            $query->bindParam(':sessionid', $userId, \PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $accessToken, \PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage('Failed to log out of this session using access token provided');
                $response->send();
                exit;
            }

            $returnData = [];
            $returnData['session_id']= intval($this->getEntity());

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Logged out');
            $response->setData($returnData);
            $response->send();
            exit;

        } catch(\PDOException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue logging out - please try again');
            $response->send();
            exit;
        }
    }

    protected function OPTIONS() {}

    private function createSession(&$data){
        try {
            $returned_id = $data['returned_id'];
            $accesstoken = $data['accesstoken'];
            $refreshtoken = $data['refreshtoken'];
            $username = $data['username'];
            $access_token_expiry_seconds = $data['access_token_expiry_seconds'];
            $refresh_token_expiry_seconds = $data['refresh_token_expiry_seconds'];

            $this->getWriteDB()->beginTransaction();

            $query = $this->getWriteDB()->prepare('UPDATE gdhc_users SET loginattempts = 0 WHERE id = :id');
            $query->bindParam(':id', $returned_id, \PDO::PARAM_INT);
            $query->execute();

            $query = $this->getWriteDB()->prepare('INSERT INTO gdhc_sessions (userid, accesstoken, accesstokenexpiry, refreshtoken, refreshtokenexpiry) VALUES(:userid, :accesstoken, date_add(NOW(), INTERVAL :accesstokenexpireysecconds SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpireysecconds SECOND))');
            $query->bindParam(':userid', $returned_id, \PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $accesstoken, \PDO::PARAM_STR);
            $query->bindParam(':accesstokenexpireysecconds', $access_token_expiry_seconds, \PDO::PARAM_INT);
            $query->bindParam(':refreshtoken', $refreshtoken, \PDO::PARAM_STR);
            $query->bindParam(':refreshtokenexpireysecconds', $refresh_token_expiry_seconds, \PDO::PARAM_INT);
            $query->execute();

            $lastSessionId = $this->getWriteDB()->lastInsertId();

            $this->getWriteDB()->commit();

            $returnData = [];
            $returnData['user_id'] = intval($returned_id);
            $returnData['user'] = Authenticator::instance()->prepareToSendUsername($username);
            $returnData['session_id'] = intval($lastSessionId);
            $returnData['accesstoken'] = $accesstoken;
            $returnData['access_token_expires_in'] = $access_token_expiry_seconds;
            // $returnData['refreshtoken'] = $refreshtoken;
            $returnData['refresh_token_expires_in'] = $refresh_token_expiry_seconds;

            Authenticator::storeRefreshToken($refreshtoken, $refresh_token_expiry_seconds, $returned_id);

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(201);
            $response->setSuccess(true);
            $response->setData($returnData);
            $response->send();
            exit;

        } catch(\PDOException $e){
            $this->getWriteDB()->rollback();

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('An issue occured logging in - please try again');
            $response->send();
            exit;
        }
    }

    private function refreshSession(&$jsonData){
        $this->validateRefreshToken($jsonData->refresh_token);

        try {
            /* START USER SESSION VALIDATION */
            /* Validates if session & tokens belong to user */
            $this->setRefreshToken($jsonData->refresh_token);

            $sessionId = $this->getEntity();
            $accessToken = $this->getAccessToken();
            $refreshToken = $this->getRefreshToken();

            $query = $this->getWriteDB()->prepare('SELECT gdhc_sessions.id as sessionId, gdhc_sessions.userid as userId, accesstoken, refreshtoken, useractive, loginattempts, accesstokenexpiry, refreshtokenexpiry FROM gdhc_sessions, gdhc_users WHERE gdhc_sessions.userid = gdhc_users.id AND gdhc_sessions.id = :sessionId AND gdhc_sessions.accesstoken = :accesstoken AND gdhc_sessions.refreshtoken = :refreshtoken');
            $query->bindParam(':sessionId', $sessionId, \PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $accessToken, \PDO::PARAM_STR);
            $query->bindParam(':refreshtoken', $refreshToken , \PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('Access token or refresh token is incorrect for session id');
                $response->send();
                exit;
            }

            $row = $query->fetch(\PDO::FETCH_ASSOC);
            
            $returned_sessionId = $row['sessionId'];
            $returned_userId = $row['userId'];
            $returned_accesstoken = $row['accesstoken'];
            $returned_refreshtoken = $row['refreshtoken'];
            $returned_useractive = $row['useractive'];
            $returned_loginattempts = $row['loginattempts'];
            $returned_accesstokenexpiry = $row['accesstokenexpiry'];
            $returned_refreshtokenexpiry = $row['refreshtokenexpiry'];

            if($returned_useractive !== 'Y'){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('User account is not active');
                $response->send();
                exit;
            }
            
            if($returned_loginattempts >= 3){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('User account is currently locked out');
                $response->send();
                exit;
            }

            if(strtotime($returned_refreshtokenexpiry) < time()){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('Refresh token has expired - please log in again');
                $response->send();
                exit;
            }
            /* END USER SESSION VALIDATION */

            /* START CREATE NEW SESSION DATA */
            $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
            $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());

            $access_token_expiry_seconds = 1200;
            $refresh_token_expiry_seconds = 1209600;

            $query = $this->getWriteDB()->prepare('UPDATE gdhc_sessions SET accesstoken = :accesstoken, accesstokenexpiry = date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), refreshtoken = :refreshtoken, refreshtokenexpiry = date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND) WHERE id = :sessionId AND userid = :userId AND accesstoken = :returnedaccesstoken AND refreshtoken = :returnedrefreshtoken');
            $query->bindParam(':sessionId', $returned_sessionId, \PDO::PARAM_INT);
            $query->bindParam(':userId', $returned_userId, \PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $accesstoken, \PDO::PARAM_STR);
            $query->bindParam(':accesstokenexpiryseconds', $access_token_expiry_seconds, \PDO::PARAM_INT);
            $query->bindParam(':refreshtoken', $refreshtoken, \PDO::PARAM_STR);
            $query->bindParam(':refreshtokenexpiryseconds', $refresh_token_expiry_seconds, \PDO::PARAM_INT);
            $query->bindParam(':returnedaccesstoken', $returned_accesstoken, \PDO::PARAM_STR);
            $query->bindParam(':returnedrefreshtoken', $returned_refreshtoken, \PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('Access token could not be refreshed - please log in again');
                $response->send();
                exit;
            }
            /* END CREATE NEW SESSION DATA */

            $returnData = [];
            $returnData['user_id'] = $returned_userId;
            $returnData['user'] = $jsonData->username;
            $returnData['session_id'] = $returned_sessionId;
            $returnData['accesstoken'] = $accesstoken;
            $returnData['access_token_expires_in'] = $access_token_expiry_seconds;
            // $returnData['refreshtoken'] = $refreshtoken;
            $returnData['refresh_token_expires_in'] = $refresh_token_expiry_seconds;
            
            Authenticator::storeRefreshToken($refreshtoken, $refresh_token_expiry_seconds, $returned_userId);

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setData($returnData);
            $response->addMessage('Token refreshed');
            $response->send();
            exit;
        } catch(\PDOException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue refreshing the access token - please log in again');
            $response->send();
            exit;
        }
    }

    private function deleteSession(){
        try {
            $sessionId = $this->getEntity();
            $accessToken = $this->getAccessToken();

            $query = $this->getWriteDB()->prepare('DELETE FROM gdhc_sessions WHERE id = :sessionid AND accesstoken = :accesstoken');
            $query->bindParam(':sessionid', $sessionId, \PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $accessToken, \PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage('Failed to log out of this session using access token provided');
                $response->send();
                exit;
            }

            $returnData = [];
            $returnData['session_id']= intval($this->getEntity());

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Logged out');
            $response->setData($returnData);
            $response->send();
            exit;

        } catch(\PDOException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue logging out - please try again');
            $response->send();
            exit;
        }
    }

    private function validateUserCredentials(&$jsonData){
        try {
            $username = $jsonData->username;
            $password = $jsonData->password;

            $query = $this->getWriteDB()->prepare('SELECT id, fullname, username, password, useractive, loginattempts FROM gdhc_users WHERE username = :username');
            $query->bindParam(':username', $username, \PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('Username or password is incorrect');
                $response->send();
                exit;
            }

            $row = $query->fetch(\PDO::FETCH_ASSOC);
            $returned_id = $row['id'];
            $returned_fullname = $row['fullname'];
            $returned_username = $row['username'];
            $returned_password = $row['password'];
            $returned_useractive = $row['useractive'];
            $returned_loginattempts = $row['loginattempts'];

            if($returned_useractive !== 'Y'){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('User account not active');
                $response->send();
                exit;
            }

            if($returned_loginattempts >= 3){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('User account is currently locked out');
                $response->send();
                exit;
            }

            if(!password_verify($password, $returned_password)){

                $query = $this->getWriteDB()->prepare('UPDATE gdhc_users SET loginattempts = loginattempts+1 WHERE id = :id');
                $query->bindParam(':id', $returned_id, \PDO::PARAM_INT);
                $query->execute();

                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('Password is incorrect');
                $response->send();
                exit;
            }

            $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
            $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());

            $access_token_expiry_seconds = 1200; //20 minutes
            $refresh_token_expiry_seconds = 1209600; //14 days (change to 1 day maybe)

            $data = [];
            $data['username'] = $username;
            $data['returned_id'] = $returned_id;
            $data['accesstoken'] = $accesstoken;
            $data['refreshtoken'] = $refreshtoken;
            $data['access_token_expiry_seconds'] = $access_token_expiry_seconds;
            $data['refresh_token_expiry_seconds'] = $refresh_token_expiry_seconds;
            return $data;

        } catch (\PDOException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('An issue occured logging in');
            $response->send();
            exit;
        }
    }

    private function validateCredentialsInput(&$jsonData){
        if(isset($jsonData->username) && !isset($jsonData->password)){
            $jsonData->password = $this->fetchPasswordFromData($jsonData); 
        }

        if(!isset($jsonData->username) || !isset($jsonData->password)){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            if(!isset($jsonData->username)){
                $response->addMessage('Username is not supplied');
            } if(!isset($jsonData->password)){
                $response->addMessage('Password is not supplied');
            }
            $response->send();
            exit;
        }
    
        if(strlen($jsonData->username) <= 0 || strlen($jsonData->username) > 255 || strlen($jsonData->password) <= 0 || strlen($jsonData->password) > 255){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            if(strlen($jsonData->username) <= 0 || strlen($jsonData->username) > 255){
                $response->addMessage('Username is invalid');
            } if(strlen($jsonData->password) <= 0 || strlen($jsonData->password) > 255){
                $response->addMessage('Password is invalid');
            }
            $response->send();
            exit;
        }
    }

    private function fetchPasswordFromData(&$jsonData){
        return AUTH_SALT.$jsonData->username;
    }
}