<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 16.02.2018
 * Time: 13:19
 */

require_once(DIR_WS_CLASSES . 'dropshipping/DropshippingConstants.php');
require_once(DROPSHIPPING_ROOT_DIR . 'DropshippingTrait.php');
require_once(DROPSHIPPING_MODELS_DIR . 'DropshippingTtnModel.php');

class DropshippingController
{
    use DropshippingTrait;

    protected $minCashOnDelivery = 0;

    public function __construct()
    {
        $this->setDbManager( new DropshippingDBRepository() );

    }

    public function getButtonView($order_id, $products, $button_name = 'Данные для ТТН ')
    {
        $ttn_manager = new DropshippingTtnModel();

        $ttn_manager->getButton($order_id, $products, $button_name);

        if($ttn_manager->isError()){

            echo  $this->getSystemName(). ': ' . $ttn_manager->createErrorsMessage();
            return false;
        }

        return true;
    }

    public function checkOrderForTtnRulesAction($order_id)
    {
        $data_to_check =  $this->getDbManager()->getRulesParamsFromDb($order_id);

        $ttn_manager = new DropshippingTtnModel();

        $check_result = $ttn_manager->checkOrderForRules($data_to_check);

        if($check_result === true){

            $this->getMessage('success', '');

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

    public function generateTtnAction($order_id)
    {
        $this->setMinCashOnDelivery(10);

        $this->setOrderId($order_id);

        if(!$this->getOrderId()){

            $this->addErrorsMessages('Ошибка в получении номера заказа!');
            return false;
        }

        $common_orders_data = $this->getDbManager()->getTtnParamsFromDb($order_id, $this->getMinCashOnDelivery());

        $products_orders_data = $this->getDbManager()->getProductsDataFromDb($order_id);

        $ttn_manager = new DropshippingTtnModel();

        if($ttn_manager->isError()){

            $this->setErrorsMessages( $ttn_manager->getErrorsMessages() );
            return false;
        }

        $ttn_manager->getFileExcel($common_orders_data, $products_orders_data);

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


    /**
     * @return int
     */
    protected function getMinCashOnDelivery()
    {
        return $this->minCashOnDelivery;
    }

    /**
     * @param int $minCashOnDelivery
     */
    protected function setMinCashOnDelivery($minCashOnDelivery)
    {
        $this->minCashOnDelivery = $minCashOnDelivery;
    }

}