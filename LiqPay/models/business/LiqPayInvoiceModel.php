<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 26.02.2018
 * Time: 17:23
 */

require_once DIR_FS_ADMIN_CLASSES . 'payments/OrdersForPayments.php';

/**
 * Class LiqPayInvoiceModel
 * Отправка счета на e-mail клиента.
 * https://www.liqpay.ua/documentation/api/aquiring/invoice/
 *
 */

class LiqPayInvoiceModel extends AcquireAbstract
{
    use LiqPayInvoiceTrait{
        setAmount as traitSetAmount;
        setUserEmail as traitSetUserEmail;
        setLanguage as traitSetLanguage;
        setMainOrderId as traitSetMainOrderId;
        setOrdersChain as traitSetOrdersChain;
        setOrdersChainActive as traitSetOrdersChainActive;
        setInvoiсesType as traitSetInvoiсesType;
    }

    use LiqPayBookingOrderIdTrait;

    //Данные для цепочки заказов (Сессии) - таблица orders_to_prepayment_percent
    protected $ordersChainPercentData = [];

    //Данные для платежных груп заказов - таблица order_payment_group_storage
    protected $ordersPaymentGroupData = [];

    protected $processCompleted = false;

    public function __construct($order_id)
    {
        parent::__construct();
        $this->setOrderId($order_id);
        $this->initAvailableInvoicesTypes();
        //LOG CALLBACK
        $this->setLogsFile("LiqPay_sending_invoice_results.txt");
    }

