<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 25.01.2018
 * Time: 17:50
 */

require_once(DIR_FS_INC . 'XMLPrice/XMLPriceConstants.php');
require_once(XMLPrice_CONTROLLERS_DIR . 'XMLPriceAbstractController.php');
require_once(ROZETKA_COMPONENTS_DIR . 'RozetkaComponentCategories.php');
require_once(ROZETKA_COMPONENTS_DIR . 'RozetkaComponentOffers.php');


class RozetkaController extends XMLPriceAbstractController
{
    protected $dataInput = [];

    protected $nameParams = [];

    protected $categoriesIdArr = [];

    protected $sellersIdArr = [];

    public function getFileAction($dataInputArr, $is_output = true)
    {
        if( $this->checkForArray($dataInputArr) == false){
            echo 'Ошибка инициализации параметров';
            return;
        }

       $is_input_data_ready = $this->initData($dataInputArr);

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


    protected function initData($dataInputArr = false)
    {

        $this->setDataInput($dataInputArr);

        $this->filterDataInput();

        if( $this->isError() ){
            return false;
        }

        $categoriesIdStr = implode(", ", array_values( $this->getCategoriesIdArr() ));
        $sellersIdStr = implode(", ", array_values( $this->getSellersIdArr() ));

        $this->setCategoriesCollection($categoriesIdStr);

        $this->setOffersCollection($categoriesIdStr, $sellersIdStr, $this->getNameParams());

        $this->setCurrencies();

        $this->setShopInfo();

        return true;
    }

    protected function setCurrencies()
    {
        $this->currencies[] = (new Currency())->setId('UAH')->setRate(1);
    }

    /**
     * Фильтрует входящие данные и разбивает по группам, сохраняя по свойствам
     * @return bool
     */
    protected function filterDataInput()
    {
        $categories_id_arr = [];
        $sellers_id_arr = [];
        $categories_name_params = [];

        foreach ( $this->getDataInput()['categories_id'] as $category_id => $data ){
            $categories_id_arr[] = intval($category_id);
            $sellers_id_arr = array_merge($sellers_id_arr, array_map('intval' , $data['sellers_id']));
            $categories_name_params[$category_id] = $data['addition_name_param'];
        }

        if($this->checkForArray($categories_id_arr) == false){
            $this->setIsError(true);
            return false;
        }

        if($this->checkForArray($sellers_id_arr) == false){
            $this->setIsError(true);
            return false;
        }

        $this->setCategoriesIdArr( $categories_id_arr );
        $this->setSellersIdArr( $sellers_id_arr );
        $this->setNameParams($categories_name_params);

        return true;

    }

    protected function setShopInfo()
    {
        $this->shopInfo = (new ShopInfo())
            ->setName('GreenMarket')
            ->setCompany('GreenMarket')
            ->setUrl(HTTP_SERVER . '/');
    }

    /**
     * @param object XMLPriceSettings
     */
    protected function setGenerator(XMLPriceSettings $settings)
    {
        $this->generator = new XMLPriceGenerator($settings);
    }


    /**
     * @param astring $categoriesId
     */
    protected function setCategoriesCollection($categoriesId)
    {
        if(!$categoriesId){
            $this->categoriesCollection = false;
            return false;
        }

        $this->categoriesCollection = (new RozetkaComponentCategories($this->getDbManager()))->setCategoriesId($categoriesId)->setChildren();
    }

    /**
     * @param string $categoriesId
     * @param string $sellersId
     * @param string $nameParams
     */
    protected function setOffersCollection($categoriesId, $sellersId = false, $nameParams = false)
    {
        if(!$categoriesId || !$sellersId || !$nameParams){
            $this->offersCollection = false;
            return false;
        }

        $this->offersCollection = (new RozetkaComponentOffers($this->getDbManager()))
                                                                                    ->setCategoriesId($categoriesId)
                                                                                    ->setSellersId($sellersId)
                                                                                    ->setNameParts($nameParams)
                                                                                    ->setChildren();
    }

    /**
     * @return array
     */
    protected function getDataInput()
    {
        return $this->dataInput;
    }

    /**
     * @param array $dataInput
     */
    protected function setDataInput($dataInput)
    {
        $this->dataInput = $dataInput;
    }

    /**
     * @return array
     */
    protected function getCategoriesIdArr()
    {
        return $this->categoriesIdArr;
    }

    /**
     * @param array $categoriesIdArr
     */
    protected function setCategoriesIdArr($categoriesIdArr)
    {
        $this->categoriesIdArr = $categoriesIdArr;
    }

    /**
     * @return array
     */
    protected function getSellersIdArr()
    {
        return $this->sellersIdArr;
    }

    /**
     * @param array $sellersIdArr
     */
    protected function setSellersIdArr($sellersIdArr)
    {
        $this->sellersIdArr = $sellersIdArr;
    }

    /**
     * @return array
     */
    protected function getNameParams()
    {
        return $this->nameParams;
    }

    /**
     * @param array $categoriesNameParams
     */
    protected function setNameParams($nameParams)
    {
        $this->nameParams = $nameParams;
    }

}