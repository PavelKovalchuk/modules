<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 12:24
 */

require_once(XMLPrice_REPOSITORY_DIR . 'XMLPriceDBCategoriesRepositoryAbstract.php');

class RozetkaCategoriesRepository extends XMLPriceDBCategoriesRepositoryAbstract
{

    protected function getAdditionalWherePart()
    {
        return "AND categories.categories_id IN (" . $this->getCategoriesId() . ")";
    }

}