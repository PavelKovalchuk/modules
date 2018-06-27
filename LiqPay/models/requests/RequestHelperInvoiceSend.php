<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 27.02.2018
 * Time: 10:18
 */


class RequestHelperInvoiceSend extends RequestHelperAbstract
{
    protected $email;

    //URL API в Вашем магазине для уведомлений об изменении статуса платежа
    protected $server_url;

    //скрипт для обработки уведомлений об изменении статуса платежа
    protected $server_url_action = 'liqpay_callback.php';

    //Тип операции.
    protected $action_payment;

    //Тип операции. Возможные значения
    protected $availableActionPayments = [
        'pay' => 'платеж',
        'hold' => 'блокировка средств на счету отправителя',
        'subscribe' => 'регулярный платеж',
        'paydonate' => 'пожертвование'
    ];

    //Назначение платежа.
    protected $description;

    //Время до которого клиент может оплатить счет по UTC. Передается в формате 2016-04-24 00:00:00
    protected $expiredDate;

    protected $maxDaysToPay = 3;

    protected $emailByDefault = 'info@greenmarket.com.ua';

    protected $goods = [];

    public function __construct()
    {
        $this->setServerUrl(HTTP_SERVER . '/' . $this->getServerUrlAction());

    }

    /**
     * Возвращает массив-параметр для передачи в API LiqPay
     * @param array $requestData
     */
    public function getRequestData()
    {
        $expired_date = $this->generateExpiredDate();
        $this->setExpiredDate($expired_date);

        $response =  array(
            'action'    => $this->getAction(),
            'version'   => $this->getVersion(),
            //'email'     => $this->getEmail(),
            //Клиенту email от LiqPay не отправляем
            'email' => $this->getEmailByDefault(),
            'amount'    => $this->getAmount(),
            'currency'  => $this->getCurrency(),
            'order_id'  => $this->getGeneratedOrderId(),
            'description' => $this->getDescription(),
            'action_payment' => $this->getActionPayment(),
            'expired_date' => $this->getExpiredDate(),
            'language' => $this->getLanguage(),
            'server_url' => $this->getServerUrl()

        );

        if($this->isError()){
            return false;
        }

        if(!$this->isFullArrayData($response)){
            $this->addErrorsMessages('Параметры для запроса к LiqPay определены не полностью.');
            return false;
        }

        return $response;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        if(!$email){
            $this->email = false;
            $this->addErrorsMessages('Не определен email заказа.');
            return $this;
        }
        $this->email = $email;
        return $this;

    }

    /**
     * @return mixed
     */
    public function getActionPayment()
    {
        return $this->action_payment;
    }

    /**
     * @param mixed $action_payment
     */
    public function setActionPayment($action_payment)
    {
        if(!isset($this->getAvailableActionPayments()[$action_payment])){
            $this->action_payment = false;
            $this->addErrorsMessages('Не определен Тип операции.');
            return $this;
        }

        $this->action_payment = $action_payment;
        return $this;

    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        if(!$description){
            $this->description = false;
            $this->addErrorsMessages('Не определено Назначение платежа для API.');
            return $this;
        }
        $this->description = $description;
        return $this;

    }

    /**
     * @return array
     */
    protected function getAvailableActionPayments()
    {
        return $this->availableActionPayments;
    }

    /**
     * @return mixed
     */
    public function getExpiredDate()
    {
        return $this->expiredDate;
    }

    //2016-04-24 00:00:00
    protected function generateExpiredDate()
    {
        $modify_days = '+' .  $this->getMaxDaysToPay() . ' days' ;
        $date = (new \DateTime())->setTimezone( new DateTimeZone("UTC") )->modify($modify_days)->format($this->getDatetimeFormat());
        return $date;
    }

    protected function setExpiredDate($expiredDate)
    {
        $this->expiredDate = $expiredDate;
    }

    /**
     * @return int
     */
    public function getMaxDaysToPay()
    {
        return $this->maxDaysToPay;
    }

    /**
     * @return mixed
     */
    public function getServerUrl()
    {
        return $this->server_url;
    }

    /**
     * @param mixed $server_url
     */
    protected function setServerUrl($server_url)
    {
        $this->server_url = $server_url;
    }

    /**
     * @return string
     */
    protected function getServerUrlAction()
    {
        return $this->server_url_action;
    }

    /**
     * @return string
     */
    protected function getEmailByDefault()
    {
        return $this->emailByDefault;
    }

}