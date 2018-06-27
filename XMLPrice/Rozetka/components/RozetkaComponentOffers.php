<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 25.01.2018
 * Time: 17:30
 */

require_once(XMLPrice_COMPONENTS_DIR . 'XMLPriceAbstractComponentOffers.php');
require_once(ROZETKA_REPOSITORY_DIR . 'RozetkaOffersRepository.php');
require_once(ROZETKA_MODELS_DIR . 'RozetkaOffer.php');
require_once(XMLPrice_MODELS_DIR . 'Offer/OfferParam.php');

class RozetkaComponentOffers extends XMLPriceAbstractComponentOffers
{
    protected $sellersId = [];

    protected $nameParts = [];

    protected $partsParamDimension = [

        'Высота, см' => '',
        'Ширина, см' => '',
        'Диаметр, см' => '',
        'Длина, см' => ''

    ];

    protected $paramDimensionName = 'Размеры';

    protected $paramDimensionMeasure = 'см';

    //Категории вазонов, для товаров которых создаем параметр (param Размеры)
    protected $paramDimensionCategories = [
        663,
    ];

    //Категории крупномеров, для товаров которых создаем параметр (param Размеры)
    protected $categoriesTrees = [
        651, 652, 605, 649
    ];

    //Из каких опций творим Размеры
    protected $optionsParamDimensionTrees = [
        'Размер посадочного материала, В/Ш' => [
            'delimiter' => '/'
        ],
    ];

    //Названия парамтров - доп. текст,
    //Доп текст добавляем к значению этого параметра
    protected $componentParts = [
        'Объём, л' => 'л',
    ];

    //Название параметра => функция-фильтр. Для добавления в <name></name>
    protected $additional_param_name_filters = [

        651 => [
            'Размер посадочного материала, В/Ш' => 'filterTreeHeight',
            'Размер посадочного материала' => 'filterTreeHeight',
            'Упаковка' => 'filterPackaging',
        ],
        652 => [
            'Размер посадочного материала, В/Ш' => 'filterTreeHeight',
            'Размер посадочного материала' => 'filterTreeHeight',
            'Упаковка' => 'filterPackaging',
        ],
        605 => [
            'Размер посадочного материала, В/Ш' => 'filterTreeHeight',
            'Размер посадочного материала' => 'filterTreeHeight',
            'Упаковка' => 'filterPackaging',
        ],
        649 => [
            'Размер посадочного материала, В/Ш' => 'filterTreeHeight',
            'Размер посадочного материала' => 'filterTreeHeight',
            'Упаковка' => 'filterPackaging',
        ],

    ];


