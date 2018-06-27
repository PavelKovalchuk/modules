<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 08.02.2018
 * Time: 10:23
 */


trait XMLPriceTestControllerTrait
{

    protected $numberToTest = 0;

    protected $offset;

    protected $baseOffsetName = 'yml_xml_test_offset_';

    protected $offsetName;

    protected $numberElements;

    protected $isNeededTestImage = false;

    protected $isNeededTestUrl = false;

    public function testOneUrlAction($website)
    {
        if(!$website){
            return false;
        }

        if( !$this->checkUrlAvailability( $website ) ) {
            echo $website ." <b> сломано! </b> </br>";

        }
        else { echo $website ." связь есть.</br>"; }

    }

    //Go further after current set of results
    public function increaseCounterOffset()
    {
        $offset_name = $this->getOffsetName();

        if (!isset($_SESSION[$offset_name])) {

            $_SESSION[$offset_name] = 0;

        }else{

            $_SESSION[$offset_name] = $_SESSION[$offset_name] + $this->getNumberToTest();

        }
    }

    /**
     * @param int $numberToTest
     */
    public function setNumberToTest($numberToTest)
    {
        $this->numberToTest = intval($numberToTest);
    }

    /**
     * @param bool $isNeededTestImage
     */
    public function setIsNeededTestImage($isNeededTestImage)
    {
        $this->isNeededTestImage = $isNeededTestImage;
    }

    /**
     * @param bool $isNeededTestUrl
     */
    public function setIsNeededTestUrl($isNeededTestUrl)
    {
        $this->isNeededTestUrl = $isNeededTestUrl;
    }

    protected function initSessionsOptions(){

        //Check if this object is correct
        $this->checkCurrentInstance();

        session_start();

        $this->setOffsetName($this->getBaseOffsetName() . get_class($this));

        $this->setCounterOffset( $this->getOffsetName() );
    }

    //Main function - perform tests on selected properties and outputs results
    //Should be used in testUrlsAction() method
    protected function getResults()
    {

        $offers_to_test = $this->getNumberOfOffersToTest();

        if(!$offers_to_test){
            return false;
        }

        $is_errors = false;
        $counter = $this->getOffset();
        $this->printHeaderInfo();

        foreach ($offers_to_test as $product_id => $offer){

            $counter++;

            if($this->isNeededTestUrl()){
                $is_errors = $this->testOfferUrl(  $offer, $counter);
            }

            if($this->isNeededTestImage()){
                $is_errors = $this->testOfferImage(  $offer, $counter);
            }

        }

        $this->printSummaryResult($is_errors);

        return true;

    }

    protected function testOfferImage( AbstractOffer $offer, $counter)
    {
        $pictures = $offer->getPictures();

        foreach ($pictures as $picture){

            if( !$this->checkUrlAvailability( $picture ) ) {
                echo $counter . ' : ' . $picture ." <b> картинка сломана! </b> </br>";
                return false;
            }
            else {
                echo $counter . ' : ' . $picture ." - картинка в норме.</br>";
                return true;
            }

        }

    }

    protected function testOfferUrl( AbstractOffer $offer, $counter)
    {
        if( !$this->checkUrlAvailability( $offer->getUrl() ) ) {
            echo $counter . ' : ' . $offer->getUrl() ." <b> ссылка сломана! </b> </br>";
            return false;
        }
        else {
            echo $counter . ' : ' . $offer->getUrl() ." - ссылка в норме.</br>";
            return true;
        }

    }

    protected function getNumberOfOffersToTest()
    {
        $offers_collection = $this->getTestedOffersCollection();

        if(!$offers_collection){
            return false;
        }

        $offers_to_test = array_slice($offers_collection, $this->getOffset(), $this->getNumberToTest());
        $offset_name = $this->getOffsetName();

        if(!$offers_to_test){
            unset ($_SESSION[$offset_name]);
            return false;
        }

        return $offers_to_test;
    }

    protected function printHeaderInfo()
    {
        echo "<h2>Тестируем " . get_class($this) . "</h2>";
        echo "<h4>Всего товаров: " . $this->getNumberElements() . "</h4>";
        echo "<h4>Переменная Сессии: " . $this->getOffsetName() . "</h4>";

        echo $this->printHeaderTestCases();

    }

    protected function printHeaderTestCases()
    {
        $test_case = '';

        if($this->isNeededTestUrl()){
            $test_case .= ' Тестируются URL Карточек товаров ';
        }

        if($this->isNeededTestImage()){
            $test_case .= ' Тестируются URL Картинок товаров ';
        }

        if(empty($test_case)){
            $test_case .= ' Ничего не тестировалось ';
        }

        $test_output = '<div><h4><i>' . $test_case . '</i></h4></div>';

        return $test_output;

    }

    protected function printSummaryResult($is_errors)
    {
        if($is_errors == true){
            echo '<h2>Явных ошибок нет!</h2>';
        }else{
            echo '<h2>Есть ошибки</h2>';
        }
    }

    //Check if tested object is correct
    protected function checkCurrentInstance()
    {

        if(!$this instanceof XMLPriceTestControllerInterface){
            echo '<h2>Тестуемый обьект не использует нужный интерфейс или трейт</h2>';
            exit;
        }
    }


    protected function getTestedOffersCollection()
    {
        $collection = $this->getOffersCollection();

        $this->setNumberElements( $collection->getNumberChildren() );

        return $collection->getChildren();
    }

    protected function setCounterOffset()
    {
        $offset_name = $this->getOffsetName();

        if(! isset($_SESSION[$offset_name])){
            return false;
        }

        $offset = $_SESSION[$offset_name];

        $this->setOffset($offset);

        return true;
    }


    /**
     * PHP/cURL function to check a web site status. If HTTP status is not 200 or 302, or
     * the requests takes longer than 10 seconds, the website is unreachable.
     *
     * @param string $url URL that must be checked
     */
    protected function checkUrlAvailability($url)
    {
        $timeout = 10;
        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, $timeout );
        $http_respond = curl_exec($ch);
        $http_respond = trim( strip_tags( $http_respond ) );
        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        if ( ( $http_code == "200" ) || ( $http_code == "302" ) ) {
            return true;
        } else {
            // return $http_code;, possible too
            return false;
        }
        curl_close( $ch );

    }

    /**
     * @return int
     */
    protected function getNumberToTest()
    {
        return $this->numberToTest;
    }

    /**
     * @param mixed $offset
     */
    protected function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return mixed
     */
    protected function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return mixed
     */
    protected function getOffsetName()
    {
        return $this->offsetName;
    }

    /**
     * @param mixed $offsetName
     */
    protected function setOffsetName($offsetName)
    {
        $this->offsetName = $offsetName;
    }

    /**
     * @return string
     */
    protected function getBaseOffsetName()
    {
        return $this->baseOffsetName;
    }

    /**
     * @param string $baseOffsetName
     */
    protected function setBaseOffsetName($baseOffsetName)
    {
        $this->baseOffsetName = $baseOffsetName;
    }

    /**
     * @return mixed
     */
    protected function getNumberElements()
    {
        return $this->numberElements;
    }

    /**
     * @param mixed $numberElements
     */
    protected function setNumberElements($numberElements)
    {
        $this->numberElements = $numberElements;
    }

    /**
     * @return bool
     */
    protected function isNeededTestImage()
    {
        return $this->isNeededTestImage;
    }

    /**
     * @return bool
     */
    protected function isNeededTestUrl()
    {
        return $this->isNeededTestUrl;
    }

}