<?php
namespace Goodday\HelloChat;

use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

// Create export from Database (structure only)
// Check if a table exists, if not create when flushed
// Try out test

class AdminResources extends \SilverStripe\Core\Extension implements \SilverStripe\Core\Flushable {

	private static $isFlushing;

	public static function flush() {
		$tableCount = 5;

		self::$isFlushing = true;
		if(!defined('HELLO_CHAT_BASE_URL')) {
			define('HELLO_CHAT_BASE_URL', \SilverStripe\Control\Director::protocolAndHost().\SilverStripe\Control\Director::baseURL());
		}

		if(!defined('HELLO_CHAT_DB_CONNECTOR')) {
			define('HELLO_CHAT_DB_CONNECTOR', 'SilverStripe');
		}

		if(!defined('HELLO_CHAT_ROOT')){
			define('HELLO_CHAT_ROOT', dirname(__FILE__).'/src');
		}
		
		try {
			$db = \API\Controllers\DB::connectWriteDB();		
        } catch(\PDOException $e) {
			throw new Exception("Database connection error", 1);
            exit;
		}
	
		try {
			$sql = "SHOW TABLES LIKE 'gdhc_%'";
			$tablesExists = $db->query($sql)->rowCount() == $tableCount;
			if($tablesExists){ return; }
		} catch(\PDOException $e){
			throw new Exception("Could not check database for tables. ".$e->getMessage(), 1); 
		}

		try {
			$structure = file_get_contents(dirname(dirname(__FILE__)).'/_config/database-structure.sql');
			$alter = file_get_contents(dirname(dirname(__FILE__)).'/_config/structure-alter.sql');
        } catch(Exception $e) {
			throw new Exception("Could not fetch database setup", 1); 
			exit;
        }

		try {
			$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$row_count = $db->exec($structure);

			$sql = "SHOW TABLES LIKE 'gdhc_%'";
			$tablesExists = $db->query($sql)->rowCount() == $tableCount;
			if($tablesExists){
				try {
					$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
					$row_count = $db->exec($alter);
				}  catch(\PDOException $e){
					throw new Exception("Could not alter tables. ".$e->getMessage(), 1); 
				}
			}
		} catch(\PDOException $e){
			throw new Exception("Could not create table structures. ".$e->getMessage(), 1); 
		}
    }

	function onAfterInit() {
		if(!$this->chatCanInitialize()){ return; }

		if(!defined('HELLO_CHAT_BASE_URL')) {
			define('HELLO_CHAT_BASE_URL', \SilverStripe\Control\Director::protocolAndHost().\SilverStripe\Control\Director::baseURL());
		}

		if(!defined('HELLO_CHAT_DB_CONNECTOR')) {
			define('HELLO_CHAT_DB_CONNECTOR', 'SilverStripe');
		}

		if ($this->isAdminArea()) {
			$this->initAdminChat();
			return;
		} 
		if($this->isFrontendArea() && !$this->isPreviewArea()) {
        	$this->initFrontendChat();
        	return;
        }
	}

	private function authenticate(){
		$member = \SilverStripe\Security\Security::getCurrentUser();
		if(is_null($member)){ return false; }
        if($member->isLockedOut()){ return false; }
        if(!$member->inGroup(2)){ return false; }
		
		setcookie(
			'hc_ss',
			serialize(['mail'=>$member->Email,'firstname'=>$member->FirstName]),
			0,
			\API\Controllers\Config::instance()->get('path').'; samesite=strict',
			\API\Controllers\Config::instance()->get('domain'),
			\API\Controllers\Config::instance()->get('secure') === true ? true : false,
			TRUE
		);
		return true;
	}

	private function isAdminArea(){
		return strpos($this->getOwner()->getRequest()->getUrl(), 'admin') === 0 ? true : false;
	}

	private function isFrontendArea(){
		return strpos($this->getOwner()->getRequest()->getUrl(), 'admin') === 0 ? false : true;
	}

	private function isPreviewArea(){
		return isset($_GET['CMSPreview']) ? true : false;
	}
	
	private function initAdminChat(){
		if(!defined('API_ROOT')){
			define('API_ROOT', dirname(__FILE__).'/src/V1/API');
		}

		if($this->authenticate()){
			$_GET['admin'] = true;
			require_once(dirname(__FILE__).'/src/index.php');
		} else {
			setcookie(
				'hc_ss',
				null,
				-1,
				\API\Controllers\Config::instance()->get('path').'; samesite=strict',
				\API\Controllers\Config::instance()->get('domain'),
				\API\Controllers\Config::instance()->get('secure') === true ? true : false,
				TRUE
			);
		}
	}

	private function initFrontendChat(){
		require_once(dirname(__FILE__).'/src/index.php');
	}

	private function chatCanInitialize(){
		if(self::isFlushing()){ return false; }
		if($this->isSecurityArea()){ return false; }
		
		if($this->isAjax()){ return false; }
		if($this->isAssetRequest()){ return false; }
		
		if($this->hasRequestContentType()){ return false; }
		if($this->expectsJSON()){ return false; }

		return true;
	}

	private static function isFlushing(){
		return (self::$isFlushing === true) ? true : false;
	}

	private function hasRequestContentType(){
		return isset($_SERVER['CONTENT_TYPE']) ? true : false;
	}

	private function expectsJSON(){
		return isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/json' ? true : false;
	}

	private function isAjax(){
		return \SilverStripe\Control\Director::is_ajax() ? true : false;
	}

	private function isAssetRequest(){
		return strpos($this->getOwner()->getRequest()->getUrl(), 'assets') === 0 ? true : false;
	}

	private function isSecurityArea(){
		return strpos($this->getOwner()->getRequest()->getUrl(), 'Security') === 0 ? true : false;
	}

}
