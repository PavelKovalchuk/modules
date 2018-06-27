<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 19.09.2017
 * Time: 15:02
 */

class AnalyticsOrdersRepository
{

    /**
     * @param $id
     * @return array|bool
     */
    public static function getUserNameByIdDb($id)
    {
        $sql = "SELECT CONCAT_WS (' ', first_name, last_name) as 'manager_f_name' FROM admin_users WHERE id = '{$id}' ";

        return self::getFormattedSqlResult($sql, false);

    }

    /**
     * Make sql query and Returns formatted data array
     *
     * @param resource $sql
     * @param string $group_name
     * @return array|bool
     */
    private static function getFormattedSqlResult($sql, $group_name = 'group_manager')
    {
        if(!$sql){
            return false;
        }

        $query = vam_db_query($sql);

        if($query){

            $result_data = [];

            while ($result_sql = vam_db_fetch_array($query)) {

                if($group_name == false){

                    return $result_sql;

                }else{

                    $result_data[$result_sql[$group_name]] = $result_sql;

                }

            }
        }

        return $result_data;
    }

    /**
     * Return all new orders and money Except orders, which was divided OR child Merged orders
     * Check for stationary_manager_id
     * @param $since
     * @param $to
     *@param $season_since
     * @param $season_to
     * @return array|bool|mixed
     */
    public static function getNewOrdersRawDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                       COUNT(orders_incoming.order_id) AS 'new_orders' /*все оформленные за выбранный период заказы за исключением разделенных*/,
                       SUM(orders_total.value) AS 'new_orders_amount' /*Оборот новых заказов за исключением разделенных*/
         
                FROM orders_incoming 
                
                LEFT JOIN orders_to_managers
                ON orders_incoming.order_id = orders_to_managers.orders_id

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders_incoming.order_id = orders_total.orders_id
                
                /*join orders table to get access to orders*/
                LEFT JOIN orders
                ON orders_incoming.order_id = orders.orders_id

                WHERE 
                      /* use orders table to filter by date*/
                     ( orders_incoming.date_created BETWEEN '{$since}' AND '{$to}' )
                     
                     /*for getting access to value of orders_total table. 
                     Check if the order has discount. 
                     If yes, return value of payment with discount. If not - total value */
              /* AND (  orders_total.class = ( 
                                      CASE 
                                      WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_incoming.order_id AND class = 'ot_discount' ))  
                                          THEN 'ot_discount'
                                      ELSE 'ot_subtotal'  
                                   END  
                                )

                    )*/
                    
               AND orders_total.class = IF( 1 = (SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_incoming.order_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
               
                /*Check if the order has the amount*/
               AND orders_total.value > 0

               /* filter by season date*/
               AND ( orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )
               
               AND orders_incoming.order_id NOT IN (
                      SELECT orders_changes.extra_order_id 
                      FROM orders_changes 
                      WHERE 
                          orders_changes.extra_order_id = orders_incoming.order_id 
                          AND orders_changes.operation_code = 2)    
               

                GROUP BY group_manager
               ";

        return self::getFormattedSqlResult($sql);

    }

    /**
     * Return all new orders, which was divided from another order, and money
     * Groups by orders_to_managers.stationary_manager_id because new order from admin panel does not have manager_id in DB
     *
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @return array
     */
    public static function getDividedOrdersWithChildrenInNewDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager', 
                       COUNT(orders_incoming.order_id) AS 'new_root_divided_orders', /*все оформленные Корневые Разделенные за выбранный период заказы*/
                       SUM(orders_total.value) AS 'new_root_divided_orders_payments' /*Оборот Корневые Разделенные */

                      /*Get amount and payments of ALL Child orders, which belongs to the new_root_divided_orders START*/  
                      ,SUM( 
                            ( SELECT SUM(orders_total.value) 
                            
                              FROM orders_changes

                              LEFT JOIN orders_total
                              ON orders_changes.extra_order_id = orders_total.orders_id

                              WHERE 
                                    orders_changes.root_order_id = orders_incoming.order_id
                                    /*AND (  orders_total.class = ( 
                                          CASE 
                                          WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_changes.extra_order_id AND class = 'ot_discount' ))  
                                              THEN 'ot_discount'
                                          ELSE 'ot_subtotal'  
                                       END  
                                       )

                                    )*/
                                    
                                    AND orders_total.class = IF( 1 = (SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_changes.extra_order_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                             
                                    AND orders_changes.extra_order_id != orders_changes.main_order_id

                            ) 
                       )AS 'all_child_divided_orders_payments'/*Оборот Дочерние Разделенные */
                     /*Get amount and payments of ALL Child orders, which belongs to the new_root_divided_orders END*/ 
                     

         
                FROM orders_incoming 

                LEFT JOIN orders_to_managers
                ON orders_incoming.order_id = orders_to_managers.orders_id 

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders_incoming.order_id = orders_total.orders_id
                
                /*join orders table to get access to orders*/
                LEFT JOIN orders
                ON orders_incoming.order_id = orders.orders_id

                WHERE 
                      /* use orders_incoming table to filter by date*/
                     ( orders_incoming.date_created BETWEEN '{$since}' AND '{$to}' )
                     
                      /*Check if the order has the amount*/
                       AND orders_total.value > 0
        
                       /* filter by season date*/
                       AND ( orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )
                     
                     /*for getting access to value of orders_total table. 
                     Check if the order has discount. 
                     If yes, return value of payment with discount. If not - total value */
                     
               /*AND (  orders_total.class = ( 
                                      CASE 
                                      WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_incoming.order_id AND class = 'ot_discount' ))  
                                          THEN 'ot_discount'
                                      ELSE 'ot_subtotal'  
                                   END  
                                )

                )*/
                    
               AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_incoming.order_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')     
                    
               /*Check that the order was dividing and the order is the root order*/
                AND orders_incoming.order_id IN (
                      SELECT orders_changes.root_order_id 
                      FROM orders_changes 
                      WHERE 
                          orders_changes.root_order_id = orders_incoming.order_id 
                          AND orders_changes.operation_code = 2)    
               
                GROUP BY group_manager
               
                ";

        return self::getFormattedSqlResult($sql);
    }

    /**
     * Returns all child merged orders which income in this period
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @return array|bool
     */
    public static function getChildMergedOrdersInNewDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager', 
                      COUNT(orders_incoming.order_id) AS 'merged_new_orders'
                                
                 FROM orders_incoming

                 LEFT JOIN orders_changes 
                 ON orders_incoming.order_id = orders_changes.extra_order_id

                 LEFT JOIN orders_to_managers
                 ON orders_changes.main_order_id = orders_to_managers.orders_id  
                 
                 /*join orders table to get access to orders*/
                LEFT JOIN orders
                ON orders_changes.main_order_id = orders.orders_id                             

                 WHERE orders_incoming.date_created BETWEEN '{$since}' AND '{$to}'
                 
                        AND orders.date_purchased BETWEEN '{$since}' AND '{$to}'
                        
                        /* filter by season date*/
                        AND ( orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )
                        
                        AND orders_changes.operation_code = 1
                        AND orders_changes.main_order_id = orders_to_managers.orders_id

                GROUP BY group_manager
               
                ";

        return self::getFormattedSqlResult($sql);
    }

    /**
     *
     * Returns all incoming orders that was canceled
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @return array|bool
     */
    public static function getCanceledOrdersInNewDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT IFNULL(orders_to_managers_canceled.stationary_manager_id, 0) AS 'group_manager', 
                        COUNT(orders_incoming.order_id) AS 'canceled_orders',  
                        SUM(orders_total_canceled.value) AS 'canceled_orders_payments'
                                
                 FROM orders_incoming
                                
                 JOIN orders_to_managers_canceled
                 ON orders_incoming.order_id = orders_to_managers_canceled.orders_id 

                  /*use orders_total_canceled table to get info about payments*/
                  LEFT JOIN orders_total_canceled
                  ON orders_to_managers_canceled.orders_id = orders_total_canceled.orders_id    
                  
                  /*join orders table to get access to orders*/
                    LEFT JOIN orders_canceled
                    ON orders_to_managers_canceled.orders_id = orders_canceled.orders_id                                       

                   WHERE orders_incoming.date_created BETWEEN '{$since}' AND '{$to}'
                   
                   AND orders_total_canceled.value > 0
                   
                    /* filter by season date*/
                   AND ( orders_canceled.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )

                   /*for getting access to value of orders_total_canceled. Check if the order has discount. If yes, return value of payment with discount. If not - total value */
                  /*AND ( orders_total_canceled.class = ( 
                                                  CASE 
                                                      WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total_canceled WHERE orders_total_canceled.orders_id = orders_to_managers_canceled.orders_id AND class = 'ot_discount' )) 
                                                          THEN 'ot_discount'
                                                      ELSE 'ot_subtotal'  
                                                   END  
                                                )
                   )*/
                         
                  AND orders_total_canceled.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total_canceled WHERE orders_total_canceled.orders_id = orders_to_managers_canceled.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                                     
                  GROUP BY group_manager
               
                ";

        return self::getFormattedSqlResult($sql);
    }


