<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 24.01.2018
 * Time: 12:58
 */
require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderAbstractNotifier.php');
require_once (DIR_WS_CLASSES.'sms.class.php');

class SeparatorOrderSmsModel extends SeparatorOrderAbstractNotifier
{
    protected $phone_number = '';


    public function __construct($order_id, $separated_orders, $customer_data)
    {
        parent::__construct($order_id, $separated_orders, $customer_data);

        $phone = $this->getCustomerData()['ph_number'];

        if(!$phone){
            $this->setIsNotifyCompleted(false);
            $this->addErrorsMessages('Ошибка при отправке sms. Нет номера телефона для отправки. ');
            return false;
        }

        $this->setPhoneNumber($phone);

    }

    protected function getText()
    {
       $order_id = $this->getOrderId();
       $separated_orders_id = implode(', ', $this->getAllOrdersId());

       if($this->getLangName() == 'russian'){

           $text = "Zdravstvujte! Vash zakaz nomer " . $order_id . " razdelen na zakazi: " . $separated_orders_id . ". My svyazhemsya s Vami pered otpravkoy.";

           return $text;

       }

        if($this->getLangName() == 'ukrainian'){

            $text = "Dobryj den'! Vashe zamovlennia nomer " . $order_id . " rozdilene na zamovlennia: " . $separated_orders_id . ". My zv'yazhemosia z Vamy pered vidpravlenniam.";

            return $text;

        }

        return false;

    }

    protected function getAllOrdersId()
    {
        $data = $this->getSeparatedOrders();
        array_unshift($data, $this->getOrderId());

        return $data;

    }

    public function send()
    {
        if( !$this->getPhoneNumber()){

            $this->setIsNotifyCompleted(false);
            return false;
        }

        $sms_result = SMS::send_message($this->getText(), $this->getPhoneNumber());

        if($sms_result !== 'ACCEPTED'){

            $this->setIsNotifyCompleted(false);
            $this->addErrorsMessages('Ошибка при отправке sms. ' . $sms_result);
            return false;

        }

        $this->setIsNotifyCompleted(true);

        return true;
    }

    /**
     * @return string
     */
    protected function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @param string $phone_number
     */
    protected function setPhoneNumber($phone_number)
    {
        $this->phone_number = $phone_number;
    }

}