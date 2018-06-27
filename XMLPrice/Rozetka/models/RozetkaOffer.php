<?php

require_once(XMLPrice_OFFER_MODEL_DIR . 'AbstractOffer.php');

/**
 * Class RozetkaOffer
 */
class RozetkaOffer extends AbstractOffer
{
    private $current_seller_id;

    private $current_sellers_name;

    /**
     * @return string
     */
    public function getType()
    {
        return null;
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getHeaderOptions()
    {
        return [
            'url' => $this->getUrl(),
            'price' => $this->getPrice(),
            'currencyId' => $this->getCurrencyId(),
            'categoryId' => $this->getCategoryId(),
            'picture' => $this->getPictures(),
            'vendor' => $this->getVendor(),
            'stock_quantity' => $this->getStockQuantity(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            //For test only!!!
            //'supplier_ID' => $this->getCurrentSellerId(),
            //'supplier_name' => $this->getCurrentSellersName(),

        ];
    }

    /**
     * @return mixed
     */
    public function getCurrentSellerId()
    {
        return $this->current_seller_id;
    }

    /**
     * @param mixed $current_seller_id
     */
    public function setCurrentSellerId($current_seller_id)
    {
        $this->current_seller_id = $current_seller_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrentSellersName()
    {
        return $this->current_sellers_name;
    }

    /**
     * @param mixed $current_sellers_name
     */
    public function setCurrentSellersName($current_sellers_name)
    {
        $this->current_sellers_name = $current_sellers_name;

        return $this;
    }

}
