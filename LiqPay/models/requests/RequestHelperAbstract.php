<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 27.02.2018
 * Time: 10:17
 */

abstract class RequestHelperAbstract
{
    use LiqPayTrait;

    protected $path = 'request';

    //Версия API
    protected $version = '3';

    //Валюта платежа.
    protected $currency = 'UAH';

    protected $availableCurrencies = [
        "USD", "EUR", "RUB", "UAH"
    ];

    protected $action;

    //order_id OF GreenMarket for LiqPay
    protected $generated_order_id;

    //Язык клиента
    protected $language;

    protected $availableLanguages = [
        "ru", "uk", "en"
    ];

    //Сумма платежа.Например: 5, 7.34
    protected $amount;

    /**
     * Генерируем Значения для ассоциативного массива для передачи в качестве аргумента
     * методу api
     * обьекта LiqPaySDK
     * @param array $requestData
     */
    abstract public function getRequestData();


    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action)
    {

        if(!$action){
            $this->action = false;
            $this->addErrorsMessages('Не определен action для API.');
            return $this;
        }
        $this->action = $action;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {

        if(!$amount){
            $this->amount = false;
            $this->addErrorsMessages('Не определен платеж заказа.');
            return $this;
        }
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return int
     */
    public function getGeneratedOrderId()
    {
        return $this->generated_order_id;
    }

    /**
     * @param int $order_id
     */
    public function setGeneratedOrderId($request_order_id)
    {
        if(!$request_order_id){
            $this->generated_order_id = false;
            $this->addErrorsMessages('Не определен номер заказа.');
            return $this;
        }

        $this->generated_order_id = $request_order_id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        if(!in_array($language, $this->getAvailableLanguages())){
            $this->language = false;
            $this->addErrorsMessages('Не определен Язык клиента.');
            return $this;
        }

        $this->language = $language;
        return $this;

    }

    /**
     * @return array
     */
    protected function getAvailableLanguages()
    {
        return $this->availableLanguages;
    }


    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $path
     */
    protected function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param string $version
     */
    protected function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param string $currency
     */
    protected function setCurrency($currency)
    {
        if(!in_array($currency, $this->getAvailableCurrencies())){

            $this->currency = false;
            $this->addErrorsMessages('Не определена Валюта платежа.');
            return $this;

        }

        $this->currency = $currency;
        return $this;

    }

    /**
     * @return array
     */
    protected function getAvailableCurrencies()
    {
        return $this->availableCurrencies;
    }

}