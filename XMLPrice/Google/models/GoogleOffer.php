<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 14:29
 */

require_once(XMLPrice_OFFER_MODEL_DIR . 'AbstractOffer.php');

class GoogleOffer extends AbstractOffer
{
    protected function getOptions()
    {
        return [];
    }

    public function getType()
    {
        return null;
    }

    /**
     * @return array
     */
    protected function getHeaderOptions()
    {
        return null;
    }

}