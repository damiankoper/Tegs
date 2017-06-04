<?php
require_once "Tegs_autoloader.php";
use Tegs\Test as Test;
use Tegs\Template as Template;

$template = new Template(array());

Test::add(function() use ($template){
    if($template instanceof Template) return true;
},"Inicjacja","Tegs");

Test::add(function() use ($template){
    if($template->getImplementation() instanceof Tegs\Implementation) return true;
},"Inicjacja implementacji","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{"foo bar| . baz"}}');
    $process = $template->render(array());
    return ($process==="foo bar| . baz")?true:false;
},"Wyświetlanie stringa","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{"{%%}{{}}"}}');
    $process = $template->render(array());
    return ($process==="{%%}{{}}")?true:false;
},"Wyświetlanie tagów","Tegs");


Test::add(function() use ($template){
    $template->setContent('{{1}}');
    $process = $template->render(array());
    return ($process==="1")?true:false;
},"Wyświetlanie numeru","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{"foo bar| . baz"|length}}');
    $process = $template->render(array());
    return ($process==="14")?true:false;
},"Wyświetlanie stringa - filtr length","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{foo}}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="bar")?true:false;
},"Wyświetlanie zmiennej","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{(foo - bar + bar) == (1 + 1) && (foo + bar) == (foo + bar) && 1 == foo}}');
    $process = $template->render(array("foo"=>"2","bar"=>"1"));
    return ($process==="")?true:false;
},"Expressions (exp)","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{2 ** 2}}');
    $process = $template->render(array("foo"=>"1","bar"=>"1"));
    return ($process==="4")?true:false;
},"Expressions (pow operator **)","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{foo|length}}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="3")?true:false;
},"Wyświetlanie zmiennej - filtr length","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{lorem(11)}}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="Lorem ipsum")?true:false;
},"Funkcja lorem(11)","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{foo.bar}}');
    $process = $template->render(array("foo"=>array("bar"=>"baz")));
    return ($process==="baz")?true:false;
},"Dostęp do tablicy asocjacyjnej","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{foo.0}}');
    $process = $template->render(array("foo"=>array("baz")));
    return ($process==="baz")?true:false;
},"Dostęp do tablicy przez index","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%if foo == "bar"%}bar{%endif%}{%else%}foo{%endelse%}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="bar")?true:false;
},"If (var == string) - true","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%if foo%}bar{%endif%}{%else%}foo{%endelse%}');
    $process = $template->render(array("foo"=>true));
    return ($process==="bar")?true:false;
},"If (bool) - true","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%if foo != "foo"%}bar{%endif%}{%else%}foo{%endelse%}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="bar")?true:false;
},"If (var != string) - true","Tegs");
Test::add(function() use ($template){
    $template->setContent('{%if foo == "foo"%}bar{%endif%}{%else%}foo{%endelse%}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="foo")?true:false;
},"If else (var == string) - false","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%for 5%}foo{%endfor%}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="foofoofoofoofoo")?true:false;
},"For 5 times","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%foreach foo as bar%}{{bar}}{%endforeach%}');
    $process = $template->render(array("foo"=>array("bar")));
    return ($process==="bar")?true:false;
},"Foreach","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%foreach foobar as bar%}{{bar}}{%endforeach%}{%else%}bar{%endelse%}');
    $process = $template->render(array("foobar"=>array()));
    return ($process==="bar")?true:false;
},"Foreach else","Tegs");

Test::add(function() use ($template){
    $template->setContent("{%spaceless%}{%foreach foobar as bar%}{%foreach bar as foo%}{{foo}}{%endforeach%}{%endforeach%}{%endspaceless%}");
    $process = $template->render(array("foobar"=>array("foo"=>array("baz","boo"),"bar"=>array("moo","fee"))));
    return ($process==="bazboomoofee")?true:false;
},"Foreach deep in scope","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%spaceless%}  {{foobar}} {{foobar}} {%endspaceless%}');
    $process = $template->render(array("foobar"=>"foo  bar"));
    return ($process==="foo bar foo bar")?true:false;
},"Spaceless value","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%spaceless%} {%if foo == "bar"%}bar bar{%endif%}{%else%}foo{%endelse%}{{foo}}{%endspaceless%}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="bar barbar")?true:false;
},"Spaceless tags","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%block%}foo{{foo}}{{"foo"}}{%endblock%}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="foobarfoo")?true:false;
},"Block - text variable string","Tegs");

Test::add(function() use ($template){
    $template->setContent('{{"<p>foo</p>"|e}}');
    $process = $template->render(array("foo"=>"bar"));
    return ($process==="&lt;p&gt;foo&lt;/p&gt;")?true:false;
},"Filter escape html","Tegs");

Test::add(function() use ($template){
    $template->setContent(null);
    $template->setTemplate('tegs_templates/test_1.html.tegs');
    $process = $template->render(array("foo"=>"bar","bar"=>"baz"));
    return ($process==="barbaz")?true:false;
},"Template from file - include","Tegs");

Test::add(function() use ($template){
    $template->setContent(null);
    $template->setTemplate('tegs_templates/test_3.html.tegs');
    $process = $template->render(array("foo"=>"bar","bar"=>"baz"));
    return ($process==="foo bar baz")?true:false;
},"Template from file - extends simple","Tegs");

Test::add(function() use ($template){
    $template->setContent(null);
    $template->setTemplate('tegs_templates/test_5.html.tegs');
    $process = $template->render(array("foo"=>"bar","bar"=>"baz"));
    return ($process==="fooo fooo fooo bar baz")?true:false;
},"Template from file - extends duplicates","Tegs");

Test::add(function() use ($template){
    $template->setContent('{%set foo as 5%}{{foo + 5}}');
    $process = $template->render(array());
    return ($process==="10")?true:false;
},"Set variable simple","Tegs");

Test::run();