    /**
     * https://www.liqpay.ua/documentation/api/aquiring/invoice/doc
     * @return bool
     */
    public function sendInvoicePayment($form_data, $orders_chain, $orders_chain_active, $orders_chain_percent_data, $group_data, $invoice_type)
    {
        // 1. Парсим входящие данные //____________STAGE__________________//
        $this->parseIncomeData($form_data, $orders_chain, $orders_chain_active, $orders_chain_percent_data, $group_data, $invoice_type);

        if($this->isError()){
            return false;
        }

        // 2. Отсылаем запрос на API LiqPay //____________STAGE__________________//

        //Обьект для формирование параметра для передачи в API LiqPay
       $request_helper = new RequestHelperInvoiceSend();

       $liq_pay_answer = $this->sendInvoiceRequest($request_helper);

        if($this->isError()){
            return false;
        }

        // 3. Разбираем ответ от API LiqPay //____________STAGE__________________//

        //Обьект для работы с ответом API LiqPay
        $response_helper = new ResponseHelperInvoiceSend($liq_pay_answer);

        if($response_helper->isError()){
            //Если с инвойсом не получилось, завершаем запрос.
            $this->setIsError(true);
            $this->setErrorsMessages( $response_helper->getErrorsMessages() );
            //Добавляем АЛАРМ в случае неуспешного разбора ответа от LiqPay
            $this->setOrderAlarmTrouble( $this->getOrderId(), 'Ответ от LiqPay о приеме инвойса обработан не коректно. Ответ не сохранен в БД. Сообщите администратору! ');

            return false;
        }

        $this->addSuccessMessages( $response_helper->getSuccessMessages() );
        $this->addSuccessMessages("Срок оплаты ( GMT - Greenwich ) до  " . $request_helper->getExpiredDate() . ". ");

        //Главная цель выполнена
        $this->setProcessCompleted(true);


        // 4. Сохраняем в БД ответ от API LiqPay //____________STAGE__________________//

        $response_helper
            ->setInvoiceType( $this->getInvoiсesType() )
            ->setInvoiceShopStatus('active')
            ->setExpiredDate( $request_helper->getExpiredDate() )
        ;
        $data_to_save = $response_helper->getDataForDB();
        $result_db = $this->saveResponseEntry($data_to_save);

        //Добавляем АЛАРМ в случае неуспешного сохранения в БД
        if(!$result_db){
            $this->setOrderAlarmTrouble( $this->getOrderId(), 'Ответ от LiqPay о приеме инвойса не сохранен в БД. Сообщите администратору! ');
        }

        if( $this->isError() ){
            return false;
        }

        $this->addSuccessMessages("Ответ от LiqPay сохранен в БД.");

        // 4.1 Сохраняем в БД данные о цепочке заказов - table orders_to_prepayment_percent //____________STAGE__________________//

        $response_percentage_arr = OrdersForPayments::savePercentages( $this->getOrdersChainPercentData() );

        //Добавляем АЛАРМ в случае неуспешного сохранения в БД
        if($response_percentage_arr['error_msg']) {

            $orders_percentage_saving_error_str = 'Цепочка заказов не сохранена или не обновлена в БД. Сообщите администратору! ' . $response_percentage_arr['error_msg'];
            $this->setOrderAlarmTrouble( $this->getOrderId(), $orders_percentage_saving_error_str);
            $this->addErrorsMessages($orders_percentage_saving_error_str);

        }

        if( $this->isError() ){
            return false;
        }

        $this->addSuccessMessages("Цепочка заказов сохранена или обновлена в БД. " . $response_percentage_arr['result_msg']);

        // 4.2 Сохраняем в БД данные о платежной группе заказов - table order_payment_group_storage //____________STAGE__________________//

        $response_group_arr = OrdersForPayments::savePaymentGroup($this->getOrdersPaymentGroupData(), $this->getOrdersChainPercentData());

        //Добавляем АЛАРМ в случае неуспешного сохранения в БД
        if($response_group_arr['error_msg']) {

            $orders_payment_group_saving_error_str = 'Платежная группа заказов не сохранена в БД. Сообщите администратору! ' . $response_group_arr['error_msg'];
            $this->setOrderAlarmTrouble( $this->getOrderId(), $orders_payment_group_saving_error_str);
            $this->addErrorsMessages($orders_payment_group_saving_error_str);

        }

        if( $this->isError() ){
            return false;
        }

        $this->addSuccessMessages("Платежная группа заказов сохранена в БД. " . $response_group_arr['result_msg']);

        /**
         *  Дополнительная функциональность, которая не влияет на общий результат операции:
         *  - Отмена предыдущего инвойса, если он был.
         *  - Отправка нашего письма (так как LiqPay также отправляет письмо).
         *  - Логирование результатов операции в БД.
         */

        //___________________NOT CRITICAL STAGES________________________________//

        // 5. Отмена предыдущего инвойса //____________STAGE__________________//
        if($this->getPreviousGeneratedOrderId()){
            $cancel_result = $this->cancelInvoiceRequest( $this->getPreviousGeneratedOrderId() );
            if($cancel_result){
                $this->logManagerComment('Отменен предыдущий LiqPay инвойс №' . $this->getPreviousGeneratedOrderId() . ', ');
                $this->addSuccessMessages("Отменен предыдущий инвойс №" . $this->getPreviousGeneratedOrderId() . ". ");
            }else{
                //Добавляем АЛАРМ в случае неуспешной отмены
                $this->setOrdersChainAlarmTrouble( $this->getOrdersChain(), 'Предыдущий инвойс не отменен. Сообщите администратору! ');
            }
        }

        // 6. Отправляем письмо покупателю //____________STAGE__________________//

        //Обьект для отсылкы письма
        $notifier_helper = new NotifierHelperInvoiceSend();
        $letter_result = $this->sendLetter( $response_helper->getHref(), $request_helper->getMaxDaysToPay(), $request_helper->getExpiredDate(), $notifier_helper );
        if($letter_result){
            $this->addSuccessMessages("Отправлено письмо GreenMarket. На адрес " . $this->getUserEmail() . ". ");
        }

        // 7. Логгируем результаты проведенных операций  в БД //____________STAGE__________________//
        $log_result = $this->logResults();

        $this->logManagerComment('Создан LiqPay инвойс №' . $response_helper->getGeneratedOrderId() . ', ');

        if($log_result){
            $this->addSuccessMessages("Логирование результатов проведено.");
        }

        return true;
    }

