<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 05.03.2018
 * Time: 17:56
 */

trait LiqPayInvoiceTrait
{
    protected $userEmail;

    protected $language;

    protected $mainOrderId;

    protected $amount;

    protected $invoiсesType;

    protected $availableInvoicesTypes = [];

    protected $ordersChain = [];

    //Активные заказы - все по которым производится финансовая операция
    protected $ordersChainActive = [];

    protected $ordersIdsStr;

    protected function initAvailableInvoicesTypes()
    {

        $this->availableInvoicesTypes = [
            $this->getInvoiсesTypePrepaid() => 'Предоплата',
            $this->getInvoiсesTypePayment() => 'Оплата'
        ];
    }

    protected function isInvoiсesTypePrepaid()
    {
        if($this->getInvoiсesType() !== $this->getInvoiсesTypePrepaid()){
            return false;
        }

        return true;
    }

    /**
     * @param mixed $invoiсesType
     */
    public function setInvoiсesType($invoiсesType)
    {
        $this->invoiсesType = $invoiсesType;
        return $this;
    }

    /**
     * @return array
     */
    protected function getAvailableInvoicesTypes()
    {
        return $this->availableInvoicesTypes;
    }

    /**
     * @return mixed
     */
    protected function getInvoiсesType()
    {
        return $this->invoiсesType;
    }

    protected function getInvoiсesTypePrepaid()
    {
        return 'prepaid';
    }

    protected function getInvoiсesTypePayment()
    {
        return 'payment';
    }

    //Имя предопределено API LiqPay
    protected function getInvoiсesActionNameSend()
    {
        return 'invoice_send';
    }

    //Имя предопределено API LiqPay
    protected function getInvoiсesActionNameCancel()
    {
        return 'invoice_cancel';
    }

    /**
     * @return mixed
     */
    protected function getUserEmail()
    {
        return $this->userEmail;
    }

    /**
     * @param mixed $userEmail
     */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return int
     */
    protected function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $invoiceAmount
     */
    public function setAmount($invoiceAmount)
    {
        $this->amount = $invoiceAmount;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getMainOrderId()
    {
        return $this->mainOrderId;

    }

    /**
     * @param mixed $mainOrderId
     */
    public function setMainOrderId($mainOrderId)
    {
        $this->mainOrderId = $mainOrderId;

        return $this;
    }

    /**
     * @return array
     */
    protected function getOrdersChain()
    {
        return $this->ordersChain;
    }

    /**
     * @param array $ordersChain
     */
    public function setOrdersChain($ordersChain)
    {
        $this->ordersChain = $ordersChain;

        return $this;
    }

    /**
     * @return array
     */
    protected function getOrdersChainActive()
    {
        return $this->ordersChainActive;
    }

    /**
     * @param array $ordersChainActive
     */
    public function setOrdersChainActive($ordersChainActive)
    {
        $this->ordersChainActive = $ordersChainActive;

        $this->initOrdersIdsStr();

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getOrdersIdsStr()
    {
        return $this->ordersIdsStr;
    }


    protected function initOrdersIdsStr()
    {
        $this->ordersIdsStr = implode(', ', $this->getOrdersChainActive());

        return $this;
    }

}