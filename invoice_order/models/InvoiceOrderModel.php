<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 13.06.2018
 * Time: 11:33
 */

require_once(INVOICE_ROOT_DIR . 'InvoiceOrderTrait.php');
require_once (INVOICE_VIEWS_DIR . 'InvoiceOrderElements.php');
require_once (INVOICE_MODELS_DIR . 'InvoicePDFCreator.php');
require_once(INVOICE_ROOT_DIR . 'InvoiceOrderPlaceholder.php');

require_once( DIR_WS_CLASSES . 'dropshipping/models/DropshippingTtnModel.php');

require(DIR_WS_CLASSES . 'php-barcode.php');
require(DIR_WS_CLASSES . 'BarCodeGM.php');
require(DIR_FS_LIBS . 'number_of_words/NumberOfWords.php');


class InvoiceOrderModel
{
    use InvoiceOrderTrait;

    protected $orderSubtype;
    protected $tableProductMapName;
    protected $seedsOrderId;
    protected $isDropshipping = false;
    protected $isTwoSidePrinting = false;
    //Barcode generator
    protected $bcGenerator;
    //NumberOfWords generator
    protected $nwGenerator;
    protected $barcodeSource;
    protected $imgTemporaryDir;
    protected $imgTemplatesDir;
    protected $backSideInfoTemplatesDir;
    protected $backSideImageName = 'arrow_back_side.png';
    protected $backSideInformerTemplateName = 'print_informer_for_client.html';
    protected $shopLogoSource;

    const MAX_ROWS_PER_PAGE = 24;

    public function __construct()
    {

        $this->setImgTemplatesDir(DIR_FS_DOCUMENT_ROOT . 'templates/' . CURRENT_TEMPLATE . '/images/');
        $this->setImgTemporaryDir(SESSION_WRITE_DIRECTORY);
        $this->setBackSideInfoTemplatesDir(DIR_FS_DOCUMENT_ROOT . 'templates/' . CURRENT_TEMPLATE . '/admin/');
        $this->setShopLogoSource(DIR_FS_DOCUMENT_ROOT . 'public/themes/gm/images/logo2.png');
        $this->setBcGenerator(new BarCodeGM());
        $this->setNwGenerator(new NumberOfWords());
        $this->setDbManager(new InvoiceOrderDBRepository());

    }


    public function getFilePDF($order_id, $delivery_type, $table_product_map_name)
    {

        $order_id = $this->clearOrderId($order_id);
        $table_product_map_name = $this->clearInputStr($table_product_map_name);
        $delivery_type = $this->clearInputStr($delivery_type);

        $this->checkInputDataForGeneratingInvoice($order_id, $delivery_type, $table_product_map_name);
        $this->checkDependencies();

        if($this->isError()){
            return false;
        }

        $this->setOrderId($order_id);
        $this->setTableProductMapName($table_product_map_name);
        $this->setOrderSubtype('plant');

        if($delivery_type == 'dropshipping'){
            $this->setIsDropshipping(true);
        }

        $data_to_write = $this->getDataToWrite();

        if($this->isError()){
            return false;
        }

        $creator = new InvoicePDFCreator();
        $creator->generateFile($data_to_write);

    }

    /**
     * Метод для проверки определенных правил - можно ли генерировать инвойс.
     * @param $order_id
     */
    public function checkOrderForRules($order_id)
    {
        //Пока правил нет.
        return true;

    }

    public function getInvoiceButtonHtml($order_id, $products, $button_name, $is_need_download = true)
    {
        $order_id = $this->clearOrderId($order_id);
        if( !$order_id || !$this->checkForArray($products)){
            $this->addErrorsMessages('Невозможно инициализировать стартовые данные!');
            return false;
        }

        $order_delivery_type = 'usual';
        $dropshipping_manager = new DropshippingTtnModel();
        if($dropshipping_manager->isOneDropshippingStore($products)){
            $order_delivery_type = 'dropshipping';
        }

        if(!is_string($button_name) || empty($button_name)){
            $button_name = 'Скачать накладную';
        }

        if($this->isError()){

            return false;
        }

        InvoiceOrderElements::getInvoiceButtonHtml($order_id, $order_delivery_type, $button_name, $is_need_download);

    }

