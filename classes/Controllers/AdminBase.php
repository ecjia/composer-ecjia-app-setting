<?php


namespace Ecjia\App\Setting\Controllers;


use Ecjia\System\BaseController\EcjiaAdminController;

abstract class AdminBase extends EcjiaAdminController
{
    protected $__FILE__;

    public function __construct()
    {
        parent::__construct();

        $this->__FILE__ = dirname(dirname(__FILE__));


    }
}