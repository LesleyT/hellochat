<?php 
namespace API\Controllers;

class Conversation extends Request {

    public function __construct(){
        parent::__construct();
    }   

    public function run(){
        if($this->getAction() === 'clean'){
            if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
                $this->DELETE('clean');
                return;
            } else {
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage('Request method not allowed');
                $response->send();
                exit;
            }
        }

        $this->validateAuthenticationData();
        $this->setAccessToken($_SERVER['HTTP_AUTHORIZATION']);
        if($this->getEntity() !== null){
            $this->validateRequestHeader();
            if($_SERVER['REQUEST_METHOD'] === 'GET'){
                if(array_key_exists("latest", $_GET)){
                    $this->GET('latest');
                } elseif(array_key_exists("archived", $_GET)){
                    $this->GET('archived');
                } else {
                    $this->GET('single');
                }
            } elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
                $this->DELETE('delete');
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
        $this->createConversation();
    }

    protected function GET($type = 'all'){
        if(Authenticator::instance()->isAdmin(true)){   
            if($type === 'single'){
                $this->getConversation();
            } elseif($type === 'all'){
                $this->getConversations();
            } elseif($type === 'latest'){
                $this->getConversations($type);
            }  elseif($type === 'archived'){
                $this->getConversations($type);
            } else {
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage('Request method not allowed');
                $response->send();
                exit;
            }
        }
    }

