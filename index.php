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



/*

 * Routing

 */

    $_url = trim($_GET['_url'],'/');

    $_url = $_url == '' ? 'default' : $_url;

    $uri = explode('/',$_url);

    $page = array_shift($uri);

    // Url shortner

    if(preg_match('/^s([a-f0-9]{8})$/',$page,$matches))

    {

        $_url = Shortner::get($matches[1]);

        if($_url === false) redirect('./');

        $uri = explode('/',$_url);

        $page = array_shift($uri);

    }

    if(preg_match('/^[a-f0-9]{32}$/',$page))

    {

        define('CUSTOMER_KEY',$page);

        $page = array_shift($uri);

    }

    elseif(isset($_SESSION[SESSKEY]['key']))

    {

        define('CUSTOMER_KEY',$_SESSION[SESSKEY]['key']);

    }

/* End of Routing */







/*

 * Routing before authentication

 */

    switch($page)

    {

        case 'password-reset':

            include(dirname(__FILE__).'/password-reset.html');

            exit;

        case 'validate':

            if(preg_match("/^[a-f0-9]{32}$/",$uri[0]))

            {

                $rows = DB::query("SELECT `contact`,`newpassword`,contacts.`mail`,`key`,`release`,`wiki` FROM password_reset_request LEFT OUTER JOIN contacts ON `contact`=contacts.`id` LEFT OUTER JOIN customers ON `customer`=customers.`id` WHERE MD5(CONCAT(newpassword,validationcode))=:val AND `expireat`>TIMESTAMP(NOW())",array(':val' => $uri[0]));

                if(count($rows) == 1)

                {

                    DB::query("UPDATE contacts SET `pass`=:pwd WHERE `id`=:cid",array(

                        ':pwd' => $rows[0]['newpassword'],

                        ':cid' => $rows[0]['contact']

                        ));

                    // Automatically log this user to his homepage

                    if(Auth::autoSignIn(array(

                        'user' => $rows[0]['mail'],

                        'pass' => $rows[0]['newpassword'],

                        'key' => $rows[0]['key']

                        )))

                    {

                        redirect('./'.$rows[0]['key']);

                    }

                }

            }

    }

/* End of Routing before authentication */







/*

 * Authentification control

 */

    Auth::signIn();

    if( $page == 'signout' )

    {

        Auth::signOut();

    }

    if( ! Auth::isRegistered() )

    {

        include(dirname(__FILE__).'/login.html');

        exit;

    }

    elseif( !defined('CUSTOMER_KEY') && $_SESSION[SESSKEY]['isAdmin'] === false )

    {

        Auth::signOut();

    }

    else if( $page == 'unsubscribe-mail' ) {

        DB::query("UPDATE `contacts` SET `massmailing`=0 WHERE `mail`=:usr", array(':usr' => Auth::$usr['mail']));

        $_SESSION[SESSKEY]['massmailing'] = 0;

        redirect();

    } else if( $page == 'subscribe-mail' ) {

        DB::query("UPDATE `contacts` SET `massmailing`=1 WHERE `mail`=:usr", array(':usr' => Auth::$usr['mail']));

        $_SESSION[SESSKEY]['massmailing'] = 1;

        redirect();

    }

/* End of Authentification control */







/*

 * Public access

 */

    if(defined('CUSTOMER_KEY'))

    {

        if (Auth::isAdmin()) {

            $user = Auth::_('user');

            if ($user == '_epuig') {

                include(dirname(__FILE__).'/public2.php');

                exit;

            }

        }

        include(dirname(__FILE__).'/public.php');

        exit;

    }

/* End of Public access */







/*

 * Template

 */

    $template = new Template(Config::read('path.tpl'));

    $template->set_filenames(array('header' => '__header.html','footer' => '__footer.html'));



    if( file_exists(ROOT_PATH . $page . '.php') )

    {

        include(ROOT_PATH . $page . '.php');

    }

    if( file_exists(Config::read('path.tpl') . $page . '.html') )

    {

        $template->set_filenames(array('body' => $page . '.html'));

    }

    else

    {

        redirect();

    }



    if( Auth::isAdmin() )

    {

        $template->assign_block_vars('admin',array());

    }

    $template->assign_vars(array(

        'USERNAME'      => Auth::isAdmin() ? Auth::_('user') : Auth::_('nom').' '.Auth::_('prenom'),

        'VERSION'       => Config::read('version.num').' ('.Config::read('version.date').')',

        'SITE_NAME'     => Config::read('site.name'),

        'SITE_URL'      => Config::read('site.url'),

        'TA_URL'        => Config::read('tech.academy.url'),

        'COPYRIGHT'     => date("Y"),

        'PAGE'          => $page,

        'SEASON'        => Config::read('cfg.season'),

        'TODAY'         => date("d/m/Y")

    ));

    if( file_exists(dirname(__FILE__).'/assets/js/'.$page.'.js') )

    {

        $template->assign_block_vars('script',array('DATE' => filemtime(dirname(__FILE__).'/assets/js/'.$page.'.js')));

    }



    header("Content-type: text/html;charset=utf8");

    $template->pparse('header');

    $template->pparse('body');

    if( Auth::_('userid') == 1 )

    {

        $totaltime = round(microtime(true) - $script_start_time,4);

        $template->assign_var('FOOTER_INFOS',DB::$nbr." requ&#232;tes - ex�cution en $totaltime secondes");

    }

    $template->pparse('footer');

/* End of Template */

