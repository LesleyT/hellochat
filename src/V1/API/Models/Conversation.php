<?php 
namespace API\Models;

class Conversation {

    private $_maxID = '9223372036854775807'; // PHP_INT_MAX 

    private $_id;
    private $_participantOne;
    private $_participantTwo;
    private $_created;
    

    function __construct($id, $participantOne, $participantTwo = null, $created){
        $this->setID($id);
        $this->setParticipantOne($participantOne);
        $this->setParticipantTwo($participantTwo);
        $this->setCreated($created);
    }
    

     /* GETTERS */

    public function getID() {
        return $this->_id;
    }
 
    public function getParticipantOne() {
        return $this->_participantOne;
    }
 
    public function getParticipantTwo() {
        return $this->_participantTwo;
    }
 
    public function getCreated() {
        return $this->_created;
    }


    /* SETTERS */

    public function setID($id) {
        if($id !== null && !is_string($id)){
            throw new \API\Models\Exceptions\ConversationException('Conversation ID no string error');
        }
        if($id !== null && (!is_numeric($id) || bccomp($id, 0) !== 1 || bccomp($id, $this->_maxID) === 1 || $this->getID() !== null)){
            throw new \API\Models\Exceptions\ConversationException('Conversation ID error');
        }
        $this->_id = $id;
        return $this;
    }

    public function setParticipantOne($participantOne) {
        if(!is_string($participantOne)){
            throw new \API\Models\Exceptions\ConversationException('Participant One ID no string error');
        }
        if((!is_numeric($participantOne) || bccomp($participantOne, 0) !== 1 || bccomp($participantOne, $this->_maxID) === 1 || $this->getParticipantOne() !== null)){
            throw new \API\Models\Exceptions\ConversationException('Participant One ID error');
        }
        $this->_participantOne = $participantOne;
        return $this;
    }

    public function setParticipantTwo($participantTwo) {
        if($participantTwo != null && !is_string($participantTwo)){
            throw new \API\Models\Exceptions\ConversationException('Participant Two ID no string error');
        }
        if($participantTwo != null && (!is_numeric($participantTwo) || bccomp($participantTwo, 0) !== 1 || bccomp($participantTwo, $this->_maxID) === 1)){
            throw new \API\Models\Exceptions\ConversationException('Participant Two ID error');
        }
        $this->_participantTwo = $participantTwo;
        return $this;
    }

    public function setCreated($created) {
        if(($created !== null) && date_format(date_create_from_format('d-m-Y H:i:s', $created), 'd-m-Y H:i:s') != $created){
            throw new \API\Models\Exceptions\ConversationException('Created date time error');
        }
        $this->_created = $created;
        return $this;
    }

    public function returnAsArray(){
        return [
            'id' => $this->getID(),
            'participantOne' => $this->getParticipantOne(),
            'participantTwo' => $this->getParticipantTwo(),
            'created' => $this->getCreated()
        ];
    }
}