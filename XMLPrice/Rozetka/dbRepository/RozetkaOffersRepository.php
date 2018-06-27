<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 11:28
 */

require_once(XMLPrice_REPOSITORY_DIR . 'XMLPriceDBOffersRepositoryAbstract.php');

class RozetkaOffersRepository extends XMLPriceDBOffersRepositoryAbstract
{
    protected $categoriesId = '';

    protected $sellersId = '';

    protected function getAdditionalFieldsPart()
    {
        return " , IF(p.products_status = '1', 'true', 'false')  AS available
                , FLOOR(p.products_quantity / p.cnt_in_box) AS stock_quantity
                , slr.sellers_id AS sellers_id
                , slr.sellers_name AS sellers_name";
    }

    protected function getAdditionalJoinPart()
    {
        return " LEFT JOIN supply_position AS sp
                 ON p.products_id = sp.products_id

                 LEFT JOIN supply AS s
                 ON sp.supply_id = s.supply_id

                 LEFT JOIN sellers AS slr
                 ON s.sellers_id = slr.sellers_id";
    }

    protected function getAdditionalWherePart()
    {
        return "AND p2c.categories_id IN (" . $this->getCategoriesId() . ") 
               AND slr.sellers_id IN (" . $this->getSellersId() .")  ";
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

    /**
     * @return string
     */
    protected function getSellersId()
    {
        return $this->sellersId;

    }

    /**
     * @param string $sellersId
     */
    public function setSellersId($sellersId)
    {
        $this->sellersId = $sellersId;

        return $this;
    }

}