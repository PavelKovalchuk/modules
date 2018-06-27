<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 01.03.2018
 * Time: 17:22
 */

abstract class ResponseHelperAbstract
{
    use LiqPayTrait;

    //Статус платежа.
    protected $status;

    protected $result;

    //Ожидаемый успешный статус
    protected $expectedStatus;

    protected $availableStatuses = [];

    abstract protected function initAvailableStatuses();

    abstract protected function initExpectedStatus();

    abstract  protected function parseResponse($response);

    public function __construct($response)
    {
        $check_result = $this->checkResponse($response);

        if(!$check_result){

            return false;
        }

        $this->parseResponse($response);

    }

    protected function checkResponse($response)
    {

        if($this->isAcceptedFormat($response) == false){
            return false;
        }

        if($this->isErrorResponse($response) == true){
            return false;
        }

        $this->initAvailableStatuses();

        if($this->checkForAvailableStatus($response) == false){
            return false;
        }

        $this->initExpectedStatus();

        if ($this->checkForExpectedStatus($response) == false){
            return false;
        }

        return true;
    }


    /**
     * @return mixed
     */
    protected function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    protected function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    protected function isAcceptedFormat($response)
    {
        if( !$response instanceof stdClass){
            $this->addErrorsMessages("Непредвиденный формат ответа API.");
            return false;
        }

        return true;
    }

    protected function isErrorResponse($response)
    {
        $status = $response->status;

        $result = $response->result;

        if(!$status){
            $this->addErrorsMessages("Не получен статус ответа API.");
            return true;
        }

        if(!$result){
            $this->addErrorsMessages("Не получен результат ответа API.");
            return true;
        }

        if($status == 'error'){
            $this->addErrorsMessages("Статус ответа API - ошибка. " . $response->err_description);

            if($response->err_description == 'Duplicate order_id'){
                $this->addErrorsMessages("Попытка отправить дублирующий номер для LiqPay! Ничего страшного! Попробуйте еще раз! ");
            }

            return true;
        }

        if($result !== 'ok'){
            $this->addErrorsMessages("Результат ответа API - ошибка. " . $response->err_description);
            return true;
        }

        return false;

    }

    protected function checkForAvailableStatus($response)
    {
        $param_to_check = ($response->status) ? $response->status : $response->result;

        if( !isset($this->getAvailableStatuses()[$param_to_check]) ){
            $this->addErrorsMessages("Непредвиденный Статус ответа API. " . $param_to_check);
            return false;
        }

        return true;
    }

    protected function checkForExpectedStatus($response)
    {
        $param_to_check = ($response->status) ? $response->status : $response->result;

        if($param_to_check !== $this->getExpectedStatus()){
            $this->addErrorsMessages("Статус ответа API не соответствует ожидаемому. " . $response->status);
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getAvailableStatuses()
    {
        return $this->availableStatuses;
    }

    /**
     * @return mixed
     */
    protected function getExpectedStatus()
    {
        return $this->expectedStatus;
    }

    /**
     * @return mixed
     */
    protected function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    protected function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

}