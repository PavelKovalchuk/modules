<?php
/**
 * Created by PhpStorm.
 * User: pkovalchuk
 * Date: 07.02.2018
 * Time: 13:42
 */

/* Rontar classes  */
require_once('includes/application_top.php');
require_once(DIR_FS_INC . 'XMLPrice/Rontar/controllers/RontarController.php');
require_once(DIR_FS_CATALOG . 'includes/configure.php');
/* Rontar classes  */


$controller = new RontarController();

$controller->getFileAction(false);