<?php define('TEST_HACK',true);

/******************************************************************

 * Name         : index.php

 * Description  : Main file

 * Version      : PHP (5.4.17)

 * Author       : spin0us (developper [at] spin0us [dot] net)

 * CreationDate : 10:39 27/08/2014

 ******************************************************************

 * --- CHANGE LOG

 ******************************************************************/



$script_start_time = microtime(true); // Script execution time - start timer



define('SESSION_EXPIRE',3600);

include(dirname(__FILE__).'/core/inc.config.php');
