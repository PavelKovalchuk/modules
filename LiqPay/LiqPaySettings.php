<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 26.02.2018
 * Time: 17:51
 */

class LiqPaySettings
{
    private static
        $instance = null;
    /**
     * @return Singleton
     */
    public static function getInstance()
    {
        if (null === self::$instance)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __clone() {}
    private function __construct() {}

    protected $publicKey = '44444444';

    protected $privateKey = '654654654564654';

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }
}