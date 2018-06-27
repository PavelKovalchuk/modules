<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 21.12.2017
 * Time: 11:18
 */

require_once(DIR_FS_INC . 'XMLPrice/XMLPriceConstants.php');

require_once(XMLPrice_REPOSITORY_DIR .'XMLPriceDBRepository.php');
require_once(XMLPrice_ROOT_DIR . 'XMLPriceGenerator.php');
require_once(XMLPrice_MODELS_DIR . 'ShopInfo.php');
require_once(XMLPrice_MODELS_DIR . 'Currency.php');



abstract class XMLPriceAbstractController
{
    protected $db_manager;

    protected $shopInfo;

    protected $currencies = [];

    protected $generator;

    protected $categoriesCollection;

    protected $offersCollection;

    protected $isError = false;

    protected $allowedIp = [];

    public function __construct()
    {
        $this->setDbManager(new XMLPriceDBRepository());

    }

    abstract protected function setShopInfo();

    abstract protected function setCurrencies();

    abstract protected function initData();

    /**
     * @param object XMLPriceSettings
     */
    abstract protected function setGenerator(XMLPriceSettings $settings);

    /**
     * @param astring $categoriesId
     */
    abstract protected function setCategoriesCollection($categoriesId);

    /**
     * @param string $categoriesId     *
     */
    abstract protected function setOffersCollection($categoriesId);


    protected function checkIp()
    {
        $client_ip = $this->getClientIpServer();

        if( in_array($client_ip, $this->getAllowedIp()) ){
            return true;
        }

        return false;

    }

    protected function getClientIpServer() {
        $ipaddress = '';
        if ($_SERVER['HTTP_CLIENT_IP'])
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if($_SERVER['HTTP_X_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if($_SERVER['HTTP_X_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if($_SERVER['HTTP_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if($_SERVER['HTTP_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if($_SERVER['REMOTE_ADDR'])
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }

    /**
     * @return array
     */
    protected function getCategoriesCollection()
    {
        return $this->categoriesCollection;
    }

    /**
     * @return array
     */
    protected function getOffersCollection()
    {
        return $this->offersCollection;
    }



    protected function initXMLGenerator()
    {
        $settings = (new XMLPriceSettings())->setOutputFile(null);

        $this->setGenerator($settings);

        return $this;
    }

    protected function prepareXMLOutput($is_output = true)
    {
        header('Content-Type: application/xml; charset=windows-1251');

        // for downloading
        if($is_output){
            $filename = "Products_price__" . date('Y-m-d__H-i-s') . ".xml";
            header("Content-Type: text/html/force-download");
            header("Content-Disposition: attachment; filename='" . $filename . "'");
        }

        return $this;
    }

    protected function getXMLGenerator()
    {

        return $this->generator;
    }


    /**
     * @return XMLPriceDBRepository
     */
    public function getDbManager()
    {
        return $this->db_manager;
    }

    protected function setDbManager(XMLPriceDBRepository $db_manager)
    {
        $this->db_manager = $db_manager;
    }

    /**
     * @return mixed
     */
    public function getShopInfo()
    {
        return $this->shopInfo;
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        return $this->currencies;
    }



    /**
     * @return object
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @param $data_arr
     * @return bool
     */
    protected function checkForArray($data_arr)
    {
        if(!is_array($data_arr) || empty($data_arr) ){
            return false;
        }

        return true;

    }

    /**
     * @param bool $is_error
     */
    protected function setIsError($is_error)
    {
        $this->isError = $is_error;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->isError;
    }

    /**
     * @return array
     */
    protected function getAllowedIp()
    {
        return $this->allowedIp;
    }

    /**
     * @param array $allowedIp
     */
    protected function setAllowedIp($allowedIp)
    {
        $this->allowedIp = $allowedIp;
    }



}