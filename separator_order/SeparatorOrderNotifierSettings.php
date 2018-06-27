<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 24.01.2018
 * Time: 13:46
 */
require_once(DIR_WS_CLASSES.'separator_order/SeparatorOrderTrait.php');

class SeparatorOrderNotifierSettings
{
    use SeparatorOrderTrait;

    protected $notifierType;

    protected $corporateEmails = [];

    protected $corporatePhones = [];

    protected $notifiers = [
        'email' => 'SeparatorOrderEmailModel',
        'sms' => 'SeparatorOrderSmsModel'
    ];

    public function __construct()
    {
        $this->initData();

        if($this->isError()){
            return false;
        }

    }

    protected function initData()
    {
        if(!defined('STORE_OWNER_EMAIL_ADDRESS')){

            $this->addErrorsMessages('Ошибка в инициализации корпоративных email.');

        }

        if(!defined('CORPORATIVE_PHONE_NUMBERS')){

            $this->addErrorsMessages('Ошибка в инициализации корпоративных телефонов.');

        }

        $this->setCorporateEmails(STORE_OWNER_EMAIL_ADDRESS);
        $this->setCorporatePhones(CORPORATIVE_PHONE_NUMBERS);
    }


    public function getNotifierClassName($email, $phone)
    {
        if(!$phone || !$email){
            return false;
        }

        if( !in_array($email, $this->getCorporateEmails()) ){
            $type = 'email';
            $this->setNotifierType($type);
            return $this->getNotifierByType($type);
        }

        if( !in_array($phone, $this->getCorporatePhones()) ){
            $type = 'sms';
            $this->setNotifierType($type);
            return $this->getNotifierByType($type);
        }

        return false;

    }

    /**
     * @return array
     */
    protected function getCorporateEmails()
    {
        return $this->corporateEmails;
    }

    /**
     * @return array
     */
    protected function getCorporatePhones()
    {
        return $this->corporatePhones;
    }

    /**
     * @return array
     */
    protected function getNotifiers()
    {
        return $this->notifiers;
    }

    protected function getNotifierByType($type)
    {
        if(isset( $this->getNotifiers()[$type] )){
            return $this->getNotifiers()[$type];
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getNotifierType()
    {
        return $this->notifierType;
    }

    /**
     * @param mixed $notifierType
     */
    protected function setNotifierType($notifierType)
    {
        $this->notifierType = $notifierType;
    }

    /**
     * @param array $corporateEmails
     */
    protected function setCorporateEmails($corporateEmails)
    {

        $emails =  array_map('trim', explode(',', $corporateEmails ));

        if(!count($emails) > 0){
            return false;
        }

        $this->corporateEmails = $emails;

        return true;
    }

    /**
     * @param array $corporatePhones
     */
    protected function setCorporatePhones($corporatePhones)
    {
        $phones =  array_map('trim', explode(',', $corporatePhones ));

        if(!count($phones) > 0){
            return false;
        }

        $this->corporatePhones = $phones;

        return true;

    }


}