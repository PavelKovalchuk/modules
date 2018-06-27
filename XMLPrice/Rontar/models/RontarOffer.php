<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 14:29
 */

require_once(XMLPrice_OFFER_MODEL_DIR . 'AbstractOffer.php');

class RontarOffer extends AbstractOffer
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
        return [
            'url' => $this->getUrl(),
            'price' => $this->getPrice(),
            'categoryId' => $this->getCategoryId(),
            'picture' => $this->getPictures(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),

        ];
    }

}