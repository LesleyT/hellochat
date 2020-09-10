<?php 
    namespace HelloChat;

    if(array_key_exists('mode', $_GET)){
        $mode = $_GET['mode'];
        if($mode != 0 && $mode != 1){
            http_response_code(404);
            echo "<h1>Page not Found</h1>";
            exit;
        }
    } else {
        http_response_code(404);
        echo "<h1>Page not Found</h1>";
        exit;
    }

    global $HelloChat;

    $version = 'V1';
    define('HELLO_CHAT_ROOT', dirname(dirname(__FILE__)));
    define('HELLO_CHAT_VERSION_ROOT', HELLO_CHAT_ROOT. '/'.$version);
    define('HELLO_CHAT_DIR', HELLO_CHAT_VERSION_ROOT. '/HelloChat');
    define('HELLO_CHAT_CORE', HELLO_CHAT_DIR.'/Core');
    $url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    $url = explode('?', $url);
    define('HELLO_CHAT_URL', is_array($url) ? dirname($url[0]).'/' : dirname($url).'/');

    define('HELLO_CHAT_VERSION_URL', HELLO_CHAT_URL . '/' . $version . '/');
    define('HELLO_CHAT_RESOURCE_URL', HELLO_CHAT_VERSION_URL .'HelloChat/resources/');

    define('HELLO_CHAT_VERSION_API', HELLO_CHAT_URL . 'api/' . $version . '/');

    if(!defined('API_ROOT')){
        define('API_ROOT', HELLO_CHAT_VERSION_ROOT.'/API');
    }

    include HELLO_CHAT_VERSION_ROOT.'/autoload.php';

    if($mode == 0){
        include HELLO_CHAT_DIR.'/Views/admin.php';
    } else {
        include HELLO_CHAT_DIR.'/Views/client.php';
    }
?>