    protected function DELETE($type = 'delete'){
        if($type === 'clean'){
            if(($data = $this->deleteConversations()) != false){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->addMessage('Conversations deleted');
                $response->setData($data);
                $response->send();
                exit;
            } else {
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('Failed to delete conversation');
                $response->send();
                exit;
            }
        } elseif($type === 'delete'){
            if(Authenticator::instance()->isAdmin(true)){ 
                if($this->deleteConversation()){
                    $response = new \API\Models\Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(true);
                    $response->addMessage('Conversation deleted');
                    $response->setData([
                        'id' => $this->getEntity()
                    ]);
                    $response->send();
                    exit;
                } else {
                    $response = new \API\Models\Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage('Failed to delete conversation');
                    $response->send();
                    exit;
                }
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

    protected function PATCH(){
        $this->validateRequestData();
        $jsonData = $this->fetchBody();
        if(isset($jsonData->typing)){
            $this->setTyping($jsonData);
        } elseif(Authenticator::instance()->isAdmin(true)){ 
            $this->updateConversation($jsonData);
        } else {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(405);
            $response->setSuccess(false);
            $response->addMessage('Request method not allowed');
            $response->send();
            exit;
        }
    }

    protected function OPTIONS(){ }

    private function createConversation(){
        try {
            $this->validateRequestData();
            $jsonData = $this->fetchBody();

            $newConversation = $this->validateData($jsonData);
            $conversationData = $newConversation->returnAsArray();

            $query = $this->getWriteDB()->prepare('INSERT INTO gdhc_conversations (id, participantOne, participantTwo, created) VALUES(null, :participantOne, :participantTwo, STR_TO_DATE(:created, "%d-%m-%Y %H:%i:%s"))');
            $query->bindParam(':participantOne', $conversationData['participantOne'], \PDO::PARAM_STR);
            $query->bindParam(':participantTwo', $conversationData['participantTwo'], \PDO::PARAM_NULL);
            $query->bindParam(':created', $conversationData['created'], \PDO::PARAM_STR);
            $query->execute();
            
            $rowCount = $query->rowCount();
            
            if($rowCount === 0){  
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('Failed to create conversation');
                $response->send();
                exit;
            }

            try {
                $lastConversationId = $this->getWriteDB()->lastInsertId();
                $query = $this->getWriteDB()->prepare('SELECT id, participantOne, participantTwo, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created FROM gdhc_conversations WHERE id = :conversationId AND participantOne = :participantOne');
                $query->bindParam(':conversationId', $lastConversationId, \PDO::PARAM_INT);
                $query->bindParam(':participantOne', $conversationData['participantOne'], \PDO::PARAM_INT);
                $query->execute();
    
                $rowCount = $query->rowCount();
                
                if($rowCount === 0){  
                    $response = new \API\Models\Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage('Failed to fetch conversation after creation');
                    $response->send();
                    exit;
                }
    
                $conversationArray = [];
                while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                    $conversation = new \API\Models\Conversation(strval($row['id']), strval($row['participantOne']), strval($row['participantTwo']), $row['created']);
                    $conversationArray[] = $conversation->returnAsArray();
                }
    
                $returnData = [];
                $returnData['count'] = $rowCount;
                $returnData['conversation'] = $conversationArray;
      
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(201);
                $response->setSuccess(true);
                $response->setData($returnData);
                $response->send();
                exit;
            }  catch(\API\Models\Exceptions\ConversationException $e) {
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
                $response->addMessage('Failed to insert conversation into database - check submitted data for erros');
                $response->send();
                exit;
            }
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
            $response->addMessage('Failed to insert conversation into database - check submitted data for erros');
            $response->send();
            exit;
        }
    }

    private function getConversation(){
        try {
            /* GET FROM DB */
            $conversationId = $this->getEntity();
            $userId = Authenticator::instance()->getUser();
            //$queryString = 'SELECT id, participantOne, participantTwo, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created FROM gdhc_conversations WHERE id = :conversationId';
            $queryString = 'SELECT gdhc_conversations.id as id, participantOne, participantTwo, DATE_FORMAT(gdhc_conversations.created, "%d-%m-%Y %H:%i:%s") as created, gdhc_participantOne_u.fullname as name_1, gdhc_participantOne_u.username as email_1, gdhc_participantOne_s.userId as id_1, gdhc_participantTwo_u.fullname as name_2, gdhc_participantTwo_u.username as email_2, gdhc_participantTwo_s.userId as id_2 FROM gdhc_conversations, gdhc_sessions as gdhc_participantOne_s, gdhc_sessions as gdhc_participantTwo_s, gdhc_users as gdhc_participantOne_u, gdhc_users as gdhc_participantTwo_u WHERE (gdhc_conversations.participantOne = gdhc_participantOne_s.id AND gdhc_participantOne_s.userId = gdhc_participantOne_u.id) AND (gdhc_conversations.participantTwo = gdhc_participantTwo_s.id AND gdhc_participantTwo_s.userId = gdhc_participantTwo_u.id) AND gdhc_conversations.id = :conversationId';
            //add messages to be returned in query
            
            $query = $this->getReadDB()->prepare($queryString);
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute();


            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Conversation not found');
                $response->send();
                exit;
            }

            /* BUILD RESPONS */
            $conversationArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $conversation = new \API\Models\Conversation((string) $row['id'], strval($row['participantOne']), strval($row['participantTwo']), $row['created']);
                // $conversationArray[] = $conversation->returnAsArray();
                $conversationArray[] = array_merge($conversation->returnAsArray(), [
                    'participantOne' => [
                        'id' => $row['participantOne'],
                        'name' => $row['name_1'],
                        'email' => $row['email_1'],
                        'user' => $row['id_1']
                    ],
                    'participantTwo' => [
                        'id' => $row['participantTwo'],
                        'name' => $row['name_2'],
                        'email' => $row['email_2'],
                        'user' => $row['id_2']
                    ]
                ]);
            }

            if(count($conversationArray) == 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Conversation not found');
                $response->send();
                exit;
            }


            /* GET MESSAGES FROM CONVERSATION */
            
            $queryString = 'SELECT gdhc_messages.id as id, name, email, content, DATE_FORMAT(gdhc_messages.created, "%d-%m-%Y %H:%i:%s") as created, gdhc_messages.conversationId as conversationId FROM gdhc_messages, gdhc_conversations WHERE gdhc_messages.conversationId = gdhc_conversations.id AND gdhc_messages.conversationId = :conversationId AND (participantOne = :sessionId1 AND participantTwo = :sessionId2)';
            
            $query = $this->getReadDB()->prepare($queryString);
            $query->bindParam(':conversationId', $conversationArray[0]['id'], \PDO::PARAM_INT);
            $query->bindParam(':sessionId1',$conversationArray[0]['participantOne']['id'], \PDO::PARAM_INT);
            $query->bindParam(':sessionId2', $conversationArray[0]['participantTwo']['id'], \PDO::PARAM_INT);
            $query->execute();
            
            $messageCount = $query->rowCount();

            /* BUILD RESPONS */
            $messageArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $message = new \API\Models\Message((string) $row['id'], $row['name'], Authenticator::instance()->prepareToUseUsername($row['email']), $row['content'], $row['created'], strval($row['conversationId']));
                $messageArray[] = $message->returnAsArray();
            }
            $conversationArray[0]['messages'] = [
                'data' => [
                    'messages' => $messageArray,
                    'count' =>  $messageCount
                ]
            ];

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['conversations'] = $conversationArray;
            
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
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
            $response->addMessage('Failed to get Conversation');
            $response->send();
            exit;
        }
    }

    private function getConversations($type = 'allConversation'){
        try {
            /* GET FROM DB */
            $cache = false;
            $conversationId = $this->getEntity();
            $sessionId = Authenticator::instance()->getSession();
            $userId = Authenticator::instance()->getUser();
            $queryStringSelect = 'SELECT gdhc_conversations.id as id, participantOne, participantTwo, DATE_FORMAT(gdhc_conversations.created, "%d-%m-%Y %H:%i:%s") as created, gdhc_participantOne_u.id as id_1, gdhc_participantOne_u.fullname as name_1';
            $queryStringFrom = ' FROM gdhc_conversations, gdhc_sessions as gdhc_participantOne_s, gdhc_users as gdhc_participantOne_u';
            $queryStringWhere = ' WHERE (gdhc_conversations.participantOne = gdhc_participantOne_s.id AND gdhc_participantOne_s.userId = gdhc_participantOne_u.id)';
            
            if($this->getAction() === 'open'){
                $queryStringWhere .= ' AND done = "N" AND ((participantTwo IS NULL OR participantTwo = "") OR participantTwo = :sessionId)';
            } else if($this->getAction() === 'closed'){
                $queryStringSelect .= ', gdhc_participantTwo_u.id as id_2, gdhc_participantTwo_u.fullname as name_2';
                $queryStringFrom .= ', gdhc_sessions as gdhc_participantTwo_s, gdhc_users as gdhc_participantTwo_u';
                $queryStringWhere .= ' AND (gdhc_conversations.participantTwo = gdhc_participantTwo_s.id AND gdhc_participantTwo_s.userId = gdhc_participantTwo_u.id)  AND done = "Y" AND participantTwo IS NOT NULL AND participantTwo != ""';
            } elseif($type === 'latest'){
                $queryStringWhere .= ' AND done = "N" AND (participantTwo IS NULL OR participantTwo = "") AND gdhc_conversations.id > :anchor';
            } else {
                $cache = true;
            }

            $queryString = $queryStringSelect . $queryStringFrom . $queryStringWhere;
            
            $query = $this->getReadDB()->prepare($queryString);
            if($this->getAction() === 'open'){
                $query->bindParam(':sessionId', $sessionId, \PDO::PARAM_INT);
            }
            if($type !== 'allConversation'){
                $query->bindParam(':anchor', $conversationId, \PDO::PARAM_INT);
            }
            
            $query->execute();

            $rowCount = $query->rowCount();
            $conversationArray = [];
            if($rowCount > 0){
                /* BUILD RESPONS */
                while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                    $conversation = new \API\Models\Conversation((string) $row['id'], strval($row['participantOne']), strval($row['participantTwo']), $row['created']);
                    $conversationArray[$row['id']] = array_merge($conversation->returnAsArray(), [
                        'participantOne' => [
                            'id' => $row['participantOne'],
                            'name' => $row['name_1'],
                            'userId' => $row['id_1']
                        ],
                        'participantTwo' => [
                            'id' => $row['participantTwo'],
                            'name' => isset($row['name_2']) ? $row['name_2'] : '',
                            'userId' => isset($row['id_2']) ? $row['id_2'] : ''
                        ]
                    ]);
                }
            }

            
            if($this->getAction() === 'open'){ 
                $queryStringSelect = 'SELECT gdhc_conversations.id as id, participantOne, participantTwo, DATE_FORMAT(gdhc_conversations.created, "%d-%m-%Y %H:%i:%s") as created, gdhc_participantOne_u.id as id_1, gdhc_participantOne_u.fullname as name_1, gdhc_participantTwo_u.id as id_2, gdhc_participantTwo_u.fullname as name_2';
                $queryStringFrom = ' FROM gdhc_conversations, gdhc_sessions as gdhc_participantOne_s, gdhc_users as gdhc_participantOne_u, gdhc_sessions as gdhc_participantTwo_s, gdhc_users as gdhc_participantTwo_u';
                $queryStringWhere = ' WHERE (gdhc_conversations.participantOne = gdhc_participantOne_s.id AND gdhc_participantOne_s.userId = gdhc_participantOne_u.id) AND (gdhc_conversations.participantTwo = gdhc_participantTwo_s.id AND gdhc_participantTwo_s.userId = gdhc_participantTwo_u.id)  AND done = "N" AND participantTwo IS NOT NULL AND participantTwo != ""  AND done = "N" AND gdhc_participantTwo_s.userId = :userId';

                $queryString = $queryStringSelect . $queryStringFrom . $queryStringWhere;
            
                $query = $this->getReadDB()->prepare($queryString);
                if($this->getAction() === 'open'){
                    $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
                }
                
                $query->execute();

                $rowCount2 = $query->rowCount();

                if($rowCount2 > 0){
                    /* BUILD RESPONS */
                    while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                        $conversation = new \API\Models\Conversation((string) $row['id'], strval($row['participantOne']), strval($row['participantTwo']), $row['created']);
                        $conversationArray[$row['id']] = array_merge($conversation->returnAsArray(), [
                            'participantOne' => [
                                'id' => $row['participantOne'],
                                'name' => $row['name_1'],
                                'userId' => $row['id_1']
                            ],
                            'participantTwo' => [
                                'id' => $row['participantTwo'],
                                'name' => isset($row['name_2']) ? $row['name_2'] : '',
                                'userId' => isset($row['id_2']) ? $row['id_2'] : ''
                            ]
                        ]);
                    }
                    $rowCount = count($conversationArray);
                }
            }

            $conversationArray = array_values($conversationArray);

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Conversations not found');
                $response->send();
                exit;
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['conversations'] = $conversationArray;
            
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache($cache);
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
            $response->addMessage('Failed to get Conversation');
            $response->send();
            exit;
        }
    }

    private function updateConversation(&$jsonData){
        try {
            $userId = Authenticator::instance()->getUser();
            $conversationId = $this->getEntity();


            $queryString = 'SELECT gdhc_conversations.id as id, participantOne, participantTwo, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, done FROM gdhc_conversations, gdhc_sessions WHERE (gdhc_sessions.id = gdhc_conversations.participantTwo || gdhc_sessions.id = gdhc_conversations.participantOne) AND gdhc_conversations.id = :conversationId';

            if(isset($jsonData->done)){
                $queryString .= ' AND gdhc_sessions.userId = :userId';
            }

            $query = $this->getWriteDB()->prepare($queryString);
            if(isset($jsonData->done)){
                $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            }
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){  
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Could not find associated conversation to this user');
                $response->send();
                exit;
            }

            $done;
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $done = $row['done'];
                $conversation = new \API\Models\Conversation(strval($row['id']), strval($row['participantOne']), strval($row['participantTwo']), $row['created']);
            }

            /* UPDATE ENITY */

            $participantTwo_updated = false;
            $done_updated = false;
            
            $queryFields = '';
            
            if(isset($jsonData->participantTwo)){ 
                $participantTwo_updated = true;
                $queryFields .= 'participantTwo = :participantTwo, ';
            } if(isset($jsonData->done)){ 
                $done_updated = true;
                $queryFields .= 'done = :done, ';
            }
            $queryFields = rtrim($queryFields, ', ');

            if($participantTwo_updated === false && $done_updated === false){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage('No valid data has been provided');
                $response->send();
                exit;
            }

            if($done_updated && $done === 'Y'){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(409);
                $response->setSuccess(false);
                $response->addMessage('This chat has already been closed');
                $response->send();
                exit;
            } 
            if($participantTwo_updated && $conversation->getParticipantTwo() !== null && $conversation->getParticipantTwo() !== ''){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(409);
                $response->setSuccess(false);
                $response->addMessage('This chat has already been claimed');
                $response->send();
                exit;
            }


            $queryString = "UPDATE gdhc_conversations set ".$queryFields." WHERE ID = :conversationId";
            $query = $this->getWriteDB()->prepare($queryString);
            
            if($done_updated){
                $update_done = ($jsonData->done === 'Y') ? 'Y' : 'N';
                $query->bindParam(':done', $update_done, \PDO::PARAM_STR);
            }
            if($participantTwo_updated){
                $conversation->setParticipantTwo($jsonData->participantTwo);
                $update_participantTwo = $conversation->getParticipantTwo();
                $query->bindParam(':participantTwo', $update_participantTwo, \PDO::PARAM_STR);
            }

            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute(); 
            
            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage('Could not update conversation');
                $response->send();
                exit;
            }
            /* CHECK IF ENITY EXISTS AFTER UPDATE */
            $query = $this->getWriteDB()->prepare('SELECT id, participantOne, participantTwo, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, done FROM gdhc_conversations WHERE id = :conversationId');
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('No conversation found after update');
                $response->send();
                exit;
            }
            
            /* RETURNS NEWLY UPDATED MESSAGE */
            $conversationsArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $conversation = new \API\Models\Conversation(strval($row['id']), strval($row['participantOne']), strval($row['participantTwo']), $row['created']);
                $conversationsArray[] = array_merge($conversation->returnAsArray(), ['done' => $row['done']]);
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['conversations'] = $conversationsArray;

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
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
            $response->addMessage('Failed to update conversation - check submitted data for erros');
            $response->send();
            exit;
        }
    }

    private function setTyping($jsonData){
        try {
            $userId = Authenticator::instance()->getUser();
            $conversationId = $this->getEntity();

            if(!isset($jsonData->typing) || !isset($jsonData->participant)){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage('No valid data has been provided');
                $response->send();
                exit;
            }

            $queryString = 'SELECT gdhc_conversations.id as id, participantOne, participantTwo, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, done FROM gdhc_conversations, gdhc_sessions WHERE (gdhc_sessions.id = gdhc_conversations.participantTwo || gdhc_sessions.id = gdhc_conversations.participantOne) AND gdhc_conversations.id = :conversationId AND gdhc_sessions.userId = :userId';
            $query = $this->getWriteDB()->prepare($queryString);
            $query->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){  
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Could not find associated conversation to this user');
                $response->send();
                exit;
            }

            /* UPDATE ENITY */
            
            $queryFields = '';
            if($jsonData->participant == 'participantOne'){ 
                $queryFields .= 'participantOneTyping = '. ($jsonData->typing === 'Y' ? '"Y"' : '"N"');
            } elseif($jsonData->participant == 'participantTwo'){ 
                $queryFields .= 'participantTwoTyping = '. ($jsonData->typing === 'Y' ? '"Y"' : '"N"');
            } else {
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage('No valid data has been provided');
                $response->send();
                exit;
            }

            $queryString = "UPDATE gdhc_conversations set ".$queryFields." WHERE ID = :conversationId";
            $query = $this->getWriteDB()->prepare($queryString);
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute(); 
            
            /* CHECK IF ENITY EXISTS AFTER UPDATE */
            $query = $this->getWriteDB()->prepare('SELECT id, participantOne, participantTwo, DATE_FORMAT(created, "%d-%m-%Y %H:%i:%s") as created, done, participantOneTyping, participantTwoTyping FROM gdhc_conversations WHERE id = :conversationId');
            $query->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('No conversation found after update');
                $response->send();
                exit;
            }
            
            /* RETURNS NEWLY UPDATED MESSAGE */
            $conversationsArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                $conversation = new \API\Models\Conversation(strval($row['id']), strval($row['participantOne']), strval($row['participantTwo']), $row['created']);
                $conversationsArray[] = array_merge($conversation->returnAsArray(), ['done' => $row['done'], 'participantOneTyping' => $row['participantOneTyping'], 'participantTwoTyping' => $row['participantTwoTyping']]);
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['conversations'] = $conversationsArray;

            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
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
            $response->addMessage('Failed to update conversation - check submitted data for erros');
            $response->addMessage($queryFields);
            $response->send();
            exit;
        }
    }

    private function deleteConversation(){
        try {
            $conversationId = $this->getEntity();
            $this->getWriteDB()->beginTransaction();


            $messageQuery = $this->getWriteDB()->prepare('SELECT gdhc_messages.id as id FROM gdhc_messages WHERE conversationId = :conversationId');
            $messageQuery->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $messageQuery->execute();
            $messageRowCount = $messageQuery->rowCount();

            $conversationQuery = $this->getWriteDB()->prepare('SELECT id, participantOne, participantTwo FROM gdhc_conversations WHERE id = :conversationId');
            $conversationQuery->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $conversationQuery->execute();
            $conversationRowCount = $conversationQuery->rowCount();

            if($conversationRowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Conversation was not found');
                $response->send();
                exit;
            }

            if($messageRowCount > 0){
                $queryString = "DELETE FROM gdhc_messages WHERE id IN (";
                while($row = $messageQuery->fetch(\PDO::FETCH_ASSOC)){
                    $queryString = "{$queryString}{$row['id']},";
                }
                $queryString = rtrim($queryString, ',');
                $queryString = "{$queryString})";

                $messageQuery = $this->getWriteDB()->prepare($queryString);
                $messageQuery->execute();

                $rowCount = $messageQuery->rowCount();
    
                if($rowCount === 0){
                    $response = new \API\Models\Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage('Could not delete messages from conversation');
                    $response->send();
                    exit;
                }
            }

            $conversationData = $conversationQuery->fetch(\PDO::FETCH_ASSOC);

            $conversationQuery = $this->getWriteDB()->prepare('DELETE FROM gdhc_conversations WHERE id = :conversationId');
            $conversationQuery->bindParam(':conversationId', $conversationId, \PDO::PARAM_INT);
            $conversationQuery->execute();

            $rowCount = $conversationQuery->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Conversation was not found');
                $response->send();
                exit;
            }

            $data = $this->getWriteDB()->commit();
            
            return true;
        } catch(\PDOException $e){
            $this->getWriteDB()->rollback();
            
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to delete conversation');
            $response->addMessage($e->getMessage());
            $response->send();
            exit;
        }
        return false;
    }



    private function deleteConversations(){
        $this->authenticateRequest();
        try {
            
            $messageQuery = $this->getWriteDB()->prepare('SELECT id, participantOne, participantTwo FROM `gdhc_conversations` WHERE DATE(created) < DATE_SUB(CURDATE(), INTERVAL 14 DAY)');
            $messageQuery->execute();
            $messageRowCount = $messageQuery->rowCount();
            
            if($messageRowCount > 0){
                while($loop = $messageQuery->fetch(\PDO::FETCH_ASSOC)){
                    $this->setEntity($loop['id']);
                    $this->deleteConversation();
                }
            }
            
    
        
            $cleanSelect = $this->getWriteDB()->prepare("SELECT s.id as id FROM gdhc_sessions as s WHERE s.id NOT IN ( SELECT participantOne FROM gdhc_conversations WHERE participantOne = s.id ) AND s.id NOT IN ( SELECT participantTwo FROM gdhc_conversations WHERE participantTwo = s.id )");
            $cleanSelect->execute();

            $cleanSelectRowCount = $cleanSelect->rowCount();
            if($cleanSelectRowCount > 0){
                
                $sessionIds = [];
                while($loop = $cleanSelect->fetch(\PDO::FETCH_ASSOC)){
                    $sessionIds[$loop['id']] = $loop['id'];
                }

                if(count($sessionIds) > 0){
                    $this->getWriteDB()->beginTransaction();
                    $sessionIds = implode(',', $sessionIds);
                    $sessionQuery = $this->getWriteDB()->prepare('DELETE FROM gdhc_sessions WHERE id IN ('.$sessionIds.')');
                    $sessionQuery->execute();
                    $sessionQueryRowCount = $sessionQuery->rowCount();

                    if($sessionQueryRowCount === 0){
                        $response = new \API\Models\Response();
                        $response->setHttpStatusCode(500);
                        $response->setSuccess(false);
                        $response->addMessage('Could not remove stray sessions');
                        $response->send();
                        exit;
                    }
                    $this->getWriteDB()->commit();

                    $userCheckQuery = $this->getWriteDB()->prepare('SELECT u.id as id FROM gdhc_users as u WHERE id NOT IN ( SELECT userId FROM gdhc_sessions WHERE userId = u.id )');
                    $userCheckQuery->execute();

                    $userCheckQueryRowCount = $userCheckQuery->rowCount();
                    if($userCheckQueryRowCount > 0){
                        $userIds = [];
                        while($loop = $userCheckQuery->fetch(\PDO::FETCH_ASSOC)){
                            $userIds[$loop['id']] = $loop['id'];
                        }

                        if(count($userIds) > 0){
                            $this->getWriteDB()->beginTransaction();
                            $userIds = implode(',', $userIds);
                            $userQuery = $this->getWriteDB()->prepare('DELETE FROM gdhc_users WHERE id IN ('.$userIds.')');
                            $userQuery->execute();

                            $userQueryRowCount = $userQuery->rowCount();

                            if($userQueryRowCount === 0){
                                $response = new \API\Models\Response();
                                $response->setHttpStatusCode(500);
                                $response->setSuccess(false);
                                $response->addMessage('Could not remove users');
                                $response->send();
                                exit;
                            }
                            $this->getWriteDB()->commit();
                        }
                    }
                }

            }            
            return true;
        } catch(\PDOException $e){
            $this->getWriteDB()->rollback();
            
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to clean conversations');
            $response->send();
            exit;
        }
        return false;
    }



    private function authenticateRequest(){
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
        
        $password = $_SERVER['HTTP_AUTHORIZATION'];
        try {
            
            $query = $this->getWriteDB()->prepare('SELECT value FROM gdhc_settings WHERE name = "systemPassword"');
            $query->execute();
            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('No access allowed');
                $response->send();
                exit;
            }

            $row = $query->fetch(\PDO::FETCH_ASSOC);
            if(!isset($row['value']) || $row['value'] == null || strlen($row['value']) <= 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                if(!isset($row['value']) ||  $row['value'] == null){
                    $response->addMessage('Access token is missing from the header');
                } if(strlen($row['value']) <= 0){
                    $response->addMessage('Access token cannot be blank');
                }
                $response->send();
                exit;
            }

            if(!password_verify($password, $row['value'])){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage('You have no access');
                $response->send();
                exit;
            }

            return true;

        }  catch(\PDOException $e){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to authenticate cronjob');
            $response->send();
            exit;
        }
        return false;
    }


    private function validateData(&$jsonData){
        if(!isset($jsonData->participantOne) || !isset($jsonData->created)){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);

            if(!isset($jsonData->participantOne)){ $response->addMessage('Conversation participant hasn\'t been provided'); }
            if(!isset($jsonData->created)){ $response->addMessage('Conversation creation date hasn\'t been provided'); }

            $response->send();
            exit;
        }
        return new \API\Models\Conversation(null, strval($jsonData->participantOne), strval(null), $jsonData->created);        
    }
}