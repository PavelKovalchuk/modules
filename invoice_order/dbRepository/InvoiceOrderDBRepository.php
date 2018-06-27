<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 12.06.2018
 * Time: 17:47
 */


class InvoiceOrderDBRepository
{

    public function getCustomerData($orderId){

        $sql = "SELECT o.customers_name
                       ,o.customers_telephone
                       ,o.orders_id 
                
                FROM orders AS o
                WHERE o.orders_id = {$orderId}
                      ";

        $res = vam_db_query($sql);
        $result = vam_db_fetch_array($res);

        return $result;

    }

    public function getProductsFromDb($orderId)
    {
        $sql = "
                SELECT
                        op.products_id,
                        op.orders_products_id,
                        op.products_model,
                        op.products_name,
                        op.products_price,
                        op.final_price,
                        op.products_quantity,
                        ROUND(op.products_quantity/cnt_in_box) AS products_boxes,
                        cd.categories_name as cat_name,
                        p.cnt_in_box,
                        c.parent_id as cat_parent,
                        c.categories_id
                    FROM orders_products as op
                    LEFT JOIN products_to_categories as pc ON op.products_id = pc.products_id
                    LEFT JOIN categories as c ON c.categories_id = pc.categories_id
                    LEFT JOIN products as p ON p.products_id = op.products_id
                    LEFT OUTER JOIN categories_description as cd ON if(c.parent_id = 0, c.categories_id = cd.categories_id, cd.categories_id = c.parent_id)
                    WHERE op.orders_id={$orderId} AND cd.language_id = 1
        
        ";

        $res = vam_db_query($sql);
        $result = [];
        $i = 1;
        while ($row = vam_db_fetch_array($res)){
            $row['product_position'] = $i++;
            $result[] = $row;
        }

        return $result;

    }

    // проверка наличия вложенного заказа с семенами
    public function getDataForCheckingSeedsInnerOrder($orderId){

        $sql = "SELECT o.orders_id, oaa.add_seed_on, oaa.add_seed_confirm, oaa.seeds_order_id
                      FROM orders AS o
                      LEFT JOIN orders_add_attributes oaa ON o.orders_id = oaa.orders_id
                      WHERE o.orders_id = {$orderId}
                      ";

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result[] = $row;
        }

        return $result;

    }

    //Финансовые данные
    public function getOrderTotalData($orderId){

        $sql = "SELECT
                    ot.title, ot.value, ot.class
                FROM orders_total ot
                WHERE ot.orders_id = {$orderId}
                ORDER BY sort_order        
        ";

        $res = vam_db_query($sql);
        $result = [];
        while ($row = vam_db_fetch_array($res)){

            $result[$row['class']] = $row;
        }

        return $result;

    }

}