    //Параметры, которые надо удалить из вывода для Розетки
    protected $params_to_unset = [

        651 => [
            'Страна производитель'
        ],
        652 => [
            'Страна производитель'
        ],
        605 => [
            'Страна производитель'
        ],
        649 => [
            'Страна производитель'
        ],

    ];
    /**
     * Для создания новых параметров для товаров
     * параметр, из которого получаем исходные данные - ключ
     * @var array
     */
    protected $new_params = [

        651 => [
            'Упаковка' => [
                [
                    'name' => 'Тип саженца',
                    'function' => 'createSeedlingsType'
                ],

                [
                    'name' => 'Объем контейнера',
                    'function' => 'createContainerVolume'
                ],

            ],
            'Название русское' => [
                [
                    'name' => 'Тип',
                    'function' => 'createTreeType'
                ],

                [
                    'name' => 'Производитель',
                    'function' => 'createManufacturer'
                ],
            ],
            'Размер посадочного материала, В/Ш' => [
                [
                    'name' => 'Высота растения',
                    'function' => 'filterTreeHeight'
                ],
            ],
            'Размер посадочного материала' => [
                [
                    'name' => 'Высота растения',
                    'function' => 'filterTreeHeight'
                ],
            ],

        ],

        652 => [
            'Упаковка' => [
                [
                    'name' => 'Тип саженца',
                    'function' => 'createSeedlingsType'
                ],

                [
                    'name' => 'Объем контейнера',
                    'function' => 'createContainerVolume'
                ],

            ],
            'Название русское' => [
                [
                    'name' => 'Тип',
                    'function' => 'createTreeType'
                ],

                [
                    'name' => 'Производитель',
                    'function' => 'createManufacturer'
                ],
            ],
            'Размер посадочного материала, В/Ш' => [
                [
                    'name' => 'Высота растения',
                    'function' => 'filterTreeHeight'
                ],
            ],
            'Размер посадочного материала' => [
                [
                    'name' => 'Высота растения',
                    'function' => 'filterTreeHeight'
                ],
            ],

        ],

        605 => [
            'Упаковка' => [
                [
                    'name' => 'Тип саженца',
                    'function' => 'createSeedlingsType'
                ],

                [
                    'name' => 'Объем контейнера',
                    'function' => 'createContainerVolume'
                ],

            ],
            'Название русское' => [
                [
                    'name' => 'Тип',
                    'function' => 'createTreeType'
                ],

                [
                    'name' => 'Производитель',
                    'function' => 'createManufacturer'
                ],
            ],
            'Размер посадочного материала, В/Ш' => [
                [
                    'name' => 'Высота растения',
                    'function' => 'filterTreeHeight'
                ],
            ],
            'Размер посадочного материала' => [
                [
                    'name' => 'Высота растения',
                    'function' => 'filterTreeHeight'
                ],
            ],

        ],

        649 => [
            'Упаковка' => [
                [
                    'name' => 'Тип саженца',
                    'function' => 'createSeedlingsType'
                ],

                [
                    'name' => 'Объем контейнера',
                    'function' => 'createContainerVolume'
                ],

            ],
            'Название русское' => [
                [
                    'name' => 'Тип',
                    'function' => 'createTreeType'
                ],

                [
                    'name' => 'Производитель',
                    'function' => 'createManufacturer'
                ],
            ],
            'Размер посадочного материала, В/Ш' => [
                [
                    'name' => 'Высота растения',
                    'function' => 'filterTreeHeight'
                ],
            ],
            'Размер посадочного материала' => [
                [
                    'name' => 'Высота растения',
                    'function' => 'filterTreeHeight'
                ],
            ],
            'Страна производитель' => [
                [
                    'name' => 'Страна-производитель',
                    'function' => 'filterCountryOrigin',
                ],
            ],

        ],

    ];

    protected $paramProductCodeName;

    protected $paramDeliveryName;

    protected $paramDeliveryValue;

    public function __construct($db_manager)
    {

        parent::__construct($db_manager);

        $this->setPicturesBaseUrl(DIR_WS_THUMBNAIL_IMAGES);
        $this->setParamDeliveryName('Доставка/Оплата');
        $this->setParamDeliveryValue('Доставка - до 7 дней.Предоплата - 50%.');
        $this->setParamProductCodeName('Артикул');

    }

    public function setChildren()
    {
        $offers_request = new RozetkaOffersRepository();

        $offers_request->setSellersId( $this->getSellersId() )->setCategoriesId( $this->getCategoriesId() );

        $data = $this->getDbManager()->getOffersFromDb( $offers_request );

        if(!$data){
            return false;
        }

        $this->setNumberChildren(count($data));

        foreach ($data as $key => $value){

            $this->setProductsIdArr($value["id"]);

            $this->addChild( intval($value["id"]), (new RozetkaOffer())
                ->setId(intval($value["id"]))
                ->setIsAvailableAtrrNeeded(true)
                ->setAvailable($value["available"])
                ->setUrl($this->getBaseUrl() . '/' . $value["url"])
                ->setPrice(intval($value["price"]))
                ->setCurrencyId('UAH')
                ->setCategoryId(intval($value["categoryId"]))
                ->setDescription(($value["description"]) ? '<![CDATA[' . htmlspecialchars($value["description"], ENT_QUOTES | ENT_XML1) . ']]>' : NULL)
                ->setStockQuantity(intval($value["stock_quantity"]))
                ->setName( htmlspecialchars($value["name"], ENT_XML1) )
                ->addPicture($this->getPictureUrl($value["picture"]))

                //For test only!!!
               // ->setCurrentSellerId($value["sellers_id"])
               // ->setCurrentSellersName($value["sellers_name"])

            );

        }

        $this->setAdditionalPictures();

        $this->setOfferParams();

        return $this;

    }

    protected function createParamDimension($parts_dimension_values)
    {
        if(!is_array($parts_dimension_values) || !count($parts_dimension_values) > 0){
            return false;
        }

        $parts_map = $this->getPartsParamDimension();

        $dimensions_array = [];
        foreach ($parts_map as $part_name => $part_data){

            if(isset($parts_dimension_values[$part_name])){
                $dimensions_array[] =  $parts_dimension_values[$part_name]['value'];
            }

        }

        if(empty($dimensions_array)){
            return false;
        }

        $dimension_result = implode($this->getDimensionDelimiter(), $dimensions_array) . ' ' . $this->getParamDimensionMeasure();

        return $dimension_result;

    }

