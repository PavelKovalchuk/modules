<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 13:10
 */

require_once(XMLPrice_REPOSITORY_DIR . 'XMLPriceDBCategoriesRepositoryAbstract.php');

class RontarCategoriesRepository extends XMLPriceDBCategoriesRepositoryAbstract
{
    protected function getAdditionalWherePart()
    {
        return;
    }

}