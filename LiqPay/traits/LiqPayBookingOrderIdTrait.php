<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.03.2018
 * Time: 16:46
 */

/**
 * Trait LiqPayBookingOrderIdTrait
 * Для генерации и сохранения созданных уникальных номеров заказов
 */
trait LiqPayBookingOrderIdTrait
{
    //Order_id платежа order_id OF GreenMarket for LiqPay
    protected $generated_order_id;

    //Предыдущий (для данной цепочки заказов) уникальный сгенерированный номер заказа for LiqPay
    protected $previousGeneratedOrderId;

    //Доступные статусы инвойса в магазине
    protected $availableInvoiceShopStatuses = [

        'reserved' => 'Инвойс зарезервирован в LiqPay, но ответа о приеме инвойса нет',
        'active' => 'Инвойс послан в LiqPay, получен ответ',
        'canceled_repeated' => 'Инвойс отменен, так как был послан повторный инвойс.',
        'canceled_by_manager' => 'Инвойс отменен по требованию менеджера.',
        'finished' => 'Инвойс оплачен.',

    ];

    // генерируем номер по главному заказу в цепочке
    protected function generateUniqueOrderId()
    {
        $last_generated_order_id = $this->getLastGeneratedOrderIdFromDb();

        if($last_generated_order_id){
            //Сохраняем название предыдущего уникального номера заказа
            $this->setPreviousGeneratedOrderId($last_generated_order_id);
        }

        $new_generated_order_id = $this->reserveGeneratedUniqueOrderId();

        return $new_generated_order_id;

    }

    protected function reserveGeneratedUniqueOrderId()
    {
        $data_to_save = [
            'invoice_type' => $this->getInvoiсesType(),
            'invoice_shop_status' => 'reserved',
            'description' => $this->getOrdersIdsStr(),
        ];

        $new_generated_order_id = $this->getDbManager()->reserveGeneratedOrderIdDb($data_to_save);

        if( !is_int($new_generated_order_id) || !$new_generated_order_id > 0 ){
            $this->addErrorsMessages('Уникальный номер заказа для LiqPay не был сохранен в БД или был сохранен с ошибками.');
            return false;
        }

        return $new_generated_order_id;
    }

    // Получаем последний сгенерированый номер заказа в цепочке
    protected function getLastGeneratedOrderIdFromDb($order_id = false, $invoice_type = false)
    {
        $order_id = ($order_id) ? $order_id : $this->getOrderId();
        $invoice_type = ($invoice_type) ? $invoice_type : $this->getInvoiсesType();

        if(!$order_id || !$invoice_type){
            $this->addErrorsMessages('Ошибка при запросе последнего сгенерированого номера заказа для LiqPay. Не хватает параметров для выборки.');
            return false;
        }

        $data_condition = [
            'order_id' => $order_id,
        ];

        $last_entry = $this->getDbManager()->getLastGeneratedOrderEntryFromDb(
            $data_condition,
            $invoice_type
        );

        if(!$last_entry){
            return false;
        }

        if(!isset($last_entry['generated_order_id']) ){
            $this->addErrorsMessages('Ошибка при запросе последнего сгенерированого номера заказа для LiqPay.');
            return false;
        }

        $generated_order_id = ($last_entry['generated_order_id']) ? $last_entry['generated_order_id'] : false ;

        return $generated_order_id;

    }


    protected function getDataByGeneratedOrderId($generated_order_id, $data_map_to_select)
    {
        if(!$generated_order_id){
            $this->addErrorsMessages('Не возможно получить информацию об уникальном номере заказа, нет параметра для поиска.');
            return false;
        }

        if( !$this->checkForArray($data_map_to_select) ){
            $this->addErrorsMessages('Не возможно получить информацию об уникальном номере заказа, нет списка полей для поиска.');
            return false;
        }

        $data_condition = [
            'generated_order_id' => $generated_order_id,
        ];

        $result = $this->getDbManager()->getDataByGeneratedOrderIdFromDb($data_condition, $data_map_to_select);

        if( !$this->checkForArray($result) ){
            $this->addErrorsMessages('По Уникальному номеру заказа для LiqPay:' . $generated_order_id . " нет данніх в БД.");
            return false;
        }

        return $result;
    }