    /**
     * Форматирует имя товара под требования Розетки
     * $addition_name_params - задается в коде файла, где вызывается контроллер Розетки
     *
     * @param $name
     * @param $product_id
     * @param $addition_name_params
     * @return string
     */
    protected function createName($name, $product_id, $addition_name_params){

        if(!is_int($product_id) ){
            return $name;
        }

        $new_name = $name;

        if(is_array($addition_name_params) && count($addition_name_params) > 0){

            foreach ($addition_name_params as $param => $value){

                $new_name .= ' ' . $value;

                if( isset( $this->getComponentParts()[$param] ) ){
                    $new_name .= ' ' . $this->getOneComponentPart($param);
                }

            }

        }

        // Добавляем код (Артикул товара)
        $new_name .=  ' ' . $this->getProductCode($product_id);

        return htmlspecialchars($new_name, ENT_XML1);

    }

    protected function createManufacturer($value, $offer_name = false)
    {
        return 'ВС';
    }

    protected function createSeedlingsType($value, $offer_name = false)
    {
        if(!$value){
            return false;
        }

        if($value == 'земляной ком'){
            return 'В коме земли';
        }

        //Потому что гады-контентщики использовали разные раскладки клавиатуры
        $needle_1 = 'С';
        $needle_2 = 'C';

        $version_1 = strpos($value, $needle_1);
        $version_2 = strpos($value, $needle_2);

        if($version_1 === false && $version_2 === false){
            return false;
        }

        if($version_1 !== false){
            $needle = $needle_1;
        }

        if($version_2 !== false){
            $needle = $needle_2;
        }

        if ($needle === false) {
            return false;
        }

        if (strpos($value, $needle) !== false) {
            return 'В контейнере с грунтом';
        }

        return false;

    }

    protected function createContainerVolume($value, $offer_name = false)
    {
        if(!$value){
            return false;
        }

        //Потому что гады-контентщики использовали разные раскладки клавиатуры
        $needle_1 = 'С';
        $needle_2 = 'C';

        $version_1 = strpos($value, $needle_1);
        $version_2 = strpos($value, $needle_2);

        if($version_1 === false && $version_2 === false){
            return false;
        }

        if($version_1 !== false){
            $needle = $needle_1;
        }

        if($version_2 !== false){
            $needle = $needle_2;
        }

        if ($needle === false) {
            return false;
        }

        if($value == 'земляной ком'){
            return false;
        }

        $number = str_replace($needle, '', $value);

        if(!$number){
            return false;
        }

        return $number . ' л';

    }

    protected function createTreeType($value, $offer_name)
    {

        if(!$offer_name){
            return false;
        }
        $name_array = explode(' ', $offer_name );
        $type = $name_array[0];

        if(!$type){
            return false;
        }

        return $type;

    }

    protected function filterCountryOrigin($value, $offer_name)
    {
        if(!$value){
            return "Украина";
        }
        return $value;
    }

    //Функция-обработчик для Размер посадочного материала, В/Ш
    protected function filterTreeHeight($value)
    {
        if(!$value){
            return false;
        }
        $dimensions_array = explode('/', $value );
        $dimensions_array = array_map('trim', $dimensions_array);
        $height = $dimensions_array[0];

        if(!$height){
            return false;
        }

        if(count($dimensions_array) > 1){
            $result = $height . ' см';
        }else{
            $result = $height;
        }

        return $result;
    }

    //Функция-обработчик для Упаковка
    protected function filterPackaging($value)
    {
        if(!$value){
            return false;
        }

        //Потому что гады-контентщики использовали разные раскладки клавиатуры
        $needle_1 = 'С';
        $needle_2 = 'C';

        $version_1 = strpos($value, $needle_1);
        $version_2 = strpos($value, $needle_2);

        if($version_1 === false && $version_2 === false){
            return 'Земляной ком';
        }

        if($version_1 !== false){
            $needle = $needle_1;
        }

        if($version_2 !== false){
            $needle = $needle_2;
        }

        if ($needle === false) {
            return 'Земляной ком';
        }

        $number = str_replace($needle, '', $value);

        if(!$number){
            return false;
        }

        return 'контейнер ' . $number . ' л';


    }

