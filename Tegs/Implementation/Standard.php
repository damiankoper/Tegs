<?php
namespace Tegs\Implementation;

use Tegs\ArrayMethods\ArrayMethods as ArrayMethods;

class Standard extends \Tegs\Implementation
{
    protected $_syntax=array(
        "variable"=>array(
            "open"=>"{{",
            "close"=>"}}",
            "d_handler"=>"_echo"
        ),
        "control"=>array(
            "open"=>"{%",
            "close"=>"%}",
            "foreach"=>array(
                "end"=>"endforeach",
                "handler"=>"_foreach",
                "standalone"=>false,
                "nextIfFailure"=>array("else"),
                "ignoreAlone"=>false
            ),
            "block"=>array(
                "end"=>"endblock",
                "handler"=>"_pass",
                "standalone"=>false,
                "nextIfFailure"=>array("else"),
                "ignoreAlone"=>false
            ),
            "spaceless"=>array(
                "end"=>"endspaceless",
                "handler"=>"_spaceless",
                "standalone"=>false,
                "nextIfFailure"=>array("else"),
                "ignoreAlone"=>false
            ),
            "if"=>array(
                "end"=>"endif",
                "handler"=>"_if",
                "standalone"=>false,
                "nextIfFailure"=>array("elseif","else"),
                "ignoreAlone"=>false
            ),
            "elseif"=>array(
                "end"=>"endelseif",
                "handler"=>"_if",
                "standalone"=>false,
                "nextIfFailure"=>array("else"),
                "ignoreAlone"=>true
            ),
            "else"=>array(
                "end"=>"endelse",
                "handler"=>"_pass",
                "standalone"=>false,
                "nextIfFailure"=>array(),
                "ignoreAlone"=>true
            ),
            "for"=>array(
                "end"=>"endfor",
                "handler"=>"_for",
                "standalone"=>false,
                "nextIfFailure"=>array("else"),
                "ignoreAlone"=>false
            ),
            "include"=>array(
                "end"=>"",
                "handler"=>"_include",
                "standalone"=>true,
                "nextIfFailure"=>array("else"),
                "ignoreAlone"=>false
            ),
            "extends"=>array(
                "end"=>"",
                "handler"=>"_extends",
                "standalone"=>true,
                "nextIfFailure"=>array("else"),
                "ignoreAlone"=>false
            ),
            "set"=>array(
                "end"=>"",
                "handler"=>"_set",
                "standalone"=>true,
                "nextIfFailure"=>array("else"),
                "ignoreAlone"=>false
            ),
            "d_handler"=>"_getExceptionForImplementation"
        )
    );
    protected $_functions = array(
        ""=>array(
            "handler"=>"_expression"
        ),
        "lorem"=>array(
            "handler"=>"_lorem"
        )
    );

    protected $_filters = array(
        "length"=>array(
            "handler"=>"_length"
        ),
        "e"=>array(
            "handler"=>"_escape"
        )
    );

    protected $_delimiters = array(
        "array"=>".",
        "filter"=>"|"
    );

