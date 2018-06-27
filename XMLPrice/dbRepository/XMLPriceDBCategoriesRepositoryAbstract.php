<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 12:20
 */

abstract class XMLPriceDBCategoriesRepositoryAbstract
{
    /**
     * Should return additional part for SQL request in string
     * @return string
     */
    abstract protected function getAdditionalWherePart();

    protected $categoriesId = '';

    public function getCategoriesSQlRequest(){

        $sql = "SELECT categories.categories_id, categories.parent_id, descr.categories_name 
                FROM categories
                
                LEFT JOIN categories_description AS descr 
                ON (categories.categories_id = descr.categories_id)
                
                LEFT JOIN categories AS catParent 
                ON (categories.parent_id = catParent.categories_id)
                
                WHERE categories.categories_status= '1'
                
                "
                . $this->getAdditionalWherePart() .
                "  
                
                AND descr.language_id = '1'
                
                AND categories.categories_status= '1'                
                               
                AND ( catParent.categories_status ='1' OR catParent.categories_status IS NULL )
                            
                ORDER BY categories.categories_id";

        return $sql;
    }

    /**
     * @return string
     */
    protected function getCategoriesId()
    {
        return $this->categoriesId;

    }

    /**
     * @param string $categoriesId
     */
    public function setCategoriesId($categoriesId)
    {
        $this->categoriesId = $categoriesId;

        return $this;
    }

}