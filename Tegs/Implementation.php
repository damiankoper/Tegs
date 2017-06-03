<?php
namespace Tegs;

class Implementation extends Base
{
    protected function _handler($scope, $tree)
    {
        $returnBuffer = "";
        $waitingFor = null;
        while (list($key,$item) = \each($tree)) {
            $type = $item->getType();
            $syntax = $this->getSyntax();
            $meta = $item->getMeta();
            if ($waitingFor===null && \array_key_exists($type, $syntax) && \array_key_exists($meta["keyword"], $syntax[$type])) {
                if ($syntax[$type][$meta["keyword"]]["ignoreAlone"]===true) {
                    continue;
                }
            };
            if ($type!=="text") {
                $waitingFor = null;
            }
            if (\array_key_exists($type, $syntax) && \array_key_exists($meta["keyword"], $syntax[$type])) {
                $response = \call_user_func_array(array($this,$syntax[$type][$meta["keyword"]]["handler"]), array(
                $meta["arguments"],
                $item->getContent(),
                &$scope, &$tree
                ));
                if ($response!==false) {
                    $returnBuffer.=$response;
                } else {
                    $waitingFor = $syntax[$type][$meta["keyword"]]["nextIfFailure"];
                }
            } elseif (\array_key_exists($type, $syntax) && !\array_key_exists($meta["keyword"], $syntax[$type])) {
                $returnBuffer .= \call_user_func_array(array($this,$syntax[$type]["d_handler"]), array(
                $meta["arguments"],
                $item->getContent(),
                &$scope, &$tree
                ));
            } elseif ($type==="text") {
                $returnBuffer .= $item->getString();
            } else {
                throw $this->_getExceptionForSyntax(\print_r($item), true);
            }
        }
        return $returnBuffer;
    }
    public function handle($scope, $tree)
    {
        try {
            return self::_handler($scope, $tree);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function findNextTag($content)
    {
        $syntax = self::getSyntax();
        $position = false;
        $meta_key = null;
        foreach ($syntax as $key => $type) {
            $search = \strpos($content, $type["open"]);
            if ($search < $position || $position===false) {
                $position = $search;
                $meta_key = $key;
            }
        }
        if ($position!==false) {
            $tag = new Tag();
            $tag->getSingleTagFromString($content, $position, $syntax[$meta_key]);
        } else {
            $tag = null;
        }
        return $tag;
    }
    protected function _getExceptionForSyntax($syntax)
    {
        return new \Exception("Syntax error {$syntax}");
    }
    protected function _getExceptionForType($type, $should)
    {
        return new \Exception("Wrong type {$type}, should be {$should}");
    }
    protected function _getExceptionForValue($type, $should)
    {
        return new \Exception("Wrong value {$type}, should be {$should}");
    }
    protected function parseValue($name, $scope)
    {
        $return_scope = $scope;
        //$array_tree = \explode($this->getDelimiters()["array"],$name);
        preg_match_all('/\([\s\S]+\)|"(?:\\\\.|[^\\\\"])*"\S+|"(?:\\\\.|[^\\\\"])*"|(?:[^'.$this->getDelimiters()["array"].']+)\((?:\\\\.|[^\\\\)])*\)|[^'.$this->getDelimiters()["array"].'\s]+/', $name, $array_tree);
        
        foreach ($array_tree[0] as $step) {
            \preg_match('/(\S*)\(([\s\S]*)?\)|(?:"[\S\s]+")|[^'.$this->getDelimiters()["filter"].']+/',$step,$step);
            $step = $step[0];
            \preg_match("/\"[\s\S]+\"/", $step, $matchString);
            \preg_match("/([^\(]*)\(([\s\S]*)?\)$/", $step, $matchFunction);
            \preg_match("/^[0-9]+$/", $step, $matchNumber);
            if (\is_array($return_scope)&&\array_key_exists($step, $return_scope)) {
                $return_scope = $return_scope[$step];
                if ($return_scope == null || empty($return_scope)) {
                    return false;
                }
            } elseif ((!empty($matchString[0])&& empty($matchFilter[0]))||!empty($matchNumber[0])) {
                $return_scope = \trim($step, "\"");
                break;
            } elseif (!empty($matchFunction[0])) {
                $return_scope = self::parseFunction($matchFunction[1], $matchFunction[2], $scope);
            }
            else {
                throw $this->_getExceptionForSyntax("$step not found");
            }
        }
        \preg_match_all('/"(?:\\\\.|[^\\\\])*"|[^\|]+|(?:\\'.$this->getDelimiters()["filter"].'[\S]*)/', $name, $filter_tree);
        if (\array_key_exists("1",$filter_tree[0])) {
            foreach (\array_filter(\explode($this->getDelimiters()["filter"],$filter_tree[0][1])) as $filter) {
                $return_scope = self::parseFilter($filter, $return_scope);
            }
        }
        return $return_scope;
    }
    private function parseFunction($name, $args, $scope)
    {
        \preg_match_all("/\([^\(]+\)|[^\s,]+/", $args, $args_array);
        $functions = $this->getFunctions();
        $response="";
        if (\array_key_exists($name, $functions)) {
            $response = \call_user_func_array(array($this,$functions[$name]["handler"]), array(
                $args_array[0],
                &$scope
                ));
        }
        return $response;
    }
    private function parseFilter($name, $var)
    {
        $filters = $this->getFilters();
        $response="";
        if (\array_key_exists($name, $filters)) {
            $response = \call_user_func_array(array($this,$filters[$name]["handler"]), array(
                $var
                ));
        }
        else throw $this->_getExceptionForSyntax("undefined filter {$name}");
        return $response;
    }
    private function parseExpression($name, $scope)
    {
      $name;
        return $response;
    }
    protected function _operator($operator, $vLeft, $vRight)
    {
        if ($operator ===null || $vLeft===null|| $vRight===null) {
            throw $this->_getExceptionForSyntax("BOOL NULL");
        }
        switch ($operator) {
                case "<":
                return $vLeft < $vRight;
                case ">":
                return $vLeft > $vRight;
                case "==":
                return $vLeft == $vRight;
                case "!=":
                return $vLeft != $vRight;
                case "<=":
                return $vLeft <= $vRight;
                case ">=":
                return $vLeft >= $vRight;
                case "+":
                return $vLeft + $vRight;
                case "-":
                return $vLeft - $vRight;
                case "*":
                return $vLeft * $vRight;
                case "/":
                return $vLeft / $vRight;
                case "%":
                return $vLeft % $vRight;
                case "**":
                return pow(intval($vLeft), ($vRight));
                default:
                throw $this->_getExceptionForSyntax("invalid operator {$operator}");
        }
    }
}
