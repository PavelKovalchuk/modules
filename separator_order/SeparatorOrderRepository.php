<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 05.01.2018
 * Time: 13:27
 */

class SeparatorOrderRepository
{

    public function getOrderProductDataDB($order_id, $lang_id)
    {
        $sql = "SELECT orders.orders_status

                       ,orders_products.orders_products_id
                       ,orders_products.products_id 
                       ,orders_products.products_model
                       ,orders_products.products_name
                       ,orders_products.final_price
                       ,FLOOR((orders_products.products_quantity/products.cnt_in_box)) as quantity
                       ,orders_products.products_sellers
                       ,orders_products.storehouse_id
                
                       ,storehouses.name AS 'storehouse_name'
                       ,storehouses.wms_flag
                       ,storehouses.dropshipping_flag
                
                       ,products.products_shippingtime
                
                       ,products_description.products_name AS 'commercial_name'
                
                       ,products_to_categories.categories_id
                
                       ,categories.parent_id AS 'categories_parent_id'
                       ,categories.responce_id AS 'delivery_time_code'
                       
                       ,cd.categories_name as 'parent_category'
                
                       ,category_responces.text AS 'delivery_time_text'
                
                       ,categories_description.categories_name
                
                FROM orders
                
                    LEFT JOIN orders_products ON orders.orders_id = orders_products.orders_id
                    
                    LEFT JOIN products ON orders_products.products_id = products.products_id
                    
                    LEFT JOIN products_description ON orders_products.products_id = products_description.products_id
                    
                    LEFT JOIN products_to_categories ON orders_products.products_id = products_to_categories.products_id
                    
                    LEFT JOIN categories ON products_to_categories.categories_id = categories.categories_id
                    
                    LEFT JOIN categories_description ON products_to_categories.categories_id = categories_description.categories_id 
                    
                    LEFT JOIN storehouses ON orders_products.storehouse_id = storehouses.id 
                    
                    LEFT JOIN category_responces ON categories.responce_id = category_responces.id 
                    
                    LEFT JOIN categories_description AS cd ON categories.parent_id = cd.categories_id
                
                WHERE orders.orders_id = '{$order_id}'

                  AND products_description.language_id = '{$lang_id}'
            
                  AND categories_description.language_id = '{$lang_id}'
                  
                  AND ( cd.language_id = '{$lang_id}' OR cd.language_id IS NULL )
                             
                ";

        $query = vam_db_query($sql);

        $result_data = false;

        if($query){

            $result_data = [];

            while ($result_sql = vam_db_fetch_array($query)) {

                $result_data[$result_sql['products_id']] = $result_sql;

            }
        }

        return $result_data;

    }

    public function getOrdersDataToMailDB($order_id)
    {
        $sql = "SELECT customers_firstname c_name
                        , customers_lastname c_lastname
                        , customers_telephone ph_number
                        , customers_email_address c_email
                        , delivery_street_address office
                        , delivery_city city
                        , delivery_postcode post_code
                        , comments c_comment
                        , shipping_method s_method
                        , language o_lang
                        , orders_type o_type
                        , payment_method_tmp p_method
                         
                         FROM orders
                         
                       WHERE orders_id = '{$order_id}'
                ";

        $query = vam_db_query($sql);

        $result_data = false;

        if($query){

            $result_data = vam_db_fetch_array($query);

        }

        return $result_data;

    }

    public function getOrdersDeliveryDataToMailDB($order_id_arr, $language_code)
    {
        $sql = "SELECT op.orders_id,
                       cr.text_{$language_code} responce_text,
                       cr.sort_order
                FROM orders_products op
                
                JOIN products_to_categories ptc ON op.products_id = ptc.products_id
                JOIN categories c ON ptc.categories_id = c.categories_id
                JOIN category_responces cr ON c.responce_id = cr.id
                                
                 WHERE op.orders_id IN(". implode(',',$order_id_arr) .") 
                                                                                             
                ";

        $query = vam_db_query($sql);

        $result_data = false;

        if($query){

            $result_data = [];

            while ($result_sql = vam_db_fetch_array($query)) {

                $result_data[$result_sql['orders_id']][$result_sql['sort_order']] = $result_sql['responce_text'];

            }

        }

        return $result_data;

    }

    public function getCategoriesDataDB($cat_ids_arr, $lang_id)
    {
        $sql = "SELECT categories.categories_id
                       , categories_description.categories_name

                        FROM categories
                        
                        LEFT JOIN categories_description ON categories.categories_id = categories_description.categories_id                         
                        
                        WHERE categories.categories_id IN(". implode(',',$cat_ids_arr) .") 
                        
                        AND language_id = '{$lang_id}'    
                ";

        $query = vam_db_query($sql);

        $result_data = false;

        if($query){

            $result_data = [];

            while ($result_sql = vam_db_fetch_array($query)) {

                $result_data[$result_sql['categories_id']] = $result_sql;

            }
        }

        return $result_data;

    }

    public function getProductDatToAnalyzeDB($product_id, $lang_id, $seasons_id )
    {
        $sql = "SELECT                       
                       products.products_id 
                       ,products_description.products_name
                       ,supply_position.storehouse_id
                       ,storehouses.name AS 'storehouse_name'
                       ,storehouses.wms_flag
                       ,storehouses.dropshipping_flag
                       ,products.products_shippingtime
                
                       ,products_description.products_name AS 'commercial_name'
                
                       ,products_to_categories.categories_id
                
                       ,categories.parent_id AS 'categories_parent_id'
                       ,categories.responce_id AS 'delivery_time_code'
                       
                       ,cd.categories_name as 'parent_category'
                
                       ,category_responces.text AS 'delivery_time_text'
                
                       ,categories_description.categories_name
                
                FROM products                
                  
                    LEFT JOIN products_description ON products.products_id = products_description.products_id
                    
                    LEFT JOIN products_to_categories ON products.products_id = products_to_categories.products_id
                    
                    LEFT JOIN categories ON products_to_categories.categories_id = categories.categories_id
                    
                    LEFT JOIN categories_description ON products_to_categories.categories_id = categories_description.categories_id 

                    LEFT JOIN supply_position ON products.products_id = supply_position.products_id 

                    LEFT JOIN supply ON supply_position.supply_id = supply.supply_id 
                    
                    LEFT JOIN storehouses ON supply_position.storehouse_id = storehouses.id 
                    
                    LEFT JOIN category_responces ON categories.responce_id = category_responces.id 
                    
                    LEFT JOIN categories_description AS cd ON categories.parent_id = cd.categories_id
                
                WHERE 1

                  AND products.products_id = '{$product_id}'

                  AND supply.seasons_id = '{$seasons_id}'

                  AND products_description.language_id = '{$lang_id}'
            
                  AND categories_description.language_id = '{$lang_id}'
                  
                  AND ( cd.language_id = '{$lang_id}' OR cd.language_id IS NULL )
        ";

        $query = vam_db_query($sql);

        $result_data = false;

        if($query){

            $result_data = [];

            while ($result_sql = vam_db_fetch_array($query)) {

                $result_data[$result_sql['products_id']] = $result_sql;

            }
        }

        return $result_data;

    }

}