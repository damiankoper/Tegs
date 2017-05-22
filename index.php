<?php
include_once "Tegs_core.php";
include_once "Tegs_loader.php" ;
include_once "Tegs_template.php" ;

use \Tegs\core\Tegs_core;
use \Tegs\loader\Tegs_loader;

try {
    
    $loader = new Tegs_loader("tegs_templates/");
    $Tegs = new Tegs_core($loader);
    $template = $Tegs->load("template.html.tegs");
    $template->display(array("test"=>"damcio"));

} catch (Exception $e) {
    echo "<b>Error ".$e->getFile()."(".$e->getLine()."): </b>". $e->getMessage();
}
