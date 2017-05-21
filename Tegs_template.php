<?php 
namespace Tegs\template;

use Exception;

class Tegs_template
{
    private $template_path;

    public function __construct($file_path, $name)
    {
        $this->template_path = $file_path.$name;
        if (!\file_exists($this->template_path)) {
            throw new Exception("Template doesn't exist. Check your loader or template name.");
        }
        return $this;
    }

    public function render($input=null)
    {
        $content = \file_get_contents($this->template_path);
        
        





        return $content;
    }
    public function display($input=null)
    {
        echo self::render($input);
    }
}