    protected function isGeneratedOrderMayBeDeleted($data_array_to_analyze)
    {
        if( !$this->checkForArray($data_array_to_analyze) ){
            $this->addErrorsMessages('Не возможно проанализировать данные об уникальном номере заказа, нет данных.');
            return false;
        }

        //Analyze invoice_shop_status
        $invoice_shop_status = $data_array_to_analyze['invoice_shop_status'];
        $forbidden_shop_statuses = ['finished', 'canceled_repeated', 'canceled_by_manager'];
        if( !$invoice_shop_status ||  in_array($invoice_shop_status, $forbidden_shop_statuses)){
            return false;
        }

        //Analyze result of sending invoice
        $invoice_result = $data_array_to_analyze['result'];
        $permissible_results = ['ok'];
        if( !$invoice_result ||  !in_array($invoice_result, $permissible_results)){
            return false;
        }

        //Analyze expired_date
        $expired_date = $data_array_to_analyze['expired_date'];
        if( !$expired_date ){
            return false;
        }
        $expired_date_object = new DateTime($expired_date);
        $now    = new DateTime();
        if($now > $expired_date_object){
            return false;
        }

        return true;

    }

   /* protected function updateBookedOrdersChain($orders_in_chain, $generated_order_id = false)
    {
        if(! $this->checkForArray($orders_in_chain)){
            return false;
        }

        $process_completed = true;
        $generated_order_id = ($generated_order_id) ? $generated_order_id : $this->getGeneratedOrderId();

        if(!$generated_order_id){
            $this->addErrorsMessages('Уникальный номер заказа для LiqPay не был установлен. Обновление в БД невозможно.');
            return false;
        }

        foreach ($orders_in_chain as $order_id){
            //Создаем данные для резервирования уникального номера заказа для новых заказов в цепочке
            $data_to_change = [
                'generated_order_id' => $generated_order_id,
            ];

            $data_condition = [
                'order_id' => $order_id,
            ];

            $update_result = $this->getDbManager()->updateLogbookTableDb($data_to_change, $data_condition, $this->getInvoiсesType());

            if( !$update_result){
                $process_completed =  false;
            }
        }

        if(!$process_completed){
            $this->addErrorsMessages('Уникальный номер заказа для LiqPay не был обновлен в БД или был обновлен с ошибками.');
            return false;
        }

        return true;

    }*/

    protected function saveNewEntryGeneratedOrderId($orders_in_chain, $generated_order_id = false, $invoice_type = false)
    {
        if(! $this->checkForArray($orders_in_chain)){
            return false;
        }

        $process_completed = true;
        $generated_order_id = ($generated_order_id) ? $generated_order_id : $this->getGeneratedOrderId();
        $invoice_type = ($invoice_type) ? $invoice_type :  $this->getInvoiсesType();

        if(!$generated_order_id){
            $this->addErrorsMessages('Уникальный номер заказа для LiqPay не был установлен. Работа с БД невозможна.');
            return false;
        }

        if(!$invoice_type){
            $this->addErrorsMessages('Тип инвойса для LiqPay не был установлен. Работа с БД невозможна.');
            return false;
        }

        foreach ($orders_in_chain as $order_id){
            //Создаем данные для резервирования уникального номера заказа для новых заказов в цепочке
            $data_to_book = [
                'order_id' => $order_id,
                'generated_order_id' => $generated_order_id,
            ];

            if(!$this->isFullArrayData($data_to_book)){
                $this->addErrorsMessages('Не хватает компонент. Уникальный номер заказа для LiqPay не был сохранен в БД.');
                return false;
            }

            $entry_id = $this->getDbManager()->saveLogbookEntryDb($data_to_book, $invoice_type);

            if( !is_int($entry_id) || !$entry_id > 0 ){
                $process_completed =  false;
            }
        }

        if(!$process_completed){
            $this->addErrorsMessages('Уникальный номер заказа для LiqPay не был сохранен в БД или был сохранен с ошибками.');
            return false;
        }

        return true;

    }

