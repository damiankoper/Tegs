<?php 
namespace Tegs\template;

use Exception;
use stdClass;

class Tegs_template
{
    private $template_path;
    private $file_path;
    private $file_name;
    private $settings;

    private $content;

    private $has_parent = false;
    private $template_parent_path;
    private $template_parent_content;

    private $single_tags=["extends","include"];

    public function __construct($file_path, $name)
    {
        $this->file_path = $file_path;
        $this->file_name = $name;
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


    private function find_next_tag($string, $start, $options=null)
    {
        $tag_var_start = $this->settings["Tegs.tag_var_start"];
        $tag_var_end = $this->settings["Tegs.tag_var_end"];
        $tag_control_start = $this->settings["Tegs.tag_control_start"];
        $tag_control_end = $this->settings["Tegs.tag_control_end"];

        while (true) {
            $var_pos=\strpos($string, $tag_var_start, $start);
            $con_pos=\strpos($string, $tag_control_start, $start);

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
                if ($options->type!=="variable"&&!$options->type==null) {
                    $start = $tag->pos->end;
                    continue;
                } else {
                    break;
                }
            } else {
                $tag->type = "control";
                $tag->pos->start = \strpos($string, $tag_control_start, $start);
                $tag->pos->end = \strpos($string, $tag_control_end, $start) + \strlen($tag_control_end);
                $tag->tag_start = $tag_control_start;
                $tag->tag_end = $tag_control_end;
                if ($options->type!=="control"&&!$options->type==null) {
                    $start = $tag->pos->end;
                    continue;
                } else {
                    break;
                }
            }
        }
        $tag->command =  \substr($string, $tag->pos->start+\strlen($tag->tag_start), $tag->pos->end-$tag->pos->start-\strlen($tag->tag_start)-\strlen($tag->tag_end));
        $tag->command = \array_values(\array_filter(\explode(" ", $tag->command)));
        $tag->main_command = $tag->command[0];

        if ($options->find_closure && $tag->type==="control" && \strpos($tag->main_command, "end")===false && \array_search($tag->main_command,$this->single_tags)===false) {
            $options->find_closure=false;
            $counter = 1;
            $position = $tag->pos->end;
            do {
                $end_tag = self::find_next_tag($string, $position, $options);
                $position = $end_tag->pos->end;
                if ($end_tag->type==="control"&&(\count($end_tag->command)!==1 || \strpos($end_tag->main_command, "end")===false)) {
                    $counter++;
                } elseif ($end_tag->type==="control"&&\strpos($end_tag->main_command, "end")!==false) {
                    $counter--;
                }
            } while ($counter!==0);
            $tag->end_tag = $end_tag;
        } else {
            $tag->end_tag = null;
        }

        return $tag;
    }

    private function find_block($block_name, $content, $report_error = true)
    {
        $position = 0;
        $tag_fin=null;
        while (true) {
            $tag = self::find_next_tag($content, $position, (object)array('find_closure'=>true, 'type'=>'control'));
            if ($tag->type==="none") {
                if ($tag_fin===null && $report_error) {
                    throw new Exception("Block (<b>$block_name</b>) was not found in the template.");
                } else {
                    break;
                }
            }
            if ($tag->end_tag!==null && $tag->main_command==="block") {
                if ($tag->command[1]===$block_name) {
                    $tag_fin = $tag;
                }
            }
            $position=$tag->pos->end;
        }
        return $tag_fin;
    }

    private function parse_variable($input, $command)
    {
        $var_tree = \explode(".", $command);
        $filter_tree = \explode("|", \end($var_tree));
        $var_tree[\count($var_tree)-1] = \array_shift($filter_tree);
        $node = $input;
        foreach ($var_tree as $step) {
            if (!\array_key_exists($step, $node)) {
                throw new Exception("Variable or array (<b>$step</b>) doesn't exist.");
            }
            $node = $node[$step];
        }
        foreach ($filter_tree as $filter) {
            switch ($filter) {
                case "length":
                    $node = \count($node);
                break;
                case "e":
                    $node = \htmlspecialchars(\trim($node));
                break;
                case "s":
                    $node = \strip_tags(\trim($node));
                break;
                default:
                    throw new Exception("Undefined filter (<b>$filter</b>)");
                break;
            }
        }
        return $node;
    }

