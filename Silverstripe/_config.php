<?php

namespace Goodday\HelloChat;

$dir = basename(dirname(__FILE__));
if($dir != "hellochat") {
	user_error('HelloChat: Directory name must be "hellochat" (currently "'.$dir.'")',E_USER_ERROR);
}

if (!isset($_GET['flush']) && headers_sent()){
	die();
	include dirname(__FILE__).'/src/src/index.php';
}