    protected function setAdditionalPictures()
    {
        $additional_pictures = $this->getDbManager()->getAdditionalPicturesFromDb( implode(", ", $this->getProductsIdArr()) );

        if(!$additional_pictures || !is_array($additional_pictures)){
            return false;
        }

        foreach ($additional_pictures as $prod_id => $pictures_arr){

            if(array_key_exists($prod_id, $this->getChildren())){

                foreach ($pictures_arr as $key => $picture){

                    $this->getChild($prod_id)->addPicture( $this->getPictureUrl($picture['image_name']) );

                }

            }

        }

    }

    // Добавляет обьекты param к обьекту offer
    protected function setOfferParams()
    {
        $extra_fields = $this->getDbManager()->getExtraParamsFromDb( implode(", ", $this->getProductsIdArr()) );

        if(!$extra_fields || !is_array($extra_fields)){
            return false;
        }

        $parts_dimension_map = $this->getPartsParamDimension();

        foreach ($extra_fields as $prod_id => $data_arr){

            if(array_key_exists($prod_id, $this->getChildren())){

                //Логика изменения названия товара 1 старт
                $offer = $this->getChild($prod_id);
                $cat_id = $offer->getCategoryId();

                //Логика добавления параметра 'Размеры' старт
                //Вариант для вазонов
                if(in_array($cat_id, $this->getParamDimensionCategories())){

                    $parts_dimension_values = array_intersect_key($data_arr, $parts_dimension_map);
                    $dimension_value = $this->createParamDimension($parts_dimension_values);

                    if($dimension_value){
                        $offer->addParam(
                            (new OfferParam())->setName( $this->getParamDimensionName() )->setValue( $dimension_value )
                        );
                    }

                    //Удаляем использованные значения из данных для вывода
                    $param_keys_to_delete = array_keys($parts_dimension_values) ;
                    foreach ($param_keys_to_delete as $key_to_delete){
                        unset($data_arr[$key_to_delete]);
                    }

                }

                //Логика добавления параметра 'Размеры' финиш

                //Если у категории этого товара есть доп.параметры, которые должны быть выведены в названии товара
                //Формируем пустую болванку - массив
                if( array_key_exists($cat_id, $this->getNameParts()) ){
                    $addition_name_params = $this->getNameParts()[$cat_id];
                }
                //Логика изменения названия товара 1 финиш

                //Если у категории этого товара есть доп.параметры, которые должны быть преобразованы в новые параметры
                //Формируем пустую болванку - массив
                if( is_array($this->getNewParams()) && array_key_exists($cat_id, $this->getNewParams()) ){
                    $new_params = $this->getNewParams()[$cat_id];
                }

                foreach ($data_arr as $param_name => $param_data){

                    //Логика изменения названия товара 2 старт
                    //Если передается параметр, название которого соотносится с болванкой, сохраняем значение
                    if( array_key_exists($param_name, $addition_name_params) ){

                        //Если в болванке $additional_param_name_filters определена функция-обработчик этого параметра, применяем его
                        if( is_array($this->getAdditionalParamNameFilters()[$cat_id]) && array_key_exists($param_name, $this->getAdditionalParamNameFilters()[$cat_id])){
                            $filter_method =  $this->getAdditionalParamNameFilters()[$cat_id][$param_name];
                            $filtered_value = $this->$filter_method($param_data['value']);

                            if($filtered_value){
                                $addition_name_params[$param_name] = $filtered_value;
                            }

                        }else{
                            $addition_name_params[$param_name] = $param_data['value'];
                        }

                    }
                    //Логика изменения названия товара 2 финиш

                    //Логика создания и добавления новых параметров старт
                    if( array_key_exists($param_name, $new_params) ){

                        foreach ($new_params[$param_name] as $key => $data){

                            $creator_method = $data['function'];

                            $new_param_value = $this->$creator_method($param_data['value'], $offer->getName());

                            if($new_param_value){

                                $offer->addParam(
                                    (new OfferParam())->setName($data['name'])->setValue($new_param_value)
                                );
                            }

                        }

                    }
                    //Логика создания и добавления новых параметров finish

                    //Добавления бренд-производителя товара(<vendor>) для offer
                    if($param_name == 'Производитель'){
                        $offer->setVendor($param_data['value']);
                    }

                    //Запрещаем вывод параметров, которые нам не нужны
                    if(is_array($this->getParamsToUnset()[$cat_id]) && in_array( $param_name, $this->getParamsToUnset()[$cat_id] )){
                        continue;
                    }

                    $offer->addParam(
                        (new OfferParam())->setName($param_name)->setValue($param_data['value'])
                    );

                }

                //Если это крупномер
                if( in_array($cat_id, $this->getCategoriesTrees())){
                    $offer->setVendor('BC');

                    if( !array_key_exists('Страна производитель', $data_arr) ){
                        $offer->addParam(
                            (new OfferParam())->setName( 'Страна-производитель' )->setValue( 'Украина')
                        );
                    }

                }

                //Логика изменения названия товара 3 старт
                if(!empty($addition_name_params)){
                    //Изменяем название товара
                    $rozetka_name = $this->createName( $offer->getName(), $offer->getId(), $addition_name_params );
                    $offer->setName($rozetka_name);
                }

                //Обнуляем хранилища
                $addition_name_params = [];
                $new_params =[];
                //Логика изменения названия товара 3 финиш

                //Добавляем параметр Артикул к обьекту offer
                $offer->addParam(
                    (new OfferParam())->setName( $this->getParamProductCodeName() )->setValue( $this->getProductCode($prod_id))
                );

                //Добавляем параметр Доставка/Оплата к обьекту offer
                $offer->addParam(
                    (new OfferParam())->setName( $this->getParamDeliveryName() )->setValue( $this->getParamDeliveryValue())
                );

            }

        }

    }

