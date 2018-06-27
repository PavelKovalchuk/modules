<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 16.02.2018
 * Time: 14:15
 */

class DropshippingDBRepository
{

    /**
     * @param int $orderId
     * @param int $min_cash_on_delivery
     * @return array
     */
    public function getTtnParamsFromDb($orderId, $min_cash_on_delivery)
    {

        $sql = "SELECT o.orders_id 
                       ,o.delivery_name
                       ,o.customers_telephone                                          
                       ,o.payment_method
                       ,o.payment_method_tmp
                       ,o.payment_class
                       ,o.orders_status
                       ,o.shipping_method
                       ,o.shipping_class

                       ,CONCAT(o.delivery_city, ', ', o.delivery_street_address) AS 'delivery_data'                
                       ,os.orders_status_name
                
                       ,om.manager_id
                       ,om.stationary_manager_id
                
                       ,CONCAT(au.last_name, ' ', au.first_name) AS 'responsible_manager'                
                       ,IF( ot.value < {$min_cash_on_delivery},'нет', ROUND(ot.value, 0) ) AS 'cash_on_delivery'                
                       ,ROUND(ot_2.value, 0) AS 'assessed_value'                       
                       ,l.languages_id
                       ,DATE_FORMAT(CURDATE(), \"%d.%m.%Y\") as 'ttn_date'
                
                FROM orders AS o
                
                LEFT JOIN orders_status AS os
                ON o.orders_status = os.orders_status_id
                
                LEFT JOIN orders_to_managers AS om
                ON o.orders_id = om.orders_id
                
                LEFT JOIN admin_users AS au
                ON om.stationary_manager_id = au.id 
                
                LEFT JOIN orders_total AS ot
                ON o.orders_id = ot.orders_id
                
                LEFT JOIN orders_total AS ot_2
                ON o.orders_id = ot_2.orders_id
                
                LEFT JOIN languages AS l
                ON o.language = l.directory
                
                WHERE o.orders_id = {$orderId}
                AND ot.class = 'ot_total'
                AND ot_2.class = IF( 1 = (SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = o.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                
            ";

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result = $row;
        }

        return $result;

    }

    public function getRulesParamsFromDb($orderId)
    {

        $sql = "SELECT o.orders_id 
                ,o.payment_method_tmp AS 'control_type_payment'
                ,ot.value AS 'control_payment'
                ,(SELECT value FROM orders_total WHERE orders_id = o.orders_id AND class = 'ot_prepayment' ) AS 'control_prepayment'
                ,(SELECT value FROM orders_total WHERE orders_id = o.orders_id AND class = 'ot_guarantee' ) AS 'control_guarantee'
                ,ot_2.value AS 'amount'
                
                FROM orders AS o                               
                
                LEFT JOIN orders_total AS ot
                ON o.orders_id = ot.orders_id 
                
                LEFT JOIN orders_total AS ot_2
                ON o.orders_id = ot_2.orders_id
                
                WHERE o.orders_id = {$orderId}

                AND ot.class = 'ot_total'
                
                AND ot_2.class = IF( 1 = (SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = o.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
            ";

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result = $row;
        }

        return $result;

    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getProductsDataFromDb($orderId)
    {

        $sql = "SELECT       
                       CONCAT(op.products_quantity, ' x ', op.products_model, ' - ' , op.products_name, ' ', ROUND(op.products_price, 2), ' грн - ', ROUND(op.final_price, 2), ' грн;') AS 'products_data'
                 
                FROM orders_products  AS op                
                WHERE orders_id = {$orderId}                
            ";

        $res = vam_db_query($sql);
        $result = '';
        while ($row = vam_db_fetch_array($res)){

            $result .= $row["products_data"] . PHP_EOL;
        }

        return $result;

    }

}