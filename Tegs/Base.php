<?php
namespace Tegs;
class Base
{
    public function __construct($options = null)
    {
        if(is_array($options) || is_object($options)){
            foreach($options as $key => $option){
                $this->$key = $option;
            }
        }
    }
    public function __call($name, $arguments){
        \preg_match("/^get([\S]+)$/",$name, $getMatches);
        $property = ($getMatches==array())?null:"_".\lcfirst($getMatches[1]);
        
        if(\property_exists($this,$property)){
            return $this->$property;
        }
        \preg_match("/^getRef([\S]+)$/",$name, $getMatches);
        $property = ($getMatches==array())?null:"_".\lcfirst($getMatches[1]);
        
        if(\property_exists($this,$property)){
            return $this->$property;
        }
        \preg_match("/^set([\S]+)$/",$name, $setMatches);
        $property = "_".\lcfirst($setMatches[1]);
        
        if(\property_exists($this,$property)){
            return $this->$property = $arguments[0];
        }

        throw $this->_getExceptionForImplementation($property);
    }

    protected function _getExceptionForImplementation($method){
        return new \Exception("Method or variable {$method} is not implemented");
    }
    
}

