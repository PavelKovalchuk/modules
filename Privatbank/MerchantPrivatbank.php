<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 05.09.2017
 * Time: 15:04
 */

require_once(DIR_FS_INC . 'Privatbank/ExcelPrivatbank.php');


class MerchantPrivatbank extends ExcelPrivatbank
{

    /** @var string */
    const  MERCHANT_ID = '111111111';

    /** @var string */
    const  MERCHANT_SECRET = '111111111111';

    /** @var string */
    const  CARD_NUMBER = '1111111111111';



    protected $request;


    public function __construct(\DateTime $sinceDate, \DateTime $toDate)
    {
        parent::__construct($sinceDate, $toDate);

        $this->request = $this->createRequest();

    }

    /**
     * Create data for request in XML
     * @return SimpleXMLElement
     */
    public function createRequest()
    {

        $request = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\" ?><request version=\"1.0\"></request>");


        # Data
        $data = $request->addChild('data');
        $data->addChild('oper', 'cmt');
        $data->addChild('wait', '0');
        $data->addChild('test', '0');

        # Data > Payment
        $payment = $data->addChild('payment');
        $payment->addAttribute('id', '');

        # Data > Payment > Cardnum
        $cardnum = $payment->addChild('prop');
        $cardnum->addAttribute('name', 'card');
        $cardnum->addAttribute('value', $this->cleanupCardNumber(self::CARD_NUMBER));

        # Data > Payment > Start Date
        $country = $payment->addChild('prop');
        $country->addAttribute('name', 'sd');
        $country->addAttribute('value', $this->getSinceDate()->format('d.m.Y'));

        # Data > Payment > End Date
        $country = $payment->addChild('prop');
        $country->addAttribute('name', 'ed');
        $country->addAttribute('value', $this->getToDate()->format('d.m.Y'));

        # Merchant
        $merchant = $request->addChild('merchant');
        $merchant->addChild('id', self::MERCHANT_ID);
        $merchant->addChild('signature', $this->buildSignature($this->innerXML($data, self::MERCHANT_SECRET)));

        return $request;

    }

    public function getRequest()
    {
        return $this->request;
    }



    /**
     * @param string $data
     * @return string
     */
    protected function buildSignature($data)
    {
        return sha1(md5(sprintf(
            '%s%s',
            $data,
            self::MERCHANT_SECRET
        )));
    }

    /**
     * @param string $cardNumber
     * @return string
     */
    protected function cleanupCardNumber($cardNumber)
    {
        return preg_replace('/[^0-9]/', '', $cardNumber);
    }

    /**
     * @param \SimpleXMLElement $node
     * @return string
     */
    protected function innerXML(\SimpleXMLElement $node)
    {
        $content = "";
        foreach($node->children() as $child) {
            $content .= $child->asXML();
        }
        return $content;
    }


}