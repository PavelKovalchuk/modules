<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 01.03.2018
 * Time: 17:24
 */

/**
 * Class ResponseHelperInvoiceSend
 * DB table - liqpay_invoices, properties - are fields in the table.
 */
class ResponseHelperInvoiceSend extends ResponseHelperAbstract
{
    protected $action;

    protected $amount;

    protected $currency;

    protected $description;

    //Ссылка на инвойс
    protected $href;

    //Id платежа в системе LiqPay
    protected $liq_pay_id;

    //Order_id платежа order_id OF GreenMarket for LiqPay
    protected $generated_order_id;

    //Вид канала получения
    protected $receiver_type;

    //Значение полученное в параметре receiver_type
    protected $receiver_value;

    //Token платежа
    protected $token;

    protected $invoice_type;

    protected $invoice_shop_status;

    protected $expired_date;

    public function __construct($response)
    {

        parent::__construct($response);

        if($this->isError()){
            return false;
        }

        $this->setSuccessMessages(
            $this->getAvailableStatuses()[ $this->getStatus()]. ". "
            . "Создан инвойс " . $this->getInvoiceType() . "( LiqPay: № " . $this->getLiqPayId() . " )" . "( GreenMarket: № " . $this->getGeneratedOrderId() . " ). "
            . "На сумму: " .  $this->getAmount() . " " . $this->getCurrency() . ". "
            . "Для заказов: " . $this->getDescription(). " . "
            . "Канал получения: " . $this->getReceiverType() . " - " . $this->getReceiverValue(). " . "
        );

    }

    protected function initAvailableStatuses()
    {
        $this->availableStatuses = [
            'error' => 'Неуспешный платеж. Некорректно заполнены данные',
            'failure' => 'Неуспешный платеж',
            'sandbox' => 'Тестовый платеж',
            'success' => 'Успешный платеж',
            'invoice_wait' => 'Инвойс создан успешно, ожидается оплата',
        ];

    }

    //Инвойс создан успешно, ожидается оплата
    protected function initExpectedStatus()
    {
        $this->expectedStatus = 'invoice_wait';
    }

    protected function parseResponse($response)
    {
        $this
            ->setResult($response->result)
            ->setLiqPayId($response->id)
            ->setStatus($response->status)
            ->setAmount($response->amount)
            ->setCurrency($response->currency)
            ->setDescription($response->description)
            ->setGeneratedOrderId($response->order_id)
            ->setHref($response->href)
            ->setReceiverType($response->receiver_type)
            ->setReceiverValue($response->receiver_value)
            ->setAction($response->action)
            ->setToken($response->token)
        ;

    }

    /**
     * Table fields - values
     * @return array|bool
     */
    public function getDataForDB()
    {
        if($this->isError()){
            return false;
        }

        $response = [
            'invoice_id' => $this->getLiqPayId(),
            'generated_order_id' => $this->getGeneratedOrderId(),
            'operation_date' => (new \DateTime('NOW'))->format($this->getDatetimeFormat()),
            'invoice_type' => $this->getInvoiceType(),
            'invoice_shop_status' => $this->getInvoiceShopStatus(),
            'expired_date' => $this->getExpiredDate(),
            'result' => $this->getResult(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'description' => $this->getDescription(),
            'href' => $this->getHref(),
            'receiver_type' => $this->getReceiverType(),
            'receiver_value' => $this->getReceiverValue(),
            'action' => $this->getAction(),
            'token' => $this->getToken(),
        ];


        return $response ;

    }

    /**
     * @return mixed
     */
    protected function getLiqPayId()
    {
        return $this->liq_pay_id;
    }

    /**
     * @param mixed $liq_pay_id
     */
    protected function setLiqPayId($liq_pay_id)
    {
        $this->liq_pay_id = $liq_pay_id;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    protected function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    protected function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    protected function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    protected function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param mixed $href
     */
    protected function setHref($href)
    {
        $this->href = $href;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGeneratedOrderId()
    {
        return $this->generated_order_id;
    }

    /**
     * @param mixed $id
     */
    protected function setGeneratedOrderId($id)
    {
        $this->generated_order_id = $id;

        return $this;
    }


    /**
     * @return mixed
     */
    protected function getReceiverType()
    {
        return $this->receiver_type;
    }

    /**
     * @param mixed $receiver_type
     */
    protected function setReceiverType($receiver_type)
    {
        $this->receiver_type = $receiver_type;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getReceiverValue()
    {
        return $this->receiver_value;
    }

    /**
     * @param mixed $receiver_value
     */
    protected function setReceiverValue($receiver_value)
    {
        $this->receiver_value = $receiver_value;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    protected function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getSystemName()
    {
        return $this->systemName;
    }

    /**
     * @param string $systemName
     */
    public function setSystemName($systemName)
    {
        $this->systemName = $systemName;
    }

    /**
     * @return mixed
     */
    protected function getInvoiceType()
    {
        return $this->invoice_type;
    }

    /**
     * @param mixed $invoice_type
     */
    public function setInvoiceType($invoice_type)
    {
        $this->invoice_type = $invoice_type;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getInvoiceShopStatus()
    {
        return $this->invoice_shop_status;
    }

    /**
     * @param mixed $invoice_shop_status
     */
    public function setInvoiceShopStatus($invoice_shop_status)
    {
        $this->invoice_shop_status = $invoice_shop_status;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getExpiredDate()
    {
        return $this->expired_date;
    }

    /**
     * @param mixed $expired_date
     */
    public function setExpiredDate($expired_date)
    {
        $this->expired_date = $expired_date;

        return $this;
    }

}