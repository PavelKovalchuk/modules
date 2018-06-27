<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 06.02.2018
 * Time: 17:19
 */

/**
 * Class XMLPriceDBOffersRepositoryAbstract - Базовый запрос SQL для получения информации об offer
 */
abstract class XMLPriceDBOffersRepositoryAbstract
{

    /**
     * Should return additional part for SQL request in string
     * @return string
     */
    abstract protected function getAdditionalFieldsPart();

    /**
     * Should return additional part for SQL request in string
     * @return string
     */
    abstract protected function getAdditionalJoinPart();

    /**
     * Should return additional part for SQL request in string
     * @return string
     */
    abstract protected function getAdditionalWherePart();

    public function getOffersSQlRequest()
    {
        $sql = "SELECT p.products_id AS id
                         
                         , p.products_page_url AS url
                         , p.products_price AS price
                         , p2c.categories_id AS categoryId  
                        /* , cr.text AS time_delivery*/
                         , p.products_image AS picture
                         , descr.products_name AS name
                         , descr.products_description AS description
                         
                        "
                        . $this->getAdditionalFieldsPart() .
                        "             
                                                 
                        FROM products p
                        
                        LEFT JOIN products_description AS descr
                        ON (p.products_id = descr.products_id)
                       
                        LEFT JOIN products_to_categories AS p2c 
                        ON (p.products_id = p2c.products_id)
                        
                        LEFT JOIN categories AS cc1 
                        ON (p2c.categories_id = cc1.categories_id)
                        
                        LEFT JOIN categories AS cc2 
                        ON (cc1.parent_id = cc2.categories_id)

                        "
                        . $this->getAdditionalJoinPart() .
                        "                     
                 
                        WHERE descr.language_id = '1'                        
                        
                        "
                        . $this->getAdditionalWherePart() .
                        "  
                        AND FLOOR(p.products_quantity / p.cnt_in_box) > 0
                        AND cc1.categories_status ='1'
                        AND ( cc2.categories_status ='1' OR cc2.categories_status IS NULL )                       
                        
                        GROUP BY p.products_id 
                        ORDER BY p.products_id ASC";


        return $sql;

    }

}