    private function parse_content($content, $input)
    {
        $content = \preg_replace("/<!--[\s\S]*-->/", "", $content);
        while (true) {
            $tag = self::find_next_tag($content, 0, (object)array('find_closure'=>true, 'type'=>null));
            if ($tag->type === "variable") {
                $node = self::parse_variable($input, $tag->main_command);
                $content = \substr_replace($content, $node, $tag->pos->start, $tag->pos->end-$tag->pos->start);
            } elseif ($tag->type === "control") {
                if (\array_search($tag->main_command, $this->settings['Tegs.allowed_control'])===false) {
                    throw new Exception("Control tag (<b>$tag->main_command</b>) is not allowed. Check your .ini file");
                }
                if (\array_search($tag->main_command,$this->single_tags)!==false) {
                    $tag->end_tag = new stdClass();
                    $tag->end_tag->pos = $tag->pos;
                }
                $control_content = \substr($content, $tag->pos->end, $tag->end_tag->pos->start- $tag->pos->end);
                $control_insert="";
                switch ($tag->main_command) {
                    case "foreach":
                        $var_tree = \explode(".", $tag->command[1]);
                        $node = $input;
                        foreach ($var_tree as $step) {
                            if (!\array_key_exists($step, $node)) {
                                throw new Exception("Variable or array (<b>$step</b>) doesn't exist.");
                            }
                            $node = $node[$step];
                        }
                        if ($node==null) {
                            break;
                        }
                        foreach ($node as $item) {
                            $control_insert.= self::parse_content($control_content, array_merge(array($tag->command[3]=>$item), $input));
                        }
                    break;
                    case "for":
                        for ($i=0; $i<$tag->command[1]; $i++) {
                            $control_insert.= self::parse_content($control_content, $input);
                        }
                    break;
                    case "spaceless":
                        $control_insert = preg_replace('/\\'.$this->settings['Tegs.tag_var_end'].'\s+/', $this->settings['Tegs.tag_var_end'], $control_content);
                        $control_insert = preg_replace('/\s+\\'.$this->settings['Tegs.tag_var_start'].'/', $this->settings['Tegs.tag_var_start'], $control_insert);
                        $control_insert = preg_replace('/\\'.$this->settings['Tegs.tag_control_end'].'\s+/', $this->settings['Tegs.tag_control_end'], $control_insert);
                        $control_insert = preg_replace('/\s+\\'.$this->settings['Tegs.tag_control_start'].'/', $this->settings['Tegs.tag_control_start'], $control_insert);
                        $control_insert = self::parse_content($control_insert, $input);
                    break;
                    case "if":
                        $next_tag = self::find_next_tag($content, $tag->end_tag->pos->end, (object)array('find_closure'=>true, 'type'=>null));
                        if ($tag->command[2]==="=") {
                            if (self::parse_variable($input, $tag->command[1]) == $tag->command[3]) {
                                $control_insert.= self::parse_content($control_content, $input);
                            } else {
                                if ($next_tag->main_command==="else") {
                                    $tag->pos->end = $next_tag->pos->end;
                                    $control_content = \substr($content, $next_tag->pos->end, $next_tag->end_tag->pos->start- $next_tag->pos->end);
                                    $control_insert.= self::parse_content($control_content, $input);
                                }
                            }
                        }
                        if ($tag->command[2]==="!=") {
                            if (self::parse_variable($input, $tag->command[1]) !== $tag->command[3]) {
                                $control_insert.= self::parse_content($control_content, $input);
                            } else {
                                if ($next_tag->main_command==="else") {
                                    $tag->pos->end = $next_tag->pos->end;
                                    $control_content = \substr($content, $next_tag->pos->end, $next_tag->end_tag->pos->start- $next_tag->pos->end);
                                    $control_insert.= self::parse_content($control_content, $input);
                                }
                            }
                        }
                    break;
                    case "else":break;
                    case "extends":
                        if (!\file_exists($this->file_path.\trim($tag->command[1], '"'))) {
                            throw new Exception("Parent template (<b>".$tag->command[1]."</b>) doesn't exist. Check your loader or template name.");
                        }
                        $this->has_parent=true;
                        return $content = self::parse_content(\file_get_contents($this->file_path.\trim($tag->command[1], '"')), $input);
                    break;
                    case "block":
                        if ($this->has_parent) {
                            $block = self::find_block($tag->command[1], $this->content, false);
                            if ($block!==null) {
                                $substr = \substr($this->content, $block->pos->end, $block->end_tag->pos->start-$block->pos->end);
                                $control_insert.= self::parse_content($substr, $input);
                            } else {
                                $control_insert.= self::parse_content($control_content, $input);
                            }
                        }
                        else{
                            $control_insert.= self::parse_content($control_content, $input);
                        }
                    break;
                    case "include":
                        if ($this->file_path.\trim($tag->command[1], '"')===$this->file_path.$this->file_name) {
                            throw new Exception("Template cannot include itself.");
                        }
                        if (!\file_exists($this->file_path.\trim($tag->command[1], '"'))) {
                            throw new Exception("Parent template (<b>".$tag->command[1]."</b>) doesn't exist. Check your loader or template name.");
                        }
                        $control_insert.= self::parse_content(\file_get_contents($this->file_path.\trim($tag->command[1], '"')), $input);
                    break;
                    default:
                        $control_insert.= self::parse_content($control_content, $input);
                        break;

                }
                $content = \substr_replace($content,
                                            $control_insert,
                                            $tag->pos->start,
                                            $tag->end_tag->pos->end-$tag->pos->start);
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
    public function render_block($block_name, $input=null)
    {
        $tag = self::find_block($block_name, $this->content);
        return self::parse_content(\substr($this->content, $tag->pos->end, $tag->end_tag->pos->start - $tag->pos->end), $input);
    }
    public function display($input=null)
    {
        echo self::render($input);
    }
    public function display_block($block_name, $input=null)
    {
        echo self::render_block($block_name, $input);
    }
}
