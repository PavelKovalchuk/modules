<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 11.01.2018
 * Time: 13:53
 */
require_once(DIR_WS_CLASSES.'separator_order/SeparatorOrderTrait.php');

require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderDeleteModel.php');
require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderEmailModel.php');
require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderSmsModel.php');
require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderLogModel.php');
require_once(DIR_WS_CLASSES.'separator_order/SeparatorOrderNotifierSettings.php');

require_once (DIR_WS_MODULES.'orders_func.php');
require_once(DIR_FS_CATALOG. 'inc/update_qty.inc.php');
require_once (DIR_FS_INC.'vam_db_affected_rows.inc.php');

class SeparatorOrderController
{
    use SeparatorOrderTrait;

    protected $neededFunctions = array(

        'delete_orders_products' => array(
            'error_message' =>'Ошибка при удалении товаров. Нарушена конфигурация системы #1. Удаление товаров не выполнилось.',
        ),
        'update_qty' => array(
            'error_message' =>'Ошибка при удалении товаров. Нарушена конфигурация системы #2. Удаление товаров не выполнилось.',
        ),
        'recalculate_orders_sum' => array(
            'error_message' =>'Ошибка при удалении товаров. Нарушена конфигурация системы #3. Удаление товаров не выполнилось.',
        ),

        'upd_last_comment_to_order_log' => array(
            'error_message' =>'Ошибка при удалении товаров. Нарушена конфигурация системы #4. Удаление товаров не выполнилось.',
        ),

        'get_cur_order_lang' => array(
            'error_message' =>'Ошибка при отправке письма. Нарушена конфигурация системы #5. Отправка письма не выполнилось.',
        ),

    );

    //Имя текущего этапа
    protected $stageName = 'Инициализация';

    //Хранилище для обьекта класса логгирования
    protected $logger;

    public function __construct()
    {

        $this->setLogger( new SeparatorOrderLogModel() );

    }

    public function deleteAction($order_id, $data_to_delete)
    {
        if(!is_int($order_id)){
            return false;
        }

        $this->setStageName('Удаление');

        $this->setOrderId($order_id);

        $model_delete = new SeparatorOrderDeleteModel( $this->getOrderId() );

        if(!$this->isFunctionsExist() || $model_delete->isError()){

            $this->getMessage('error', $model_delete->createErrorsMessage());
            exit;

        }

        //Проверка, что есть данные для удаления и что они в форме массиива
        if($data_to_delete !== false && $this->checkForArray($data_to_delete)){

            $model_delete->setIsDeleteNeeded(true);

        }

        if($model_delete->isDeleteNeeded() === true){

            $model_delete->deleteProductsProcess($data_to_delete);
        }

        if( $model_delete->isError() ){

            $this->getMessage('error', $model_delete->createErrorsMessage() );
            exit;
        }

        if( $model_delete->isDeleteCompleted() ){

            $this->getMessage('success',  'Товары удалены. ' . $model_delete->getSuccessMessage());
        }

        if(!$model_delete->isDeleteNeeded()){

            $this->getMessage('success', 'Товары не удалялись. ');

        }

        return true;

    }

    public function logAction($order_id, $log_data)
    {
        //$this->setStageName('Логгирование');

        if(!is_int($order_id) || empty($log_data) ){
            $this->getMessage('error', 'Ошибка при логгировании результатов. Нет данных для записи. ');
            return false;
        }

        $this->setOrderId($order_id);

        if(!$this->isFunctionsExist() || $this->isError()){

            $this->getMessage('error', $this->createErrorsMessage());
            exit;

        }

        $result = $this->getLogger()->logResult($this->getOrderId(), $log_data);

        /*if($this->getLogger()->isLoggingCompleted()){

            $this->getMessage('success', ' Логгирование результатов произведено.');

        }*/

        return $result;

    }

    public function notifyAction($order_id, $separated_orders)
    {
        $this->setStageName('Отправка уведомления');

        if(!is_int($order_id) || ! $this->checkForArray($separated_orders) ){
            $this->getMessage('error', 'Ошибка при отправке уведомления. Нет данных о разделенных заказах. ');
            return false;
        }

        $this->setOrderId($order_id);

        $this->setDbManager( new SeparatorOrderRepository() );

        $customer_data = $this->getDbManager()->getOrdersDataToMailDB($this->getOrderId());

        if(! $this->checkForArray($customer_data) ){

            $this->getMessage('error', 'Ошибка при отправке уведомления. Нет данных о покупателе. ');
            return false;

        }

        $notifier_settings = new SeparatorOrderNotifierSettings();

        if($notifier_settings->isError()){
            $this->getMessage('error', $notifier_settings->createErrorsMessage());
            exit;
        }

        $notifier_class = $notifier_settings->getNotifierClassName($customer_data['c_email'], $customer_data['ph_number']);

        if(!$notifier_class){
            $this->getMessage('error', 'Ошибка при отправке уведомления. Телефоны и email принадлежат GreenMarket или нет данных. ');
            exit;
        }

        $notifier_model = new $notifier_class( $this->getOrderId(), $separated_orders, $customer_data );

        if(!$this->isFunctionsExist() || $notifier_model->isError()){

            $this->getMessage('error', $notifier_model->createErrorsMessage());
            exit;

        }

        $notifier_model->send();

        if($notifier_model->isNotifyCompleted()){

            $this->getMessage('success', 'Отправка ' . $notifier_settings->getNotifierType() . ' о результатах завершена.');

        }

        if(!$notifier_model->isNotifyCompleted()){

            $this->getMessage('error', $notifier_model->createErrorsMessage());

        }

        return true;

    }

    protected function isFunctionsExist()
    {
        $result = true;

        foreach ( $this->getNeededFunctions() as $function => $error_data ){

            if(! function_exists( $function )){

                $this->addErrorsMessages($error_data['error_message']);
                $result = false;

            }

        }

        return $result;
    }

    public function getMessage($result, $message, $need_to_log = false)
    {
        $message = $this->getStageName(). ' - '. $message;

        $log_result = $this->getLogger()->logResult( $this->getOrderId(), $message);

        //Если это сообщение об ошибке или есть нужда логгировать сообщение - логгируем его
        if($result == 'error' || $need_to_log === true){

            if(!$log_result){
                $message .= ' <br>  &#9888; Результаты манипуляций НЕ записаны в Комментарий.';
            }else{
                $message .= ' <br>  &#9924; Результаты манипуляций записаны в Комментарий.';
            }

            //$message = '<h2>' . $this->getSystemName() . '</h2>'. $message;
        }

        $response = array(
            'result' => $result,
            'message' => $message
        );

        echo json_encode($response);

    }

    /**
     * @return array
     */
    protected function getNeededFunctions()
    {
        return $this->neededFunctions;
    }

    /**
     * @return string
     */
    protected function getStageName()
    {
        return $this->stageName;
    }

    /**
     * @param string $stageName
     */
    protected function setStageName($stageName)
    {
        $this->stageName = $stageName;
    }

    /**
     * @return mixed
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param mixed $logger
     */
    protected function setLogger( SeparatorOrderLogModel $logger )
    {
        $this->logger = $logger;
    }




}