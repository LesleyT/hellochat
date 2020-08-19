<?php 
namespace API\Controllers;
abstract class Request {
    
    private $_accessToken;
    private $_refreshToken;

    protected $_writeDB;
    protected $_readDB;

    protected $_collection;
    protected $_entity;
    protected $_action;
    
    private $_purifier;
    private $_purifierConfig;
    
    public function __construct($accesstoken = null, $refreshToken = null){
        $this->setEntity(null);
        $this->setCollection(null);
        $this->setAction(null);

        try {
            require_once dirname(dirname(__FILE__)).'/Libs/htmlpurifier/library/HTMLPurifier.auto.php';
            $this->_purifierConfig = \HTMLPurifier_Config::createDefault();
            $this->_purifierConfig->set('HTML.Allowed', 'br');
            $this->_purifier = new \HTMLPurifier($this->_purifierConfig);
        } catch(Exception $e) {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Could not build request');
            $response->send();
            exit;
        }

        try {
            $this->setWriteDB(\API\Controllers\DB::connectWriteDB());
            $this->setReadDB($readDB = \API\Controllers\DB::connectReadDB());
        } catch(PDOException $e) {
            error_log("Connection error - " . $e, 0);
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Database connection error');
            $response->send();
            exit;
        }
    }

    abstract public function run();

    abstract protected function DELETE();
    abstract protected function PATCH();
    abstract protected function POST();
    abstract protected function GET();
    abstract protected function OPTIONS();

    protected function validateRequestData(){        
        if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Content Type header not set to JSON');
            $response->send();
            exit;
        }
    }

    protected function fetchBody(){
        $rawData = file_get_contents('php://input');
        if(!($jsonData = json_decode($rawData))){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Request body is not valid JSON');
            $response->send();
            exit;
        }

        return $jsonData;
    }


    /* ADD ROLE AUTHENTICATION */

    protected function validateAuthenticationData(){
        if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) <= 0){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            if(!isset($_SERVER['HTTP_AUTHORIZATION'])){
                $response->addMessage('Access token is missing from the header');
            } if(strlen($_SERVER['HTTP_AUTHORIZATION']) <= 0){
                $response->addMessage('Access token cannot be blank');
            }
            $response->send();
            exit;
        }

        $accesstoken = $_SERVER['HTTP_AUTHORIZATION'];

        try{
            $query = $this->getWriteDB()->prepare('SELECT gdhc_sessions.id as sessionId, userId, role, accesstokenexpiry, useractive, loginattempts FROM gdhc_sessions, gdhc_users WHERE gdhc_sessions.userId = gdhc_users.id AND accesstoken = :accesstoken');
            $query->bindParam(':accesstoken', $accesstoken, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('Invalid Access Token');
                $response->send();
                exit;
            }

            $row = $query->fetch(\PDO::FETCH_ASSOC);
            $returned_sessionId = $row['sessionId'];
            $returned_userId = $row['userId'];
            $returned_accesstokenexpiry = $row['accesstokenexpiry'];
            $returned_useractive = $row['useractive'];
            $returned_loginattempts = $row['loginattempts'];
            $returned_role = $row['role'];

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

            if(strtotime($returned_accesstokenexpiry) < time()){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('Access Token expired');
                $response->send();
                exit;
            }

            Authenticator::instance()->setUser($returned_userId);
            Authenticator::instance()->setRole($returned_role);
            Authenticator::instance()->setSession($returned_sessionId);
        } catch(\PDOException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue authenticating - please try again');
            $response->send();
            exit;
        }
    }

    protected function validateRefreshToken(&$refreshtoken){
        if(!isset($refreshtoken) || strlen($refreshtoken) <= 0){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            if(!isset($refreshtoken)){
                $response->addMessage('Refresh token not supplied');
            }
            if(strlen($refreshtoken) <= 0){
                $response->addMessage('Refresh token cannot be blank');
            }
            $response->send();
            exit;
        }
    }

    protected function validateRequestHeader(){
        if($this->getEntity() === '' || !is_numeric($this->getEntity())){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            if($this->getEntity() === ''){
                $response->addMessage('Session ID cannot be blank');
            } if(!is_numeric($this->getEntity())){
                $response->addMessage('Session ID must be numeric');
            }
            $response->send();
            exit;
        }
    
        if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) <= 0){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            if(!isset($_SERVER['HTTP_AUTHORIZATION'])){
                $response->addMessage('Access token is  missing from the header');
            } if(strlen($_SERVER['HTTP_AUTHORIZATION']) <= 0){
                $response->addMessage('Access token cannot be blank');
            }
            $response->send();
            exit;
        }
    }

    protected function sanitize($html){
        try {
            return $this->_purifier->purify($html);
        } catch(Exception $e){ }
    }
    
    public function getWriteDB() {
        return $this->_writeDB;
    }
    
    public function getReadDB() {
        return $this->_readDB;
    }

    public function getAccessToken() {
        return $this->_accessToken;
    }

    public function getRefreshToken() {
        return $this->_refreshToken;
    }

    public function setEntity($entity) {
        $this->_entity = $entity;
        return $this;
    }

    public function setCollection($collection) {
        $this->_collection = $collection;
        return $this;
    }

    public function setAction($action) {
        $this->_action = $action;
        return $this;
    }
    
    public function setWriteDB($writeDB) {
        $this->_writeDB = $writeDB;

        return $this;
    }
    
    public function setReadDB($readDB) {
        $this->_readDB = $readDB;
        return $this;
    }
        
    public function setAccessToken($accessToken) {
        $this->_accessToken = $accessToken;
        return $this;
    }
    
    public function setRefreshToken($refreshToken) {
        $this->_refreshToken = $refreshToken;
        return $this;
    }
    
    public function getCollection() {
        return $this->_collection;
    }
    
    public function getEntity() {
        return $this->_entity;
    }
    
    public function getAction() {
        return $this->_action;
    }
}