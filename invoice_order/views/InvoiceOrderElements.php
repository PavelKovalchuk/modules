<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 13.06.2018
 * Time: 11:42
 */

require_once(INVOICE_ROOT_DIR . 'InvoiceOrderTrait.php');
require_once(INVOICE_ROOT_DIR . 'InvoiceOrderPlaceholder.php');

class InvoiceOrderElements {


    static public function getInvoiceButtonHtml($order_id, $order_delivery_type, $button_name, $is_need_download)
    {
        if($is_need_download){

        ?>
            <!-- Get Invoice file -->
            <iframe id="order-invoice-iframe" src="" style="display: none; visibility: hidden;"></iframe>
            <!-- Get Invoice file -->
        <?php  } ?>

        <button class="admin-btn admin-btn-success" id="get-order-invoice" data-delivery-type="<?php echo $order_delivery_type; ?>" data-orders-id="<?php echo $order_id;  ?>" type="button"><?php echo $button_name; ?></button>

        <?php
    }


    static public function getInvoiceTitle($order_id)
    {
        if(!$order_id){
            return false;
        }

        $html = '';

        $html .= '<table cellpadding="1" cellspacing="1" border="0">';
        $html .= '<tr>';
        $html .= '<td style="font-weight: bold; font-size: 14px;line-height:30px;text-align: left ">';
        $html .= InvoiceOrderPlaceholder::getPlaceholderByKey('invoice_title') . $order_id;
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        return $html;

    }

    static public function getBackSide($backside_data, $is_two_side)
    {
        if(!$backside_data || !$backside_data['informer'] || empty($backside_data['informer'])){
            return false;
        }

        $html = '';

        if($is_two_side == false){
            $html .= '<hr/>';
            $html .= '<div style="line-height: 10px;">&nbsp;</div>';
        }

        $html .= '<table cellpadding="1" cellspacing="1" border="0">';
        $html .= '<tr>';
        $html .= '<td style="text-align: left ">';
        $html .= $backside_data['informer'];
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<table>';

        return $html;
    }

    static public function getFooter($footer_data)
    {
        if(!$footer_data || !$footer_data['amount_in_words']){
            return false;
        }

        $html = '';

        $html .= '<table cellpadding="1" cellspacing="1" border="0">';

        $html .= '<tr>';
        $html .= '<td style="text-align: left ">';
        $html .= InvoiceOrderPlaceholder::getPlaceholderByKey('footer_amount_start') . $footer_data['amount_in_words'] . InvoiceOrderPlaceholder::getPlaceholderByKey('footer_amount_finish');
        $html .= '</td>';
        $html .= '</tr>';

        $html .= self::getFakeRowPadding();

        $html .= '<tr>';
        $html .= '<td style="text-align: left ">';
        $html .= InvoiceOrderPlaceholder::getPlaceholderByKey('footer_respect');
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        return $html;

    }

    static public function getHeader($header_data, $delivery_data)
    {
        $name = $header_data['customer_name'];
        $phone = $header_data['phone'];
        $date_generated = $header_data['date_generated'];
        $barcodeSource = $header_data['barcode_src'];
        $backSideImageSource = $header_data['back_side_image_src'];
        $shop_logo = $header_data['shop_logo'];
        $is_dropshipping = $delivery_data['is_dropshipping'];

        if(!self::checkInputStr($name) || !self::checkInputStr($phone) ){
            return false;
        }

        $html = '';

        $html .= '<table cellpadding="1" cellspacing="1" border="0" style="text-align:center;">';

        $html .= '<tr>';

        $html .= '<td align="left">';
            $html .= self::getCustomerInfo($name, $phone, $date_generated);
        $html .= '</td>';

        $html .= '<td align="right">';
            $html .= self::getShopData($shop_logo);
        $html .= '</td>';

        $html .= '</tr>';

        if($is_dropshipping == false){

            $html .= self::getFakeRowPadding();

            $html .= '<tr>';

            $html .= '<td align="left">';
            $html .= self::getBarcodeImage($barcodeSource);
            $html .= '</td>';

            $html .= '<td align="right">';
            $html .= self::getBackSideImage($backSideImageSource);
            $html .= '</td>';

            $html .= '</tr>';

        }

        $html .= self::getFakeRowPadding();
        $html .= '</table>';

        $html .= '<hr/>';

        return $html;

    }

    static public function getFinanceTableRows($finance_data, $table_columns_number)
    {
        if(!$finance_data || !$table_columns_number){
            return false;
        }

        if(!self::checkInputArr($finance_data)){
            return false;
        }

        $colspan = $table_columns_number - 1;
        $html = '';

        foreach ($finance_data as $finance_class => $finance_data){
            $html .= '<tr style="font-weight: bold;line-height: 20px;" >';
            $html .= '<td style="text-align: right;" colspan="' . $colspan . '" >' . $finance_data['title'] . '</td>';
            $html .= '<td style="text-align: center;" >' . self::priceFormat($finance_data['value']) . '</td>';
            $html .= '</tr>';
        }

        return $html;

    }

    static public function getSeedsHeaderRow($table_columns_number)
    {
        if(!$table_columns_number){
            return false;
        }

        $html = '<tr style="font-weight: bold;line-height: 20px; text-align: center" >';
            $html .= '<td colspan="' . $table_columns_number . '" >' . InvoiceOrderPlaceholder::getPlaceholderByKey('seeds_header') . '</td>';
        $html .= '</tr>';

        return $html;

    }