    protected function checkDependencies()
    {
        if(
            !$this->getImgTemplatesDir()
            || !$this->getImgTemporaryDir()
            || !$this->getBackSideInfoTemplatesDir()
            || !$this->getBcGenerator()
            || !$this->getNwGenerator()
            || !$this->getDbManager()

        ){
          $this->addErrorsMessages('Ошибка инициализации настроек.');
          return false;
        }

        return true;

    }

    protected function getDataToWrite()
    {
        $table_product_map = $this->getTableProductMapName();
        $customer_info = $this->getCustomerDataFromDB();
        $products_data = $this->getProductsDataFromDB();
        $number_header_cells = $this->getNumberHeaderCells();

        if($this->isError()){
            return false;
        }

        if(!$this->checkNumberColumns($number_header_cells, $products_data)){
            $this->addErrorsMessages('Несовпадение количества колонок в таблице продуктов.');
            return false;
        }

        //Семена
        $seeds_data = $this->getSeedsInnerDataFromDB();
        if($seeds_data){
            $products_data = array_merge($products_data, $this->getSeedsTableDelimiter() ,$seeds_data);
        }

        $products_data = $this->breakIntoPages($products_data);

        //Растения + семена, если есть
        $finance_data = $this->getFinanceDataFromDB();

        if($this->isError()){
            return false;
        }

        $amount_in_words = $this->getAmountInWords($finance_data['ot_total']['value']);

        $data_to_write = [
            'order_id' => $this->getOrderId(),
            'delivery_data' => [
                'is_dropshipping' => $this->isDropshipping(),
            ],
            'print_settings' => [
                'is_two_side' => $this->isTwoSidePrinting(),
            ],
            'header_data' => [
                'customer_name' => $customer_info['customers_name'],
                'date_generated' => date('d.m.Y H:i'),
                'phone' => $customer_info['customers_telephone'],
                'barcode_src' => $this->generateBarcodeSource(),
                'back_side_image_src' => $this->getBackSideImageSource(),
                'shop_logo' => $this->getShopLogoSource(),
            ],
            'order_data' => [
                'table_product_map' => $table_product_map,
                'table_columns_number' => $number_header_cells,
                'products_data' => $products_data,
                'finance_data' => $finance_data,

            ],
            'footer_data' => [
                'amount_in_words' => $amount_in_words
            ],
            'backside_data' => [
                'informer' => $this->getBackSideInformerText(),
            ]


        ];

        return $data_to_write;

    }

    protected function breakIntoPages($products_data)
    {
        if(!$this->checkForArray($products_data)){
            return false;
        }

        $this->analyzeTwoSidePrinting();

        if($this->isTwoSidePrinting()){
            $new =  array_chunk($products_data, self::MAX_ROWS_PER_PAGE);
            return $new;
        }

        return [0 => $products_data];
    }

    protected function getSeedsTableDelimiter()
    {
        return  [ 0 => ['seeds_header' => 'true'] ];
    }

    protected function getBackSideInformerText()
    {
        if(!$this->getBackSideInformerTemplateName() || !$this->getBackSideInfoTemplatesDir()){
            return false;
        }

        $result = file_get_contents($this->getBackSideInfoTemplatesDir() . $this->getBackSideInformerTemplateName());

        if(!$result){
            return false;
        }

        return $result;
    }

    protected function getBackSideImageSource()
    {
        //Для Дропшиппнга не генерируем
        if($this->isDropshipping() === true){
            return false;
        }

        if(!$this->getImgTemplatesDir() || !$this->getBackSideImageName()){
            return false;
        }

        //Если нет текста для обратки, картинку не выводим
        if(!$this->getBackSideInformerTemplateName() || !$this->getBackSideInfoTemplatesDir()){
            return false;
        }

        return $this->getImgTemplatesDir() . $this->getBackSideImageName();
    }


