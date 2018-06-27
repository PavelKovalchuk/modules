<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 12:16
 */

require_once(XMLPrice_COMPONENTS_DIR . 'XMLPriceAbstractComponentOffers.php');
require_once(RONTAR_REPOSITORY_DIR . 'RontarOffersRepository.php');
require_once(RONTAR_MODELS_DIR . 'RontarOffer.php');


class RontarComponentOffers extends XMLPriceAbstractComponentOffers
{
    public function __construct($db_manager)
    {

        parent::__construct($db_manager);

        $this->setPicturesBaseUrl(DIR_WS_THUMBNAIL_IMAGES);

    }

    public function setChildren()
    {
        $offers_request = new RontarOffersRepository();

        $data = $this->getDbManager()->getOffersFromDb( $offers_request );

        if(!$data){
            return false;
        }

        $this->setNumberChildren(count($data));

        foreach ($data as $key => $value){

            $this->setProductsIdArr($value["id"]);

            $this->addChild( intval($value["id"]), (new RontarOffer())
                ->setIsAvailableAtrrNeeded(false)
                ->setId(intval($value["id"]))
                ->setUrl($this->getBaseUrl() . '/' . $value["url"])
                ->setPrice(intval($value["price"]))
                ->setCategoryId(intval($value["categoryId"]))
                /*->setDescription(($value["description"]) ? '<![CDATA[' . htmlspecialchars($value["description"], ENT_QUOTES | ENT_XML1) . ']]>' : NULL)*/
                ->setDescription(($value["description"]) ? $value["description"] : NULL)
                ->setName( htmlspecialchars($value["name"], ENT_XML1) )
                ->addPicture($this->getPictureUrl($value["picture"]))

            );

        }

        return $this;

    }

}