<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 24.01.2018
 * Time: 13:16
 */

require_once(DIR_WS_CLASSES.'separator_order/Models/SeparatorOrderAbstractModel.php');

abstract class SeparatorOrderAbstractNotifier extends SeparatorOrderAbstractModel
{
    protected $separatedOrders = [];

    protected $isNotifyCompleted = false;

    protected $customerData = [];

    protected $languagesData = array(
        'russian' => 'ru',
        'ukrainian' => 'uk'
    );

    protected $current_language = '';

    public function __construct($order_id, $separated_orders, $customer_data)
    {
        $this->setOrderId($order_id);

        if(!$this->getOrderId() ){

            $this->addErrorsMessages('Ошибка в инициализации данных для отправки результатов пользователю.');

        }

        $language = get_cur_order_lang($this->getOrderId());

        $this->setLangName($language);

        $this->setSeparatedOrders($separated_orders);

        $this->setDbManager( new SeparatorOrderRepository() );

        $this->setCustomerData($customer_data);

    }

    abstract public function send();

    /**
     * @return array
     */
    protected function getSeparatedOrders()
    {
        return $this->separatedOrders;
    }

    /**
     * @param array $separated_orders
     */
    public function setSeparatedOrders($separated_orders)
    {
        $this->separatedOrders = $separated_orders;
    }

    /**
     * @return bool
     */
    public function isNotifyCompleted()
    {
        return $this->isNotifyCompleted;
    }

    /**
     * @param bool $isSendEmailCompleted
     */
    protected function setIsNotifyCompleted($isNotifyCompleted)
    {
        $this->isNotifyCompleted = $isNotifyCompleted;
    }

    /**
     * @return array
     */
    public function getCustomerData()
    {
        return $this->customerData;
    }

    protected function setCustomerData($customer_data)
    {
        if(! $this->checkForArray($customer_data) ){

            return false;

        }

        $this->customerData = $customer_data;

        return true;
    }

    /**
     * @return array
     */
    protected function getLanguageCode()
    {
        $language = ( $this->getLangName() ) ? $this->getLangName() : 'russian';

        if($this->languagesData[$language]){
            return $this->languagesData[$language];
        }

        return 'ru';
    }

}