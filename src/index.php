<?php 

// Notification when new user opens a chat
// Automatically close chat if it hasn't been picked up withing a day
// add silverstripe intergration

// check what happens when claiming a chat (while someone is already chatting)
// maybe receive chat notifications from other chats (while not in that chat)


// do frontend error handling




namespace HelloChat;
global $HelloChat;

$version = 'V1';


define('HELLO_CHAT_ROOT', dirname(__FILE__));
define('HELLO_CHAT_VERSION_ROOT', HELLO_CHAT_ROOT. '/'.$version);
define('HELLO_CHAT_DIR', HELLO_CHAT_VERSION_ROOT. '/HelloChat');
define('HELLO_CHAT_CORE', HELLO_CHAT_DIR.'/Core');
$url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
$url = explode('?', $url);
define('HELLO_CHAT_URL', is_array($url) ? $url[0] : $url);
define('HELLO_CHAT_VERSION_URL', HELLO_CHAT_URL . $version . '/');
define('HELLO_CHAT_RESOURCE_URL', HELLO_CHAT_VERSION_URL .'HelloChat/resources/');

define('HELLO_CHAT_VERSION_API', HELLO_CHAT_URL . 'api/'. $version . '/');



include $version.'/autoload.php';
$HelloChat = new Core\Main();

if(isset($_GET['admin'])){
    include 'admin-view.php';
} else {
    include 'client-view.php';
}