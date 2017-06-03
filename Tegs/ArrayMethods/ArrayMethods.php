<?php
namespace Tegs\ArrayMethods;

class ArrayMethods
{
    public static function trimArrayByProp($array, $property=null, $step=null)
    {
        foreach ($array as $key=>$item) {
            $array_temp1 = $array_temp2 = $array;
            $next = next($array);
            $prev = prev($array_temp2);
            if (\is_object($item) && \property_exists($item, "_".$property)) {
                $functionSet = "set".\ucfirst($property);
                $functionGet = "get".\ucfirst($property);
                if (\ctype_space($item->$functionGet()) && (!\is_scalar($prev)&&!\is_scalar($next)&& (!$prev->getType()==="variable"||!$next->getType()==="variable")||$prev===false||$next===false)) {
                    unset($array[$key]);
                    continue;
                }
                $item->$functionSet(\preg_replace("/\s+/", " ", $item->$functionGet()));
                $item->$functionSet(($prev===false)?\ltrim($item->$functionGet()):$item->$functionGet());
                $item->$functionSet(($next===false)?\rtrim($item->$functionGet()):$item->$functionGet());
                $functionSet = "set".\ucfirst($step);
                $functionGet = "get".\ucfirst($step);
                if ($step!==null && $item->$functionGet()!==null) {
                    $item->$functionSet(self::trimArrayByProp($item->$functionGet(), $property, $step));
                }
            } elseif (\is_array($item) && \array_key_exists($property, $item)) {
                if (\ctype_space($item[$property])  && (!\is_scalar($prev)&&!\is_scalar($next) && (!$prev["type"]==="variable"&&!$next["type"]==="variable")||$prev===false||$next===false)) {
                    unset($array[$key]);
                    continue;
                }
                $item[$property]=\preg_replace("/\s+/", " ", $item[$property]);
                $item[$property]=($prev===false)?\ltrim($item[$property]):$item[$property];
                $item[$property]=($next===false)?\rtrim($item[$property]):$item[$property];
                if ($step!==null && $item[$step]!==null && \array_key_exists($step, $item)) {
                    $item[$step]=self::trimArrayByProp($item[$step], $property, $step);
                }
            }
        }
        return $array;
    }
    public static function trimArray($array)
    {
        foreach ($array as $key => $item) {
            if (\is_array($item)) {
                $array[$key] = self::trimArray($item);
            } else {
                $array[$key] = \preg_replace("/\s+/", " ", $item);
            }
        }
        return $array;
    }
    public static function unshiftReturn($array, $insert)
    {
        \array_unshift($array, $insert);
        return $array;
    }
}
