<?php 

//disable if not on ssl
//

// do frontend error handling

// maybe receive chat notifications from other chats (while not in that chat)
// Notification when new user opens a chat



namespace HelloChat;
global $HelloChat;

$version = 'V1';

if(!isset($_SERVER['HTTPS'])){
	return;
}

define('HELLO_CHAT_ROOT', dirname(__FILE__));
define('HELLO_CHAT_VERSION_ROOT', HELLO_CHAT_ROOT. '/'.$version);
define('HELLO_CHAT_DIR', HELLO_CHAT_VERSION_ROOT. '/HelloChat');
define('HELLO_CHAT_CORE', HELLO_CHAT_DIR.'/Core');

if(defined('HELLO_CHAT_BASE_URL')){
	$url = HELLO_CHAT_BASE_URL;
} else {
	$url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	$url = explode('?', $url);
	define('HELLO_CHAT_BASE_URL', $url);
}

define('HELLO_CHAT_URL', is_array($url) ? $url[0] .'hellochat/src/src/' : $url .'hellochat/src/src/');
define('HELLO_CHAT_VERSION_URL', HELLO_CHAT_URL . $version . '/');
define('HELLO_CHAT_RESOURCE_URL', HELLO_CHAT_VERSION_URL .'HelloChat/resources/');

define('HELLO_CHAT_VERSION_API', HELLO_CHAT_URL . 'api/'. $version . '/');


include HELLO_CHAT_VERSION_ROOT.'/autoload.php';
$HelloChat = new Core\Main();

if(isset($_GET['admin'])){
    include 'admin-view.php';
} else {
    include 'client-view.php';
}