<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 14:25
 */

require_once(XMLPrice_REPOSITORY_DIR . 'XMLPriceDBOffersRepositoryAbstract.php');

class RontarOffersRepository extends XMLPriceDBOffersRepositoryAbstract
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
        return "AND p.products_status = 1";
    }

}