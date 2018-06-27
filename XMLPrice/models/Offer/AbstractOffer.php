<?php

require_once(XMLPrice_OFFER_MODEL_DIR . 'OfferInterface.php');
/**
 * Abstract Class Offer
 */
abstract class AbstractOffer implements OfferInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var bool
     */
    private $available;

    /**
     * @var string
     */
    private $url;

    /**
     * @var float
     */
    private $price;

    /**
     * @var string
     */
    private $currencyId;

    /**
     * @var int
     */
    private $categoryId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $vendor;

    /**
     * @var string
     */
    private $stockQuantity;

    /**
     * @var array
     */
    private $pictures = [];

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var bool
     */
    private $isAvailableAtrrNeeded;

    /**
     * @return array
     */
    public function toArray()
    {
        return \array_merge($this->getHeaderOptions(), $this->getOptions(), $this->getFooterOptions());
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getStockQuantity()
    {
        return $this->stockQuantity;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setStockQuantity($stockQuantity)
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }


    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param string $vendor
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }


    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @return bool
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * @param bool $available
     *
     * @return $this
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     *
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @param string $currencyId
     *
     * @return $this
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     *
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }



    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }



    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param OfferParam $param
     *
     * @return $this
     */
    public function addParam(OfferParam $param)
    {
        $this->params[] = $param;

        return $this;
    }

    /**
     * Add picture
     *
     * @param string $url
     *
     * @return $this
     */
    public function addPicture($url)
    {
        if (\count($this->pictures) < 10) {
            $this->pictures[] = $url;
        }

        return $this;
    }

    /**
     * Set pictures
     *
     * @param array $pictures
     *
     * @return $this
     */
    public function setPictures(array $pictures)
    {
        $this->pictures = $pictures;

        return $this;
    }

    /**
     * Get picture list
     *
     * @return array
     */
    public function getPictures()
    {
        return $this->pictures;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    abstract protected function getOptions();

    /**
     * Forms nodes in offer
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
            'stock_quantity' => $this->getStockQuantity(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),

        ];
    }

    /**
     * @return array
     */
    private function getFooterOptions()
    {
        return [

        ];
    }


    /**
     * @return bool
     */
    public function isAvailableAtrrNeeded()
    {
        return $this->isAvailableAtrrNeeded;
    }

    /**
     * @param bool $isAvailableAtrrNeeded
     */
    public function setIsAvailableAtrrNeeded($isAvailableAtrrNeeded)
    {
        $this->isAvailableAtrrNeeded = $isAvailableAtrrNeeded;

        return $this;
    }


}