    //Добавляет новый заказ в таблицу журнала инвойсов, присваивается родительский инвойс
    public function addOneNewOrderToLogbook($new_order_id, $parent_order_id = false)
    {
        $new_order_id = intval($new_order_id);
        if(! is_int($new_order_id) || !$new_order_id > 0){
            return false;
        }
        $parent_order_id = ($parent_order_id) ? $parent_order_id : $this->getOrderId();
        if(! is_int($parent_order_id) || !$parent_order_id > 0){
            return false;
        }
        $result_prepaid = false;
        $result_payment = false;

        //Update for Prepaid
        $generated_order_id_prepaid = $this->getLastGeneratedOrderIdFromDb($parent_order_id, $this->getInvoiсesTypePrepaid());
        if($generated_order_id_prepaid){
            $result_prepaid = $this->saveNewEntryGeneratedOrderId([$new_order_id], $generated_order_id_prepaid, $this->getInvoiсesTypePrepaid());
        }

        //Update for Payment
        $generated_order_id_payment = $this->getLastGeneratedOrderIdFromDb($parent_order_id, $this->getInvoiсesTypePayment());
        if($generated_order_id_payment){
            $result_payment = $this->saveNewEntryGeneratedOrderId([$new_order_id], $generated_order_id_payment, $this->getInvoiсesTypePayment());
        }

        if( ($generated_order_id_payment && !$result_payment) || ($generated_order_id_prepaid && !$result_prepaid) ){
            $this->addErrorsMessages('У родительского заказа: ' . $parent_order_id . ' есть LiqPay инвойс, но для отделенного заказа: ' .  $new_order_id . ' не удалось сохранить запись в БД.');
            return false;
        }

        if($generated_order_id_prepaid){
           $this->addSuccessMessages('LiqPay инвойс: перенесена запись о инвойсе №' . $generated_order_id_prepaid . '.');
        }

        if($generated_order_id_payment){
            $this->addSuccessMessages('LiqPay инвойс: перенесена запись о инвойсе №' . $generated_order_id_payment. '.');
        }

        return true;

    }

    protected function sendLetter($invoice_href, $max_days_to_pay, $expired_date, NotifierHelperInvoiceSend $notifier_helper)
    {
        if(!$invoice_href){
            $this->addErrorsMessages('В ответе LiqPay не содержится ссылка на инвойс. Письмо от Greenmarket не отправилось.');
            return false;
        }

        if(!$max_days_to_pay || !is_int($max_days_to_pay) || !(intval($max_days_to_pay) > 0)){
            $this->addErrorsMessages('Не передан параметр срока жизни инвойса. Письмо от Greenmarket не отправилось.');
            return false;
        }


        $notifier_helper
            ->setMainOrderId( $this->getMainOrderId() )
            ->setAmount( $this->getAmount() )
            ->setOrdersChain( $this->getOrdersChain() )
            ->setOrdersChainActive( $this->getOrdersChainActive() )
            ->setInvoiceHref($invoice_href)
            ->setInvoiсesType( $this->getInvoiсesType() )
            ->setUserEmail( $this->getUserEmail() )
            ->setLanguage( $this->getLanguage() )
            ->setMaxDaysToPay($max_days_to_pay)
            ->setNumberOrdersInChain( count($this->getOrdersChain()) )
            ->setExpiredDate($expired_date)
        ;

        $notify_result = $notifier_helper->sendLetter();

        if($notifier_helper->isError()){
            $this->addErrorsMessages( $notifier_helper->getErrorsMessages() );
            return false;
        }

        return $notify_result;

    }

    protected function saveResponseEntry($data_to_save)
    {
        if(!$this->checkForArray($data_to_save)){
            $this->addErrorsMessages('Ответ от LiqPay не был отформатирован и не был сохранен в БД.');
            return false;
        }

        $condition_array = [
            'generated_order_id' => $data_to_save['generated_order_id'],
        ];

        //Сохраняем ответ от LiqPay в БД
        $is_updated = $this->getDbManager()->updateInvoiceEntryDb($data_to_save, $condition_array);

        if( $is_updated != true ){
            $this->addErrorsMessages('Ответ от LiqPay не был сохранен в БД.');
            return false;
        }

        return true;

    }

    protected function initAvailableActions()
    {

        $this->availableActions = [
            $this->getInvoiсesActionNameSend() => 'Выставление инвойса',
            $this->getInvoiсesActionNameCancel() => 'Отмена инвойса'
        ];
    }

    protected function sendInvoiceRequest( RequestHelperInvoiceSend $request_helper )
    {
        $action = $this->getInvoiсesActionNameSend();
        $action_payment = 'pay';
        //Если инвойс отправляется не из живого серверa, добавляем приставку во избежание дублирования уникальных номепров заказов
        //$generated_order_id = $this->getGeneratedOrderId();
        $generated_order_id = ( $this->isLiveServer() ) ? $this->getGeneratedOrderId() : $this->getGeneratedOrderId() . '-test';

        $request_helper
            ->setEmail( $this->getUserEmail() )
            ->setAction($action)
            ->setAmount( $this->getAmount() )
            ->setGeneratedOrderId( $generated_order_id )
            ->setActionPayment($action_payment)
            ->setDescription( $this->getOrdersIdsStr() )
            ->setLanguage(  $this->convertOrdersLanguage( $this->getLanguage() ) )
        ;

        if($request_helper->isError()){
            $this->setIsError(true);
            $this->setErrorsMessages( $request_helper->getErrorsMessages() );
            return false;
        }

        $liq_pay_result = $this->request( $action, $request_helper->getRequestData() );

        if(!$liq_pay_result){
            $this->addErrorsMessages('Не удалось совершить запрос к API Liqpay.');
            return false;
        }

        return $liq_pay_result;
    }