    /**
     * Return merged orders -  child orders, and money
     *
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @return array
     */
    public static function getMergedOrdersDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                        COUNT(orders_changes.extra_order_id) AS 'merged_orders', /*Присоедененные*/
                        SUM(orders_changes_total.value) AS 'merged_orders_amount'
   
                FROM orders_changes

                /*use orders_changes_total table to get info about payments*/
                LEFT JOIN orders_changes_total
                ON orders_changes.extra_order_id = orders_changes_total.order_id
                
                /*To get info about the manager in main order*/
                LEFT JOIN orders_to_managers
                ON orders_changes.main_order_id = orders_to_managers.orders_id
                
                /*join orders table to get access to orders*/
                LEFT JOIN orders
                ON orders_changes.main_order_id = orders.orders_id      

                WHERE 
                  
                   /* use orders table to filter by date*/
                  (orders_changes.operation_date BETWEEN '{$since}' AND '{$to}')
                  
                  /* filter by season date*/
                  AND ( orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' ) 
                  
                   /*Check if the order has the amount*/
                  AND orders_changes_total.value > 0
                  
                  /*Merging operation*/
                 AND orders_changes.operation_code = 1
                 
                /*for getting access to value of orders_changes_total table. 
                Check if the order has discount. 
                If yes, return value of payment with discount. 
                If not - total value */
                 /*AND ( orders_changes_total.class = ( 
                                    CASE 
                                    WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_changes_total WHERE order_id = orders_changes.extra_order_id AND class = 'ot_discount' )) 
                                        THEN 'ot_discount'
                                    ELSE 'ot_subtotal'  
                                    END  
                      )
                )*/
                
                 AND orders_changes_total.class = IF( 1 = (SELECT 1 FROM orders_changes_total WHERE order_id = orders_changes.extra_order_id AND class = 'ot_discount' ) ,'ot_discount','ot_subtotal')
                
                
                /*Check if the main order of the merged order exist START*/
                /* AND orders_changes.main_order_id IN ( SELECT orders.orders_id FROM orders WHERE orders.orders_id = orders_changes.main_order_id ) */
                /*Check if the main order of the merged order exist  END*/
  
                GROUP BY  group_manager
                ";


        return self::getFormattedSqlResult($sql);
    }


    /**
     * Returns separeted orders -  child and main orders, and money
     *
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @return array
     */
    public static function getSeparetedOrdersDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                       COUNT(orders_changes.change_id) AS 'separeted_orders' /*Разделенные*/
       
                FROM orders_changes
                
                /*To get info about the manager in main order*/
                LEFT JOIN orders_to_managers
                ON orders_changes.extra_order_id = orders_to_managers.orders_id
                
                /*join orders table to get access to orders*/
                LEFT JOIN orders
                ON orders_changes.main_order_id = orders.orders_id    
                          
                WHERE 
                    /* use orders table to filter by date*/
                    (orders_changes.operation_date BETWEEN '{$since}' AND '{$to}')
                    
                    /* filter by season date*/
                    AND ( orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )
                    
