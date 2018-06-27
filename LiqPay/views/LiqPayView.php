<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 27.02.2018
 * Time: 17:20
 */

abstract class LiqPayView
{
    protected static
        $instance = null;

    /**
     * @return Singleton
     */
    abstract static function getInstance();

    protected function __clone() {}
    protected function __construct() {}

}