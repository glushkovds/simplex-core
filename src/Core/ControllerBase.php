<?php

namespace Simplex\Core;

/**
 * Use ControllerBase for your component-class instead of ComponentBase to automatic routing URI paths to class methods
 * @example http://site.ru/myext/myact/ will call "myact" method from "myext" class
 * @example http://site.ru/myext/ will call "index" method from "myext" class
 *
 */
class ControllerBase extends ComponentBase
{

    protected $s; // private session for class
    protected $d; // private data for class
    protected $action = '';

    public function __construct()
    {
        parent::__construct();
        $this->s = &$_SESSION[get_class($this)]['session'];
        if (!isset($this->d)) {
            $this->d = array();
        }
    }

    protected function content()
    {
        $mname = $this->mname();
        if (!method_exists($this, $mname)) {
            Core::error404();
        }
        $this->$mname(...array_slice(Core::uri(), 2));
    }

    /**
     * Find appropriate method by path
     * @return string
     */
    protected function mname()
    {
        if ($this->action) {
            return $this->action;
        }

        // by default
        $mname = $default = "index";
        $uri = Core::uri();
        // if /article/1/
        if (isset($uri[1]) && (int)$uri[1]) {
            $mname = 'item';
        } // read URI
        elseif (isset($uri[1]) && $uri[1]) {
            $mname = $uri[1];
        }

        return $mname;
    }

    public function index()
    {

    }

}