    protected function cancelInvoiceRequest($generated_order_id)
    {
        if(!$generated_order_id){
            $this->addErrorsMessages('Не передан параметр уникального номера инвойса. Не удалось совершить запрос на отменеу инвойса к API Liqpay.');
            return false;
        }


        // 1. Проверка инвойса на возможность его отмены //____________STAGE__________________//

        $data_map_to_select = [
            'invoice_id',
            'invoice_type',
            'invoice_shop_status',
            'expired_date',
            'result'
        ];

        $data_invoice_to_cancel = $this->getDataByGeneratedOrderId($generated_order_id, $data_map_to_select);
        $can_cancel = $this->isGeneratedOrderMayBeDeleted($data_invoice_to_cancel);

        //Если инвойс отменяется не из живого серверa, добавляем приставку во избежание отмены не того инвойса
        $generated_order_id = ( $this->isLiveServer() ) ? $generated_order_id : $generated_order_id . '-test';

        if(!$can_cancel){
            $this->addErrorsMessages('Предыдущий инвойс: ' . $generated_order_id .' не может быть отменен.');
            return false;
        }


        // 2. Формируем обьект с данными для запроса //____________STAGE__________________//

        $action = $this->getInvoiсesActionNameCancel();

        //Обьект для формирование параметра для передачи в API LiqPay
        $request_helper = new RequestHelperInvoiceCancel();
        $request_helper
            ->setAction($action)
            ->setGeneratedOrderId($generated_order_id)
        ;

        if($request_helper->isError()){
            $this->setIsError(true);
            $this->setErrorsMessages( $request_helper->getErrorsMessages() );
            return false;
        }


        // 3.  Отсылаем запрос на API LiqPay на отмену инвойса //____________STAGE__________________//
        $liq_pay_answer = $this->request( $action, $request_helper->getRequestData() );

        if(!$liq_pay_answer){
            $this->addErrorsMessages('Не удалось совершить запрос на отменеу инвойса: ' . $generated_order_id . ' к API Liqpay.');
            return false;
        }


        // 4. Разбираем ответ от API LiqPay //____________STAGE__________________//

        //Обьект для работы с ответом API LiqPay
        $response_helper = new ResponseHelperInvoiceCancel($liq_pay_answer);

        if($response_helper->isError()){
            $this->setIsError(true);
            $this->setErrorsMessages( $response_helper->getErrorsMessages() );
            return false;
        }


        // 5. Изменяем статус инвойса в БД. //____________STAGE__________________//

        $change_status_result = $this->changeInvoiceStatus('canceled_repeated', $generated_order_id);

        if(!$change_status_result){
            $this->addErrorsMessages('Статус Отмененного номера заказа для LiqPay: ' . $generated_order_id . ' не был изменен в БД.');
            return false;
        }

        return true;

    }

    protected function parseIncomeData($form_data, $orders_chain, $orders_chain_active, $orders_chain_percent_data, $group_data, $invoice_type)
    {
//        var_dump($form_data);var_dump($orders_chain);var_dump($orders_chain_active);var_dump($orders_chain_percent_data);
//        exit;

        if(! $this->checkForArray($form_data)){
            $this->addErrorsMessages('Не пришли данные с формы.');
        }

        $this
            ->setUserEmail($form_data['customer_email'])
            ->setMainOrderId($form_data['main_order'])
            ->setAmount($form_data['total_liqpay'])
            ->setOrdersChain($orders_chain)
            ->setOrdersChainActive($orders_chain_active)
            ->setLanguage($form_data['current_order_lang'])
            ->setInvoiсesType($invoice_type)
            //Генерируем уникальный номер заказа для LiqPay
            ->setGeneratedOrderId( $this->generateUniqueOrderId() )
            ->setOrdersChainPercentData($orders_chain_percent_data)
            ->setOrdersPaymentGroupData($group_data)
        ;

        //LOG IN FILE
        $log_stage = 'SEND_INVOICE_PARSE_INCOME_DATA';
        $this->logInFile($log_stage, '$form_data', json_encode($form_data));
        $this->logInFile($log_stage, '$orders_chain', json_encode($orders_chain));
        $this->logInFile($log_stage, '$orders_chain_active', json_encode($orders_chain_active));
        $this->logInFile($log_stage, '$orders_chain_percent_data', json_encode($orders_chain_percent_data));
        $this->logInFile($log_stage, '$group_data', json_encode($group_data));
        $this->logInFile($log_stage, 'GeneratedOrderId', $this->getGeneratedOrderId());
        $this->logInFile($log_stage, '$invoice_type', $invoice_type);

        return true;

    }

