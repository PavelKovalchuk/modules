<?php

require_once(GOOGLE_HELPER_DIR . 'Item.php');

class Feed
{

    /**
     * Define Google Namespace url
     * @var string
     */
    protected $namespace = 'http://base.google.com/ns/1.0';

    /**
     * @var string
     */
    protected $version = '2.0';

    /**
     * @var string
     */
    protected $iso4217CountryCode = 'UAH';

    /**
     * Stores the list of items for the feed
     * @var Item[]
     */
    private $items = array();

    /**
     * @var bool
     */
    private $channelCreated = false;

    /**
     * The base for the feed
     * @var SimpleXMLElement
     */
    private $feed = null;

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var string
     */
    private $link = '';

    /**
     * Feed constructor
     */
    public function __construct()
    {
        $this->feed = new SimpleXMLElement('<rss xmlns:g="' . $this->namespace . '" version="' . $this->version . '"></rss>');
    }

    /**
     * @param string $title
     */
    public function title($title)
    {
        $this->title = (string)$title;
    }

    /**
     * @param string $description
     */
    public function description($description)
    {
        $this->description = (string)$description;
    }

    /**
     * @param string $link
     */
    public function link($link)
    {
        $this->link = (string)$link;
    }

    /**
     * @param $code
     */
    public function setIso4217CountryCode( $code )
    {
        $this->iso4217CountryCode = $code;
    }

    /**
     * @return string
     */
    public function getIso4217CountryCode()
    {
        return $this->iso4217CountryCode;
    }

    /**
     * [channel description]
     */
    private function channel()
    {
        if (! $this->channelCreated) {
            $channel = $this->feed->addChild('channel');
            $channel->addChild('title', htmlspecialchars($this->title));
            $channel->addChild('link', htmlspecialchars($this->link));
            $channel->addChild('description', htmlspecialchars($this->description));
            $this->channelCreated = true;
        }
    }

    /**
     * @return Item
     */
    public function createItem()
    {

        $this->channel();
        $item = new Item($this);
        $this->items[] = $item;
        return $item;
    }

    /**
     * Adds items to feed
     */
    private function addItemsToFeed()
    {
        foreach ($this->items as $item) {
            /** @var SimpleXMLElement $feedItemNode */
            $feedItemNode = $this->feed->channel->addChild('item');
            foreach ($item->nodes() as $itemNode) {
                if (is_array($itemNode)) {
                    foreach ($itemNode as $node) {
                        $feedItemNode->addChild($node->get('name'), $node->get('value'), $node->get('_namespace'));
                    }
                } else {
                    $itemNode->attachNodeTo($feedItemNode);
                }
            }
        }
    }

    /**
     * Generate RSS feed
     * @param bool $output
     * @return string
     */
    public function asRss($output = false)
    {
        if (ob_get_contents()) ob_end_clean();
        $this->addItemsToFeed();
        $data = $this->feed->asXml();
        if ($output) {
            header('Content-Type: application/xml; charset=utf-8');
            die($data);
        }
        return $data;
    }

}
