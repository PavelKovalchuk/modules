<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 05.01.2018
 * Time: 11:23
 */
require_once(DIR_WS_CLASSES.'separator_order/SeparatorOrderTrait.php');
require_once(DIR_WS_CLASSES.'separator_order/SeparatorOrderViews.php');
require_once DIR_FS_ADMIN_CLASSES . 'seasons.php';

class SeparatorOrderAnalyzer
{
    use SeparatorOrderTrait;

    protected $isNeededToSeparate = false;

    protected $isNeededToAnalyze = false;

    protected $isForbiddenAddingNewProduct = false;

    //Контейнер для хранения данных о видах времени поставки, товары которых МОГУТ БЫТЬ быть отделены
    protected $separatedDeliveryTimeData = [];

    //Контейнер для хранения данных о категориях, товары которых ВСЕГДА ДОЛЖНЫ быть отделены
    protected $separatedCategoriesData = [];

    //Контейнер для хранения данных о категориях, товары которых универсальные по сроку отправки (дешевые по стоимости)
    // Луковичные цветы, Лютики, Анемона
    protected $universalCategoriesData = [];

    //Контейнер для хранения данных о статусах заказов, которые анализируются системой
    protected $allowedStatusesOrders = [];

    //Контейнер для хранения данных о складах дропшиппинга по заказу, товары которых ВСЕГДА ДОЛЖНЫ быть отделены
    protected $storehousesDropshippingData = [];

    //Контейнер для хранения отфильтрованных данных товаров
    protected $proposalData = array(

        'uneditable' => array(
            'categories' => array(),
            'dropshipping' => array(),
            'time_delivery' => array(),
        ),

        'editable' => array(
            'time_delivery' => array(),
            'other' => array()
        ),

    );

    protected $systemResultMessages = array(
        'need_to_separate' => array(
            'result' => 'need_to_separate',
            'title' => 'Внимание!',
            'text' => '<b>Данный заказ нуждается в разделении!</b> Примите решение относительно целесообразности разделения заказа.',
            'css_class' => 'ribbon-danger',
            'btn_text' => 'Показать варианты'
        ),
        'skip' => array(
            'result' => 'skip',
            'title' => 'Поздно',
            'text' => 'Данный заказ не должен анализироваться',
            'css_class' => 'ribbon-success',

        ),
        'new_product_forbidden' => array(
            'result' => 'error',
            'title' => 'Добавление запрещено',
            'text' => 'В данный заказ нельзя помещать этот продукт, так как потом заказ будет нуждаться в разделении.',
            'css_class' => 'ribbon-warning',

        ),
        'error' => array(
            'result' => 'error',
            'title' => 'Ошибки!!',
            'text' => '',
            'css_class' => 'ribbon-warning',
            'btn_text' => 'Показать предложения'
        ),
        'ok'=> array(
            'result' => 'ok',
            'title' => 'Все хорошо!',
            'text' => 'Данный заказ не нуждается в разделении!',
            'css_class' => 'ribbon-success',
        ),
    );

    public function __construct($order_id, $lang_id)
    {

        $this->initObjectsDependencies();

        $this->initExternalData($order_id, $lang_id);

        if($this->isError()){
            return false;
        }

    }

    protected function initObjectsDependencies()
    {
        $this->setDbManager(new SeparatorOrderRepository());

        $this->setViewsManager(new SeparatorOrderViews());
    }

    protected function initExternalData($order_id, $lang_id)
    {
        $this->setLangId($lang_id);
        $this->setOrderId($order_id);

        //admin/configuration.php?gID=3
        if(!defined('CATEGORIES_ALWAYS_TO_PROPOSE_SEPARATE_IN_ORDER')){

            $this->addErrorsMessages('Ошибка в инициализации категорий.');

        }

        if(!defined('STATUSES_ANALYZE_TO_PROPOSE_SEPARATE_IN_ORDER')){

            $this->addErrorsMessages('Ошибка в инициализации статусов.');

        }

        $this->setSeparatedCategoriesData(CATEGORIES_ALWAYS_TO_PROPOSE_SEPARATE_IN_ORDER);

        //TODO - добавить настройку в БД
        $this->setUniversalCategoriesData('490, 613, 555');

        $this->setAllowedStatusesOrders(STATUSES_ANALYZE_TO_PROPOSE_SEPARATE_IN_ORDER);

        if(!$this->getOrderId() || !$this->getLangId() ){

            $this->addErrorsMessages('Ошибка в инициализации данных для деления заказов в полуавтоматическом режиме');

        }

    }

