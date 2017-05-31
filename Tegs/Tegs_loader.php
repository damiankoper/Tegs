<?php 
namespace Tegs;
 
class Tegs_loader
{
    private $file_path;

    public function __construct($file_path = null)
    {
        if ($file_path === null) {
            throw new Exception("Specify file_path at first.");
        }      
        $this->file_path = $file_path;
        return $this;
    }
    public function get_template($name)
    {
        return new Tegs_template($this->file_path, $name);
    }
}
