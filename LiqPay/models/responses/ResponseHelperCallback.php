<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 15.03.2018
 * Time: 15:39
 */


/**
 * Class ResponseHelperCallback
 * https://www.liqpay.ua/documentation/api/callback
 */
class ResponseHelperCallback extends ResponseHelperAbstract
{
    //Тип операции.
    protected $action;

    //Сумма платежа
    protected $amount;

    //Дата создания платежа
    // Converted
    protected $create_date;

    //Дата завершения/изменения платежа
    // Converted
    protected $end_date;

    //Валюта платежа
    protected $currency;

    //Order_id платежа в системе LiqPay
    protected $liqpay_order_id;

    //Order_id платежа GreenMarket - LiqPay order_id
    protected $generated_order_id;

    //Id платежа в системе LiqPay
    protected $payment_id;

    //Способ оплаты.
    protected $paytype;

    //Тип платежа
    protected $type;

    //Комментарий к платежу
    protected $description;

    //Комиссия с отправителя в валюте платежа
    protected $sender_commission;

    //Комиссия с получателя в валюте платежа
    protected $receiver_commission;

    //Комиссия агента в валюте платежа
    protected $agent_commission;

    //Телефон отправителя
    protected $sender_phone;

    //Банк отправителя
    protected $sender_card_bank;

    //Статус платежа.
    protected $status;

    public function __construct($response)
    {
        parent::__construct($response);

        if($this->isError()){
            return false;
        }

    }

    protected function initAvailableStatuses()
    {
        $this->availableStatuses = [
            'error' => 'Неуспешный платеж. Некорректно заполнены данные',
            'failure' => 'Неуспешный платеж',
            'reversed' => 'Платеж возвращен',
            'sandbox' => 'Тестовый платеж',
            'subscribed' => 'Подписка успешно оформлена',
            'success' => 'Успешный платеж',
            'unsubscribed' => 'Подписка успешно деактивирована',
        ];
    }

    protected function initExpectedStatus()
    {
        $this->expectedStatus = 'success';
    }

    protected function isErrorResponse($response)
    {

        $result = $response->status;

        if(!$result){
            $this->addErrorsMessages("Не получен результат ответа API.");
            return true;
        }

        if($result !== 'success'){
            $this->addErrorsMessages("Результат ответа API - ошибка. " );
            return true;
        }

        return false;

    }

    protected function parseResponse($response)
    {
        $this
            ->setGeneratedOrderId($response->order_id)
            ->setAction($response->action)
            ->setAmount($response->amount)
            ->setCreateDate( (new \DateTime())->setTimestamp($response->create_date / 1000)->format($this->getDatetimeFormat()) )
            ->setEndDate((new \DateTime())->setTimestamp($response->end_date / 1000)->format($this->getDatetimeFormat()))
            ->setCurrency($response->currency)
            ->setLiqpayOrderId($response->liqpay_order_id)
            ->setPaymentId($response->payment_id)
            ->setPaytype($response->paytype)
            ->setSenderPhone($response->sender_phone)
            ->setStatus($response->status)
            ->setType($response->type)
            ->setDescription($response->description)
            ->setSenderCommission($response->sender_commission)
            ->setReceiverCommission($response->receiver_commission)
            ->setAgentCommission($response->agent_commission)
            ->setSenderCardBank($response->sender_card_bank)
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
            'generated_order_id' => $this->getGeneratedOrderId(),
            'operation_date' => (new \DateTime('NOW'))->format($this->getDatetimeFormat()),
            'status' => $this->getStatus(),
            'action' => $this->getAction(),
            'amount' => $this->getAmount(),
            'create_date' => $this->getCreateDate(),
            'end_date' => $this->getEndDate(),
            'currency' => $this->getCurrency(),
            'liqpay_order_id' => $this->getLiqpayOrderId(),
            'payment_id' => $this->getPaymentId(),
            'paytype' => $this->getPaytype(),
            'sender_phone' => $this->getSenderPhone(),
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'sender_commission' => $this->getSenderCommission(),
            'receiver_commission' => $this->getReceiverCommission(),
            'agent_commission' => $this->getAgentCommission(),
            'sender_card_bank' => $this->getSenderCardBank(),
        ];

        return $response ;

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
    protected function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param mixed $create_date
     */
    protected function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param mixed $end_date
     */
    protected function setEndDate($end_date)
    {
        $this->end_date = $end_date;

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
    protected function getLiqpayOrderId()
    {
        return $this->liqpay_order_id;
    }

    /**
     * @param mixed $liqpay_order_id
     */
    protected function setLiqpayOrderId($liqpay_order_id)
    {
        $this->liqpay_order_id = $liqpay_order_id;

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
     * @param mixed $generated_order_id
     */
    protected function setGeneratedOrderId($generated_order_id)
    {
        $this->generated_order_id = $generated_order_id;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getPaymentId()
    {
        return $this->payment_id;
    }

    /**
     * @param mixed $payment_id
     */
    protected function setPaymentId($payment_id)
    {
        $this->payment_id = $payment_id;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getPaytype()
    {
        return $this->paytype;
    }

    /**
     * @param mixed $paytype
     */
    protected function setPaytype($paytype)
    {
        $this->paytype = $paytype;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    protected function setType($type)
    {
        $this->type = $type;

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
    protected function getSenderCommission()
    {
        return $this->sender_commission;
    }

    /**
     * @param mixed $sender_commission
     */
    protected function setSenderCommission($sender_commission)
    {
        $this->sender_commission = $sender_commission;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getReceiverCommission()
    {
        return $this->receiver_commission;
    }

    /**
     * @param mixed $receiver_commission
     */
    protected function setReceiverCommission($receiver_commission)
    {
        $this->receiver_commission = $receiver_commission;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getAgentCommission()
    {
        return $this->agent_commission;
    }

    /**
     * @param mixed $agent_commission
     */
    protected function setAgentCommission($agent_commission)
    {
        $this->agent_commission = $agent_commission;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getSenderPhone()
    {
        return $this->sender_phone;
    }

    /**
     * @param mixed $sender_phone
     */
    protected function setSenderPhone($sender_phone)
    {
        $this->sender_phone = $sender_phone;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getSenderCardBank()
    {
        return $this->sender_card_bank;
    }

    /**
     * @param mixed $sender_card_bank
     */
    protected function setSenderCardBank($sender_card_bank)
    {
        $this->sender_card_bank = $sender_card_bank;

        return $this;
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

}