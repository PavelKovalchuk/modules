<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 26.02.2018
 * Time: 17:29
 */

require_once (DIR_FS_DOCUMENT_ROOT. 'admin/includes/classes/SQLArrayHelperAbstract.php');

class LiqPayDBRepository extends SQLArrayHelperAbstract
{
    protected $sendResponseTable = 'liqpay_invoices';

    protected $prepaidInvoicesLogbookTable = 'liqpay_logbook_prepaid';

    protected $paymentInvoicesLogbookTable = 'liqpay_logbook_payment';

    protected $callbackTable = 'liqpay_callback_invoices';

    private static
        $instance = null;
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
    private function __clone() {}
    private function __construct() {}


    /**
     * Резервирование номера уникального заказа
     * @param $data_array
     * @return bool|int
     */
    public function reserveGeneratedOrderIdDb($data_array)
    {
        if( !$this->isDataArray($data_array)){
            return false;
        }

        $sql = "INSERT INTO " . $this->getSendResponseTable() . $this->getFieldsToValuesInsertSqlString($data_array) ;
        $orders_query = vam_db_query($sql);
        $id = vam_db_insert_id($orders_query);

        return $id;
    }

    /**
     * Запись в журнал LiqPay инвойсов тиблицы БД соответствующей типу инвойса
     * @param $data_array
     * @param $invoice_type
     * @return bool|int
     */
    public function saveLogbookEntryDb($data_array, $invoice_type)
    {
        if( !$this->isDataArray($data_array) || !$invoice_type){
            return false;
        }

        $sql = "INSERT INTO " . $this->getInvoicesLogbookTable($invoice_type) . $this->getFieldsToValuesInsertSqlString($data_array) ;
        $orders_query = vam_db_query($sql);
        $id = vam_db_insert_id($orders_query);

        return $id;
    }

    public function saveCallbackEntryDb($data_array)
    {
        if( !$this->isDataArray($data_array)){
            return false;
        }

        $sql = "INSERT INTO " . $this->getCallbackTable() . $this->getFieldsToValuesInsertSqlString($data_array) ;
        $orders_query = vam_db_query($sql);
        $id = vam_db_insert_id($orders_query);

        return $id;
    }

    public function updateInvoiceEntryDb($data_array, $condition_array)
    {
        if( !$this->isDataArray($data_array) || !$this->isDataArray($condition_array) ){
            return false;
        }

        $sql = "UPDATE " . $this->getSendResponseTable() . $this->getFieldsToValuesUpdateSqlString($data_array, $condition_array) ;

        $orders_query = vam_db_query($sql);

        return $orders_query;
    }

    public function updateLogbookTableDb($data_array, $condition_array, $invoice_type)
    {
        if( !$this->isDataArray($condition_array) || !$this->isDataArray($data_array) || !$invoice_type){
            return false;
        }

        $sql = "UPDATE " . $this->getInvoicesLogbookTable($invoice_type) . $this->getFieldsToValuesUpdateSqlString($data_array, $condition_array) ;

        $orders_query = vam_db_query($sql);

        return $orders_query;
    }

    public function getBookedOrdersChainFromDb($condition_array, $invoice_type)
    {
        if( !$this->isDataArray($condition_array) || !$invoice_type){
            return false;
        }

        $sql = "SELECT order_id                      
                FROM " . $this->getInvoicesLogbookTable($invoice_type) . " AS ll " . $this->getWhereEqualConditions($condition_array);

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result[] = $row["order_id"];
        }

        return $result;

    }

    public function getDataByGeneratedOrderIdFromDb($condition_array, $fields_to_select_array)
    {
        if( !$this->isDataArray($condition_array) || !$this->isDataArray($fields_to_select_array) ){
            return false;
        }

        $sql = "SELECT " . $this->getFieldsListFromArray($fields_to_select_array) . "                     
                FROM " . $this->getSendResponseTable() . " " . $this->getWhereEqualConditions($condition_array);

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result = $row;
        }

        return $result;
    }

    public function getLastGeneratedOrderEntryFromDb($condition_array, $invoice_type)
    {
        if( !$this->isDataArray($condition_array) || !$invoice_type){
            return false;
        }

        $ll_table = $this->getInvoicesLogbookTable($invoice_type);
        $li_table = $this->getSendResponseTable();

        $sql = "SELECT ll.generated_order_id
                FROM " . $ll_table . " as ll 
                LEFT JOIN " . $li_table . " as li"
                . " ON  ll.generated_order_id = li.generated_order_id "
            . $this->getWhereEqualConditions($condition_array)
            . " AND invoice_shop_status = 'active' "
            . " AND li.operation_date = (
                    SELECT MAX(li2.operation_date)
                    FROM " . $ll_table . " as ll2 
                    LEFT JOIN " . $li_table . " as li2 ON  ll2.generated_order_id = li2.generated_order_id "
                    . $this->getWhereEqualConditions($condition_array)
                    . " AND invoice_shop_status = 'active' "
                . ")";

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result = $row;
        }

        return $result;

    }

    /**
     * @return string
     */
    protected function getSendResponseTable()
    {
        return $this->sendResponseTable;
    }

    /**
     * @return string
     */
    protected function getInvoicesLogbookTable($invoice_type)
    {
        if($invoice_type == 'prepaid'){
            return $this->getPrepaidInvoicesLogbookTable();
        }elseif ($invoice_type == 'payment'){
            return $this->getPaymentInvoicesLogbookTable();
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getPrepaidInvoicesLogbookTable()
    {
        return $this->prepaidInvoicesLogbookTable;
    }

    /**
     * @return string
     */
    protected function getPaymentInvoicesLogbookTable()
    {
        return $this->paymentInvoicesLogbookTable;
    }

    /**
     * @return string
     */
    public function getCallbackTable()
    {
        return $this->callbackTable;
    }


}