    protected function analyzeOrderStatus()
    {

        if(!function_exists('get_cur_order_status')){
            $this->addErrorsMessages('Ошибка в инициализации функционала.');
            return;
        }

        $current_status = intval( get_cur_order_status( $this->getOrderId() ));

        if(! is_int($current_status)){
            $this->addErrorsMessages('Ошибка в получении статуса заказа.');
            return;
        }

        if( in_array($current_status, $this->getAllowedStatusesOrders()) ){

            $this->setIsNeededToAnalyze(true);
            return true;

        }

        return false;
    }

    protected function filterProducts($products_data)
    {
        //Временное хранилище для продуктов универсальных категорий
        $universal_products_data = [];

        foreach ($products_data as $product){

            //Check for categories, which always should be separated
            if($this->isSeparatedCategory($product['categories_id'])){

                $this->addProductToUneditableCategoriesGroup($product['categories_id'], $product);
                continue;

            }

            //Check for categories, which always should be separated
            if($this->isSeparatedCategory($product['categories_parent_id'])){

                $this->addProductToUneditableCategoriesGroup($product['categories_parent_id'], $product);
                continue;

            }

            //Check for dropshipping, which always should be separated
            if($this->isDropshipping($product['dropshipping_flag'])){

                $this->addProductToUneditableDropshippingGroup($product);
                continue;

            }

            //Check for time delivery group, which can be separated
            if($this->isHasTimeDelivery($product['delivery_time_code'])){

                //$this->addProductToEditableTimeDeliveryGroup($product);
                //Запретить оставлять товар, который может быть отделен от текущего заказа
                // В случае переделывания под разрешение редактирования - менять группу uneditable на editable

                //Сохраняем продукты универсальных категорий во временное хранилище
                if( $this->isUniversalCategory($product['categories_id']) || $this->isUniversalCategory($product['categories_parent_id']) ){
                    $universal_products_data[] = $product;
                    continue;
                }

                $this->addProductToUneditableTimeDeliveryGroup($product);
                continue;

            }

            //For not-categoriesed products
            $this->addProductToEditableOtherGroup($product);

        }

        //работаем с продуктами универсальных категорий START
        if(empty($universal_products_data)){
            return;
        }

        if(empty($this->getProposalData()['uneditable']['time_delivery'])){
            foreach ($universal_products_data as $universal_product){
                $this->addProductToUneditableTimeDeliveryGroup($universal_product);
            }
            return;
        }

        $this->addProductsToNearestTimeDeliveryGroup($universal_products_data);
        //работаем с продуктами универсальных категорий FINISH

    }

    public function analyzeOrder()
    {
        if($this->isError()){
            return $this;
        }

        $this->analyzeOrderStatus();

        if($this->isNeededToAnalyze() == false){
            return $this;
        }

        $products_data = $this->getOrderProductData();
        $this->filterProducts($products_data);

        //Определяем количество подгруп к разделению в каждой группе и определяем необходимость разделения
        $proposal_groups_arr = $this->calculateNumberProposalGroups();
        $checking_result = $this->checkNeedingToSeparate($proposal_groups_arr);
        $this->setIsNeededToSeparate($checking_result);

        if( $proposal_groups_arr['number_other'] > 0 ){

            $this->addErrorsMessages('Ошибка в фильтрации товаров #2. Товар не соответсвует критериям фильтрации. Сообщите системному администратору!');
            $this->setIsNeededToSeparate(false);

        }

        return $this;

    }

