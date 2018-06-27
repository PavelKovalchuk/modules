<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 05.03.2018
 * Time: 15:03
 */

require_once DIR_FS_INC . 'SendCustomMail.php';

abstract class NotifierHelperAbstract
{
    use LiqPayTrait;

    protected $senderLetterClassName = 'SendCustomMail';

    /**
     * @return string
     */
    protected function getSenderLetterClassName()
    {
        return $this->senderLetterClassName;
    }

}