    /**
     * @param array $ordersChain
     */
    protected function setOrdersChain($ordersChain)
    {
        if( !$this->checkForArray($ordersChain) ){
            $this->addErrorsMessages('Не передана цепочка номеров заказов.');
            return $this;
        }

        return $this->traitSetOrdersChain($ordersChain);
    }

    /**
     * @param array $ordersChain
     */
    protected function setOrdersChainActive($ordersChainActive)
    {
        if( !$this->checkForArray($ordersChainActive) ){
            $this->addErrorsMessages('Не передана цепочка номеров активных заказов.');
            return $this;
        }

        return $this->traitSetOrdersChainActive($ordersChainActive);
    }

    /**
     * @param mixed $mainOrderId
     */
    protected function setMainOrderId($mainOrderId)
    {
        $order_id = intval($mainOrderId);

        if(!is_int( $order_id ) || !$order_id > 0){

            $this->mainOrderId = false;
            $this->addErrorsMessages('Не передан номер основного заказа.');
            return $this;

        }

        return $this->traitSetMainOrderId($mainOrderId);
    }

    /**
     * @param mixed $userEmail
     */
    protected function setUserEmail($userEmail)
    {

        if(!$userEmail){
            $this->userEmail = false;
            $this->addErrorsMessages('Не пришли данные с формы о email покупателя');
            return $this;
        }

       return $this->traitSetUserEmail($userEmail);
    }

    /**
     * @param int $invoiceAmount
     */
    protected function setAmount($invoiceAmount)
    {
        $invoice_amount = intval($invoiceAmount);

        if(!is_int( $invoice_amount ) || !$invoice_amount > 0){

            $this->amount = false;
            $this->addErrorsMessages('Не передана сумма для инвойса.');
            return $this;

        }

        return $this->traitSetAmount($invoice_amount);

    }

    /**
     * @param mixed $language
     */
    protected function setLanguage($language)
    {

        if(!$language){
            $this->language = false;
            $this->addErrorsMessages('Не пришли данные с формы о настройке Языка покупателя');
            return $this;
        }

        return $this->traitSetLanguage($language);
    }

    protected function setInvoiсesType($invoiсesType)
    {

        if(!$invoiсesType){
            $this->invoiсesType = false;
            $this->addErrorsMessages('Не пришли данные с формы о типе будущего Инвойса.');
            return $this;
        }

        if(!isset( $this->getAvailableInvoicesTypes()[$invoiсesType] )){
            $this->invoiсesType = false;
            $this->addErrorsMessages('Тип будущего Инвойса не предопределен.');
            return $this;
        }

        return $this->traitSetInvoiсesType($invoiсesType);
    }

    /**
     * @return bool
     */
    public function isProcessCompleted()
    {
        return $this->processCompleted;
    }

    /**
     * @param bool $processCompleted
     */
    protected function setProcessCompleted($processCompleted)
    {
        if(!is_bool($processCompleted)){
            return false;
        }

        $this->processCompleted = $processCompleted;
    }

    /**
     * @return array
     */
    protected function getOrdersChainPercentData()
    {
        return $this->ordersChainPercentData;
    }

    /**
     * @param array $ordersChainPercentData
     */
    protected function setOrdersChainPercentData($ordersChainPercentData)
    {
        if( !$this->checkForArray($ordersChainPercentData) ){
            $this->addErrorsMessages('Не передана цепочка номеров заказов для "сессии".');
            return $this;
        }

        $this->ordersChainPercentData = $ordersChainPercentData;

        return $this;
    }

    /**
     * @return array
     */
    protected function getOrdersPaymentGroupData()
    {
        return $this->ordersPaymentGroupData;
    }

    /**
     * @param array $ordersPaymentGroupData
     */
    protected function setOrdersPaymentGroupData($ordersPaymentGroupData)
    {
        if( !$this->checkForArray($ordersPaymentGroupData) ){
            $this->addErrorsMessages('Не переданы данные для платежной группы заказов.');
            return $this;
        }

        $this->ordersPaymentGroupData = $ordersPaymentGroupData;

        return $this;
    }

}