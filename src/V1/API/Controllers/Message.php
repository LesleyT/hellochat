<?php 
namespace API\Controllers;

class Message extends Request {

    private $_limit;
    private $_sleepTime;

    private $_maxSleepTime;

    public function __construct(){
        $this->_limit = 10;
        $this->_sleepTime = 0;
        $this->_maxSleepTime = 10;
        parent::__construct();
    }   

    public function run(){
        $this->validateAuthenticationData();
        $this->setAccessToken($_SERVER['HTTP_AUTHORIZATION']);
        
        if($this->getEntity() !== null){
            $this->validateRequestHeader();
            if($_SERVER['REQUEST_METHOD'] === 'GET'){
                if(array_key_exists("latest", $_GET)){
                    $this->GET('latest');
                } elseif(array_key_exists("previous", $_GET)){
                    $this->GET('previous');
                } else {
                    $this->GET('single');
                }
            } elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
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
        } elseif(array_key_exists("page", $_GET)) {
            if($_SERVER['REQUEST_METHOD'] === 'GET'){
                $this->GET('paged');
            } else {
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage('Request method not allowed');
                $response->send();
                exit;
            }
        } elseif($this->getCollection() !== null){
            if(array_key_exists("allConversation", $_GET)){
                $this->GET('allConversation');
            } else {
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage('Request method not allowed');
                $response->send();
                exit;
            }
        } elseif($this->getEntity() === null){
            if($_SERVER['REQUEST_METHOD'] === 'GET'){
                $this->GET('all');
            } elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
                $this->POST();
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
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage('Endpoint was not found');
            $response->send();
            exit;
        }
    }

    protected function POST(){
        $this->createMessage();
    }

    protected function GET($type = 'paged'){
        if($type === 'single'){
            $this->getMessage();
        } elseif($type === 'paged') {
            $page = (!isset($_GET['page'])) ? 1 : $_GET['page'];
            $this->getPage($page);
        } elseif($type === 'all'){
            $this->getAll(); //add access management (only allow admins)
        } elseif($type === 'latest' || $type === 'previous' || $type === 'allConversation'){
            $this->getSelection($type);
        } else {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(405);
            $response->setSuccess(false);
            $response->addMessage('Request method not allowed');
            $response->send();
            exit;
        }
    }

    protected function DELETE(){
        if($this->deleteMessage()){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Message deleted');
            $response->send();
            exit;
        } else {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to delete message');
            $response->send();
            exit;
        }
    }

    protected function PATCH(){
        $this->validateRequestData();
        $jsonData = $this->fetchBody();
        $this->updateMessage($jsonData);
    }

    protected function OPTIONS(){ }

