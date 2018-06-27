<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 05.09.2017
 * Time: 15:14
 */

require_once('includes/configure.php');

require_once(DIR_FS_INC . 'Privatbank/ExcelPrivatbank.php');


class CallerPrivatbank extends ExcelPrivatbank
{
    const API_BASE_URL = 'https://api.privatbank.ua/p24api';

    /** @var resource */
    protected $curl;

    /**
     *Массив путей запросов
     */
    public $api_pathes_data = array(
        //Выписки по счёту мерчанта - физлица https://api.privatbank.ua/#p24/registration
        'history' => 'rest_fiz'

    );

    /**
     *
     *
     */
    public function __construct(\DateTime $sinceDate, \DateTime $toDate)
    {
        parent::__construct($sinceDate, $toDate);

        $this->curl = curl_init();

        if($this->curl == false){
            self::getErrorMessageExcelFile('Не возможно создать дескриптор cURL ', self::getFormattedDate($sinceDate), self::getFormattedDate($toDate));
        }

    }

    /**
     * Close curl
     */
    public function __destruct()
    {
        $this->curl && curl_close($this->curl);
    }

    /**
     * @see https://api.privatbank.ua/#p24/orders
     *
     * Совершает запрос на API. Возвращает ответ в случае успеха.
     *
     * @param string $method
     * @param SimpleXMLElement
     *
     * @return Statements
     */
    public function getHistory($path, $request)
    {
        if(!array_key_exists($path, $this->api_pathes_data)){
            self::getErrorMessageExcelFile('Указан неверный URL для запроса к API Приватбанка');
        }

        if(!$request instanceof SimpleXMLElement){
            self::getErrorMessageExcelFile('Передан неверный параметр для cURL запроса');
        }

        $response = $this->call($this->api_pathes_data[$path], $request->asXML());


        return $response->data->info->statements;

    }



    /**
     * Call method
     *
     * @param string $method
     * @param string|null $data
     * @return \SimpleXMLElement
     */
    protected function call($path = '', $data = null)
    {
        $options = [
            CURLOPT_URL => sprintf('%s/%s', self::API_BASE_URL, $path),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => null,
            CURLOPT_POSTFIELDS => null,
        ];

        if ($data) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $data;
        }

        $response = $this->executeCurl($options);

        return $response;
    }

    /**
     * Parses SimpleXMLElement response.
     * If the error exists, return the message of error or false
     * @param \SimpleXMLElement $data_xml
     * @return string/ false
     */
    protected function getErrorsFromResponse($data_xml)
    {

        if ($data_xml === false) {

            return 'Ошибка 1: ' . curl_error($this->curl) . curl_errno($this->curl);

        }

        $httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        if (!in_array($httpCode, array(200))) {

            return 'Ошибка 2: ' . sprintf('Server returned HTTP code %s', $httpCode);
        }

        if (isset($data_xml->data->error)) {

            return 'Ошибка 3: ' . $data_xml->data->error['message'];
        }


        if (isset($data_xml->data->info->error)) {

            return 'Ошибка 4: ' . $data_xml->data->info->error;
        }

        if(!isset($data_xml->data->info->statements['status'])){
            return 'Ошибка 5: ' . $data_xml->data->info;
        }

        if($data_xml->data->info->statements['status'] != 'excellent'){
            return 'Ошибка 6: ' . 'Статус ответа не равен excellent';
        }

        if(!$data_xml->data->info->statements->statement){
            return 'Сообщение: ' . $data_xml->data->info->statements->children() . 'Данные за указанный период отсутствуют';
        }

        return false;


    }

    /**
     * @param array $options
     * @return string
     */
    protected function executeCurl(array $options)
    {
        curl_setopt_array($this->curl, $options);

        $result = new \SimpleXMLElement(curl_exec($this->curl));

        //parse result for possible errors
        $result_error = $this->getErrorsFromResponse($result);

        $result_error_since_date = $this->getSinceDate();
        $result_error_to_date = $this->getToDate();

        if($result_error){
            self::getErrorMessageExcelFile($result_error, $result_error_since_date, $result_error_to_date);
            return false;
        }


        return $result;
    }

}