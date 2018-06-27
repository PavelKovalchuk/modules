<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 21.12.2017
 * Time: 16:53
 */

require_once(XMLPrice_COMPONENTS_DIR . 'XMLPriceAbstractComponent.php');


abstract class XMLPriceAbstractComponentOffers extends XMLPriceAbstractComponent
{

    protected $baseUrl;

    protected $picturesBaseUrl;

    protected $productsIdArr = [];

    /**
     *
     * @param $db_manager
     */
    public function __construct($db_manager)
    {

        parent::__construct($db_manager);

        $this->initBaseUrl();

    }
    /**
     * @param string $picturesBaseUrl
     */
    public function setPicturesBaseUrl($picturesBaseUrl)
    {
        $this->picturesBaseUrl = $this->getBaseUrl() . $picturesBaseUrl;
    }

    /**
     * @return string
     */
    public function getPicturesBaseUrl()
    {
        return $this->picturesBaseUrl;
    }

    /**
     * @return array
     */
    public function getProductsIdArr()
    {
        return $this->productsIdArr;
    }

    /**
     * @param array $productsIdArr
     */
    public function setProductsIdArr($productsId)
    {
        $this->productsIdArr[] = $productsId;
    }


    public function getBaseUrl()
    {
        if($this->baseUrl){
            return $this->baseUrl;
        }

        return false;
    }

    protected function getPictureUrl($picture_name)
    {
        return $this->getPicturesBaseUrl() . $picture_name;
    }

    // код (Артикул товара)
    protected function getProductCode($product_id)
    {
        if(!is_int($product_id)){
            return false;
        }
        return sprintf("%06d", $product_id);
    }


    protected function initBaseUrl()
    {
        if(defined('XMLPrice_TEST_BASE_URL')){
            $this->baseUrl = XMLPrice_TEST_BASE_URL;
        }else{
            $this->baseUrl = XMLPrice_BASE_URL;
        }

    }


}