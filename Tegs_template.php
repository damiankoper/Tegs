<?php 
namespace Tegs\template;

use Exception;

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
        $this->content = \file_get_contents($this->template_path);
        return $this;
    }


    private function parse_content($content, $input)
    {
        $tag_var_start = $this->settings["Tegs.tag_var_start"];
        $tag_var_end = $this->settings["Tegs.tag_var_end"];
        $tag_control_start = $this->settings["Tegs.tag_control_start"];
        $tag_control_end = $this->settings["Tegs.tag_control_end"];

        if ($tag_control_start===$tag_var_start) {
            throw new Exception("Control and variable tag cannot be the same.");
        }

        while (true) {
            $var_pos=\strpos($content, $tag_var_start);
            $con_pos=\strpos($content, $tag_control_start);
            if($var_pos===false&&$con_pos===false) break;
            //variable tag
            if ($var_pos < $con_pos || $con_pos===false) {
                $end_pos = \strpos($content, $tag_var_end, $var_pos) + \strlen($tag_var_end);
                $substr = \substr($content,$var_pos,$end_pos-$var_pos);
                $var_name = \str_replace(array(" ", $tag_var_end, $tag_var_start),"",$substr);
                $var = $input[$var_name];
                $pos = \strpos($content, $substr);
                if($pos!==false)
                $content = \substr_replace($content, $var, $pos, strlen($substr));
            }
            //control tag
            else {
                
            }
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
