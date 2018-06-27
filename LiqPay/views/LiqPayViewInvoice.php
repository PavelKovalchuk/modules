<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 27.02.2018
 * Time: 17:13
 */

class LiqPayViewInvoice extends LiqPayView
{
    protected $sendInvoiceText = "LiqPay Invoice";

    /**
     * @return Singleton
     */
    public static function getInstance()
    {
        if (null === self::$instance)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getButtonLiqPayInvoice($invoice_type, $commission, $text = false)
    {
        $btn_text = $text ? $text : $this->getSendInvoiceText();

        $btn = "<span class='button'>
                    <input
                        type='button'
                        name='LiqPayInvoice'
                        value='$btn_text'     
                        onclick='sendLiqPayInvoice()'                   
                        class='js-send-liqpay-invoice'
                        data-invoice-type='$invoice_type'
                        data-liqpay-commission='$commission'
                    >
                </span>";

        return $btn;

    }

    /**
     * @return string
     */
    protected function getSendInvoiceText()
    {
        return $this->sendInvoiceText;
    }
}