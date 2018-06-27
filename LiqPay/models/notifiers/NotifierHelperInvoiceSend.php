<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 05.03.2018
 * Time: 15:04
 */

class NotifierHelperInvoiceSend extends NotifierHelperAbstract
{
    use LiqPayInvoiceTrait;

    protected $invoiceHref;

    protected $maxDaysToPay;

    protected $expired_date;

    protected $numberOrdersInChain = 0;

    //соотношение типа инвойса к настройкам класса, отвечающего за отправку писем - пример
    protected $sendersMap =
        [
            'invoice_type_example' => [
                'method_name' => '',
                //template name for letter
                'template' => '',

                //variables from SendCustomMail::_langsTexts
                'subject_intro' => '',
                'subject_main' => '',
                'present_money_text' => '',
            ]
        ];


    public function __construct()
    {
        $this->initAvailableInvoicesTypes();
        $this->initSendersMap();
    }

    public function sendLetter()
    {
        $settings = $this->getSendersMap()[$this->getInvoiсesType()];
        $method_name = $settings['method_name'];
        $SenderClassName = $this->getSenderLetterClassName();

        if(!method_exists($SenderClassName, $method_name)){
            $this->addErrorsMessages('Не предопределен метод отправки ' . $method_name . ' у  ' . $SenderClassName);
            return false;
        }

        $expired_date = new DateTime( $this->getExpiredDate(), new DateTimeZone('UTC') );
        $expired_date->setTimezone(new DateTimeZone('Europe/Kiev'));

        $params = [
            'email' => $this->getUserEmail(),
            'language' => $this->getLanguage(),
            'mainOrderId' => $this->getMainOrderId(),
            'ordersIdsStr' => $this->getOrdersIdsStr(),
            'amount' => $this->getAmount(),
            'commission' => $this->getCommissionPercent(),
            'max_days' => $this->getMaxDaysToPay(),
            'expired_date' => $expired_date->format('d.m.Y H:i'),
            'link_target' => $this->getInvoiceHref(),
            'number_orders' => $this->getNumberOrdersInChain(),

            'template' => $settings['template'],
            'letter_subject_intro' => $settings['subject_intro'],
            'letter_subject_main' => $settings['subject_main'],
            'letter_content_present_money_text' => $settings['present_money_text'],
        ];

        $sendingResult = $SenderClassName::$method_name( $params );

        if($sendingResult != true){
            $this->addErrorsMessages('Магазин не смог отправить email с ссылкой на инвойс покупателю.' . $sendingResult);
            return false;
        }

        return true;

    }

    protected function initSendersMap()
    {

        $this->sendersMap = [
            $this->getInvoiсesTypePrepaid() => [
                'method_name' => 'sendLiqPayInvoiceLetter',
                'template' => 'letter_liqpay',
                'subject_intro' => 'LETTER_SUBJECT_LIQPAY_INTRO',
                'subject_main' => 'LETTER_SUBJECT_LIQPAY_PREPAID',
                'present_money_text' => 'LETTER_CONTENT_LIQPAY_MONEY_TEXT_PREPAID',
            ],

            $this->getInvoiсesTypePayment() => [
                'method_name' => 'sendLiqPayInvoiceLetter',
                'template' => 'letter_liqpay',
                'subject_intro' => 'LETTER_SUBJECT_LIQPAY_INTRO',
                'subject_main' => 'LETTER_SUBJECT_LIQPAY_PAYMENT',
                'present_money_text' => 'LETTER_CONTENT_LIQPAY_MONEY_TEXT_PAYMENT',
            ],

        ];
    }

    /**
     * @return array
     */
    protected function getSendersMap()
    {
        return $this->sendersMap;
    }

    /**
     * @return mixed
     */
    protected function getInvoiceHref()
    {
        return $this->invoiceHref;
    }

    /**
     * @param mixed $invoiceHref
     */
    public function setInvoiceHref($invoiceHref)
    {
        $this->invoiceHref = $invoiceHref;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getMaxDaysToPay()
    {
        return $this->maxDaysToPay;
    }

    /**
     * @param mixed $maxDaysToPay
     */
    public function setMaxDaysToPay($maxDaysToPay)
    {
        $this->maxDaysToPay = $maxDaysToPay;

        return $this;
    }

    /**
     * @return int
     */
    protected function getNumberOrdersInChain()
    {
        return $this->numberOrdersInChain;
    }

    /**
     * @param int $numberOrdersInChain
     */
    public function setNumberOrdersInChain($numberOrdersInChain)
    {
        $this->numberOrdersInChain = $numberOrdersInChain;

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