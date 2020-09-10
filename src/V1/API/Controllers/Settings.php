<?php 
namespace API\Controllers;

class Settings extends Request {

    private $fields;

    public function __construct(){
        parent::__construct();

        $this->fields = [
            'initial' => [ 'initial' => true, 'initialMessage' => true, 'initialEmail' => true, 'initialName' => true],
            'header' => [ 'chatPlaceholder' => true ],
            'welcome' => [ 'introTitle' => true, 'introDescription' => true, 'formName' => true, 'formEmail' => true, 'formPrivacy' => true, 'formSubmit' => true ],
            'chat' => [ 'chatMessage' => true, 'chatClose' => true, 'chatNotification' => true, 'chatOverview' => true ],
            'connect' => [ 'connectButton' => true ],
            'users' => [ 'userJoin' => true, 'userTitle' => true, 'userButton' => true, 'userActive' => true, 'userInactive' => true],
            'archive' => [ 'archiveTitle' => true, 'archiveButton' => true, 'archiveRefresh' => true, 'archiveUsers' => true, 'archiveFilter' => true, 'archiveFilterDefault' => true ],
            "detail" => [ "detailMail" => true, "detailConversation" => true, "detailRemove" => true, 'detailArchive' => true ],
            "confirmation" => [ "confirmationMessage" => true, "confirmationYes" => true, "confirmationNo" => true ],
            "settings" => [ "settingsTitle" => true, "settingsMenu" => true ],
            'main' => [ "mainColor" => true, "primaryShade" => true ],
            'secondary' => [ "secondaryColor" => true, "secondaryShade" => true ],
            'ui' => [ "uiText" => true ],
            'system' => [ 'active' => true, 'inactiveUrl' => true, 'inactiveDescription' => true, 'inactiveButton' => true, 'activateLabel' => true, 'systemPassword' => true, 'systemDisabled' => true ],
            'end' => ['endTitle' => true, 'endDescription' => true ],
        ];
    }

    public function run(){
        if($_SERVER['REQUEST_METHOD'] == 'PATCH'){
            $this->PATCH();
        } elseif($_SERVER['REQUEST_METHOD'] == 'GET'){
            $this->GET();
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
            $query = $this->getReadDB()->prepare('SELECT * FROM gdhc_settings');
            $query->execute();
            
            $rowCount = $query->rowCount();

            /* BUILD RESPONS */
            $settingsArray = [];
            while($row = $query->fetch(\PDO::FETCH_ASSOC)){
                if(!isset($settingsArray[$row['category']])){
                    $settingsArray[$row['category']] = [];
                }
                $settingsArray[$row['category']][$row['name']] = $row['value'];
            }

            $returnData = [];
            $returnData['count'] = $rowCount;
            $returnData['settings'] = $settingsArray;
            
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(false);
            $response->setData($returnData);
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

    protected function POST(){ }

    protected function PATCH(){
        $this->validateRequestData();
        $jsonData = $this->fetchBody();
        $this->validateData($jsonData);
        $returnData = $this->updateSettings($jsonData);
        if($returnData){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Settings updated');
            $response->setData($returnData);
            $response->send();
        } else {
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue updating the settings - please try again');
            $response->send();
            exit;
        }
    }

    protected function DELETE(){ }

    protected function OPTIONS(){ }

    private function updateSettings(&$jsonData){
        if(\API\Controllers\Authenticator::instance()->isAdmin(true) === false){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage('You don\'t have access');
            $response->send();
            exit;
        }

        
        $queryString = "INSERT INTO gdhc_settings (name, value, category) VALUES ";
        foreach((array) $jsonData as $category => $items){
            foreach((array) $items as $name => $item){
                if($name === 'systemPassword'){
                    if($item != 'This is a placeholder value..'){
                        $hashed_password = password_hash($this->sanitize($item), PASSWORD_DEFAULT);
                        $queryString .= "('{$name}', '".$hashed_password."', '{$category}'),";
                    }                    
                } else {
                    $queryString .= "('{$name}', '".$this->sanitize($item)."', '{$category}'),";
                }
            }
        }
        $queryString = rtrim($queryString, ',') . "ON DUPLICATE KEY UPDATE value = VALUES(value);";
        try {
            $query = $this->getWriteDB()->prepare($queryString);
            $query->execute();

            $rowCount = $query->rowCount();
            if($rowCount === 0){
                $response = new \API\Models\Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(false);
                $response->addMessage('No settings where updated');
                $response->send();
                exit;
            }

            $returnData = [
                'count' => $rowCount
            ];
            return $returnData;
        } catch(\PDOException $e){
            error_log("Connection error - " . $e, 0);
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('There was an issue updating the settings - please try again');
            $response->send();
            exit;
        }
        return false;
    }

    private function validateData($jsonData){
        $errors = [];
        foreach((array) $jsonData as $category => $items){
            foreach((array) $items as $name => $item){
                if(!$this->fieldsExists($category, $name)){
                    $errors[$category] = $name;
                }
            }
        }

        if(count($errors) > 0){
            $response = new \API\Models\Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Invalid data supplied in settings');
            $response->setData($errors);
            $response->send();
            exit;
        }
    }

    private function fieldsExists($category, $name){
        return (isset($this->fields[$category]) && isset($this->fields[$category][$name]));
    }

}