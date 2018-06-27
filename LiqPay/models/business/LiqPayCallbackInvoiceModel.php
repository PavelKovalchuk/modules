<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 15.03.2018
 * Time: 16:00
 */

require_once(LIQPAY_RESPONSES_MODELS_DIR . 'ResponseHelperCallback.php');

class LiqPayCallbackInvoiceModel
{
    use LiqPayTrait;

    use LiqPayBookingOrderIdTrait;

    protected $liqpayHelper;

    protected $moduleName = 'LiqPay Callback: ';

    protected $data = [];

    public function __construct()
    {
        $this->setLiqpayHelper( new LiqPaySDK($this->getSettings()->getPublicKey(), $this->getSettings()->getPrivateKey()) );

        //LOG CALLBACK
        $this->setLogsFile("LiqPay_callback_results.txt");
    }

    public function manageCallbackData($data_encoded, $signature)
    {
        //LOG CALLBACK
        $log_stage = 'MANAGE CALLBACK';
        $this->logInFile($log_stage, 'callback', '___START_CALLBACK___');

        if(!$data_encoded || !$signature){
            $this->addErrorsMessages( $this->getModuleName() . 'Проверка подлинности запроса с сервера LiqPay не пройдена, не переданы параметры запроса.');
            return false;
        }

        // 1. Проверяем ответ от API LiqPay //____________STAGE__________________//
        $check_result = $this->checkSignature($data_encoded, $signature);
        if(!$check_result){
            return false;
        }

        // 2. Декодируем данные //____________STAGE__________________//
        $data_decoded = $this->getLiqpayHelper()->decode_params($data_encoded);

        //LOG CALLBACK
        $this->logInFile($log_stage, '$data_encoded', $data_encoded);
        $this->logInFile($log_stage, '$signature', $signature);
        if(is_array($data_decoded)){
            $this->logInFile($log_stage, '$data_decoded', json_encode($data_decoded));
        }

        if(! $this->checkForArray($data_decoded)){
            $this->addErrorsMessages( $this->getModuleName() . 'Проверка подлинности запроса с сервера LiqPay не пройдена, не раскодирован параметр: data.');
            return false;
        }

        // 3. Разбираем ответ от API LiqPay //____________STAGE__________________//

        $data_object = $this->convertObjectFromArray($data_decoded);
        //Обьект для работы с ответом API LiqPay
        $response_helper = new ResponseHelperCallback($data_object);

        if($response_helper->isError()){
            //Если с инвойсом не получилось, завершаем запрос.
            $this->setIsError(true);
            $this->setErrorsMessages( $response_helper->getErrorsMessages() );
            return false;
        }

        // 4. Сохраняем в БД ответ от API LiqPay //____________STAGE__________________//
        $data_to_save = $response_helper->getDataForDB();
        $result_db = $this->saveCallbackResponseEntry($data_to_save);

        // 5. Получаем данные из БД по уникальному номеру заказа //____________STAGE__________________//
        $invoice_data = $this->getInvoiceDataByGeneratedOrderId( $response_helper->getGeneratedOrderId() );
        if(!$invoice_data){
            return false;
        }

        // Все заказы цепочки
        $orders_chain = $this->getOrdersChainByGeneratedOrderId( $response_helper->getGeneratedOrderId(), $invoice_data['invoice_type']);
        if(!$orders_chain){
            return false;
        }

        //___________________NOT CRITICAL STAGES________________________________//

        //Добавляем АЛАРМ в случае неуспешного сохранения callback в БД
        if(!$result_db){
            $this->setOrdersChainAlarmTrouble($orders_chain, $this->getModuleName() . 'Ответ об оплате инвойса не сохранен в БД. Сообщите администратору! ');
        }

        // 6. Обновляем статус инвойса в БД //____________STAGE__________________//
        $change_status_result = $this->changeInvoiceStatus('finished', $response_helper->getGeneratedOrderId());
        if(!$change_status_result){
            $change_status_message = 'Статус инвойса не был изменен после получения callback.';
            $this->addErrorsMessages( $this->getModuleName() . $change_status_message);
            $this->setOrdersChainAlarmTrouble($orders_chain, $this->getModuleName() . $change_status_message . 'Сообщите администратору! ');
        }

        // 7. Логгируем результаты проведенных операций  в БД  //____________STAGE__________________//
        if( $this->isError() ){
            return false;
        }

        $this->addSuccessMessages('Callback LiqPay сохранен успешно.');

        $this->logResults($orders_chain);

        $this->logManagerComment('Пришел результат о LiqPay инвойсе №' . $response_helper->getGeneratedOrderId() . ', ', $orders_chain);

        //LOG CALLBACK
        $this->logInFile($log_stage, 'callback', '___END_CALLBACK__');
        return true;

    }

    protected function saveCallbackResponseEntry($data_to_save)
    {
        if(!$this->checkForArray($data_to_save)){
            $this->addErrorsMessages( $this->getModuleName() .'Ответ от LiqPay не был отформатирован и не был сохранен в БД.');
            return false;
        }

        //Сохраняем ответ от LiqPay в БД
        $entry_id = $this->getDbManager()->saveCallbackEntryDb($data_to_save);

        if(! is_int($entry_id) || !$entry_id > 0 ){
            $this->addErrorsMessages( $this->getModuleName() .'Ответ от LiqPay не был сохранен в БД.');
            return false;
        }

        return true;

    }

    protected function checkSignature($data_raw, $signature)
    {

        if(!$signature || !$data_raw){
            $this->addErrorsMessages( $this->getModuleName() . 'Проверка подлинности запроса с сервера LiqPay не пройдена, checkSignature не получил параметров.');
            return false;
        }
        $signature_server = $this->getLiqpayHelper()->str_to_sign($this->getSettings()->getPrivateKey() . $data_raw . $this->getSettings()->getPrivateKey());

        if($signature !== $signature_server){
            $this->addErrorsMessages( $this->getModuleName() . 'Проверка подлинности запроса с сервера LiqPay не пройдена, не подлинный ответ от сервера LiqPay: signature.');
            return false;
        }

        return true;
    }

    protected function convertObjectFromArray($data_array)
    {
        if( ! $this->checkForArray($data_array)){
            $this->addErrorsMessages("Непредвиденный формат массива для конвертации в обьект.");
            return false;
        }

        $object = new stdClass();

        foreach ($data_array as $key => $value)
        {
            $object->$key = $value;
        }

        return $object;

    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    protected function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    protected function getLiqpayHelper()
    {
        return $this->liqpayHelper;
    }

    /**
     * @param mixed $liqpayHelper
     */
    protected function setLiqpayHelper(LiqPaySDK $liqpayHelper)
    {
        $this->liqpayHelper = $liqpayHelper;
    }
}