    protected function getAmountInWords($amount)
    {
        if(!$amount){
            return false;
        }

        $result = $this->nwGenerator->num2str($amount);
        return $result;
    }

    protected function getNumberHeaderCells()
    {
        $table_product_map = $this->getTableProductMapName();
        $table_header_placeholders = InvoiceOrderPlaceholder::getTableProductMap($table_product_map);
        $number_header_cells = count($table_header_placeholders);

        if(!$number_header_cells || !is_int($number_header_cells)){
            $this->addErrorsMessages('Нет данных о названии колонок таблицы.');
            return false;
        }

        return $number_header_cells;
    }

    protected function getFinanceDataFromDB()
    {
        $result = $this->dbManager->getOrderTotalData($this->getOrderId());
        if(!$this->checkForArray($result)){
            $this->addErrorsMessages('Нет финансовых данных о заказе.');
            return false;
        }

        if(!$result['ot_total']['value']){
            $this->addErrorsMessages('Нет данных об общей стоимости.');
            return false;
        }

        //Меняем заголовок для отображения
        $result = $this->changeFinanceTitles($result);

        if(!$this->getSeedsOrderId()){
            return $result;
        }

        //Если есть семена, дополняем данными о семенах

        //Меняем заголовок для отображения
        $result['ot_total']['title'] = InvoiceOrderPlaceholder::getPlaceholderByKey('total_title_with_seeds');

        $seeds_result = get_ot_total($this->getSeedsOrderId(), true, true);
        if (!$seeds_result || $seeds_result == 'Ошибка') {
            $this->addErrorsMessages('Нет финансовых данных о семенах в заказе, хотя данные о семенах в наличии.');
            return false;
        }

        $result['seeds_total'] = [
            'title' => InvoiceOrderPlaceholder::getPlaceholderByKey('total_title_for_seeds'),
            'value' => $seeds_result
        ];

        return $result;

    }

    protected function changeFinanceTitles($result)
    {

        if($result['ot_guarantee']){
            $result['ot_guarantee']['title'] = InvoiceOrderPlaceholder::getPlaceholderByKey('total_title_guarantee');
        }
        if($result['ot_prepayment']){
            $result['ot_prepayment']['title'] = InvoiceOrderPlaceholder::getPlaceholderByKey('total_title_prepayment');
        }
        if($result['ot_total']){
            $result['ot_total']['title'] = InvoiceOrderPlaceholder::getPlaceholderByKey('total_title_total');
        }
        if ($result['ot_discount']) {
            $dis = (int)str_replace('%:','',$result['ot_discount']['title']);
            $result['ot_discount']['title'] = InvoiceOrderPlaceholder::getPlaceholderByKey('total_title_discount') . ' ( ' . $dis . '% ):';
        }

        return $result;

    }

    protected function getProductsDataFromDB()
    {
        $products_data = $this->dbManager->getProductsFromDb($this->getOrderId());
        if(!$products_data){
            $this->addErrorsMessages('Нет данных о товарах.');
            return false;
        }

        $sorted_products_data = $this->sortProductData($products_data);
        if(!$sorted_products_data){
            $this->addErrorsMessages('Невозможно отсортировать данные о товарах.');
            return false;
        }

        return $sorted_products_data;
    }

    /**
     * Сортировка полей в соответсвтии с расположением заголовков в таблице инвойса
     * @param $products_data
     * @return array
     */
    protected function sortProductData($products_data)
    {

        if(!$this->checkForArray($products_data)){
            return false;
        }

        $products_data = $this->adjustProductData($products_data);

        $result = [];

        foreach (InvoiceOrderPlaceholder::getTableProductMaps()[$this->getTableProductMapName()] as $index_piece => $piece){

            $title = $piece['header'];

            foreach ($products_data as $index_product => $product){

                $result[$index_product][$title] = $product[$title];
            }

        }

        return $result;

    }