    public function canAddNewProductToOrder($new_product_id)
    {
        $product_id = intval($new_product_id);

        if(!$product_id > 0){
            $this->addErrorsMessages('Невозможно проверить новый продукт - нет ID');
            return false;
        }

        // Получаем данные о новом товаре
        $product_data = $this->getProductDataById($product_id);
        if(!$product_data){
            $this->addErrorsMessages('Невозможно проверить новый продукт - не получены данные из БД');
            return false;
        }

        $order_products_data = $this->getOrderProductData();
        if(array_key_exists($product_id, $order_products_data)){
            $this->addErrorsMessages('Данный продукт уже присутствует в заказе. Проверка на добавление не осуществлялась.');
            //Если в заказе уже есть такой продукт, предполагаем, что проверка уже пройдена
            $this->setIsForbiddenAddingNewProduct(false);
            return true;
        }

        //Создаем массив для проверки
        $products_to_check = $order_products_data + $product_data;
        $this->filterProducts($products_to_check);

        //Определяем количество подгруп к разделению в каждой группе
        $proposal_groups_arr = $this->calculateNumberProposalGroups();
        $checking_result = $this->checkNeedingToSeparate($proposal_groups_arr);

        //Если в результате добавления нового продукта, его потом нужно будет разделять, запрещаем добавление такого продукта
        if($checking_result){
            $this->setIsForbiddenAddingNewProduct(true);
            return false;
        }

        $this->setIsForbiddenAddingNewProduct(false);
        return true;

    }

    public function showAnalyzedResultBlock()
    {
        $message = $this->getAnalyzedResultMessage();

        $views_manager = $this->getViewsManager();

        $views_manager->getAnalyzedInfoBlock($message);

        if($this->isNeededToSeparate()){

            $views_manager->getFormBlock(
                $this->getOrderId(),
                $this->getProposalData(),
                $this->getAllSeparatedCategoriesData(),
                $this->getStorehousesDropshippingData(),
                $this->getSeparatedDeliveryTimeData()
            );
        }

    }

    protected function calculateNumberProposalGroups()
    {
        $result = [
            'number_categories' => count($this->getProposalData()['uneditable']['categories']),
            'number_dropshipping' => count($this->getProposalData()['uneditable']['dropshipping']),
            'number_time_delivery' => count($this->getProposalData()['uneditable']['time_delivery']),
            'number_other' => count($this->getProposalData()['editable']['other']),
        ];

        return $result;
    }

    protected function checkNeedingToSeparate($proposal_groups_arr)
    {
        if ( ($proposal_groups_arr['number_categories'] + $proposal_groups_arr['number_dropshipping'] +
                $proposal_groups_arr['number_time_delivery'] + $proposal_groups_arr['number_other']) > 1 ){
            return true;
        }

        return false;
    }

    protected function getAnalyzedResultMessage()
    {
        if($this->isError()){

            $this->createErrorsMessage();

            return $this->getSystemResultMessages('error');
        }

        if($this->isForbiddenAddingNewProduct()){
            return $this->getSystemResultMessages('new_product_forbidden');
        }

        if($this->isNeededToAnalyze() == false){

            return $this->getSystemResultMessages('skip');
        }

        if($this->isNeededToSeparate()){

            return $this->getSystemResultMessages('need_to_separate');
        }

        return $this->getSystemResultMessages('ok');

    }

    protected function addProductToUneditableCategoriesGroup($cat, $product)
    {
        $cat_data = $this->getOneSeparatedCategoryData($cat);

        if(!$cat_data){

            $this->addErrorsMessages('Ошибка в фильтрации товаров по категории, обязательных для разделения');

            return false;

        }

        $this->addProposalData('uneditable', 'categories', $cat_data['categories_id'], $product);

        return true;
    }

    /**
     * @return array
     */
    protected function getProposalData()
    {
        return $this->proposalData;
    }

    /**
     * Сохраняет данные о продукте в соответсвующий массив
     *
     * $editable_group - редактируемая или не редактируемая группа (uneditable, editable)ж
     * $group - группа (categories, dropshipping, time_delivery)
     *
     */
    protected function addProposalData($editable_group, $group, $subgroup, $product)
    {

        $this->proposalData[$editable_group][$group][$subgroup][] = $product;
    }

    protected function isDropshipping($dropshipping_flag)
    {
        if(intval($dropshipping_flag) == 1){
            return true;
        }

        return false;

    }

    protected function isHasTimeDelivery($delivery_time_code)
    {
        if(intval($delivery_time_code) > 1){
            return true;
        }

        return false;

    }

    protected function addProductToEditableTimeDeliveryGroup($product)
    {
        $delivery_time_code = intval($product['delivery_time_code']);

        //Сохраняем данные о всех вариантах сроков поставки, которые есть в этом заказе
        if(!$this->getSeparatedDeliveryTimeData()[$delivery_time_code] == $product['delivery_time_text']){

            $this->addSeparatedDeliveryTimeData(
                array(
                    'delivery_time_code' => $delivery_time_code,
                    'delivery_time_text' => $product['delivery_time_text']
                )
            );

        }


        $this->addProposalData('editable', 'time_delivery', $delivery_time_code, $product);

    }

