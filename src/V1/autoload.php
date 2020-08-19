<?php 
    spl_autoload_register(function ($class){
        require dirname(__FILE__) . '/' . str_replace("\\", '/', $class) . '.php';
    });