    protected function getDimensionDelimiter()
    {
        return 'x';
    }


    /**
     * @return array
     */
    protected function getSellersId()
    {
        return $this->sellersId;
    }

    /**
     * @param array $sellersId
     */
    public function setSellersId($sellersId)
    {
        $this->sellersId = $sellersId;

        return $this;
    }

    /**
     * @return array
     */
    protected function getNameParts()
    {
        return $this->nameParts;
    }

    /**
     * @param array $nameParts
     */
    public function setNameParts($nameParts)
    {
        $this->nameParts = $nameParts;

        return $this;
    }

    /**
     * @return array
     */
    protected function getOneComponentPart($paramName)
    {
        if( isset($this->componentParts[$paramName]) ){
            return $this->componentParts[$paramName];
        }

        return null;
    }

    /**
     * @return array
     */
    protected function getComponentParts()
    {
        return $this->componentParts;
    }

    /**
     * @return mixed
     */
    protected function getParamProductCodeName()
    {
        return $this->paramProductCodeName;
    }

    /**
     * @param mixed $paramProductCodeName
     */
    protected function setParamProductCodeName($paramProductCodeName)
    {
        $this->paramProductCodeName = $paramProductCodeName;
    }

    /**
     * @return mixed
     */
    protected function getParamDeliveryName()
    {
        return $this->paramDeliveryName;
    }

    /**
     * @param mixed $paramDeliveryName
     */
    protected function setParamDeliveryName($paramDeliveryName)
    {
        $this->paramDeliveryName = $paramDeliveryName;
    }

    /**
     * @return mixed
     */
    protected function getParamDeliveryValue()
    {
        return $this->paramDeliveryValue;
    }

    /**
     * @param mixed $paramDeliveryValue
     */
    protected function setParamDeliveryValue($paramDeliveryValue)
    {
        $this->paramDeliveryValue = $paramDeliveryValue;
    }

    /**
     * @return array
     */
    protected function getPartsParamDimension()
    {
        return $this->partsParamDimension;
    }

    /**
     * @return string
     */
    protected function getParamDimensionName()
    {
        return $this->paramDimensionName;
    }

    /**
     * @return string
     */
    protected function getParamDimensionMeasure()
    {
        return $this->paramDimensionMeasure;
    }

    /**
     * @return array
     */
    public function getParamDimensionCategories()
    {
        return $this->paramDimensionCategories;
    }

    /**
     * @return array
     */
    protected function getCategoriesTrees()
    {
        return $this->categoriesTrees;
    }

    /**
     * @return array
     */
    protected function getOptionsParamDimensionTrees()
    {
        return $this->optionsParamDimensionTrees;
    }

    /**
     * @return array
     */
    protected function getAdditionalParamNameFilters()
    {
        return $this->additional_param_name_filters;
    }

    /**
     * @return array
     */
    protected function getNewParams()
    {
        return $this->new_params;
    }

    /**
     * @return array
     */
    protected function getParamsToUnset()
    {
        return $this->params_to_unset;
    }


}