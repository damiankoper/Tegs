<?php 
spl_autoload_register(function ($class_name) {
    include "Tegs\\".end(explode("\\",$class_name)). '.php';
});