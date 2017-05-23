<?php 
namespace Tegs\template;

use Exception;
use stdClass;

class Tegs_template
{
    private $template_path;
    private $settings;
    private $content;
    public function __construct($file_path, $name)
    {
        $this->template_path = $file_path.$name;
        if (!\file_exists($this->template_path)) {
            throw new Exception("Template doesn't exist. Check your loader or template name.");
        }
        $this->settings = \parse_ini_file("Tegs.ini");
        if ($this->settings["Tegs.tag_var_start"]===$this->settings["Tegs.tag_control_start"]) {
            throw new Exception("Control and variable tags cannot be the same. Check Tegs's .ini file.");
        }
        $this->content = \file_get_contents($this->template_path);
        return $this;
    }

    private function find_next_tag($string, $start)
    {
        $tag_var_start = $this->settings["Tegs.tag_var_start"];
        $tag_var_end = $this->settings["Tegs.tag_var_end"];
        $tag_control_start = $this->settings["Tegs.tag_control_start"];
        $tag_control_end = $this->settings["Tegs.tag_control_end"];


        $var_pos=\strpos($string, $tag_var_start);
        $con_pos=\strpos($string, $tag_control_start);

        $tag = new stdClass();
        $tag->pos = new stdClass();
        
        if ($var_pos===false&&$con_pos===false) {
            $tag->type = "none";
            return $tag;
        }
        if (($var_pos < $con_pos || $con_pos===false) && $var_pos!==false) {
            $tag->type = "variable";
            $tag->pos->start = \strpos($string, $tag_var_start, $start);
            $tag->pos->end = \strpos($string, $tag_var_end, $start) + \strlen($tag_var_end);
            $tag->tag_start = $tag_var_start;
            $tag->tag_end = $tag_var_end;
        } else {
            $tag->type = "control";
            $tag->pos->start = \strpos($string, $tag_control_start, $start);
            $tag->pos->end = \strpos($string, $tag_control_end, $start) + \strlen($tag_control_end);
            $tag->tag_start = $tag_control_start;
            $tag->tag_end = $tag_control_end;
        }
        $tag->command =  \substr($string, $tag->pos->start+\strlen($tag->tag_start), $tag->pos->end-$tag->pos->start-\strlen($tag->tag_start)-\strlen($tag->tag_end));
        $tag->command = \array_values(\array_filter(\explode(" ", $tag->command)));
        return $tag;
    }


    private function parse_content($content, $input)
    {
        while (true) {
            $tag = self::find_next_tag($content, 0);
            if ($tag->type === "variable") {
                $var_tree = \explode(".", $tag->command[0]);
                $node = $input;
                foreach ($var_tree as $step) {
                    $node = $node[$step];
                }
                $content = \substr_replace($content, $node, $tag->pos->start, $tag->pos->end-$tag->pos->start);
            } elseif ($tag->type === "control") {
                $counter = 1;
                $position = $tag->pos->end;
                do {
                    $end_tag = self::find_next_tag($content, $position);
                    $position = $end_tag->pos->end;
                    if ($end_tag->type==="command"||\count($end_tag->command)!==1) {
                        $counter++;
                    } elseif (\strpos($end_tag->command[0], "end")!==false) {
                        $counter--;
                    }
                } while ($counter!==0);

                $control_content = \substr($content, $tag->pos->end, $end_tag->pos->start- $tag->pos->end);
                $control_insert="";
                switch ($tag->command[0]) {
                    case "foreach":
                        $var_tree = \explode(".", $tag->command[1]);
                        $node = $input;
                        foreach ($var_tree as $step) {
                            $node = $node[$step];
                        }
                        foreach ($node as $item) {
                            $control_insert.= self::parse_content($control_content,array_merge(array($tag->command[3]=>$item),$input));
                        }
                        break;
                }
                $content = \substr_replace($content,
                                            $control_insert,
                                            $tag->pos->start,
                                            $end_tag->pos->end-$tag->pos->start);
            } else {
                break;
            }
            $position = $tag->pos->end;
        }
        return $content;
    }


    public function render($input=null)
    {
        $content = $this->content;
        $content = self::parse_content($content, $input);
        





        return $content;
    }
    public function display($input=null)
    {
        echo self::render($input);
    }
}
