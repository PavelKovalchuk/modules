<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 13.06.2018
 * Time: 11:25
 */

require_once(DIR_WS_CLASSES . 'invoice_order/InvoiceOrderConstants.php');
require_once(INVOICE_ROOT_DIR . 'InvoiceOrderTrait.php');
require_once(INVOICE_MODELS_DIR . 'InvoiceOrderModel.php');
require_once(DIR_WS_MODULES . 'orders_func.php');

class InvoiceOrderController
{
    use InvoiceOrderTrait;

    public function checkOrderForRulesAction($order_id)
    {

        $invoice_manager = new InvoiceOrderModel();

        $check_result = $invoice_manager->checkOrderForRules($order_id);

        if($check_result === true){

            $this->getMessage('success', 'start');

            return true;
        }

        if( is_string($check_result) ){
            $this->getMessage('error', $check_result);
            return false;
        }

        if(!$check_result){
            $this->getMessage('error', 'Что-то пошлоне так при проверке заказа!');
            return false;
        }


    }

    public function generateInvoiceAction($order_id, $delivery_type, $table_product_map_name)
    {
        $order_id = $this->clearOrderId($order_id);
        $table_product_map_name = $this->clearInputStr($table_product_map_name);
        $delivery_type = $this->clearInputStr($delivery_type);

        $this->checkInputDataForGeneratingInvoice($order_id, $delivery_type, $table_product_map_name);

        if($this->isError()){
            return false;
        }

        $invoice_manager = new InvoiceOrderModel();
        $invoice_manager->getFilePDF($order_id, $delivery_type, $table_product_map_name);
        if($invoice_manager->isError()){

            $this->setErrorsMessages( $invoice_manager->getErrorsMessages() );
            $this->setIsError(true);
            return false;
        }

    }


    public function getInvoiceButtonHtml($order_id, $products, $button_name = '', $is_need_download = true)
    {

        $order_id = $this->clearOrderId($order_id);

        if(!$order_id){
            $this->addErrorsMessages('Ошибка в получении номера заказа!');
            return false;
        }

        if(!$this->checkForArray($products)){
            $this->addErrorsMessages('Ошибка в получении данных о товарах.');
            return false;
        }

        if($this->isError()){
            echo  $this->getSystemName(). ': ' . $this->createErrorsMessage();
            return false;
        }

        $invoice_manager = new InvoiceOrderModel();
        $invoice_manager->getInvoiceButtonHtml($order_id, $products, $button_name, true);
        if($invoice_manager->isError()){

            echo  $this->getSystemName(). ': ' . $invoice_manager->createErrorsMessage();
            return false;
        }

        return true;
    }

    public function getMessage($result, $message)
    {
        if(!$message){
            $message = 'Пустое сообщение';
        }

        if($result == 'error') {
            $message = ' <br>  &#9888; ' . $message;
        }

        $response_message = '<h2>' . $this->getSystemName() . '</h2>'. $message;

        $response = array(
            'result' => $result,
            'message' => $response_message
        );

        echo json_encode($response);

    }

}