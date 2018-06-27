<?php

require_once(XMLPrice_ROOT_DIR . 'XMLPriceSettings.php');
require_once(XMLPrice_MODELS_DIR . 'ShopInfo.php');

/**
 * Class Generator
 */
class XMLPriceGenerator
{
    /**
     * @var string
     */
    private $tmpFile;

    /**
     * @var \XMLWriter
     */
    private $writer;

    /**
     * @var XMLPriceSettings
     */
    private $settings;

    /**
     * Generator constructor.
     *
     * @param XMLPriceSettings $settings
     */
    public function __construct($settings = null)
    {
        $this->settings = $settings instanceof XMLPriceSettings ? $settings : new XMLPriceSettings();
        $this->tmpFile = $this->settings->getOutputFile() !== null ? tempnam(sys_get_temp_dir(), 'YMLGenerator') : 'php://output';
        //$this->tmpFile = $this->settings->getOutputFile() !== null ? tempnam('c:/OpenServer/domains/greenmarket/inc/Rozetka/files', 'YMLGenerator') : 'php://output';

        $this->writer = new \XMLWriter();
        $this->writer->openURI($this->tmpFile);

        if ($this->settings->getIndentString()) {
            $this->writer->setIndentString($this->settings->getIndentString());
            $this->writer->setIndent(true);
        }
    }

    /**
     * @param ShopInfo $shopInfo
     * @param array    $currencies
     * @param array    $categories
     * @param array    $offers
     * @param array    $deliveries
     *
     * @return bool
     */
    public function generate(ShopInfo $shopInfo, $currencies, $categories, $offers)
        //public function generate(ShopInfo $shopInfo, array $currencies, array $categories, array $offers, array $deliveries = [])
    {
        try {
            $this->addHeader();

            $this->addShopInfo($shopInfo);

            if($this->isValidData($currencies)){

                $this->addCurrencies($currencies);

            }

            if($this->isValidData($categories)){

                $this->addCategories($categories);

            }


            /*if (\count($deliveries) !== 0) {
                $this->addDeliveries($deliveries);
            }*/

            if($this->isValidData($offers)){

                $this->addOffers($offers);

            }

            $this->addFooter();

            if (null !== $this->settings->getOutputFile()) {
                copy($this->tmpFile, $this->settings->getOutputFile());
                @unlink($this->tmpFile);
            }

            return true;

        } catch (\Exception $exception) {
            throw new \RuntimeException(\sprintf('Problem with generating YML file: %s', $exception->getMessage()), 0, $exception);
        }
    }

    protected function isValidData($data_array)
    {
        if(is_array($data_array) && count($data_array) > 0){
            return true;
        }
        return false;
    }

    /**
     * Add document header
     */
    protected function addHeader()
    {
        $this->writer->startDocument('1.0', $this->settings->getEncoding());
        $this->writer->startDTD('yml_catalog', null, 'shops.dtd');
        $this->writer->endDTD();
        $this->writer->startElement('yml_catalog');
        $this->writer->writeAttribute('date', \date('Y-m-d H:i'));
        $this->writer->startElement('shop');
    }

    /**
     * Add document footer
     */
    protected function addFooter()
    {
        $this->writer->fullEndElement();
        $this->writer->fullEndElement();
        $this->writer->endDocument();
    }

    /**
     * Adds shop element data.
     *
     * @param ShopInfo $shopInfo
     */
    protected function addShopInfo(ShopInfo $shopInfo)
    {
        foreach ($shopInfo->toArray() as $name => $value) {
            if ($value !== null) {
                $this->writer->writeElement($name, $value);
            }
        }
    }

    /**
     * @param Currency $currency
     */
    protected function addCurrency(Currency $currency)
    {
        $this->writer->startElement('currency');
        $this->writer->writeAttribute('id', $currency->getId());
        $this->writer->writeAttribute('rate', $currency->getRate());
        $this->writer->endElement();
    }

