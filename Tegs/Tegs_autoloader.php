<?php 
spl_autoload_register(function ($class_name) {
    $class=explode("\\",$class_name);
    include end($class).'.php';
});