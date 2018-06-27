<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 18.05.2018
 * Time: 12:57
 */

require_once(DIR_FS_INC . 'XMLPrice/XMLPriceConstants.php');
require_once(XMLPrice_CONTROLLERS_DIR . 'XMLPriceAbstractController.php');
require_once(GOOGLE_COMPONENTS_DIR . 'GoogleComponentOffers.php');
require_once(GOOGLE_HELPER_DIR . 'GoogleShoppingGenerator.php');
// URL /xml_price_google.php


class GoogleController extends XMLPriceAbstractController
{
    protected function setShopInfo()
    {
        $this->shopInfo = (new ShopInfo())
            ->setName('Купить саженцы в Украине-GreenMarket-интернет-магазин саженцев почтой')
            ->setUrl('https://www.greenmarket.com.ua')
            ->setDescription('Купить саженцы в Украине по выгодным ценам в интернет-магазине GreenMarket. Доставка саженцев почтой. Наибольший ассортимент в Украине. Гарантируем качество');
    }

    protected function setCurrencies()
    {
        $this->currencies[] = (new Currency())->setId('UAH')->setRate(1);
    }

    protected function initData()
    {

        $this->setOffersCollection(false);

        $this->setCurrencies();

        $this->setShopInfo();

        return true;
    }

    protected function setGenerator(XMLPriceSettings $settings)
    {
        $this->generator = new GoogleShoppingGenerator();
    }

    protected function setCategoriesCollection($categoriesId)
    {
        $this->categoriesCollection = false;
    }

    protected function setOffersCollection($categoriesId)
    {
        $this->offersCollection = (new GoogleComponentOffers($this->getDbManager()))->setChildren();
    }

    public function getFileAction($is_output = true)
    {

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
                $this->getOffersCollection()->getChildren()
            );

    }

}