<?php
namespace EndpointService;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;

/**
 * EndpointService
 *
 * Base class for Endpoint Services
 *
 * @package EndpointService
 * @author akirk
 * @copyright Copyright (c) 2021 NPEU
 * @version 0.1

 **/

#require_once __DIR__ . '/vendor/autoload.php';

class EndpointService
{

    protected $valid_params = [];
    protected $param_defs = [];

    public function __construct()
    {
        if (!empty($this->param_defs)) {
            foreach ($this->param_defs as $name => $pattern) {
                if (array_key_exists($name, $_GET)) {
                    if (preg_match($pattern, $_GET[$name])) {
                        $this->valid_params[$name] = $_GET[$name];
                    }
                }
            }
        }
    }

    /*public function init()
    {
        return true;
    }*/

    public function run()
    {
    }
}