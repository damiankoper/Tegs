<?php
namespace Tegs;

class Test
{
    private static $_tests = array();
    public static function add($callback, $title, $set)
    {
        self::$_tests[]=array("callback"=>$callback, "set"=>$set, "title"=>$title,"exception"=>null, "result"=>null);
    }
    public static function run()
    {
        $results = array();
        echo 
        "<style>
        table{
            border-collapse:collapse;
            width:100%;
        }
        th,td{
            padding:0.25em;
            border:solid 1px black;
        }
        td:first-child,td:nth-child(2){
            white-space:pre;}
        td:last-child{
            width:100%;
            }
        </style>".
        "<table><thead><tr style='background-color:#d9d9d9;'><th>Set</th><th>Title</th><th>Result</th><th>Time</th><th>Exception</th></thead><tbody>";
        foreach (self::$_tests as $test) {
            try {
                $timestart=\microtime(true);
                $test["result"] = \call_user_func($test["callback"]);
                $time = \number_format((\microtime(true)- $timestart)*1000,2);
                $results[] = $test;
            } catch (\Exception $e) {
                $test["result"] = false;
                $test["exception"] = "<b>Error ".$e->getFile()."(".$e->getLine()."): </b>". $e->getMessage();
                $results[] = $test;
            }
            if($test["result"]===true) $color = "LimeGreen";
            elseif($test["result"]===false && $test["exception"]===null) $color = "Orange";
            else $color = "OrangeRed";
            $a=($test['result'])?"Success":"Failed";
            echo
            "<tr style='background-color:$color;'>
             <td>".$test['set']."</td>
                <td>".$test['title']."</td>
                <td>".$a."</td>
                <td style='text-align:right;'>".$time."</td>
                <td>".$test['exception']."</td>
            </tr>";
        }
        echo "</tbody></table>";
        return $results;
    }
}
