<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 14:25
 */

require_once(XMLPrice_REPOSITORY_DIR . 'XMLPriceDBOffersRepositoryAbstract.php');

class GoogleOffersRepository extends XMLPriceDBOffersRepositoryAbstract
{
    protected function getAdditionalFieldsPart()
    {
        return;
    }

    protected function getAdditionalJoinPart()
    {
        return;
    }

    protected function getAdditionalWherePart()
    {
        return;
    }

    /**
     * Переопределяем метод, т.к. нужен другой sql запрос
     */

    public function getOffersSQlRequest()
    {

        $sql = "SELECT p.products_id AS id
                         
                         , p.products_page_url AS url
                         , p.products_price AS price  
                         , p.products_image AS picture
                         , descr.products_name AS name
                         , descr.products_description AS description
                         , IF( (p.products_status = '1' AND FLOOR(p.products_quantity / p.cnt_in_box) > 0), 'true', 'false' )  AS available
                                                                   
                        FROM products p
                        
                        LEFT JOIN products_description AS descr
                        ON (p.products_id = descr.products_id)
                       
                        LEFT JOIN products_to_categories AS p2c 
                        ON (p.products_id = p2c.products_id)
                        
                        LEFT JOIN categories AS cc1 
                        ON (p2c.categories_id = cc1.categories_id)
                        
                        LEFT JOIN categories AS cc2 
                        ON (cc1.parent_id = cc2.categories_id)
  
                        WHERE descr.language_id = '1'                        
                        AND p.products_price > 0                        
                        AND cc1.categories_status ='1'
                        AND ( cc2.categories_status ='1' OR cc2.categories_status IS NULL )                       
                        
                        GROUP BY p.products_id 
                        ORDER BY p.products_id ASC";

        return $sql;

    }

}