<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 16.02.2018
 * Time: 13:21
 */

require_once(DIR_WS_CLASSES . 'dropshipping/DropshippingConstants.php');
require_once(DROPSHIPPING_MODELS_DIR . 'DropshippingTtnExcelModel.php');
require_once(DROPSHIPPING_ROOT_DIR . 'DropshippingTrait.php');

class DropshippingTtnModel
{
    use DropshippingTrait;

    protected $dataMap = [

        'ttn_date' => array(
            'header' => 'Дата заполнения',
            'value' => ''
        ),

        'responsible_manager' => array(
            'header' => 'Ответственный менеджер',
            'value' => ''
        ),

        'orders_status_name' => array(
            'header' => 'Статус заказа',
            'value' => ''
        ),

        'shipping_method' => array(
            'header' => 'Способ доставки (НП, Деливери, Интайм):',
            'value' => ''
        ),

        'delivery_data' => array(
            'header' => 'Адрес доставки (город, отделение№, адрес)',
            'value' => ''
        ),

        'delivery_name' => array(
            'header' => 'Получатель (ФИО)',
            'value' => ''
        ),

        'customers_telephone' => array(
            'header' => 'Мобильный тел. получателя',
            'value' => ''
        ),

        'empty_field_1' => array(
            'header' => '',
            'value' => ''
        ),

        'sender' => array(
            'header' => 'Отправитель:',
            'value' => 'Код ОКПО 3038012211'
        ),

        'cash_on_delivery' => array(
            'header' => 'Наложный платеж (сумма)',
            'value' => ''
        ),

        'assessed_value' => array(
            'header' => 'Оценочная стоимость (сумма заказа)',
            'value' => ''
        ),

        'payer' => array(
            'header' => 'Оплата за доставку:',
            'value' => 'Получатель'
        ),

        'empty_field_2' => array(
            'header' => '',
            'value' => ''
        ),

        'products_data' => array(
            'header' => 'Заказ (наименование, цена, количество)',
            'value' => ''
        ),

        'orders_id' => array(
            'header' => 'Номер заказа',
            'value' => ''
        ),

        'ttn_number' => array(
            'header' => 'Номер накладной',
            'value' => ''
        ),

    ];

    protected $controlTypePayment = "На карточку";

    protected $controlMaxBalance = 9;

    protected $controlMinPrepaymentPercent = 30;

    protected $notDropshippingStorehousesId = [1,2];

    public function getButton($order_id, $products, $button_name)
    {
        $this->setOrdersData($order_id, $products);

        if($this->isError()){

            return false;
        }

        if(!$this->isOneDropshippingStore($products)){
            return;
        }

        $this->getButtonHtml($button_name);

    }

    public function getFileExcel($common_orders_data, $products_orders_data)
    {

        if(! $this->checkForArray($common_orders_data)){
            $this->addErrorsMessages('Ошибка в получении данных по заказу!');
        }

        if(empty($products_orders_data)){
            $this->addErrorsMessages('Ошибка в получении данных о товарах по заказу!');
        }

        if($this->isError()){
            $this->setErrorsMessages( $this->createErrorsMessage() );
            return false;
        }

        $generator = new DropshippingTtnExcelModel();

        $data_to_write = $this->getFilledContent($common_orders_data, $products_orders_data);

        $generator->setStartColumn('A')->setEndColumn('B')->setSheetTitle('ТТН')->generateFile($data_to_write);

    }

    public function checkOrderForRules($data_to_check)
    {
        if(!$this->checkForArray($data_to_check)){
            return 'Ошибка в получении данных при проверке заказа!';
        }

        if($this->checkControlPrepayment($data_to_check)=== false){
            return 'Недостаточный размер предоплаты! Дождитесь оплаты.';
        }

        if($this->checkControlTypePayment($data_to_check) === false){
            return 'Заказ не оплачен! Дождитесь оплаты или смените Способ оплаты на Наложенный платеж.';
        }

        return true;

    }

    /**
     * @return array
     */
    protected function getDataMap()
    {
        return $this->dataMap;
    }

    protected function getFilledContent($common_orders_data, $products_orders_data)
    {
        $map = $this->getDataMap();

        foreach ($common_orders_data as $field => $value){

            if(array_key_exists($field, $map)){

                $map[$field]['value'] = $value;

            }

        }

        $map['products_data']['value'] = $products_orders_data;

        return $map;

    }

    protected function getButtonHtml($button_name)
    {
        ?>
        <!-- Get excel file for TTN Dropshipping -->
        <iframe id="dropshipping_iframe" src="" style="display: none; visibility: hidden;"></iframe>
        <!-- Get excel file for TTN Dropshipping-->
        <button class="admin-btn admin-btn-success" id="get-ttn-dropshipping" data-orders-id="<?php echo $this->getOrderId();  ?>" type="button"><?php echo $button_name; ?></button>

        <?php
    }

    protected function setOrdersData($order_id, $products)
    {
        $order_id = intval($order_id);

        if( !is_int($order_id) || !$this->checkForArray($products) ){
            $this->addErrorsMessages('Невозможно инициализировать стартовые данные!');
            return false;
        }

        $this->setOrderId($order_id);

    }

    public function isOneDropshippingStore($products)
    {
        $storehouses = [];

        foreach ($products as $key => $product){

            //Если есть домашние склады - не соответствует критериям
            if( in_array($product['storehouse_id'], $this->getNotDropshippingStorehousesId()) ){
                return false;
                break;
            }

            $storehouses[$key] = $product['storehouse_id'];

        }

        //Если уникальных складов не 1 - не соответствует критериям
        if(count(array_unique($storehouses)) != 1){
            return false;
        }

        return true;

    }

    /**
     * если в заказе сумма предоплаты меньше 30% - данные не формировать
     * @param $data_to_check
     * @return bool
     */
    protected function checkControlPrepayment($data_to_check)
    {
        if( !isset($data_to_check['control_prepayment']) ){
            return false;
        }

        $prepayment_percent = ( ($data_to_check['control_prepayment'] + $data_to_check['control_guarantee']) / $data_to_check['amount']) * 100;

        if($prepayment_percent < $this->getControlMinPrepaymentPercent()){
            return false;
        }

        return true;
    }

    /**
     * в случае оплаты на карту, если всего больше 9 грн, данные не формировать
     * @param $data_to_check
     * @return bool
     */
    protected function checkControlTypePayment($data_to_check)
    {
        if(!isset($data_to_check['control_type_payment']) || !isset($data_to_check['control_payment'])){
            return false;
        }

        if($data_to_check['control_type_payment'] != $this->getControlTypePayment()){
            return true;
        }


        if( intval($data_to_check['control_payment']) > $this->getControlMaxBalance()){
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getNotDropshippingStorehousesId()
    {
        return $this->notDropshippingStorehousesId;
    }

    /**
     * @return string
     */
    public function getSystemName()
    {
        return $this->systemName;
    }

    /**
     * @return string
     */
    protected function getControlTypePayment()
    {
        return $this->controlTypePayment;
    }

    /**
     * @return int
     */
    protected function getControlMaxBalance()
    {
        return $this->controlMaxBalance;
    }

    /**
     * @return int
     */
    protected function getControlMinPrepaymentPercent()
    {
        return $this->controlMinPrepaymentPercent;
    }


}