                    AND orders_changes.operation_code = 2   
                     /*Check if the order was not deleted(canceled) START*/
                    AND orders_changes.extra_order_id IN ( SELECT orders.orders_id FROM orders WHERE orders.orders_id = orders_changes.extra_order_id )
                    /*Check if the order was not deleted(canceled) END*/ 
                
                GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }

    /**
     * Returns simple confirmed orders
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @param $data_not_confirmed_statuses - array of integers
     * @return array|bool
     */
    public static function getConfirmedOrdersRawDb($since, $to, $season_since, $season_to, $data_not_confirmed_statuses)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                       COUNT(orders.orders_id) AS 'confirmed_orders', /* Только Подтвержденные*/
                       SUM(orders_total.value) AS 'confirmed_orders_amount' /*Оборот Подтвержденные*/
                                              
                FROM orders 
                
                LEFT JOIN orders_to_managers
                ON orders.orders_id = orders_to_managers.orders_id
                
                /*join orders_total table to get access to price in orders*/
                LEFT JOIN orders_total
                ON orders.orders_id = orders_total.orders_id
               
                WHERE 
                     /* use orders table to filter by date*/
                    ( orders.date_purchased BETWEEN '{$since}' AND '{$to}')
                    
                    /* filter by season date*/
                    /*AND ( orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )*/
                    
                    AND orders_total.value > 0
                    
                     /*for getting access to value of orders_total table. Check if the order has discount. If yes, return value of payment with discount. If not - total value */
                     
                    /* AND (  orders_total.class = ( CASE 
                                                      WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders.orders_id AND class = 'ot_discount' )) 
                                                          THEN 'ot_discount'
                                                      ELSE 'ot_subtotal'  
                                                   END  
                                                )
                
                     )*/
                     
                     AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                     
                     /*Get orders with specific statuses START*/
                     AND orders.orders_status NOT IN (". implode(',',$data_not_confirmed_statuses) .")
                     /*Get orders with specific statuses END*/
                
                     /*Check that the order was NOT dividing and the order is the root order*/
                      AND orders.orders_id NOT IN (
                          SELECT orders_changes.extra_order_id 
                          FROM orders_changes 
                          WHERE 
                            orders_changes.extra_order_id = orders.orders_id
                            AND orders_changes.operation_code = 2)           
                
                GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }


    /**
     * Returns all child merged orders which belongs to the Confirmed
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     *@param $data_not_confirmed_statuses - array of integers
     * @return array|bool
     */
    public static function getChildMergedOrdersInConfirmedDb($since, $to, $season_since, $season_to, $data_not_confirmed_statuses)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager', 
                      COUNT(orders_changes.extra_order_id) AS 'merged_confirmed_orders'
                                
                 FROM orders 
                
                /* join orders table to get date of order*/
                LEFT JOIN orders_to_managers
                ON orders.orders_id = orders_to_managers.orders_id
                                
                /*join merged orders to confirmed orders. Because some orders have merged (added) orders in theirs history*/
                LEFT JOIN orders_changes
                ON orders.orders_id = orders_changes.main_order_id                  

                 WHERE (orders.date_purchased BETWEEN '{$since}' AND '{$to}')
                 
                        /* filter by season date*/
                        /* AND ( orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )*/
                 
                        AND orders_changes.operation_code = 1

                        /*Get orders with specific statuses */
                        AND orders.orders_status NOT IN (".implode(',',$data_not_confirmed_statuses).")

                        AND orders_changes.main_order_id = orders.orders_id

                GROUP BY group_manager
               
                ";

        return self::getFormattedSqlResult($sql);
    }

    /**
     * Returns Child orders, which was divided from the Confirmed Orders
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @param $data_not_confirmed_statuses - array of integers
     * @return array|bool
     */
    public static function getDividedOrdersWithChildrenInConfirmedDb($since, $to, $season_since, $season_to, $data_not_confirmed_statuses)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager', 
                       COUNT(orders.orders_id) AS 'confirmed_root_divided_orders', /*все оформленные Подтвержденные Корневые Разделенные за выбранный период заказы*/
                       SUM(orders_total.value) AS 'confirmed_root_divided_orders_payments' /*Оборот Подтвержденные Корневые Разделенные */

                      /*Get amount and payments of ALL Child orders, which belongs to the confirmed_root_divided_orders START*/  
                      ,SUM( 
                            ( SELECT SUM(orders_total.value) 
                            
                              FROM orders_changes

                              LEFT JOIN orders_total
                              ON orders_changes.extra_order_id = orders_total.orders_id

                              WHERE 
                                    orders_changes.root_order_id = orders.orders_id
                                    
                                   /* AND (  orders_total.class = ( 
                                          CASE 
                                          WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_changes.extra_order_id AND class = 'ot_discount' ))  
                                              THEN 'ot_discount'
                                          ELSE 'ot_subtotal'  
                                       END  
                                       )

                                    )*/
                                    AND orders_total.value > 0
                                    
                                    AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_changes.extra_order_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                             
                                    AND orders_changes.extra_order_id != orders_changes.main_order_id

                            ) 
                       )AS 'all_child_divided_orders_payments'/*Оборот Дочерние Разделенные */
                     /*Get amount and payments of ALL Child orders, which belongs to the confirmed_root_divided_orders END*/ 
                     

         
                FROM orders

                /* join orders table to get date of order*/
                LEFT JOIN orders_to_managers
                ON orders.orders_id = orders_to_managers.orders_id

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders.orders_id = orders_total.orders_id

                WHERE 
                      /* filter by date*/
                     ( orders.date_purchased BETWEEN '{$since}' AND '{$to}' )
                     
                     /* filter by season date*/
                    /*AND ( orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )*/
                    
                    AND orders_total.value > 0
                     
                     /*for getting access to value of orders_total table. 
                     Check if the order has discount. 
                     If yes, return value of payment with discount. If not - total value */
                     
               /*AND (  orders_total.class = ( 
                                      CASE 
                                      WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders.orders_id AND class = 'ot_discount' ))  
                                          THEN 'ot_discount'
                                      ELSE 'ot_subtotal'  
                                   END  
                                )

               )*/
                    
               AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                    
                /*Get orders with specific statuses START*/
                AND orders.orders_status NOT IN (".implode(',',$data_not_confirmed_statuses).")
                /*Get orders with specific statusesEND*/   
                    
               /*Check that the order was dividing and the order is the root order*/
                AND orders.orders_id IN (
                      SELECT orders_changes.root_order_id 
                      FROM orders_changes 
                      WHERE 
                          orders_changes.root_order_id = orders.orders_id 
                          AND orders_changes.operation_code = 2)    
               
                GROUP BY group_manager
               
                ";

        return self::getFormattedSqlResult($sql);
    }

    /**
     * Returns all canceled Orders (NOT Duplicated)
     * @param $since
     * @param $to
     * @param $season_since
     * @param $season_to
     * @return array|bool
     */

    public static function getCanceledOrdersDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT a.group_manager AS 'group_manager' 
                , COUNT(a.canceled_orders) AS 'canceled_orders' 
                , SUM(a.canceled_orders_amount) AS 'canceled_orders_amount'  
                    FROM (
                        SELECT IFNULL(orders_to_managers_canceled.stationary_manager_id,  0) AS 'group_manager'
                               ,COUNT(orders_canceled.orders_id) AS 'canceled_orders'
                              ,orders_total_canceled.value AS 'canceled_orders_amount'
                               
                        FROM orders_canceled
                        
                        /*to get info about  manager START*/
                        LEFT JOIN orders_to_managers_canceled
                        ON orders_canceled.orders_id = orders_to_managers_canceled.orders_id
                        /*to get info about real manager START*/
                        
                        /*use canceled_orders_log table to get info about dates */
                        LEFT JOIN canceled_orders_log
                        ON orders_to_managers_canceled.orders_id = canceled_orders_log.orders_id
                        
                        /*use orders_total_canceled table to get info about payments*/
                        LEFT JOIN orders_total_canceled
                        ON orders_to_managers_canceled.orders_id = orders_total_canceled.orders_id
                        
                        
                        WHERE
                             /* filter by date*/
                             (canceled_orders_log.time_stamp BETWEEN '{$since}' AND '{$to}')
                             
                             /* filter by season date*/
                             AND (orders_canceled.date_purchased BETWEEN '{$season_since}' AND '{$season_to}')
                             
                             /* exclude orders which was restored and are living now*/
                             AND orders_to_managers_canceled.orders_id NOT IN (
                               SELECT orders.orders_id FROM orders WHERE orders.orders_id = orders_canceled.orders_id
                              )
        
                             /* Only orders with products*/
                             AND orders_total_canceled.value > 0
                        
                             /*for getting access to value of orders_total_canceled. Check if the order has discount. If yes, return value of payment with discount. If not - total value */
                             
                           /*  AND ( orders_total_canceled.class = ( 
                                                          CASE 
                                                              WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total_canceled WHERE orders_total_canceled.orders_id = orders_to_managers_canceled.orders_id AND class = 'ot_discount' )) 
                                                                  THEN 'ot_discount'
                                                              ELSE 'ot_subtotal'  
                                                           END  
                                                        )
                                 )*/
                                 
                             AND orders_total_canceled.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total_canceled WHERE orders_total_canceled.orders_id = orders_to_managers_canceled.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                             
                        
                          GROUP BY orders_canceled.orders_id
        
                         /*Not duplicated orders*/
                         HAVING ( canceled_orders = 1) 
                  ) AS a 
                 GROUP BY group_manager 

                ";


        return self::getFormattedSqlResult($sql);
    }

    /**
     * Returns all canceled Duplicated Orders
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @return array|bool
     */
    public static function getCanceledOrdersDuplicatedDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT a.group_manager AS 'group_manager', COUNT(a.canceled_orders) AS 'duplicated_canceled_orders', SUM(a.canceled_orders_amount) AS 'duplicated_canceled_orders_amount'  FROM (
            
                    SELECT IFNULL(orders_to_managers_canceled.stationary_manager_id,  0) AS 'group_manager'
                              ,orders_canceled.orders_id AS 'canceled_orders'
                              
                              ,orders_total_canceled.value AS 'canceled_orders_amount'
                               
                        FROM orders_canceled
                        
                        /*to get info about  manager START*/
                        LEFT JOIN orders_to_managers_canceled
                        ON orders_canceled.orders_id = orders_to_managers_canceled.orders_id
                        /*to get info about real manager START*/
                        
                        /*use canceled_orders_log table to get info about dates */
                        LEFT JOIN canceled_orders_log
                        ON orders_to_managers_canceled.orders_id = canceled_orders_log.orders_id
                        
                        /*use orders_total_canceled table to get info about payments*/
                        LEFT JOIN orders_total_canceled
                        ON orders_to_managers_canceled.orders_id = orders_total_canceled.orders_id
        
                        WHERE
                             /* filter by date*/
                             (canceled_orders_log.time_stamp BETWEEN '{$since}' AND '{$to}' )
        
                             
                             /* filter by season date*/
                             AND (orders_canceled.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )
        
                             /* exclude orders which was restored and are living now*/
                             AND orders_to_managers_canceled.orders_id NOT IN (
                               SELECT orders.orders_id FROM orders WHERE orders.orders_id = orders_canceled.orders_id
                              )
        
                             /* Only orders with products*/
                             AND orders_total_canceled.value > 0
        
        
                             AND orders_total_canceled.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total_canceled WHERE orders_total_canceled.orders_id = orders_to_managers_canceled.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                      
                 GROUP BY orders_canceled.orders_id  
                 /*Get orders which are duplicated*/
                  HAVING ( COUNT(orders_canceled.orders_id) > 1 )
            
            
            ) AS a GROUP BY group_manager       
        ";


        return self::getFormattedSqlResult($sql);
    }


    /**
     *
     * Returns restored Orders from unfinished and deleted (NOT DUPLICATED)
     * @param $since
     * @param $to
     * *@param $season_since
     * @param $season_to
     * @return array|bool
     */
    public static function getRestoredOrdersOriginalDb($since, $to, $season_since, $season_to)
    {
        $sql = "
                SELECT a.group_manager AS 'group_manager', 
                COUNT(a.restored_orders) AS 'restored_orders', 
                SUM(a.restored_orders_amount) AS 'restored_orders_amount'  
              FROM (
              
                        SELECT IFNULL(orders_to_managers.stationary_manager_id, 0)  AS 'group_manager',
                               orders.orders_id AS 'restored_orders', /*восстановленные из незавершенных*/
                               orders_total.value AS 'restored_orders_amount'
                               
                        FROM orders
                        
                        LEFT JOIN orders_changes
                        ON orders.orders_id = orders_changes.main_order_id
        
                        /*to get info about manager START*/                LEFT JOIN orders_to_managers
                        ON orders_changes.main_order_id = orders_to_managers.orders_id
                        /*to get info about  manager START*/
                       
                        /*use orders_total table to get info about payments*/
                        LEFT JOIN orders_total
                        ON orders_to_managers.orders_id = orders_total.orders_id
                                        
                        WHERE 
                              /* filter by date*/
                              (orders_changes.operation_date BETWEEN '{$since}' AND '{$to}')
                              
                              /* filter by season date*/
                              AND (orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )
                              
                              /* filter by season date*/
                              AND (orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )
                        
                             /*for getting access to value of orders_total. Check if the order has discount. If yes, return value of payment with discount. If not - total value */
                             
                             /*AND ( orders_total.class = ( 
                                                          CASE 
                                                              WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_total.orders_id = orders_to_managers.orders_id AND class = 'ot_discount' )) 
                                                                    THEN 'ot_discount'
                                                              ELSE 'ot_subtotal'  
                                                           END  
                                                        )
                              )*/
                              
                              AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_total.orders_id = orders_to_managers.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                              
                             /*Get orders which was restored START*/
                             AND ( orders_changes.operation_code = 3 OR orders_changes.operation_code = 4)
                            /*Get orders which was was restored  END*/
                            
                            /*Check if the order has the amount*/
                              AND orders_total.value > 0
                            
                            /* Get not duplicated orders */
                              GROUP BY restored_orders 
                              HAVING(COUNT(orders_changes.main_order_id) = 1 ) 
                 ) AS a 
                GROUP BY group_manager
                ";

        return self::getFormattedSqlResult($sql);
    }

    /**
     * Returns restored Orders from unfinished and deleted (DUPLICATED)
     * @param $since
     * @param $to
     * @param $season_since
     * @param $season_to
     * @return array|bool
     */

    public static function getRestoredOrdersDuplicatedDb($since, $to, $season_since, $season_to)
    {
        $sql = "

              SELECT a.group_manager AS 'group_manager', 
                COUNT(a.duplicated_restored_orders) AS 'duplicated_restored_orders', 
                SUM(a.duplicated_restored_orders_amount) AS 'duplicated_restored_orders_amount'  
              FROM (
              
                   SELECT IFNULL(orders_to_managers.stationary_manager_id, 0)  AS 'group_manager',
                           orders.orders_id AS 'duplicated_restored_orders', /*восстановленные из незавершенных*/
                           orders_total.value AS 'duplicated_restored_orders_amount'
                           
                    FROM orders
                    
                    LEFT JOIN orders_changes
                    ON orders.orders_id = orders_changes.main_order_id
    
                    /*to get info about manager START*/
                    LEFT JOIN orders_to_managers
                    ON orders_changes.main_order_id = orders_to_managers.orders_id
                    /*to get info about  manager START*/
                   
                    /*use orders_total table to get info about payments*/
                    LEFT JOIN orders_total
                    ON orders_to_managers.orders_id = orders_total.orders_id
                                    
                    WHERE 
                          /* filter by date*/
                          (orders_changes.operation_date BETWEEN '{$since}' AND '{$to}')
                          
                          /* filter by season date*/
                          AND (orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' )
                          
                    
                         /*for getting access to value of orders_total. Check if the order has discount. If yes, return value of payment with discount. If not - total value */
                         
                         /*AND ( orders_total.class = ( 
                                                      CASE 
                                                          WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_total.orders_id = orders_to_managers.orders_id AND class = 'ot_discount' )) 
                                                                THEN 'ot_discount'
                                                          ELSE 'ot_subtotal'  
                                                       END  
                                                    )
                          )*/
                          
                          AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_total.orders_id = orders_to_managers.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                          
                         /*Get orders which was restored START*/
                         AND ( orders_changes.operation_code = 3 OR orders_changes.operation_code = 4)
                        /*Get orders which was was restored  END*/
                        
                        /*Check if the order has the amount*/
                        AND orders_total.value > 0
                        
                        /* Get duplicated orders */
                        GROUP BY duplicated_restored_orders 
                       HAVING(COUNT(orders_changes.main_order_id) > 1 )  
                
                   ) AS a 
                GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }

    /**
     *
     * Returns orders, which was created from admin panel. Excluding divided orders and their payments
     * @param $since
     * @param $to
     * @return array|bool
     */
       public static function getPhoneOrdersRawDb($since, $to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                       COUNT(orders_attracted_managers.orders_id) AS 'phone_orders', /*Заказы через админку (телефон)*/
                       SUM(orders_total.value) AS 'phone_orders_amount'
                       /*Get Guarantee payments START*/
                       , SUM( 
                            (SELECT SUM(orders_total.value) 
                            
                              FROM orders_total
                              
                              WHERE 
                                    orders_total.orders_id = orders_attracted_managers.orders_id

                                    AND orders_total.class = 'ot_guarantee'
                             
                                    /* AND orders_changes.extra_order_id = orders_changes.main_order_id */

                            ) 
                         )
                       AS 'phone_orders_guarantee' 
                       /*Get Guarantee payments END*/
                       
                FROM orders_attracted_managers
                
                /*use orders_total table to get info about payments*/
                LEFT JOIN orders_total
                ON orders_attracted_managers.orders_id = orders_total.orders_id
                
                 /*To get info about the manager in main order*/
                LEFT JOIN orders_to_managers
                ON orders_attracted_managers.orders_id = orders_to_managers.orders_id
                
                WHERE 
                      /* filter by date*/
                      (orders_attracted_managers.date_attract BETWEEN '{$since}' AND '{$to}')
                
                     /*for getting access to value of orders_total. Check if the order has discount. If yes, return value of payment with discount. If not - total value */
                     
                    /* AND ( orders_total.class = ( 
                                                  CASE 
                                                      WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_total.orders_id = orders_attracted_managers.orders_id AND class = 'ot_discount' ))  
                                                          THEN 'ot_discount'
                                                      ELSE 'ot_subtotal'  
                                                   END  
                                                )
                      )*/
                      
                      AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_total.orders_id = orders_attracted_managers.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                                               
                     /*Get orders which was created from the admin panel START*/
                     AND orders_attracted_managers.atype = 1
                    /*Get orders which was created from the admin panel  END*/
                    
                    AND orders_total.value > 0

                    AND orders_attracted_managers.orders_id NOT IN (
                      SELECT orders_changes.extra_order_id 
                      FROM orders_changes 
                      WHERE 
                          orders_changes.extra_order_id = orders_attracted_managers.orders_id 
                          AND orders_changes.operation_code = 2
                    )   
                     
                  
                
                GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }

    /**
     * Returns array : All Root orders, which was divided, their payments, their amount of guarantee;
     * and the same data of ALL Children orders/
     *
     * @param $since
     * @param $to
     * @return array|bool
     */
    public static function getPhoneDividedOrdersWithChildrenDb($since, $to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager', 
                       COUNT(orders_attracted_managers.orders_id) AS 'phone_root_divided_orders', /*все Телефонные Корневые Разделенные за выбранный период заказы*/
                       SUM(orders_total.value) AS 'phone_root_divided_orders_payments' /*Оборот Телефонные Корневые Разделенные */

                       /*Get guarantee payments of phone_root_divided_orders START*/ 
                      , ( SELECT SUM(orders_total.value) 
                            
                              FROM orders_total
                              
                              WHERE 
                                    orders_total.orders_id = orders_attracted_managers.orders_id

                                    AND orders_total.class = 'ot_guarantee'
                             
                                    /* AND orders_changes.extra_order_id = orders_changes.main_order_id */

                            ) 
                       AS 'phone_root_divided_orders_guarantee' 
                       /*Get guarantee payments of phone_root_divided_orders END*/ 

                      /*Get  payments of ALL Child orders, which belongs to the phone_root_divided_orders START*/  
                      , SUM( ( SELECT SUM(orders_total.value) 
                            
                              FROM orders_changes

                              LEFT JOIN orders_total
                              ON orders_changes.extra_order_id = orders_total.orders_id

                              WHERE 
                                    orders_changes.root_order_id = orders_attracted_managers.orders_id
                                    
                                    /*AND (  orders_total.class = ( 
                                          CASE 
                                          WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_changes.extra_order_id AND class = 'ot_discount' ))  
                                              THEN 'ot_discount'
                                          ELSE 'ot_subtotal'  
                                       END  
                                       )

                                    )*/
                                    
                                    AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_changes.extra_order_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                             
                                    AND orders_changes.extra_order_id != orders_changes.main_order_id

                            ) )
                       AS 'all_phone_child_divided_orders_payments'   /*Оборот Телефонные Дочерние Разделенные */
                     /*Get payments of ALL Child orders, which belongs to the phone_root_divided_orders END*/ 
                     
                     
                     /*Get guarantee payments of ALL Child orders, which belongs to the phone_root_divided_orders START*/  
                      , SUM( ( SELECT SUM(orders_total.value) 
                            
                              FROM orders_changes

                              LEFT JOIN orders_total
                              ON orders_changes.extra_order_id = orders_total.orders_id

                              WHERE 
                                    orders_changes.root_order_id = orders_attracted_managers.orders_id

                                    AND orders_total.class = 'ot_guarantee'
                             
                                    AND orders_changes.extra_order_id != orders_changes.main_order_id

                            ) )
                       AS 'all_child_divided_orders_guarantee'   /*Оборот Дочерние Разделенные */
                     /*Get guarantee payments of ALL Child orders, which belongs to the phone_root_divided_orders END*/ 
                     

         
                FROM orders_attracted_managers 

                /* join orders table to get date of order*/
                LEFT JOIN orders
                ON orders_attracted_managers.orders_id = orders.orders_id

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders_attracted_managers.orders_id = orders_total.orders_id
                
                /*To get info about the manager in main order*/
                LEFT JOIN orders_to_managers
                ON orders_attracted_managers.orders_id = orders_to_managers.orders_id

                WHERE 
                      /* use orders table to filter by date*/
                     ( orders.date_purchased BETWEEN '{$since}' AND '{$to}' )
                     
                     /*for getting access to value of orders_total table. 
                     Check if the order has discount. 
                     If yes, return value of payment with discount. If not - total value */
                     
              /* AND (  orders_total.class = ( 
                                      CASE 
                                      WHEN 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_attracted_managers.orders_id AND class = 'ot_discount' ))  
                                          THEN 'ot_discount'
                                      ELSE 'ot_subtotal'  
                                   END  
                                )

                )*/
                
                AND orders_total.class = IF( 1 = ( SELECT EXISTS(SELECT 1 FROM orders_total WHERE orders_id = orders_attracted_managers.orders_id AND class = 'ot_discount' ) ) ,'ot_discount','ot_subtotal')
                
                /*Get orders which was created from the admin panel START*/
                AND orders_attracted_managers.atype = 1
                /*Get orders which was created from the admin panel  END*/
                
                /*Check if the order has the amount*/
                AND orders_total.value > 0

                AND orders_attracted_managers.orders_id IN (
                      SELECT orders_changes.root_order_id 
                      FROM orders_changes 
                      WHERE 
                          orders_changes.root_order_id = orders_attracted_managers.orders_id 
                          AND orders_changes.operation_code = 2)    
               
                GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }



    /**
     * Returns phone orders, which have guarantee payments
     * @param $since
     * @param $to
     * @return array|bool
     */
    public static function getPhoneOrdersGuaranteeDb($since, $to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                       COUNT(orders_attracted_managers.orders_id) AS 'phone_orders_with_guarantee', /*Заказы через админку (телефон)*/
                       SUM(orders_total.value) AS 'phone_orders_with_guarantee_payments'
                       
                FROM orders_attracted_managers
                
                /*use orders_total table to get info about payments*/
                LEFT JOIN orders_total
                ON orders_attracted_managers.orders_id = orders_total.orders_id
                
                /*To get info about the manager in main order*/
                LEFT JOIN orders_to_managers
                ON orders_attracted_managers.orders_id = orders_to_managers.orders_id
                
                WHERE 
                      /* filter by date*/
                     (orders_attracted_managers.date_attract BETWEEN '{$since}' AND '{$to}')
                
                    /*for getting access to value of orders_total guarantee */
                     AND orders_total.class = 'ot_guarantee'
                     /*Get orders which was created from the admin panel START*/
                     AND orders_attracted_managers.atype = 1
                    /*Get orders which was created from the admin panel  END*/
                    
                    /*Check if the order has the amount*/
                     AND orders_total.value > 0
                
                GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }


    /**
     * Returns orders, which was not divided and was created in the admin panel and have guarantee
     * @param $since
     * @param $to
     * @return array|bool
     */
    public static function getGuaranteeOrdersRawDb($since, $to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0)  AS 'group_manager', 
                       COUNT(orders_attracted_managers.orders_id) AS 'guarantee_orders', /*все Простые заказы с гарантией*/
                       SUM(orders_total.value) AS 'guarantee_orders_amount' /*Оборот Простых заказов с гарантией*/

                FROM orders_attracted_managers 

                /* join orders table to get date of order*/
                LEFT JOIN orders
                ON orders_attracted_managers.orders_id = orders.orders_id

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders_attracted_managers.orders_id = orders_total.orders_id
                
                /*To get info about the manager in main order*/
                LEFT JOIN orders_to_managers
                ON orders_attracted_managers.orders_id = orders_to_managers.orders_id

                WHERE 
                      /* use orders table to filter by date*/
                     ( orders.date_purchased BETWEEN '{$since}' AND '{$to}' )
                     
                     /* for getting access to value of orders_total guarantee */
                     AND orders_total.class = 'ot_guarantee'

                     /*Get orders which was created from the admin panel START*/
                     AND orders_attracted_managers.atype = 1
                    /*Get orders which was created from the admin panel  END*/
                    
                     /*Check if the order has the amount*/
                     AND orders_total.value > 0
                    
                    /*Check if the order was not divided*/
                    AND orders_attracted_managers.orders_id NOT IN (
                          SELECT orders_changes.root_order_id 
                          FROM orders_changes 
                          WHERE 
                              orders_changes.root_order_id = orders_attracted_managers.orders_id 
                              AND orders_changes.operation_code = 2)    
               
                GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }


    /**
     * Returns orders, which was divided and was created in the admin panel and have guarantee
     * @param $since
     * @param $to
     * @return array|bool
     */
    public static function getGuaranteeDividedOrdersWithChildrenDb($since, $to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager', 
                       COUNT(orders_attracted_managers.orders_id) AS 'guarantee_root_divided_orders', /*все Корневые Разделенные с гарантией*/
                       SUM(orders_total.value) AS 'guarantee_root_divided_payments' /*Гарантия Корневых Разделенных с гарантией заказов*/

                 /*Get guarantee payments of ALL Child orders, which belongs to the guarantee_root_divided_orders START*/  
                      , SUM( ( SELECT SUM(orders_total.value) 
                            
                              FROM orders_changes

                              LEFT JOIN orders_total
                              ON orders_changes.extra_order_id = orders_total.orders_id

                              WHERE 
                                    orders_changes.root_order_id = orders_attracted_managers.orders_id

                                    AND orders_total.class = 'ot_guarantee'
                             
                                    AND orders_changes.extra_order_id != orders_changes.main_order_id

                            ) )
                       AS 'all_guarantee_child_divided_orders'   /*Оборот Дочерние Разделенные */

                     /*Get guarantee payments of ALL Child orders, which belongs to the guarantee_root_divided_orders END*/  


                FROM orders_attracted_managers 

                /* join orders table to get date of order*/
                LEFT JOIN orders
                ON orders_attracted_managers.orders_id = orders.orders_id

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders_attracted_managers.orders_id = orders_total.orders_id
                
                /*To get info about the manager in main order*/
                LEFT JOIN orders_to_managers
                ON orders_attracted_managers.orders_id = orders_to_managers.orders_id

                WHERE 
                      /* use orders table to filter by date*/
                     ( orders.date_purchased BETWEEN '{$since}' AND '{$to}' )
                     
                     /*for getting access to value of orders_total guarantee */
                     AND orders_total.class = 'ot_guarantee'

                /*Get orders which was created from the admin panel START*/
                AND orders_attracted_managers.atype = 1
                /*Get orders which was created from the admin panel  END*/
                
                /*Check if the order has the amount*/
                AND orders_total.value > 0

                AND orders_attracted_managers.orders_id IN (
                      SELECT orders_changes.root_order_id 
                      FROM orders_changes 
                      WHERE 
                          orders_changes.root_order_id = orders_attracted_managers.orders_id 
                          AND orders_changes.operation_code = 2)    
               
                GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }



    /**
     * Return all orders and money with Coupon. Except orders, which was divided
     * Check for stationary_manager_id
     *
     * @return array|bool|mixed
     */
    public static function getCouponOrdersRawDb($since, $to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                       COUNT(orders.orders_id) AS 'coupon_orders', /*заказы с использованием кода купона партнерской программы*/
                       SUM(orders_total.value) AS 'coupon_orders_amount' /*их оборот*/

                FROM orders 

               /* join orders table to get date of order*/
                LEFT JOIN orders_to_managers
                ON orders.orders_id = orders_to_managers.orders_id 

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders.orders_id = orders_total.orders_id

                /*join coupon table to get access to orders, which has coupon*/
                LEFT JOIN coupons
                ON orders.orders_id = coupons.order_id
               
                WHERE 
                      /* use orders table to filter by date*/
                     ( orders.date_purchased BETWEEN '{$since}' AND '{$to}' )
                 
                       AND coupons.order_id IS NOT NULL
                      
                       AND orders_total.class = 'ot_discount'
        
                       /*Check if the order was not divided*/
                       AND orders.orders_id NOT IN (
                              SELECT orders_changes.root_order_id 
                              FROM orders_changes 
                              WHERE 
                                  orders_changes.root_order_id = orders.orders_id
                                  AND orders_changes.operation_code = 2)    
        
                      /* CHeck if order was created with coupon */
                       AND orders.orders_id = coupons.order_id
                       
                       /*Check if the order has the amount*/
                        AND orders_total.value > 0
               
               GROUP BY group_manager
               ";

        return self::getFormattedSqlResult($sql);

    }

    /**
     * Returns merged orders which child has coupon
     * @param $since
     * @param $to
     * @return array|bool
     */
    public static function getCouponOrdersWithMergedRawDb($since, $to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                        COUNT(orders.orders_id) AS 'coupon_merged_orders', /*заказы к которым присоединили заказы с использованием купона ПП*/
                        SUM(orders_total.value) AS 'coupon_merged_orders_payments' /*их оборот*/
      
                FROM orders 
               
                LEFT JOIN orders_to_managers
                ON orders.orders_id = orders_to_managers.orders_id 

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders.orders_id= orders_total.orders_id

                /*To get order which has merged order with coupon*/
                LEFT JOIN orders_changes
                ON orders.orders_id = orders_changes.main_order_id

                /*join coupon table to get access to orders, which has coupon. Check by Child merged order*/
                LEFT JOIN coupons
                ON orders_changes.extra_order_id = coupons.order_id

                WHERE 
                      /* use orders table to filter by date*/
                     ( orders.date_purchased BETWEEN '{$since}' AND '{$to}' )

                       AND coupons.order_id IS NOT NULL
                      
                       AND orders_total.class = 'ot_discount'
        
                       /*Check if the order was not divided */
                       AND orders.orders_id NOT IN (
                              SELECT orders_changes.root_order_id 
                              FROM orders_changes 
                              WHERE 
                                  orders_changes.root_order_id = orders.orders_id 
                                  AND orders_changes.operation_code = 2)   
         
                       /* Get info by the child merged order*/ 
                       AND orders_changes.extra_order_id = coupons.order_id
                       
                       /*Check if the order has the amount*/
                        AND orders_total.value > 0
                      
                       /* Only in the merged operation*/
                       AND orders_changes.operation_code = 1
                       
                GROUP BY group_manager

               ";

        return self::getFormattedSqlResult($sql);

    }


    public static function getCouponDividedOrdersWithChildrenDb($since, $to)
    {
        $sql = "SELECT IFNULL(orders_to_managers.stationary_manager_id, 0) AS 'group_manager',
                      COUNT(orders.orders_id) AS 'coupon_divided_root_orders', /*заказы с использованием купона ПП, от которых отсоединили заказы */
                      SUM(orders_total.value) AS 'coupon_divided_root_orders_payments' /*их оборот*/

                      /*Get payments of ALL Child orders, which belongs to the divided_root_coupon_orders START*/  
                      , SUM( ( SELECT SUM(orders_total.value) 
                            
                              FROM orders_changes

                              LEFT JOIN orders_total
                              ON orders_changes.extra_order_id = orders_total.orders_id

                              WHERE 
                                    orders_changes.root_order_id = orders.orders_id

                                    AND orders_total.class = 'ot_discount'
                             
                                    AND orders_changes.extra_order_id != orders_changes.main_order_id

                            ) )
                       AS 'all_coupon_child_divided_orders'   /*Оборот Дочерние Разделенные с Купоном*/

                     /*Get payments of ALL Child orders, which belongs to the divided_root_coupon_orders END*/  
          
      
                FROM orders

                LEFT JOIN orders_to_managers
                ON orders.orders_id = orders_to_managers.orders_id

                /*join orders_total table to get access to payments in orders*/
                LEFT JOIN orders_total
                ON orders.orders_id = orders_total.orders_id

                /*join coupon table to get access to orders, which has coupon. Check by Child merged order*/
                LEFT JOIN coupons
                ON orders.orders_id = coupons.order_id

                WHERE 
                      /* use orders table to filter by date*/
                     ( orders.date_purchased BETWEEN '{$since}' AND '{$to}' )
                     
                       AND coupons.order_id IS NOT NULL
                      
                       AND orders_total.class = 'ot_discount'
                       
                       /*Check if the order has the amount*/
                        AND orders_total.value > 0
        
                       /*Check if the order was divided */
                       AND orders.orders_id IN (
                              SELECT orders_changes.root_order_id 
                              FROM orders_changes 
                              WHERE 
                                  orders_changes.root_order_id = orders.orders_id 
                                  AND orders_changes.operation_code = 2)   
 
                GROUP BY group_manager


               ";

        return self::getFormattedSqlResult($sql);

    }

    /**
     * Returns amount of attracted money
     * @param $since
     * @param $to
     * @return array|bool
     */
    public static function getAttractedAmountDb($since, $to, $season_since, $season_to)
    {
        $sql = "SELECT IFNULL(orders_attracted_managers.manager_id, 0) AS 'group_manager', 
                       SUM(orders_attracted_managers_log.asum) AS 'attract_amount'
                 
                 FROM orders_attracted_managers_log

                 LEFT JOIN orders_attracted_managers
                 ON orders_attracted_managers_log.id_parent = orders_attracted_managers.id
                 
                 /*join orders table to get access to orders*/
                LEFT JOIN orders
                ON orders_attracted_managers.orders_id = orders.orders_id
                
                 WHERE 
                        (orders_attracted_managers_log.edate BETWEEN '{$since}' AND '{$to}')
                      
                        /* ManagersAttractToOrders TYPE_ATTRACT_ORDER_EDITED */
                        AND orders_attracted_managers.atype = 2
                        
                         /* filter by season date*/
                         AND (orders.date_purchased BETWEEN '{$season_since}' AND '{$season_to}' ) 
                        

                 GROUP BY group_manager

                ";

        return self::getFormattedSqlResult($sql);
    }





}