    static public function getInvoiceTableStart($placeholders)
    {
        if(!$placeholders){
            return false;
        }

        $html = '';

        $html .= '<table cellpadding="1" cellspacing="1" border="1">';

        $html .= '<tr style="font-weight: bold;line-height: 20px" >';

        $html .= self::getTableHeaderCells($placeholders);

        $html .= '</tr>';

        return $html;

    }

    static public function getInvoiceTableFinish()
    {

        $html = '</table>';
        return $html;
    }

    static public function getProductsTableRows($products_data, $table_columns_number)
    {
        if(!self::checkInputArr($products_data)){
            return false;
        }

        $price_formatted_array = [
            'products_price',
            'final_price'
        ];

        $text_aligned_array = [
            'products_price',
            'final_price',
            'products_boxes',
            'product_position',
        ];

        $text_small_array = [
            'products_model',
        ];

        $html = '';

        foreach($products_data as $index => $product){

            if($product['seeds_header']){
                $html .= self::getSeedsHeaderRow($table_columns_number);
                continue;
            }

            $html .= '<tr>';
            foreach($product as $key => $value){

                $style = '';

                if(in_array($key, $price_formatted_array)){
                    $value = self::priceFormat($value);
                }

                if(in_array($key, $text_aligned_array)){
                    $style = 'text-align: center;';
                }

                if(in_array($key, $text_small_array)){
                    $style = 'font-size: 8px;';
                }

                $html .= '<td style="' .$style . '" >' . $value . '</td>';
            }
            $html .= '</tr>';
        }

        return $html;
    }

    static protected function getShopLogo($logoSource)
    {
        if(!$logoSource){
            return false;
        }
        $html = '<img src="' . $logoSource . '" alt="info" width="180" height="100" align="middle" border="0" />';
        return $html;
    }

    static protected function getBarcodeImage($barcodeSource)
    {
        $html = '<img src="' . $barcodeSource . '" alt="barcode" width="205" height="80" border="0" />';

        return $html;
    }

    static public function getBackSideImage($backSideImageSource)
    {

        $html = '<img src="' . $backSideImageSource . '" alt="info" width="410" height="80" align="middle" border="0" />';

        return $html;
    }

    static protected function getCustomerInfo($name, $phone, $date_generated)
    {

        $html = '';
            $html .= '<div style="line-height: 40px;">&nbsp;</div>';
            $html .= '<div style="line-height: 10px;" >';
                $html .= '<span style="font-weight: bold; ">';
                    $html .= InvoiceOrderPlaceholder::getPlaceholderByKey('customer');
                $html .= '</span>';

                $html .= '<span>';
                    $html .= $name;
                $html .= '</span>';
            $html .= '</div>';

            $html .= '<div style="line-height: 10px;">';
                $html .= '<span style="font-weight: bold;">';
                    $html .= InvoiceOrderPlaceholder::getPlaceholderByKey('phone');
                $html .= '</span>';

                $html .= '<span>';
                    $html .= $phone;
                $html .= '</span>';
            $html .= '</div>';

            $html .= '<div style="line-height: 10px;">';
                $html .= '<span style="font-weight: bold;">';
                    $html .= InvoiceOrderPlaceholder::getPlaceholderByKey('date_generated');
                $html .= '</span>';

                $html .= '<span>';
                    $html .= $date_generated;
                $html .= '</span>';
            $html .= '</div>';

        return $html;

    }

    static protected function getShopData($shop_logo)
    {

        $html = '';

        $html .= '<div style="font-weight: bold; line-height: 5px; font-size: 30px; text-align: center">';
            //$html .= InvoiceOrderPlaceholder::getPlaceholderByKey('shop_name');
            $html .= self::getShopLogo($shop_logo);
        $html .= '</div>';

        $html .= '<div style="line-height: 10px; text-align: center">';

        $html .= '<span style="">';
        $html .= InvoiceOrderPlaceholder::getPlaceholderByKey('shop_site');
        $html .= '</span>';

        $html .= '</div>';


        return $html;

    }

    static protected function priceFormat($price)
    {
        if(!$price){
            return false;
        }

        return number_format((float)$price, 2, '.', ' ');

    }

    static public function getTableHeaderCells($placeholders)
    {
        $html = '';

        if(!self::checkInputArr($placeholders)){
            return false;
        }

        foreach ($placeholders as $index => $data){
            $html .= self::tableHeaderCell($data['header'], $data['width']);
        }

        return $html;

    }

    static public function tableHeaderCell($header, $width)
    {
        if(!$header){
            return false;
        }

        $style = 'style="text-align:center;';
        $style .= $width ? 'width:' . $width . ';' : '';
        $style .= '"';

        $html = '<td ' . $style . '>';
        $html .= InvoiceOrderPlaceholder::getPlaceholderByKey($header);
        $html .= '</td>';

        return $html;
    }

    static protected function getFakeRowPadding()
    {

        return '<tr><td height="15px"></td></tr>';

    }

    static protected function checkInputStr($str)
    {
        if(empty($str) || !is_string($str)){
            return false;
        }

        return true;

    }

    static protected function checkInputArr($arr)
    {
        if(!is_array($arr) || empty($arr)){
            return false;
        }

        return true;

    }

}