    protected function _for($arguments, $content, $scope)
    {
        $returnBuffer="";
        if (!\is_numeric($arguments[0])) {
            throw $this->_getExceptionForType("STRING", "INT");
        }
        for ($i=0;$i<\intval($arguments[0]);$i++) {
            $returnBuffer .= self::handle($scope, $content);
        }
        return $returnBuffer;
    }
    protected function _foreach($arguments, $content, $scope)
    {
        $returnBuffer="";
        if ($arguments[1]!=="as") {
            throw $this->_getExceptionForSyntax(\implode($arguments));
        }
        $foreach = $this->parseValue($arguments[0], $scope);
        if ($foreach ===false) {
            return false;
        }
        foreach ($foreach as $$arguments[2]) {
            $scope[$arguments[2]] = $$arguments[2];
            $returnBuffer .= self::handle($scope, $content);
        }
        return $returnBuffer;
    }
    protected function _echo($arguments, $content, $scope)
    {
        return self::_expression($arguments, $scope);
    }
    protected function _spaceless($arguments, $content, $scope)
    {
        $content = ArrayMethods::trimArrayByProp($content, "string", "content");
        $scope = ArrayMethods::trimArray($scope);
        return self::handle($scope, $content);
    }
    protected function _if($arguments, $content, $scope)
    {
        $bool = self::_expression($arguments,$scope);
        return ($bool) ? self::handle($scope, $content):false;
    }
    protected function _pass($arguments, $content, $scope)
    {
        return self::handle($scope, $content);
    }
    protected function _lorem($args, $scope)
    {
        if (!\is_numeric($args[0]) || $args[0] > \strlen($this->getLorem())) {
            throw $this->_getExceptionForValue($args[0], "MAX: ".\strlen($this->getLorem()));
        }
        return \substr($this->getLorem(), 0, $args[0]);
    }
    protected function _expression($args, $scope)
    {
        $bool = null;
        $bool_waiting = null;
        $vLeft = null;
        $vRight = null;
        $operator = null;
        foreach ($args as $argument) {
            switch ($argument) {
                case "&&":
                    $bool = ($bool===null)?$vLeft:$bool && $vLeft;
                    $bool_waiting = $argument;
                    $vLeft = $vRight = $operator = null;
                break;
                case "||":
                    $bool = ($bool===null)?$vLeft:$bool || $vLeft;
                    $bool_waiting = $argument;
                    $vLeft = $vRight = $operator = null;
                break;
                case "<": case ">": case "==": case "!=": case "<=": case ">=":
                case "+": case"-":case"*":case"/":case"**":
                    $operator = $argument;
                break;
                default:
                    if ($vLeft===null) {
                        $vLeft = $this->parseValue($argument, $scope);
                    } else {
                        $vRight = $this->parseValue($argument, $scope);
                        if($bool_waiting==="&&"){
                            $vLeft = $bool && self::_operator($operator, $vLeft, $vRight);
                            $bool_waiting=null;
                        }
                        elseif($bool_waiting==="||"){
                            $vLeft = $bool || self::_operator($operator, $vLeft, $vRight);
                            $bool_waiting=null;
                        }
                        else{
                        $vLeft = self::_operator($operator, $vLeft, $vRight);
                        }
                    }/* elseif (!$vLeft===null && $operator!==null && $vRight!==null) {
                        
                    } else {
                        throw $this->_getExceptionForSyntax($argument);
                    }*/
            }
        }
        return $vLeft;
    }
    protected function _length($var)
    {
        return (\is_array($var))?count($var):\strlen($var);
    }
    protected function _escape($var)
    {
        return \htmlspecialchars($var);
    }
    protected function _include($arguments, $content, $scope, &$tree)
    {
        $template = new \Tegs\Template(array("_template"=>$scope["_templateDir"]."/".\trim($this->parseValue($arguments[0], $scope), "\\/")));
        $key = \key($tree);
        \array_splice($tree, \key($tree), 0, $template->getTree());
        while ($key!==\key($tree)) {
            next($tree);
        }
    }
    protected function _extends($arguments, $content, $scope, &$tree)
    {
        $template = new \Tegs\Template(array("_template"=>$scope["_templateDir"]."/".\trim($this->parseValue($arguments[0], $scope), "\\/")));
        $parent_tree = $template->getTree();
        $blocks_parent = &self::_searchBlock($parent_tree);
        $blocks_child = self::_searchBlock($tree);
        foreach ($blocks_parent as &$block) {
            foreach ($blocks_child as $child) {
                if ($block->getMeta()["arguments"][0] === $child->getMeta()["arguments"][0]) {
                    $block->setContent($child->getContent());
                }
            }
        }
        $tree = $parent_tree;
    }
    protected function _set($arguments, $content, &$scope){
        if ($arguments[1]!=="as") {
            throw $this->_getExceptionForSyntax(\implode($arguments));
        }
        $scope[$arguments[0]]=self::_expression(\array_slice($arguments,2),$scope);
    }

    private function &_searchBlock(&$tree, $replace=false, $blocks=null)
    {
        $result = array();
        foreach ($tree as &$item) {
            if ($item->getMeta()["keyword"]==="block") {
                $result[]=&$item;
            }
            if ($item->getContent()!==null) {
                $content = $item->getContent();
                $more = &self::_searchBlock($content);
                $result = \array_merge($result, $more);
            }
        }
        return $result;
    }
    

    protected $_lorem = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis a volutpat quam. Cras et consequat turpis. Curabitur ultrices efficitur faucibus. Pellentesque sollicitudin dignissim dui ut hendrerit. Donec eu ante quis tellus mollis euismod. Cras tristique dui in blandit tempus. Morbi magna purus, tempus sit amet dolor id, mollis faucibus dolor. Mauris nunc dui, dictum quis tempor vel, imperdiet in quam. Interdum et malesuada fames ac ante ipsum primis in faucibus. Curabitur varius dapibus malesuada. Proin eget erat id nisi ornare molestie. Pellentesque a faucibus tortor. Phasellus nec nisi sit amet sapien finibus rhoncus. Etiam scelerisque porttitor sem quis elementum.";
}
