<?php
namespace Tegs;

class Template extends Base
{
    protected $_implementation;
    protected $_template;
    protected $_array;
    protected $_tree;
    protected $_parsed;
    protected $_content;
    public function __construct($options)
    {
        parent::__construct($options);

        //Statyczne podanie implementacji składni
        //TODO: Przenieść to wyżej
        $this->setImplementation(new Implementation\Standard());
    }

    protected function _array()
    {
        if($this->getContent()==null){
            if(\file_exists($this->_template)){
            $this->setContent(\file_get_contents($this->_template));}
            else {
                throw $this->_getExceptionForSyntax("template {$this->_template} not found");
            }
        }
        $content = $this->getContent();
        $syntax = $this->_implementation->getSyntax();

        $array = array();

        while ($content!=="") {
            $tag = $this->getImplementation()->findNextTag($content);
            if ($tag !== null) {
                $array[] = \substr($content, 0, $tag->getPos()["start"]);
                $array[] = \substr($content, $tag->getPos()["start"], $tag->getPos()["end"] - $tag->getPos()["start"]);
                $content = \substr($content, $tag->getPos()["end"]);
            } else {
                $array[] = $content;
                $content = "";
            }
        }

        foreach ($array as &$item) {
            $tag = new Tag();
            $tag->getTagMeta($item, $syntax);
            $item = $tag;
        }
        $this->setArray($array);
        return $array;
    }

    protected function _tree($array = null)
    {
        $array = ($array ===null) ? $this->getArray() : $array;
        $tree = array();
        $syntax = $this->_implementation->getSyntax();
        $wait = 0;

        foreach ($array as $key => $item) {
            if ($wait!==0) {
                $wait--;
                continue;
            }
            if ($item->getType()==="text" || $item->getType()==="variable") {
                $tree[] = $item;
            } else {
                if (\array_key_exists($item->getMeta()["keyword"], $syntax[$item->getType()])) {
                    if ($syntax[$item->getType()][$item->getMeta()["keyword"]]["standalone"]===true) {
                        $tree[] = $item;
                    } else {
                        $open = $key;
                        $open_c = 1;
                        $close = null;
                        foreach ($array as $key_search => $item_search) {
                            if ($key_search<=$key) {
                                continue;
                            }
                            if ($item_search->getMeta()["keyword"] == $syntax[$item->getType()][$item->getMeta()["keyword"]]["end"]) {
                                $open_c--;
                            }
                            if ($item_search->getMeta()["keyword"] == $item->getMeta()["keyword"]) {
                                $open_c++;
                            }
                            if ($open_c===0) {
                                $close = $key_search;
                                break;
                            }
                        }
                        if($close===null) throw self::_getExceptionForSyntax($item->getMeta()["keyword"]." closing tag not found");
                        $array_node = \array_slice($array, $open+1, $close-$open);
                        $item->setContent($this->_tree($array_node));
                        $tree[]=$item;
                        $wait = $close-$open;
                    }
                } else {
                    $found = false;
                    foreach ($syntax[$item->getType()] as $tag) {
                        if (\is_array($tag)) {
                            if ($tag["end"]===$item->getMeta()["keyword"]) {
                                $found=true;
                            }
                        }
                    }
                    if (!$found) {
                        throw parent::_getExceptionForImplementation($item->getMeta()["keyword"]);
                    }
                }
            }
        }
        return $tree;
    }
    public function getTree(){
        self::_array();
        return self::_tree();
    }
    public function render($scope)
    {
        $scope["_templateDir"] = \dirname($this->getTemplate());
        self::_array();
        $tree = self::_tree();
        return $this->getImplementation()->handle($scope, $tree);
    }

    protected function _getExceptionForSyntax($syntax)
    {
        return new \Exception("Syntax error {$syntax}");
    }

}
