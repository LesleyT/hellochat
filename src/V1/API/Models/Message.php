<?php 
namespace API\Models;

class Message {

    private $_maxID = '9223372036854775807'; // PHP_INT_MAX 

    private $_id;
    private $_name;
    private $_email;
    private $_content;
    private $_created;
    private $_conversation;
    

    function __construct($id, $name, $email, $content, $created, $conversation){
        $this->setID($id);
        $this->setName($name);
        $this->setEmail($email);
        $this->setContent($content);
        $this->setCreated($created);
        $this->setConversation($conversation);
    }
    

     /* GETTERS */

    public function getID() {
        return $this->_id;
    }
 
    public function getName() {
        return $this->_name;
    }
 
    public function getEmail() {
        return $this->_email;
    }
 
    public function getContent() {
        return $this->_content;
    }
 
    public function getCreated() {
        return $this->_created;
    }
 
    public function getConversation() {
        return $this->_conversation;
    }


    /* SETTERS */

    public function setID($id) {
        if($id !== null && !is_string($id)){
            throw new \API\Models\Exceptions\MessageException('Message ID no string error');
        }
        if($id !== null && (!is_numeric($id) || bccomp($id, 0) !== 1 || bccomp($id, $this->_maxID) === 1 || $this->getID() !== null)){
            throw new \API\Models\Exceptions\MessageException('Message ID error');
        }
        $this->_id = $id;
        return $this;
    }

    public function setName($name) {
        if(strlen($name) < 0 || strlen($name) > 100){
            throw new \API\Models\Exceptions\MessageException('Message owner name error');
        }
        $this->_name = $name;
        return $this;
    }

    public function setEmail($email) {
        if(strlen($email) < 0 || strlen($email) > 100){
            throw new \API\Models\Exceptions\MessageException('Message owner email error');
        }
        $this->_email = $email;
        return $this;
    }

    public function setContent($content) {
        if(($content !== null) && (strlen($content) < 0 || strlen($content) > 16777215)){
            throw new \API\Models\Exceptions\MessageException('Message content error');
        }
        $this->_content = $content;
        return $this;
    }

    public function setCreated($created) {
        if(($created !== null) && date_format(date_create_from_format('d-m-Y H:i:s', $created), 'd-m-Y H:i:s') != $created){
            throw new \API\Models\Exceptions\MessageException('Created date time error');
        }
        $this->_created = $created;
        return $this;
    }

    public function setConversation($id) {
        if(!is_string($id)){
            throw new \API\Models\Exceptions\MessageException('Conversation ID no string error');
        }
        if((!is_numeric($id) || bccomp($id, 0) !== 1 || bccomp($id, $this->_maxID) === 1 || $this->getConversation() !== null)){
            throw new \API\Models\Exceptions\MessageException('Conversation ID error');
        }
        
        $this->_conversation = $id;
        return $this;
    }

    public function returnAsArray(){
        return [
            'id' => $this->getID(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'content' => $this->getContent(),
            'created' => $this->getCreated(),
            'conversationId' => $this->getConversation()
        ];
    }
}