    /**
     * @param Category $category
     */
    protected function addCategory(Category $category)
    {
        $this->writer->startElement('category');
        $this->writer->writeAttribute('id', $category->getId());

        if ($category->getParentId() !== null) {
            $this->writer->writeAttribute('parentId', $category->getParentId());
        }

        $this->writer->text($category->getName());
        $this->writer->fullEndElement();
    }

    /**
     * @param Delivery $delivery
     */
    protected function addDelivery(Delivery $delivery)
    {
        $this->writer->startElement('option');
        $this->writer->writeAttribute('cost', $delivery->getCost());
        $this->writer->writeAttribute('days', $delivery->getDays());
        if ($delivery->getOrderBefore() !== null) {
            $this->writer->writeAttribute('order-before', $delivery->getOrderBefore());
        }
        $this->writer->endElement();
    }

    /**
     * @param OfferInterface $offer
     */
    protected function addOffer(OfferInterface $offer)
    {
        $this->writer->startElement('offer');
        $this->writer->writeAttribute('id', $offer->getId());

        if($offer->isAvailableAtrrNeeded() == true){
            $this->writer->writeAttribute('available', $offer->isAvailable() );
        }

        if ($offer->getType() !== null) {
            $this->writer->writeAttribute('type', $offer->getType());
        }

        if ($offer instanceof OfferGroupAwareInterface && $offer->getGroupId() !== null) {
            $this->writer->writeAttribute('group_id', $offer->getGroupId());
        }

        foreach ($offer->toArray() as $name => $value) {
            if (\is_array($value)) {
                foreach ($value as $itemValue) {
                    $this->addOfferElement($name, $itemValue);
                }
            } else {
                $this->addOfferElement($name, $value);
            }
        }
        $this->addOfferParams($offer);

        $this->writer->fullEndElement();
    }

    /**
     * Adds <currencies> element.
     *
     * @param array $currencies
     */
    private function addCurrencies(array $currencies)
    {
        $this->writer->startElement('currencies');

        /** @var Currency $currency */
        foreach ($currencies as $currency) {
            if ($currency instanceof Currency) {
                $this->addCurrency($currency);
            }
        }

        $this->writer->fullEndElement();
    }

    /**
     * Adds <categories> element.
     *
     * @param array $categories
     */
    private function addCategories(array $categories)
    {
        $this->writer->startElement('categories');

        /** @var Category $category */
        foreach ($categories as $category) {
            if ($category instanceof Category) {
                $this->addCategory($category);
            }
        }

        $this->writer->fullEndElement();
    }

    /**
     * Adds <delivery-option> element.
     *
     * @param array $deliveries
     */
    private function addDeliveries(array $deliveries)
    {
        $this->writer->startElement('delivery-options');

        /** @var Delivery $delivery */
        foreach ($deliveries as $delivery) {
            if ($delivery instanceof Delivery) {
                $this->addDelivery($delivery);
            }
        }

        $this->writer->fullEndElement();
    }

    /**
     * Adds <offers> element.
     *
     * @param array $offers
     */
    private function addOffers(array $offers)
    {
        $this->writer->startElement('offers');

        /** @var OfferInterface $offer */
        foreach ($offers as $offer) {
            if ($offer instanceof OfferInterface) {
                $this->addOffer($offer);
            }
        }

        $this->writer->fullEndElement();
    }

    /**
     * @param OfferInterface $offer
     */
    private function addOfferParams(OfferInterface $offer)
    {
        /** @var OfferParam $param */
        foreach ($offer->getParams() as $param) {
            if ($param instanceof OfferParam) {
                $this->writer->startElement('param');

                $this->writer->writeAttribute('name', $param->getName());

                $this->writer->text($param->getValue());

                $this->writer->endElement();
            }
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return bool
     */
    private function addOfferElement($name, $value)
    {
        if ($value === null) {
            return false;
        }

        if (\is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $this->writer->writeElement($name, $value);

        return true;
    }
}
