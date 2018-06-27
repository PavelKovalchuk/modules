<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 12.03.2018
 * Time: 15:47
 */

class ResponseHelperInvoiceCancel extends ResponseHelperAbstract
{
    //Id платежа в системе LiqPay
    protected $invoice_id;

    public function __construct($response)
    {
        parent::__construct($response);

        if($this->isError()){
            return false;
        }

        $this->setSuccessMessages(
            $this->getAvailableStatuses()[ $this->getStatus()]. ". "
            . "Отменен инвойс ( LiqPay: № " . $this->getInvoiceId() . " )"

        );

    }

    protected function isErrorResponse($response)
    {

        $result = $response->result;

        if(!$result){
            $this->addErrorsMessages("Не получен результат ответа API.");
            return true;
        }

        if($result !== 'ok'){
            $this->addErrorsMessages("Результат ответа API - ошибка. " );
            return true;
        }

        return false;

    }

    protected function initAvailableStatuses()
    {
        $this->availableStatuses = [
            'error' => 'Успешный запрос.',
            'ok' => 'Неуспешный запрос.',
        ];
    }

    protected function initExpectedStatus()
    {
        $this->expectedStatus = 'ok';
    }

    protected function parseResponse($response)
    {
        $this
            ->setResult($response->result)
            ->setInvoiceId($response->invoice_id)
        ;
    }

    /**
     * @return mixed
     */
    public function getInvoiceId()
    {
        return $this->invoice_id;
    }

    /**
     * @param mixed $invoice_id
     */
    protected function setInvoiceId($invoice_id)
    {
        $this->invoice_id = $invoice_id;
    }

}