    private function adjustProductData($products_data)
    {

        foreach ($products_data as $index_product => $product){

            if($product['cat_name'] && $product['cat_parent'] != 0){
                $products_data[$index_product]['products_model'] =  $product['cat_name'] . '. ' . $product['products_model'];
            }
        }

        return $products_data;

    }

    protected function getCustomerDataFromDB()
    {
        $customer_info = $this->dbManager->getCustomerData($this->getOrderId());

        if(!$customer_info){
            $this->addErrorsMessages('Нет данных о покупателе.');
            return false;
        }

        return $customer_info;
    }

    protected function checkNumberColumns($number_header_cells, $products_data)
    {

        $number_products_row_cells = array_reduce($products_data, function ($carry, $data) {
            if(count($data) == $carry){
                return count($data);
            }
            return false;

        }, count($products_data[0]));

        if(!is_int($number_products_row_cells)){
            return false;
        }

        if($number_products_row_cells != $number_header_cells){
            return false;
        }

        return true;
    }

    protected function getSeedsInnerDataFromDB()
    {
        $data_for_check = $this->dbManager->getDataForCheckingSeedsInnerOrder($this->getOrderId());

        if(!$this->checkForArray($data_for_check)){
            return false;
        }

        //Допрускаем, что только один вложенный заказа с семенами.
        $data = $data_for_check[0];
        if( !isset($data['seeds_order_id']) ){
            return false;
        }

        $seeds_order_id = $this->clearOrderId($data['seeds_order_id']);
        if(!$seeds_order_id){
            return false;
        }

        $seeds_data = $this->dbManager->getProductsFromDb($seeds_order_id);
        if(!$this->checkForArray($seeds_data)){
            return false;
        }

        $sorted_seeds_data = $this->sortProductData($seeds_data);
        if(!$sorted_seeds_data){
            $this->addErrorsMessages('Невозможно отсортировать данные о семенах.');
            return false;
        }

        $this->setSeedsOrderId($seeds_order_id);

        return $sorted_seeds_data;

    }

    /**
     * @return mixed
     */
    protected function getBarcodeSource()
    {
        return $this->barcodeSource;
    }

    /**
     * @param mixed $barcodeSource
     */
    protected function setBarcodeSource($barcodeSource)
    {
        $this->barcodeSource = $barcodeSource;
    }

    protected function generateBarcodeSource()
    {
        //Для Дропшиппнга не генерируем
        if($this->isDropshipping() === true){
            return false;
        }

        if(!$this->bcGenerator instanceof BarCodeGM){
            $this->addErrorsMessages('Невозможно инициализировать штрихкод генератор.');
            return false;
        }

        $order_code = $this->bcGenerator->EncodeBarCodeExtend(array(
            'type' => 'order',
            'subtype' => $this->getOrderSubtype(),
            'code' => $this->getOrderId(),
        ));
        if(!$order_code){
            $this->addErrorsMessages('Невозможно создать штрихкод.');
            return false;
        }

        $image_src = $this->getImgTemporaryDir() . $order_code . '.png';
        $this->setBarcodeSource($image_src);

        $image_result = imagepng($this->bcGenerator->generateBarCodeExtend('tare', $order_code, 'ean13', 'object'), $image_src);

        if(!$image_result){
            $this->addErrorsMessages('Невозможно создать картинку штрихкода.');
            return false;
        }

        return $image_src;

    }


    /**
     * @return string
     */
    protected function getImgTemplatesDir()
    {
        return $this->imgTemplatesDir;
    }

    /**
     * @param string $imgTemplatesDir
     */
    protected function setImgTemplatesDir($imgTemplatesDir)
    {
        $this->imgTemplatesDir = $imgTemplatesDir;
    }

