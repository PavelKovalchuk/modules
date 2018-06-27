<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 01.03.2018
 * Time: 17:44
 */

//Requests Helpers
require_once(LIQPAY_REQUESTS_MODELS_DIR . 'RequestHelperAbstract.php');
require_once(LIQPAY_REQUESTS_MODELS_DIR . 'RequestHelperInvoiceSend.php');
require_once(LIQPAY_REQUESTS_MODELS_DIR . 'RequestHelperInvoiceCancel.php');

//Response Helpers
require_once(LIQPAY_RESPONSES_MODELS_DIR . 'ResponseHelperAbstract.php');
require_once(LIQPAY_RESPONSES_MODELS_DIR . 'ResponseHelperInvoiceSend.php');
require_once(LIQPAY_RESPONSES_MODELS_DIR . 'ResponseHelperInvoiceCancel.php');

//Notifiers Helpers
require_once(LIQPAY_NOTIFIERS_MODELS_DIR . 'NotifierHelperInvoiceSend.php');


/**
 * Class AcquireAbstract
 * Общий класс для набора инструментов для приема платежей на сайте
 * https://www.liqpay.ua/documentation/api/aquiring/
 *
 */

abstract class AcquireAbstract
{
    use LiqPayTrait;

    //Режим тестирования
    protected $isTestMode = false;

    // Используемые в магазине языки заказов
    // и их соответствие API LiqPay
    protected $ordersLanguagesMap = [
        'russian' => 'ru',
        'ukrainian' => 'uk'
    ];

    protected $availableActions = [];

    public function __construct()
    {

        $this->initAvailableActions();
    }

    abstract protected function initAvailableActions();

    /**
     * @return array
     */
    protected function getAvailableActions()
    {
        return $this->availableActions;
    }


    protected function request($action, $request_params)
    {

        if($this->isError()){
            return false;
        }

        if(!isset( $this->getAvailableActions()[$action] )){
            $this->addErrorsMessages('Ошибка! Не определена возможность отправки запроса к API ' . $action);
            return false;
        }

        if(!$this->checkForArray($request_params)){
            $this->addErrorsMessages('Ошибка! Не переданы параметры для отправки запроса к API ' . $action);
            return false;
        }

        //LOG IN FILE
        $log_stage = 'SENDING REQUEST';
        $this->logInFile($log_stage, 'request', '__START_REQUEST__ ' . $this->getCurrentManagerInfo());
        $this->logInFile($log_stage, '$action', $action);
        $this->logInFile($log_stage, '$request_params', json_encode($request_params));

        //FOR test only START
        if($this->isTestMode()){

            //FAKE answer
            $fake_result_creator = new FakeResult();
            if($action == 'invoice_send'){
                $fake_result = $fake_result_creator->getSuccess($request_params['order_id']);
               // $fake_result =  $fake_result_creator->getError();

                //LOG IN FILE
                $this->logInFile($log_stage, '$result', json_encode( $fake_result, true));
                $this->logInFile($log_stage, 'request', '__END_REQUEST__');

                return $fake_result;
            }elseif($action == 'invoice_cancel'){
                $fake_result = $fake_result_creator->getCancelSuccess($request_params['order_id']);

                //LOG IN FILE
                $this->logInFile($log_stage, '$result', json_encode( $fake_result, true));
                $this->logInFile($log_stage, 'request', '__END_REQUEST__');

                return $fake_result;
            }
            return;
        }

        //FOR test only END

        try{
            $liqpay = new LiqPaySDK($this->getSettings()->getPublicKey(), $this->getSettings()->getPrivateKey());
        } catch (\InvalidArgumentException $e){
            //LOG IN FILE
            $this->logInFile($log_stage, 'catch_error: ', $e->getMessage());
            $this->addErrorsMessages($e->getMessage());
            return false;
        }

        $result = $liqpay->api("request", $request_params);

        //LOG IN FILE
        $this->logInFile($log_stage, 'request: ', '__END_REQUEST__');

        return $result;
    }
    /**
     * @return array
     */
    protected function getOrdersLanguagesMap()
    {
        return $this->ordersLanguagesMap;
    }

    protected function convertOrdersLanguage($language)
    {

        if(!$language){
            $this->addErrorsMessages('Не возможно конвертировать настройку Языка покупателя - не передан параметр.');
            return false;
        }

        if( !isset($this->ordersLanguagesMap[$language]) ){
            $this->addErrorsMessages('Не возможно конвертировать настройку Языка покупателя к требованиям API.');
            return false;
        }

        return $this->ordersLanguagesMap[$language];
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->isTestMode;
    }

    /**
     * @param bool $isTestMode
     */
    public function setIsTestMode($isTestMode)
    {
        $this->isTestMode = $isTestMode;
    }

}