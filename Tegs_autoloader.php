<?php 
spl_autoload_register(function($classname){
    $dir = __DIR__;
        require_once __DIR__."\\".$classname.".php";
});