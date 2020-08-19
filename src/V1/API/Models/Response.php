<?php 
namespace API\Models;

class Response {

    private $_httpStatusCode;
    private $_success;
    private $_messages = [];
    private $_data;
    private $_toCache = false;
    private $_responseData = [];

        

    /* GETTERS */
 
    public function getHttpStatusCode() {
        return $this->_httpStatusCode;
    }
 
    public function getSuccess() {
        return $this->_success;
    }
 
    public function getMessages() {
        return $this->_messages;
    }
 
    public function getData() {
        return $this->_data;
    }
 
    public function getToCache() {
        return $this->_toCache;
    }
 
    public function getResponseData() {
        return $this->_responseData;
    }


    /* SETTERS */

    public function setHttpStatusCode($httpStatusCode) {
        $this->_httpStatusCode = $httpStatusCode;
        return $this;
    }
    
    public function setSuccess($success) {
        $this->_success = $success;
        return $this;
    }

    public function addMessage($message) {
        $this->_messages[] = $message;
        return $this;
    }

    public function setData($data) {
        $this->_data = $data;
        return $this;
    }

    public function toCache($toCache) {
        $this->_toCache = $toCache;
        return $this;
    }

    public function send(){
        header('Content-type: application/json;charset=utf-8');

        if($this->_toCache === true) {
            header('Cache-control: max-age=60'); //cached for max 60 seconds
        } else {
            header('Cache-control: no-cache, no-store');
        }

        if(($this->getSuccess() !== false && $this->getSuccess() !== true) || !is_numeric($this->getHttpStatusCode())) {
            http_response_code(500);
            $this->_responseData['statusCode'] = 500;
            $this->_responseData['success'] = false;
            $this->_responseData['messages'] = ['Response creation error'];
            $this->_responseData['data'] = $this->getData();
        } else {
            http_response_code($this->getHttpStatusCode());
            $this->_responseData['statusCode'] = $this->getHttpStatusCode();
            $this->_responseData['success'] = $this->getSuccess();
            $this->_responseData['messages'] = $this->getMessages();
            $this->_responseData['data'] = $this->getData();
        }

        echo json_encode($this->_responseData);
    }
}