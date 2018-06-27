<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 13:38
 */

require_once(DIR_FS_INC . 'XMLPrice/XMLPriceConstants.php');
require_once(XMLPrice_CONTROLLERS_DIR . 'XMLPriceAbstractController.php');
require_once(RONTAR_COMPONENTS_DIR . 'RontarComponentCategories.php');
require_once(RONTAR_COMPONENTS_DIR . 'RontarComponentOffers.php');

class RontarController extends XMLPriceAbstractController
{
    protected $allowedIp = [
        '144.76.173.169',
        '193.243.156.26',
        '127.0.0.1'
    ];

    protected function setCurrencies()
    {
        $this->currencies[] = (new Currency())->setId('UAH')->setRate(1);
    }

    protected function setShopInfo()
    {
        $this->shopInfo = (new ShopInfo());
    }

    /**
     * @param object XMLPriceSettings
     */
    protected function setGenerator(XMLPriceSettings $settings)
    {
        $this->generator = new XMLPriceGenerator($settings);
    }

    public function getFileAction($is_output = true)
    {
        if(!$this->checkIp()){
            echo 'Доступ запрещен!';
            return false;
        }

        $is_input_data_ready = $this->initData();

        if(!$is_input_data_ready){
            echo 'Ошибка обработки параметров';
            return;
        }

        $this->initXMLGenerator()
            ->prepareXMLOutput($is_output)
            ->getXMLGenerator()
            ->generate(
                $this->getShopInfo(),
                $this->getCurrencies(),
                $this->getCategoriesCollection()->getChildren(),
                $this->getOffersCollection()->getChildren()
            );

    }

    protected function initData()
    {

        $this->setCategoriesCollection(false);

        $this->setOffersCollection(false);

        $this->setCurrencies();

        $this->setShopInfo();

        return true;
    }

    /**
     * @param astring $categoriesId
     */
    protected function setCategoriesCollection($categoriesId)
    {
        $this->categoriesCollection = (new RontarComponentCategories($this->getDbManager()))->setCategoriesId($categoriesId)->setChildren();
    }

    /**
     * @param string $categoriesId
     */
    protected function setOffersCollection($categoriesId = false)
    {

        $this->offersCollection = (new RontarComponentOffers($this->getDbManager()))->setChildren();
    }

}