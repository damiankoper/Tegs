<?php
include_once "Tegs_core.php";
include_once "Tegs_loader.php" ;
include_once "Tegs_template.php" ;

use \Tegs\core\Tegs_core;
use \Tegs\loader\Tegs_loader;

try {

    $loader = new Tegs_loader("tegs_templates/");
    $Tegs = new Tegs_core($loader);
    $template = $Tegs->load("base.html.tegs");
    $template->display(array("test"=>"damcio", "tablica"=>array("heh<p></p>", "hih", "huh")));
    //$template->display_block("damian",array("test"=>"damcio", "tablica"=>array("heh<b>hehehe</b>", "hih", "huh")));

} catch (Exception $e) {
    echo "<b>Error ".end($e->getTrace())["file"]."(".end($e->getTrace())["line"]."): </b>". $e->getMessage();
}
?>
<html