    private function createMessage(){
        try {
            $this->validateRequestData();
            $jsonData = $this->fetchBody();

            if(isset($jsonData->email)){
                $jsonData->email = Authenticator::instance()->prepareToUSeUsername($jsonData->email);
            }

            $sessionId = Authenticator::instance()->getSession();
            $userId = Authenticator::instance()->getUser();
            $conversationId = $jsonData->conversationId;

            $query = $this->getWriteDB()->prepare('SELECT gdhc_conversations.id as id, done FROM gdhc_conversations, gdhc_sessions WHERE (gdhc_sessions.id = gdhc_conversations.participantTwo || gdhc_sessions.id = gdhc_conversations.participantOne) AND gdhc_conversations.id = :conversationId AND gdhc_sessions.userId = :userId');
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){  
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('You do not have premission to post a message here');
                $response->send();
                exit;
            }

            $row = $query->fetch(\PDO::FETCH_ASSOC);
            if($row['done'] === 'Y'){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->setData(['done' => true]);
                $response->addMessage('Could not create message - Conversation closed');
                $response->send();
                exit;
            }

            $newMessage = $this->validateData($jsonData);
            $messageData = $newMessage->returnAsArray();
            
            $messageData['name'] = $this->sanitize($messageData['name']);
            $messageData['email'] = $this->sanitize($messageData['email']);
            $messageData['content'] = $this->sanitize($messageData['content']);

            $userId = Authenticator::instance()->getUser();
            $query = $this->getWriteDB()->prepare('INSERT INTO gdhc_messages (id, name, email, content, created, userId, conversationId) VALUES(null, :name, :email, :content, STR_TO_DATE(:created, "%d-%m-%Y %H:%i:%s"), :userId, :conversationId)');
            $query->bindParam(':name', $messageData['name'], \PDO::PARAM_STR);
            $query->bindParam(':email', $messageData['email'], \PDO::PARAM_STR);
            $query->bindParam(':content', $messageData['content'], \PDO::PARAM_STR);
            $query->bindParam(':created', $messageData['created'], \PDO::PARAM_STR);
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute();
            
            $rowCount = $query->rowCount();
            
            if($rowCount === 0){  
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('Failed to create message');
                $response->send();
                exit;
            }

            try {
                $lastMessageId = $this->getWriteDB()->lastInsertId();
                $query = $this->getWriteDB()->prepare('SELECT id, name, email, content, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, conversationId FROM gdhc_messages WHERE id = :messageId AND userId = :userId');
                $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
                $query->bindParam(':messageId', $lastMessageId, \PDO::PARAM_INT);
                $query->execute();
    
                $rowCount = $query->rowCount();
                
                if($rowCount === 0){  
                    $response = new \API\Models\Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage('Failed to fetch message after creation');
                    $response->send();
                    exit;
                }
    
                $messageArray = [];
                while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                    $message = new \API\Models\Message(strval($row['id']), $row['name'], $row['email'], $row['content'], $row['created'], strval($row['conversationId']));
                    $messageArray[] = $message->returnAsArray();
                }
    
                $returnData = [];
                $returnData['count'] = $rowCount;
                $returnData['messages'] = $messageArray;
      
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(201);
                $response->setSuccess(true);
                $response->setData($returnData);
                $response->send();
                exit;
            }  catch(\API\Models\Exceptions\MessageException $e) {
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
                $response->addMessage('Failed to insert message into database - check submitted data for erros');
                $response->send();
                exit;
            }
        } catch(\API\Models\Exceptions\MessageException $e) {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($e->getMessage());
            $response->send();
            exit;
        } catch(\PDOException $e) {
            error_log("Database query error - " . $e, 0);
            var_dump($e->getMessage());
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to insert message into database - check submitted data for erros');
            $response->send();
            exit;
        }
    }

    private function getMessage(){
        try {
            /* GET FROM DB */
            $messageId = $this->getEntity();
            $userId = Authenticator::instance()->getUser();
            $query = $this->getReadDB()->prepare('SELECT id, name, email, content, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created FROM gdhc_messages WHERE id = :messageId AND userId = :userId');
            $query->bindParam(':messageId', $messageId, \PDO::PARAM_INT);
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Message not found');
                $response->send();
                exit;
            }

            /* BUILD RESPONS */
            $messageArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $message = new \API\Models\Message((string) $row['id'], $row['name'], $row['email'], $row['content'], $row['created']);
                $messageArray[] = $message->returnAsArray();
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['messages'] = $messageArray;
            
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;
        } catch(\API\Models\Exceptions\MessageException $e) {
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
            $response->addMessage('Failed to get Message');
            $response->send();
            exit;
        }
    }

    private function updateMessage(&$jsonData){
        try {
            /* CHECK WHAT FIELDS NEED UPDATING / BUILD QUERY STRING */
            $name_updated = false;
            $email_updated = false;
            $content_updated = false;        

            $queryFields = '';
            
            if(isset($jsonData->name)){ 
                $name_updated = true;
                $queryFields .= 'name = :name, ';
            }
            if(isset($jsonData->email)){ 
                $email_updated = true;
                $queryFields .= 'email = :email, ';
            }
            if(isset($jsonData->content)){ 
                $content_updated = true;
                $queryFields .= 'content = :content, ';
            }
            $queryFields = rtrim($queryFields, ', ');

            if($name_updated === false && $email_updated === false && $content_updated === false){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage('No message fields have been provided');
                $response->send();
                exit;
            }

            /* CHECKS IF THERE IS AN ASSOCIATED ENTITY TO UPDATE  */
            $messageId = $this->getEntity();
            $userId = Authenticator::instance()->getUser();
            $query = $this->getWriteDB()->prepare('SELECT id, name, email, content, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, conversationId FROM gdhc_messages WHERE id = :messageId AND userId = :userId');
            $query->bindParam(':messageId', $messageId, \PDO::PARAM_INT);
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Message not found');
                $response->send();
                exit;
            }

            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $message = new \API\Models\Message(strval($row['id']), $this->sanitize($row['name']), $this->sanitize($row['email']), $this->sanitize($row['content']), $row['created'], strval($row['conversationId']));
            }

            /* UPDATE ENITY */
            $queryString = "UPDATE gdhc_messages set ".$queryFields." WHERE ID = :messageId AND userId = :userId";
            $query = $this->getWriteDB()->prepare($queryString);
            
            if($name_updated){
                $message->setName($jsonData->name);
                $update_name = $message->getName();
                $query->bindParam(':name', $update_name, \PDO::PARAM_STR);
            }
            if($email_updated){
                $message->setEmail($jsonData->email);
                $update_email = $message->getEmail();
                $query->bindParam(':email', $update_email, \PDO::PARAM_STR);
            }
            if($content_updated){
                $message->setContent($jsonData->content);
                $update_content = $message->getContent();
                $query->bindParam(':content', $update_content, \PDO::PARAM_STR);
            }

            $query->bindParam(':messageId', $messageId, \PDO::PARAM_INT);
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->execute(); 
            
            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('Could not update message');
                $response->send();
                exit;
            }
            /* CHECK IF ENITY EXISTS AFTER UPDATE */
            $query = $this->getWriteDB()->prepare('SELECT id, name, email, content, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, conversationId FROM gdhc_messages WHERE id = :messageId AND userId = :userId');
            $query->bindParam(':messageId', $messageId, \PDO::PARAM_INT);
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('No message found after update');
                $response->send();
                exit;
            }

            /* RETURNS NEWLY UPDATED MESSAGE */
            $messageArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $message = new \API\Models\Message(strval($row['id']), $row['name'], $row['email'], $row['content'], $row['created'], strval($row['conversationId']));
                $messageArray[] = $message->returnAsArray();
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['messages'] = $messageArray;

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->setData($returnData);
            $response->send();
            exit;

        } catch(\API\Models\Exceptions\MessageException $e) {
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
            $response->addMessage('Failed to update message - check submitted data for erros');
            $response->send();
            exit;
        }
    }

    private function deleteMessage(){
        try {
            $messageId = $this->getEntity();
            $userId = Authenticator::instance()->getUser();
            $query = $this->getWriteDB()->prepare('DELETE FROM gdhc_messages WHERE id = :messageId AND userId = :userId');
            $query->bindParam(':messageId', $messageId, \PDO::PARAM_INT);
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Message was not found');
                $response->send();
                exit;
            }

            return true;
        } catch(\PDOException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to delete message');
            $response->send();
            exit;
        }
        return false;
    }

    /* FETCHES ALL MESSAGES OF A USER - PAGE BASED */
    private function getPage($page){
        if($page == '' || !is_numeric($page)){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Page number cannot be blank and must be numeric');
            $response->send();
            exit;
        }
    
        $limitPerPage = 10;
        try {
            $userId = Authenticator::instance()->getUser();
            $query = $this->getReadDB()->prepare('SELECT count(id) as messagesCount FROM gdhc_messages WHERE userId = :userId');
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->execute();
    
            $row = $query->fetch(\PDO::FETCH_ASSOC);
            
            $messagesCount = intval($row['messagesCount']);
            $numberOfPages = ceil($messagesCount/$limitPerPage);
    
            if($numberOfPages == 0){
                $numberOfPages = 1;
            }
            
            if($page <= 0 || $page > $numberOfPages){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Page not found');
                $response->send();
                exit;
            }
    
            $offset = ($page == 1) ? 0 : ($limitPerPage * ($page - 1)); //remove
            $offset = ($limitPerPage * ($page - 1));
    
            $query = $this->getReadDB()->prepare('SELECT id, name, email, content, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, conversationId FROM gdhc_messages WHERE userId = :userId LIMIT :limit OFFSET :offset');
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $query->bindParam(':limit', $limitPerPage, \PDO::PARAM_INT);
            $query->execute();
    
            $rowCount = $query->rowCount();
    
            /* BUILD RESPONS */
            $messageArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $message = new \API\Models\Message((string) $row['id'], $row['name'], $row['email'], $row['content'], $row['created'], strval($row['conversationId']));
                $messageArray[] = $message->returnAsArray();
            }
    
            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['total_pages'] = $numberOfPages;
            $returnData['total_messages'] = $messagesCount;
            
            $returnData['has_next_page'] = ($page < $numberOfPages) ? true : false;
            $returnData['has_previous_page'] = ($page > 1) ? true : false;
            
            $returnData['messages'] = $messageArray;
    
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;
        } catch(\API\Models\Exceptions\MessageException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($e->getMessage());
            $response->send();
            exit;
        } catch(\PDOException $e){
            error_log("Database query error - " . $e, 0);
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get Message');
            $response->send();
            exit;
        }
    }

    /* FETCHES ALL MESSAGES OFN A USER */
    private function getAll(){
        try {
            $userId = Authenticator::instance()->getUser();
            $query = $this->getReadDB()->prepare('SELECT id, name, email, content, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, conversationId FROM gdhc_messages WHERE userId = :userId');
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            /* BUILD RESPONS */
            $messageArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $message = new \API\Models\Message((string) $row['id'], $row['name'], $row['email'], $row['content'], $row['created'], strval($row['conversationId']));
                $messageArray[] = $message->returnAsArray();
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['messages'] = $messageArray;

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;

        } catch(\API\Models\Exceptions\MessageException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($e->getMessage());
            $response->send();
            exit;
        } catch(\PDOException $e){
            error_log('Database query error = '.$e, 0);
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get messages');
            $response->send();
            exit;
        } 
    }


    /* FETCHES A SELECTION MESSAGES OF A USER - CONVERSATION BASED */
    private function getSelection($type){
        try {

            $sessionId = Authenticator::instance()->getSession();
            $lastestMessageId = intval($this->getEntity());
            $conversationId = intval($this->getCollection());
            $userId = Authenticator::instance()->getUser();

            $isTyping = [];

            $query = $this->getWriteDB()->prepare('SELECT gdhc_conversations.id as id, done, participantOneTyping, participantTwoTyping, participantOne, participantTwo,userId FROM gdhc_conversations, gdhc_sessions WHERE (gdhc_sessions.id = gdhc_conversations.participantOne OR gdhc_sessions.id = gdhc_conversations.participantTwo) AND gdhc_conversations.id = :conversationId AND gdhc_sessions.userId = :userId');
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){  
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('You do not have premission to post a message here');
                $response->send();
                exit;
            }

            $row = $query->fetch(\PDO::FETCH_ASSOC);
            $isDone = ($row['done'] == 'Y') ? true : false;
            
            $otherIsTyping = false;
            if((($row['participantOne'] == $sessionId && $row['userId'] == $userId) && $row['participantTwoTyping'] === 'Y')){
                $otherIsTyping = true;
            } elseif ((($row['participantOne'] != $sessionId && $row['userId'] == $userId ||($row['participantTwo'] == $sessionId && $row['userId'] == $userId)) && $row['participantOneTyping'] === 'Y')){
                $otherIsTyping = true;
            }

            $queryString = 'SELECT gdhc_messages.id as id, name, email, content, DATE_FORMAT(gdhc_messages.created, "%d-%m-%Y %H:%i:%s") as created, gdhc_messages.conversationId as conversationId FROM gdhc_messages, gdhc_conversations WHERE gdhc_messages.conversationId = gdhc_conversations.id AND gdhc_messages.conversationId = :conversationId';
            if($type === 'latest'){
                $queryString .= ' AND gdhc_messages.id > :anchor';
            } elseif($type === 'previous'){
                $queryString .= ' AND gdhc_messages.id <= :anchor';
            } elseif($type === 'all'){
                // $queryString .= 'AND gdhc_messages.id <= :anchor';
            }
            
            $query = $this->getReadDB()->prepare($queryString);
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            // $query->bindParam(':sessionId1', $sessionId, \PDO::PARAM_INT);
            // $query->bindParam(':sessionId2', $sessionId, \PDO::PARAM_INT);
            // $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            // var_dump($type);
            if($type !== 'allConversation'){
                $query->bindParam(':anchor', $lastestMessageId, \PDO::PARAM_INT);
            }
            $query->execute();
            
            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                if($otherIsTyping){
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(false);
                    $response->setData(['isTyping' => true]);
                    $response->send();
                    exit;
                } else { 
                    if($this->_sleepTime < $this->_maxSleepTime){
                        $this->_sleepTime += 2;
                        sleep(2);
                        $this->getSelection($type);
                        return;
                    }
                }
            }

            /* BUILD RESPONS */
            $messageArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $message = new \API\Models\Message((string) $row['id'], $row['name'], $row['email'], $row['content'], $row['created'], strval($row['conversationId']));
                $messageArray[] = $message->returnAsArray();
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['messages'] = $messageArray;
            $returnData['done'] = $isDone;
            $returnData['isTyping'] = $otherIsTyping;

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(false);
            $response->setData($returnData);
            $response->send();
            exit;

        } catch(\API\Models\Exceptions\MessageException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($e->getMessage());
            $response->send();
            exit;
        } catch(\PDOException $e){
            error_log('Database query error = '.$e, 0);
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get messages');
            $response->send();
            exit;
        } 
    }

    private function validateData(&$jsonData){
        if(!isset($jsonData->name) || !isset($jsonData->email) || !isset($jsonData->content) || !isset($jsonData->created) || !isset($jsonData->conversationId)){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);

            if(!isset($jsonData->name)){ $response->addMessage('Message owner name hasn\'t been provided'); }
            if(!isset($jsonData->email)){ $response->addMessage('Message owner email hasn\'t been provided'); }
            if(!isset($jsonData->content)){ $response->addMessage('Message body hasn\'t been provided'); }
            if(!isset($jsonData->created)){ $response->addMessage('Message creation date hasn\'t been provided'); }
            if(!isset($jsonData->conversationId)){ $response->addMessage('Message conversation ID hasn\'t been provided'); }

            $response->send();
            exit;
        }
        return new \API\Models\Message(null, $jsonData->name, $jsonData->email, $jsonData->content, $jsonData->created, strval($jsonData->conversationId));        
    }

    protected function getLimit() {
        return $this->_limit;
    }

    protected function setLimit($limit) {
        $this->_limit = $limit;
        return $this;
    }
}