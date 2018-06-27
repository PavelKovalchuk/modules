<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 18.01.2018
 * Time: 10:26
 */

require_once(DIR_FS_DOCUMENT_ROOT.'inc/SendOrderMail.php');
require_once(DIR_WS_CLASSES.'separator_order/SeparatorOrderTrait.php');

class SeparatorOrderSenderMail extends SendOrdersMail
{
    use SeparatorOrderTrait;

    private $email_template = 'separate_letter_order';

    private $separatedOrders = [];

    private $separatedOrdersMaxTimeDelivery = [];

    protected function generate_text(){

        $ret = array();
        $mail_root_img_dir = HTTP_SERVER . '/templates/' . CURRENT_TEMPLATE.'/images/mail/tmpl/';
        $mail_utm_link = '?utm_source=Direct-mail&utm_medium=email&utm_campaign=thanks-for-order';

        $text_advises = $this->generateOrdersAdvices();

        $orders_data = $this->generateOrdersProductsData();

        $email_template_html =  CURRENT_TEMPLATE.'/mail/'. $this->getLangName() .'/' . $this->getEmailTemplate() . '.html';
        $email_template_txt =  CURRENT_TEMPLATE.'/mail/'. $this->getLangName() . '/' . $this->getEmailTemplate() . '.txt';

        $template =  CURRENT_TEMPLATE.'/module/mail_main_template.html';
        $vamTemplate = new vamTemplate;

        $mail_template = new vamTemplate;
        $mail_template->assign('TMPL_IMG_ROOT', $mail_root_img_dir);
        $mail_template->assign('UTM_LINK', $mail_utm_link);
        $mail_template->assign('CUSTOMER_FIRST_NAME', $this->c_lastname);
        $mail_template->assign('CUSTOMER_LAST_NAME', $this->c_name);
        $mail_template->assign('TEXT_ADVISES', $text_advises);
        $mail_template->assign('MAIN_ORDER_ID', $this->c_oid);
        $mail_template->assign('ORDERS_DATA', $orders_data);
        $mail_template->assign('CUSTOMER_CITY',$this->c_city);
        $mail_template->assign('SHIPPING_METHOD',$this->c_s_method);
        $mail_template->assign('DELIVERY',$this->c_address);
        $mail_template->assign('COMMENTS',$this->c_comment);
        $mail_template->assign('ITER_VAR',1);
        $mail_template->assign('SHOULD_SHOW_DELIVERY_TERMS',false);
        $mail_template->caching = 0;

        $email_txt = $mail_template->fetch($email_template_html);
        $ret['txt'] = $mail_template->fetch($email_template_txt);

        $vamTemplate->assign('TEXT_LETTER', $email_txt);
        $vamTemplate->assign('TMPL_IMG_ROOT', $mail_root_img_dir);
        $vamTemplate->assign('SHOP_URL_MAIN_PAGE', HTTP_SERVER);
        $vamTemplate->assign('LOGO_URL', $mail_root_img_dir . 'head_mail_logo_'.$this->getLangCode().'.png');
        $vamTemplate->assign('language', $this->getLangName());
        $vamTemplate->assign('UTM_LINK', $mail_utm_link);

        $vamTemplate->caching = 0;

        $ret['html'] = $vamTemplate->fetch($template);

        return  $ret;
    }

    /**
     * @return string
     */
    protected function getEmailTemplate()
    {
        return $this->email_template;
    }

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

    protected function generateOrdersProductsData()
    {
        $result = [];

        $data = $this->getAllOrdersId();

        foreach ($data as $key => $order_id){

            $result[] =  array(
                'order_id' => $order_id,
                'order_data' => $this->get_products($order_id),
                'order_total' => number_format(ceil($this->total($order_id)[0]['VALUE']), 2),
                'order_max_time' => $this->getOrdersMaxTimeDelivery($order_id),
            );

        }

        return $result;
    }

    protected function generateOrdersAdvices()
    {
        $result = [
            'html' => false,
            'txt' => false,
            'singl' => false
        ];

        $data = $this->getAllOrdersId();

        $is_single = false;

        $result_html = [];
        $result_txt = [];

        foreach ($data as $key => $order_id){

            //$separator = ( $key > 0 && $key != (count($data) - 1) )? ', ' : '';

            $order_advice = $this->generate_advises_text($order_id);

            if(!empty($order_advice['html'])){
                $result_html[] =  $order_advice['html'];
            }

            if(!empty($order_advice['txt'])){
                $result_txt[] = $order_advice['txt'];
            }

            if($order_advice['singl'] == 1 && $is_single == false){
                $result['singl'] = $order_advice['singl'];
            }

        }

        $result['html'] = implode(', ', $result_html);
        $result['txt'] = implode(', ', $result_txt);

        return $result;

    }

    protected function getAllOrdersId()
    {
        $data = $this->getSeparatedOrders();
        array_unshift($data, $this->c_oid);

        return $data;

    }

    /**
     * @return bool
     */
    public function getLangCode()
    {
        return $this->lang_code;
    }

    /**
     * @param bool $lang_code
     */
    public function setLangCode($lang_code)
    {
        $this->lang_code = $lang_code;
    }

    /**
     * @return array
     */
    protected function getSeparatedOrdersMaxTimeDelivery()
    {
        return $this->separatedOrdersMaxTimeDelivery;
    }

    protected function getOrdersMaxTimeDelivery($order_id)
    {

        if($this->separatedOrdersMaxTimeDelivery[$order_id]){
            return $this->separatedOrdersMaxTimeDelivery[$order_id];
        }

        return 'срок будет уточняться перед началом сезона';
    }

    /**
     * @param array $separatedOrdersMaxTimeDelivery
     */
    public function setSeparatedOrdersMaxTimeDelivery($separatedOrdersMaxTimeDelivery)
    {
        $this->separatedOrdersMaxTimeDelivery = $separatedOrdersMaxTimeDelivery;
    }


}