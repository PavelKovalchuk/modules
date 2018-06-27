<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 26.02.2018
 * Time: 17:26
 */

require_once(DIR_FS_INC . 'LiqPay/LiqPayConstants.php');
require_once(LIQPAY_ROOT_DIR . 'LiqPayIncludes.php');

/**
 * Class LiqPayController
 *
 */

class LiqPayController
{
    use LiqPayTrait;

    private static $instance = null;

    /**
     * @return Singleton
     */
    public static function getInstance()
    {
        if (null === self::$instance)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __clone() {}
    private function __construct() {}

    //Отправка инвойса LiqPay по требованию
    public function sendInvoicePaymentAction($order_id, $form_data, $orders_chain, $orders_chain_active, $orders_chain_percent_data, $group_data, $invoice_type)
    {
        $this->setOrderId($order_id);

        if($this->isError()){
            return false;
        }

        //LOG IN FILE
        $log_stage = 'LiqPayController';

        $invoice_manager = new LiqPayInvoiceModel($this->getOrderId());

        // Test only! DELETE in LIVE SERVER
        //$invoice_manager->setIsTestMode(true);
        // Test only! DELETE in LIVE SERVER

        $invoice_manager->sendInvoicePayment($form_data, $orders_chain, $orders_chain_active, $orders_chain_percent_data, $group_data, $invoice_type);

        if($invoice_manager->isProcessCompleted() == false){
            $this->setIsError(true);
            $this->setErrorsMessages( $invoice_manager->getErrorsMessages() );

            //LOG IN FILE
            $this->logInFile($log_stage, 'sendInvoicePaymentAction error', $this->outputMessage(false, false) );
            return false;
        }

        //Все успешные сообщения и ошибки, если были
        $final_message_to_log = $this->getFinalMessage($invoice_manager);
        //LOG IN FILE
        $this->logInFile($log_stage, 'sendInvoicePaymentAction result', $this->outputMessage($final_message_to_log, false) );

        //Показываем только краткое успешно сообщение и ошибки
        $this->setSuccessMessages( ['Запрос к LiqPay совершен успешно.']);
        $this->setErrorsMessages( $invoice_manager->getErrorsMessages() );
        $final_message_output = $this->getFinalMessage($this);
        $this->setSuccessMessages( $final_message_output );

        return true;

    }

    //работа с ответом от LiqPay после оплаты инвойса
    public function handleCallback($data, $signature)
    {
        $callback_manager = new LiqPayCallbackInvoiceModel();

        $callback_manager->manageCallbackData($data, $signature);

        if($callback_manager->isError()){
            $this->setIsError(true);
            $this->setErrorsMessages( $callback_manager->getErrorsMessages() );
            return false;
        }

        $final_message = $this->getFinalMessage($callback_manager);
        $this->setSuccessMessages( $final_message );
        return true;

    }

    /**
     * Копирует связь о последнем инвойсе в $main_order_id в $new_order_id
     * @param $main_order_id
     * @param $new_order_id
     */
    public function copyOrdersLiqPayConnection($main_order_id, $new_order_id)
    {
        $this->setOrderId($main_order_id);
        //LOG IN FILE
        $log_stage = 'copyOrdersLiqPayConnection';
        $this->setLogsFile("LiqPay_copy_order.txt");

        if($this->isError()){
            return false;
        }

        $invoice_manager = new LiqPayInvoiceModel($this->getOrderId());
        $invoice_manager->addOneNewOrderToLogbook($new_order_id);

        if($invoice_manager->isError()){
            $this->setIsError(true);
            $this->setErrorsMessages( $invoice_manager->getErrorsMessages() );
            //LOG IN FILE
            $this->logInFile($log_stage, 'copyOrdersLiqPayConnection error', $invoice_manager->outputMessage(false, false) );
            return false;
        }
        $this->logInFile($log_stage, 'copyOrdersLiqPayConnection test', $invoice_manager->outputMessage(false, false) );
        $this->setSuccessMessages( $invoice_manager->getSuccessMessages() );

        return true;

    }

    public function getMessage($result, $message)
    {
        if(!$message){
            $message = 'Пустое сообщение';
        }

        if($result == 'error') {
            $message = ' <br> ' . $message;
            $symbol = '&#9760;';
        }else{
            $symbol = '&#9787;';
        }

        $response_message = '<h2>' . $symbol . $this->getSystemName() . '</h2>'. $message;

        $response = array(
            'result' => $result,
            'message' => $response_message
        );

        echo json_encode($response);

    }

    //Отображение кнопки для отправки инвойса
    public function getButtonLiqPayInvoice($invoice_type, $text = false)
    {

        return $this->getViewInvoiceManager()->getButtonLiqPayInvoice($invoice_type, $this->getCommissionPercentToCalculate(), $text);

    }

    /**
     * Возвращает обьект по работе с отображениями LiqPay
     * @return object
     */
    protected function getViewInvoiceManager()
    {
        return LiqPayViewInvoice::getInstance();
    }

}