<?php 
    spl_autoload_register(function ($class){
    	if(file_exists(HELLO_CHAT_VERSION_ROOT.'/'.str_replace("\\", '/', $class).'.php')){
        	require dirname(__FILE__) . '/' . str_replace("\\", '/', $class) . '.php';
    	}
    });