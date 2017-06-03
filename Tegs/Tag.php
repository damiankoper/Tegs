<?php
namespace Tegs;
use Tegs\ArrayMethods\ArrayMethods as ArrayMethods;

class Tag extends Base
{
    protected $_type;
    protected $_meta;
    protected $_pos;
    protected $_string;
    protected $_content;

    public function getSingleTagFromString($content, $position, $syntax)
    {
        $this->setPos(array(
            "start"=>$position,
            "end"=>\strpos($content, $syntax["close"]) + \strlen($syntax["close"])
            ));
        return $this;
    }
    public function getTagMeta($string, $syntax)
    {
        foreach ($syntax as $type_key => $meta) {
            $search = \strpos($string, $syntax[$type_key]["open"]);
            if ($search !== false) {
                $type = $type_key;
                break;
            } else {
                $type = "text";
            }
        }

        $this->setType($type);
        $this->setString($string);

        if($type!=="text")
        $this->setMeta(self::analyseTagString($string, $type, $syntax));
        

        return $this;
    }
    private function analyseTagString($string, $type, $syntax){
        $string = \ltrim($string,$syntax[$type]["open"]." ");
        $string = \rtrim($string,$syntax[$type]["close"]." ");
        //$array = \array_filter(\explode(" ", $string));
        preg_match_all('/"(?:\\\\.|[^\\\\"])*"\S+|"(?:\\\\.|[^\\\\"])*"|[^\( ]*(\((?>[^()]+|(?1))*\))|\S+/', $string,$array);
        $keyword = \array_shift($array[0]);
        return array(
            "keyword" => $keyword,
            "arguments" => ($type==="variable")?ArrayMethods::unshiftReturn($array[0],$keyword):$array[0]
            );
    }
}
