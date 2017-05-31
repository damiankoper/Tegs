<?php
namespace Tegs\ArrayMethods;

class ArrayMethods
{
    public static function trimArrayByProp($array, $property=null, $step=null)
    {
        foreach ($array as &$item) {
            if (\is_object($item) && \property_exists($item, "_".$property)) {
                $functionSet = "set".\ucfirst($property);
                $functionGet = "get".\ucfirst($property);
                $item->$functionSet(\preg_replace("/\s+/", " ", $item->$functionGet()));
                $functionSet = "set".\ucfirst($step);
                $functionGet = "get".\ucfirst($step);
                if ($step!==null && $item->$functionGet()!==null) {
                    $item->$functionSet(self::trimArrayByProp($item->$functionGet(), $property, $step));
                }
            } elseif (\is_array($item) && \array_key_exists($property, $item)) {
                $item[$property]=\preg_replace("/\s+/", " ", $item[$property]);
                if ($step!==null && $item[$step]!==null && \array_key_exists($step, $item)) {
                    $item[$step]=self::trimArrayByProp($item[$step], $property, $step);
                }
            }
        }
        return $array;
    }
    public static function trimArray($array)
    {
        foreach ($array as $key => &$item) {
            if (\is_object($array)) {
                $functionSet = "set".\ucfirst($key);
                $functionGet = "get".\ucfirst($key);
                if (\is_object($item) || \is_array($item)) {
                    $item->$functionSet(self::trimArray($item->$functionGet()));
                } else {
                    $item->$functionSet(\preg_replace("/\s+/", " ", $item->$functionGet()));
                }
            } elseif (\is_array($array)) {
                $item=\preg_replace("/\s+/", " ", $item);
                if (\is_object($item) || \is_array($item)) {
                    $item=self::trimArray($item);
                }
            }
        }
        return $array;
    }
}
