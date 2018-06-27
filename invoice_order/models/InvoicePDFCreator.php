<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 13.06.2018
 * Time: 14:21
 */

require_once(INVOICE_ROOT_DIR . 'InvoiceOrderTrait.php');
require_once(DIR_FS_LIBS_EXT . 'tcpdf_library/tcpdf.php');
require_once (INVOICE_VIEWS_DIR . 'InvoiceOrderElements.php');


class InvoicePDFCreator
{
    use InvoiceOrderTrait;

    protected $pdf;

    const DEFAULT_FONT_NAME = 'dejavusans';
    const DEFAULT_FONT_SIZE = 10;

    public function generateFile($data_to_write)
    {
        if(!$this->checkForArray($data_to_write)){
            return false;
        }

        $this->setOrderId($data_to_write['order_id']);

        $this->initPDF();
        $delivery_data = $data_to_write['delivery_data'];
        $print_settings = $data_to_write['print_settings'];
        $header_data = $data_to_write['header_data'];
        $order_data = $data_to_write['order_data'];
        $footer_data = $data_to_write['footer_data'];
        $backside_data = $data_to_write['backside_data'];

        $this->printHeaderData($header_data, $delivery_data);
        $this->printInvoiceTitle();
        $this->printInvoiceTable($order_data, $backside_data, $print_settings);
        $this->printFooterData($footer_data);
        $this->printBackSideData($backside_data, $print_settings['is_two_side']);

        //Clean everything unnecessary
        $this->wipeOutTrash($data_to_write['header_data']['barcode_src']);

        $file_name = 'Invoice__' . $this->getOrderId();
        $this->downloadFile($file_name);

    }

    protected function printHeaderData($header_data, $delivery_data)
    {
        $this->setFontSettings();
        $html = InvoiceOrderElements::getHeader($header_data, $delivery_data);
        $this->writeHTML($html);

    }

    protected function printBackSideData($backside_data, $is_two_side)
    {
        if(!$backside_data || !$backside_data['informer'] || empty($backside_data['informer'])){
            return false;
        }

        if($is_two_side){
            // add a page
            $this->pdf->AddPage();
        }

        $this->setFontSettings();
        $html = InvoiceOrderElements::getBackSide($backside_data, $is_two_side);
        $this->writeHTML($html);

    }

    protected function printFooterData($footer_data)
    {
        if(!$footer_data || !$footer_data['amount_in_words']){
            return false;
        }

        $this->setFontSettings();
        $html = InvoiceOrderElements::getFooter($footer_data);
        $this->writeHTML($html);

    }

    protected function printInvoiceTitle()
    {
        $html = InvoiceOrderElements::getInvoiceTitle($this->getOrderId());
        $this->writeHTML($html);
    }

    protected function printInvoiceTable($order_data, $backside_data, $print_settings)
    {

        $this->setFontSettings(false, 9);

        $table_product_map = $order_data['table_product_map'];
        $products_data = $order_data['products_data'];
        $table_columns_number = $order_data['table_columns_number'];
        $finance_data = $order_data['finance_data'];
        $is_two_side = $print_settings['is_two_side'];
        $placeholders = InvoiceOrderPlaceholder::getTableProductMap($table_product_map);
        $number_pages = count($products_data);

        foreach ($products_data as $page_index => $page_data){

            $html = '';
            if($page_index > 0){

                if($is_two_side){
                    // add a new page for the table
                    $this->printBackSideData($backside_data, $is_two_side);
                    $this->pdf->AddPage();
                }

            }
            //Начало html таблицы
            $html .= InvoiceOrderElements::getInvoiceTableStart($placeholders);

            //Данные о продуктах заказа
            $html .= InvoiceOrderElements::getProductsTableRows($page_data, $table_columns_number);

            //Кoнец html таблицы
            if( ($page_index + 1) != $number_pages){
                $html .= InvoiceOrderElements::getInvoiceTableFinish();
                $this->writeHTML($html);
            }else{
                //Финансовые данные
                $html .= InvoiceOrderElements::getFinanceTableRows($finance_data, $table_columns_number);

                //Кoнец таблицы
                $html .= InvoiceOrderElements::getInvoiceTableFinish();
                $this->writeHTML($html);
            }

        }

    }

    protected function wipeOutTrash($barcode_src)
    {
        if(!$barcode_src){
            return false;
        }
        //Remove barcode image
        $result = unlink($barcode_src);
        return $result;
    }

    protected function initPDF()
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setPdf($pdf);

        $default_doc_info = 'GreenMarket';

        // set document information
        $this->pdf->SetCreator($default_doc_info);
        $this->pdf->SetAuthor($default_doc_info);
        $this->pdf->SetTitle($default_doc_info);
        $this->pdf->SetSubject($default_doc_info);

        // set header and footer fonts
        $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $this->pdf->SetMargins(10, 10, 10);
        $this->pdf->SetHeaderMargin(10);
        $this->pdf->SetFooterMargin(10);
        $this->pdf->SetPrintHeader(false);

        // set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $this->setFontSettings(false, 14);

        // add a page
        $this->pdf->AddPage();
    }

    /**
     * @param mixed $pdf
     */
    protected function setPdf(TCPDF $pdf)
    {
        $this->pdf = $pdf;
    }

    protected function downloadFile($name = 'Накладная')
    {
        //Close and output PDF document
        $this->pdf->Output($name .'.pdf', 'D');
        exit();
    }

    protected function setFontSettings($font_name = false, $font_size = false)
    {
        if(!$font_name){
            $font_name = self::DEFAULT_FONT_NAME;
        }

        if(!$font_size){
            $font_size = self::DEFAULT_FONT_SIZE;
        }

        $this->pdf->SetFont($font_name, '', $font_size);
    }

    protected function writeHTML($html)
    {
        if(empty($html)){
            return false;
        }

        $this->pdf->writeHTML($html, true, false, true, false, '');
    }

}