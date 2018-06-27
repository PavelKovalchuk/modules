<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 12:16
 */

require_once(XMLPrice_COMPONENTS_DIR . 'XMLPriceAbstractComponentOffers.php');
require_once(GOOGLE_REPOSITORY_DIR . 'GoogleOffersRepository.php');
require_once(GOOGLE_MODELS_DIR . 'GoogleOffer.php');


class GoogleComponentOffers extends XMLPriceAbstractComponentOffers
{
    public function __construct($db_manager)
    {

        parent::__construct($db_manager);

        $this->setPicturesBaseUrl(DIR_WS_THUMBNAIL_IMAGES);

    }

    public function setChildren()
    {
        $offers_request = new GoogleOffersRepository();

        $data = $this->getDbManager()->getOffersFromDb( $offers_request );


        if(!$data){
            return false;
        }

        $data_products_ids = array_map(function ($v){
            return $v['id'];
        }, $data);

        $data_russian_names = $this->getDbManager()->getRussianNames(implode(', ', $data_products_ids));
        $is_russian_names_arr = is_array($data_russian_names) ? true : false;

        $this->setNumberChildren(count($data));

        foreach ($data as $key => $value){

            if($is_russian_names_arr){
                $name = (isset($data_russian_names[$value["id"]])) ? $data_russian_names[$value["id"]]['russian_name'] : $value['name'];
            }else{
                $name = $value['name'];
            }

            $this->addChild( intval($value["id"]), (new GoogleOffer())
                ->setId(intval($value["id"]))
                ->setUrl($this->getBaseUrl() . '/' . $value["url"])
                ->setAvailable( ($value["available"] == 'true') ? 'in stock' : 'out of stock')
                ->setPrice(intval($value["price"]))
                ->setDescription(($value["description"]) ? '<![CDATA[' . htmlentities( strip_tags($value["description"]), ENT_QUOTES | ENT_XML1, "UTF-8") . ']]>' : NULL)
                ->setName( htmlentities($name, ENT_QUOTES | ENT_XML1 , "UTF-8")  )
                ->addPicture($this->getPictureUrl($value["picture"]))

            );

        }

        return $this;

    }

}