    protected function bookGeneratedOrderId($orders_in_chain = false)
    {
        $orders_in_chain = ($orders_in_chain) ? $orders_in_chain : $this->getOrdersChain();

        if(!$this->checkForArray($orders_in_chain)){
            return false;
        }

        $process_completed = $this->saveNewEntryGeneratedOrderId($orders_in_chain);
        return $process_completed;

    }


    protected function getInvoiceDataByGeneratedOrderId($generated_order_id)
    {
        if(!$generated_order_id){
            $this->addErrorsMessages( 'Ответ от LiqPay не содержит $generated_order_id и не был сохранен в БД.');
            return false;
        }

        $data_map_to_select = [
            'invoice_id',
            'invoice_type',
            'invoice_shop_status',

        ];

        $invoice_saved_data = $this->getDataByGeneratedOrderId($generated_order_id, $data_map_to_select);

        if(!$invoice_saved_data){
            $this->addErrorsMessages( 'По $generated_order_id: ' . $generated_order_id . 'не были получены данные.');
            return false;
        }

        return $invoice_saved_data;
    }

    protected function getOrdersChainByGeneratedOrderId($generated_order_id, $invoice_type)
    {
        if(!$generated_order_id || !$invoice_type){
            $this->addErrorsMessages('Не возможно получить информацию об уникальном номере заказа, нет параметров для поиска.');
            return false;
        }

        $condition_array = [
            'generated_order_id' => $generated_order_id,
        ];

        $result = $this->getDbManager()->getBookedOrdersChainFromDb($condition_array, $invoice_type);

        if( !$this->checkForArray($result) ){
            $this->addErrorsMessages('По Уникальному номеру заказа для LiqPay:' . $generated_order_id . " нет данных в БД.");
            return false;
        }

        return $result;

    }


    protected function changeInvoiceStatus($new_status, $generated_order_id = false)
    {

        $generated_order_id = ($generated_order_id) ? $generated_order_id : $this->getGeneratedOrderId();

        if(!$generated_order_id){
            $this->addErrorsMessages('Не возможно определить уникальный номер заказа, для которого есть намерение изменить статус в БД.');
            return false;
        }

        if(!$new_status || !is_string($new_status)){
            $this->addErrorsMessages('Не передано название для изменения текущего статуса инвойса. Статус инвойса LiqPay: ' . $generated_order_id . ' не был изменен в БД.');
            return false;
        }

        if(!array_key_exists($new_status, $this->getAvailableInvoiceShopStatuses())){
            $this->addErrorsMessages('Для изменения текущего статуса инвойса передан не предусмотренный статус: ' . $new_status . '. Статус не был изменен в БД.');
            return false;
        }

        //Создаем данные для изменения статуса по  уникальному номеру заказа
        $data_to_change = [
            'invoice_shop_status' => $new_status,
        ];

        $data_condition = [
            'generated_order_id' => $generated_order_id,
        ];

        $update_result = $this->getDbManager()->updateInvoiceEntryDb($data_to_change, $data_condition);

        return $update_result;

    }

    /**
     * @return mixed
     */
    protected function getGeneratedOrderId()
    {
        return $this->generated_order_id;
    }

    /**
     *  Генерируем уникальный номер заказа для LiqPay
     * @param mixed $generated_order_id
     */
    protected function setGeneratedOrderId($generated_order_id)
    {
        if(!$generated_order_id){
            $this->generated_order_id = false;
            $this->addErrorsMessages('Невозможно сформировать уникальный номер заказа для LiqPay.');
            return $this;
        }

        $this->generated_order_id = $generated_order_id;

        //Сохраняем в БД запись для каждого заказа из цепочки заказов о номере Уникального заказа
        //$this->bookGeneratedOrderId($this->getOrdersChainActive());
        $this->bookGeneratedOrderId();

        return $this;
    }

    /**
     * @return array
     */
    protected function getAvailableInvoiceShopStatuses()
    {
        return $this->availableInvoiceShopStatuses;
    }

    /**
     * @return mixed
     */
    protected function getPreviousGeneratedOrderId()
    {
        return $this->previousGeneratedOrderId;
    }

    /**
     * @param mixed $previousGeneratedOrderId
     */
    protected function setPreviousGeneratedOrderId($previousGeneratedOrderId)
    {
        $this->previousGeneratedOrderId = $previousGeneratedOrderId;
    }

}