    /**
     * @return mixed
     */
    protected function getBackSideImageName()
    {
        return $this->backSideImageName;
    }

    /**
     * @param mixed $backSideImageName
     */
    protected function setBackSideImageName($backSideImageName)
    {
        $this->backSideImageName = $backSideImageName;
    }

    /**
     * @return mixed
     */
    protected function getBcGenerator()
    {
        return $this->bcGenerator;
    }

    /**
     * @param mixed $bcGenerator
     */
    protected function setBcGenerator(BarCodeGM $bcGenerator)
    {
        $this->bcGenerator = $bcGenerator;
    }

    /**
     * @return mixed
     */
    protected function getOrderSubtype()
    {
        return $this->orderSubtype;
    }

    /**
     * @param mixed $orderType
     */
    protected function setOrderSubtype($orderType)
    {
        $this->orderSubtype = $orderType;
    }

    /**
     * @return mixed
     */
    protected function getImgTemporaryDir()
    {
        return $this->imgTemporaryDir;
    }

    /**
     * @param mixed $imgTemporaryDir
     */
    protected function setImgTemporaryDir($imgTemporaryDir)
    {
        $this->imgTemporaryDir = $imgTemporaryDir;
    }

    /**
     * @return mixed
     */
    protected function getTableProductMapName()
    {
        return $this->tableProductMapName;
    }

    /**
     * @param mixed $tableProductMapName
     */
    protected function setTableProductMapName($tableProductMapName)
    {
        $this->tableProductMapName = $tableProductMapName;
    }

    /**
     * @return mixed
     */
    protected function getSeedsOrderId()
    {
        return $this->seedsOrderId;
    }

    /**
     * @param mixed $seedsOrderId
     */
    protected function setSeedsOrderId($seedsOrderId)
    {
        $this->seedsOrderId = $seedsOrderId;
    }

    /**
     * @return mixed
     */
    protected function getNwGenerator()
    {
        return $this->nwGenerator;
    }

    /**
     * @param mixed $nwGenerator
     */
    protected function setNwGenerator($nwGenerator)
    {
        $this->nwGenerator = $nwGenerator;
    }

    /**
     * @return mixed
     */
    protected function getBackSideInfoTemplatesDir()
    {
        return $this->backSideInfoTemplatesDir;
    }

    /**
     * @param mixed $backsideInfoTemplatesDir
     */
    protected function setBackSideInfoTemplatesDir($backSideInfoTemplatesDir)
    {

        $this->backSideInfoTemplatesDir = $backSideInfoTemplatesDir;
    }

    /**
     * @return string
     */
    protected function getBackSideInformerTemplateName()
    {
        return $this->backSideInformerTemplateName;
    }

    /**
     * @return mixed
     */
    protected function getShopLogoSource()
    {
        return $this->shopLogoSource;
    }

    /**
     * @param mixed $shopLogoSource
     */
    protected function setShopLogoSource($shopLogoSource)
    {
        $this->shopLogoSource = $shopLogoSource;
    }

    /**
     * @return bool
     */
    protected function isDropshipping()
    {
        return $this->isDropshipping;
    }

    /**
     * @param bool $isDropshipping
     */
    protected function setIsDropshipping($isDropshipping)
    {
        $this->isDropshipping = $isDropshipping;
    }

    /**
     * @return bool
     */
    protected function isTwoSidePrinting()
    {
        return $this->isTwoSidePrinting;
    }

    /**
     * @param bool $isTwoSidePrinting
     */
    protected function setIsTwoSidePrinting($isTwoSidePrinting)
    {
        $this->isTwoSidePrinting = $isTwoSidePrinting;
    }

    //Определение необходимости двухсторонней печати
    protected function analyzeTwoSidePrinting()
    {
        if($this->isDropshipping() == false){
            $this->setIsTwoSidePrinting(true);
            return;
        }

        $this->setIsTwoSidePrinting(false);
        return;
    }

}