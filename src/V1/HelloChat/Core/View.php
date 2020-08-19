<?php 
namespace HelloChat\Core;
class View {

    function __construct (){
        
    }

    function load($view, $data = []){
        extract((array) $data, EXTR_SKIP);
        include
    }

}