<?php


/**
 * Class RozetkaSettings
 */
class XMLPriceSettings
{
    /**
     * Xml file encoding
     *
     * @var string
     */
    protected $encoding = 'windows-1251';

    /**
     * Output file name. If null 'php://output' is used.
     *
     * @var string
     */
    protected $outputFile;

    /**
     * Indent string in xml file. False or null means no indent;
     *
     * @var string
     */
    protected $indentString = "\t";

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @param string $encoding
     *
     * @return XMLPriceSettings
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputFile()
    {
        return $this->outputFile;
    }

    /**
     * @param string $outputFile
     *
     * @return XMLPriceSettings
     */
    public function setOutputFile($outputFile)
    {
        $this->outputFile = $outputFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getIndentString()
    {
        return $this->indentString;
    }

    /**
     * @param string $indentString
     *
     * @return XMLPriceSettings
     */
    public function setIndentString($indentString)
    {
        $this->indentString = $indentString;

        return $this;
    }
}
