<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 22.01.2018
 * Time: 15:43
 */

require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderAbstractNotifier.php');
require_once(DIR_WS_CLASSES.'separator_order/SeparatorOrderSenderMail.php');

class SeparatorOrderEmailModel extends SeparatorOrderAbstractNotifier
{

    protected $sender = 'greenmarket';

    protected $separatedOrdersMaxTimeDelivery = [];

    public function __construct($order_id, $separated_orders, $customer_data)
    {
        parent::__construct($order_id, $separated_orders, $customer_data);

        $orders_delivery_data = $this->getOrdersDeliveryData();

        $this->setSeparatedOrdersMaxTimeDelivery($orders_delivery_data);

    }

    /**
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    public function send()
    {

        $customer_data_arr = $this->getCustomerData();

        $mailer = new SeparatorOrderSenderMail(
            $customer_data_arr['c_name'],
            $customer_data_arr['c_lastname'],
            $customer_data_arr['c_email'],
            $this->getOrderId(),
            $customer_data_arr['city'],
            $customer_data_arr['s_method'],
            $customer_data_arr['p_method'],
            $customer_data_arr['office'],
            $customer_data_arr['ph_number'],
            $customer_data_arr['o_type'],
            $customer_data_arr['post_code'],
            ''
        );

        $mailer->setSeparatedOrders( $this->getSeparatedOrders() );

        $mailer->setLangName($this->getLangName());

        $lang_code = $this->getLanguageCode();

        $mailer->setLangCode($lang_code);

        $mailer->setSeparatedOrdersMaxTimeDelivery( $this->getSeparatedOrdersMaxTimeDelivery() );

        $mail_results = $mailer->send_mail($this->getSender(), $this->getLangName());

        foreach ($mail_results as $mail_result){

            if(!$mail_result){
                $this->setIsNotifyCompleted(false);
                $this->addErrorsMessages('Ошибка при отправке письма о результатах разделения/удаления. ');
                break;
            }

        }

        if($this->isError()){

            $this->setIsNotifyCompleted(false);
            return false;
        }

        $this->setIsNotifyCompleted(true);

        return true;

    }


    protected function getOrdersDeliveryData()
    {
        $orders_data = $this->getSeparatedOrders();

        $orders_data[] = $this->getOrderId();

        $orders_delivery_data = $this->getDbManager()->getOrdersDeliveryDataToMailDB(
            $orders_data,
            $this->getLanguageCode()
        );

        return $orders_delivery_data;
    }

    /**
     * @return array
     */
    protected function getSeparatedOrdersMaxTimeDelivery()
    {
        return $this->separatedOrdersMaxTimeDelivery;
    }

    /**
     * @param array $orders_delivery_data
     */
    protected function setSeparatedOrdersMaxTimeDelivery($orders_delivery_data)
    {
        $delivery_data = [];
        foreach ($orders_delivery_data as $order_id => $data){
            $max = max(array_keys($data));
            $delivery_data[$order_id] = $data[$max];
        }

        $this->separatedOrdersMaxTimeDelivery = $delivery_data;
    }

}