    protected function addProductToUneditableTimeDeliveryGroup($product, $delivery_time_code = false)
    {
        if(!$delivery_time_code){
           $delivery_time_code = intval($product['delivery_time_code']);
        }

        //Сохраняем данные о всех вариантах сроков поставки, которые есть в этом заказе
        if(!$this->getSeparatedDeliveryTimeData()[$delivery_time_code] == $product['delivery_time_text']){

            $this->addSeparatedDeliveryTimeData(
                array(
                    'delivery_time_code' => $delivery_time_code,
                    'delivery_time_text' => $product['delivery_time_text']
                )
            );

        }

        $this->addProposalData('uneditable', 'time_delivery', $delivery_time_code, $product);

    }

    //Для всех продуктов, которые никуда не отсортировались.
    protected function addProductToEditableOtherGroup($product)
    {
        $cat_id = intval($product['categories_id']);

        $this->addProposalData('editable', 'other', $cat_id, $product);
    }

    protected function addProductToUneditableDropshippingGroup($product)
    {
        $storehouse_id = intval($product['storehouse_id']);

        //Сохраняем данные о всех складах дропшиппинга, которые есть в этом заказе
        if(!$this->getStorehousesDropshippingData()[$storehouse_id]){

            $this->addStorehousesDropshippingData(
                array(
                    'storehouse_id' => $storehouse_id,
                    'storehouse_name' => $product['storehouse_name']
                )
            );

        }

        $this->addProposalData('uneditable', 'dropshipping', $storehouse_id, $product);

    }


    protected function isSeparatedCategory($cat_id)
    {
        if( $this->getOneSeparatedCategoryData($cat_id)){

            return true;
        }

        return false;
    }

    protected function isUniversalCategory($cat_id)
    {
        if( $this->getOneUniversalCategoryData($cat_id)){

            return true;
        }

        return false;
    }

    protected function createErrorsMessage()
    {
        if($this->isError()){

            $errors = '';

            foreach ($this->getErrorsMessages() as $error){
                $errors .= '<br> <b>&#9760;</b> ' . $error . '<hr>';
            }

            $this->setSystemErrorResultMessages($errors);

        }

    }

    protected function setSystemErrorResultMessages($errors)
    {
        $this->systemResultMessages['error']['text'] = $errors;
    }

    protected function getOrderProductData()
    {
        return  $this->getDbManager()->getOrderProductDataDB($this->getOrderId(), $this->getLangId());

    }

    protected function getProductDataById($product_id)
    {
        $product_id = intval($product_id);
        if(!$product_id || !$product_id > 0){
            return false;
        }

        $season = Seasons::getSeasonByOrderId($this->getOrderId());
        if(!$this->checkForArray($season)){
            return false;
        }
        if(!isset($season['seasons_id']) || empty($season['seasons_id'])){
            return false;
        }

        return  $this->getDbManager()->getProductDatToAnalyzeDB($product_id, $this->getLangId(), $season['seasons_id'] );

    }

    /**
     * @param array $separatedCategoriesData
     */
    protected function addSeparatedCategoriesData($separatedCategoriesData)
    {
        $this->separatedCategoriesData[$separatedCategoriesData['categories_id']] = $separatedCategoriesData;
    }

    /**
     * @return array
     */
    protected function getAllSeparatedCategoriesData()
    {
        return $this->separatedCategoriesData;
    }

    protected function getAllSeparatedCategoriesId()
    {
        return array_keys($this->getAllSeparatedCategoriesData());
    }

    protected function getOneSeparatedCategoryData($category_id)
    {
        if(isset($this->separatedCategoriesData[$category_id])){

            return $this->separatedCategoriesData[$category_id];

        }

        return false;
    }

    /**
     * @param array $separatedCategoriesData
     */
    protected function setSeparatedCategoriesData($separated_categories_str)
    {

        $separated_categories_data = $this->getCategoriesData($separated_categories_str);

        foreach ($separated_categories_data as $cat_data){

            $this->addSeparatedCategoriesData($cat_data);

        }

    }

    /**
     * @return array
     */
    protected function getAllUniversalCategoriesData()
    {
        return $this->universalCategoriesData;
    }

