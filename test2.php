<?php

require_once "Tegs_autoloader.php";
use Tegs\Template as Template;
try {
    $template = new Template(array("_template"=>"tegs_templates/base.html.tegs"));
    echo $template->render(array("test"=>"damcio","empty_tab"=>array("xd"), "tablica"=>array("heh", "hih", "huh")));
} catch (Exception $e) {
    echo "<b>Error ".$e->getFile()."(".$e->getLine()."): </b>". $e->getMessage();
}
?>