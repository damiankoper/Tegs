<?php 
namespace Tegs\core;

use Exception;

class Tegs_core
{
    private $loader;
    public function __construct($loader = null)
    {
        $this->loader = $loader;
        return $this;
        
    }

    /**
     * Loads template from file and returns Tegs_template object
     * @param string $name
     * @return \Tegs\template\Tegs_template
     */
    public function load($name)
    {
        if ($this->loader === null) {
            throw new Exception("You need to set your loader first. Try set_loader(loader).");
        }
        return   $this->loader->get_template($name);
    }

    public function set_loader($loader)
    {
        $this->$loader = $loader;
    }
}