    protected function getOneUniversalCategoryData($category_id)
    {
        if(isset($this->universalCategoriesData[$category_id])){

            return $this->universalCategoriesData[$category_id];

        }

        return false;
    }

    /**
     * @param array $universalCategoriesData
     */
    protected function setUniversalCategoriesData($universalCategoriesDataStr)
    {

        $universal_categories_data = $this->getCategoriesData($universalCategoriesDataStr);

        foreach ($universal_categories_data as $cat_data){

            $this->addUniversalCategoriesData($cat_data);

        }
    }
    protected function addUniversalCategoriesData($universalCategoriesData)
    {
        $this->universalCategoriesData[$universalCategoriesData['categories_id']] = $universalCategoriesData;
    }

    protected function addProductsToNearestTimeDeliveryGroup($products)
    {

        $not_used_sort_orders = [1, 2];
        $time_delivery_groups = $this->getProposalData()['uneditable']['time_delivery'];

        $delivery_time_code = array_reduce(
            array_keys($time_delivery_groups),
            function($min, $item) use ($not_used_sort_orders)  {

                if(in_array($item, $not_used_sort_orders)){
                    return $min;
                }

                return min($min, $item);
            }, array_pop($time_delivery_groups));

        foreach ($products as $product){
            $this->addProductToUneditableTimeDeliveryGroup($product, $delivery_time_code);
        }
    }


    protected function getCategoriesData($categories_str)
    {
        $categories =  array_map('intval', explode(',', $categories_str ));

        if(!count($categories) > 0){
            return false;
        }

        $categories_data = $this->getDbManager()->getCategoriesDataDB($categories, $this->getLangId());

        return $categories_data;
    }

    /**
     * @return array
     */
    protected function getStorehousesDropshippingData()
    {
        return $this->storehousesDropshippingData;
    }

    /**
     * @param array $storehousesDropshippingData
     */
    protected function addStorehousesDropshippingData($storehousesDropshippingData)
    {
        $this->storehousesDropshippingData[$storehousesDropshippingData['storehouse_id']] = $storehousesDropshippingData['storehouse_name'];
    }

    /**
     * @return array
     */
    protected function getSeparatedDeliveryTimeData()
    {
        return $this->separatedDeliveryTimeData;
    }

    /**
     * @param array $separatedDeliveryTimeData
     */
    protected function addSeparatedDeliveryTimeData($separatedDeliveryTimeData)
    {
        $this->separatedDeliveryTimeData[$separatedDeliveryTimeData['delivery_time_code']] = $separatedDeliveryTimeData['delivery_time_text'];
    }

    /**
     * @return bool
     */
    public function isNeededToSeparate()
    {
        return $this->isNeededToSeparate;
    }

    /**
     * @param bool $isNeededToSeparate
     */
    protected function setIsNeededToSeparate($isNeededToSeparate)
    {

         $this->isNeededToSeparate = $isNeededToSeparate;

    }

    /**
     * @return array
     */
    protected function getSystemResultMessages($result)
    {
        return $this->systemResultMessages[$result];
    }

    /**
     * @return array
     */
    protected function getAllowedStatusesOrders()
    {
        return $this->allowedStatusesOrders;
    }

    /**
     * @param string $allowedStatusesOrdersStr
     */
    protected function setAllowedStatusesOrders($allowedStatusesOrdersStr)
    {
        $statuses =  array_map('intval', explode(',', $allowedStatusesOrdersStr ));

        if(!count($statuses) > 0){
            return false;
        }

        $this->allowedStatusesOrders = $statuses;
    }

    /**
     * @return bool
     */
    protected function isNeededToAnalyze()
    {
        return $this->isNeededToAnalyze;
    }

    /**
     * @param bool $isNeededToAnalyze
     */
    protected function setIsNeededToAnalyze($isNeededToAnalyze)
    {
        $this->isNeededToAnalyze = $isNeededToAnalyze;
    }

    /**
     * @return mixed
     */
    protected function isForbiddenAddingNewProduct()
    {
        return $this->isForbiddenAddingNewProduct;
    }

    /**
     * @param mixed $isForbiddenAddingNewProduct
     */
    protected function setIsForbiddenAddingNewProduct($isForbiddenAddingNewProduct)
    {
        $this->isForbiddenAddingNewProduct = $isForbiddenAddingNewProduct;
    }


}