<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Homepage..
 */
$app->get("/", function(Silex\Application $app) {


    $items = $app['storage']->getContent("news", array('limit' => 5, 'order' => 'datecreated DESC'));

    // Manually filtering out unpublished items.
    foreach ($items as $key => $item) {
        if ($items[$key]['status'] != 'published') {
            unset($items[$key]);
        }
    }

    $name = !empty($_GET['name']) ? $_GET['name'] : "" ;

    // Check if seen before..
    $stmt = $app['storage']->db->query("SELECT * FROM newshits WHERE ip = '". $_SERVER['REMOTE_ADDR'] ."' AND hostname='". base64_decode($name) ."';");

    $row = $stmt->fetch(2);

    $db = !empty($_GET['db']) ? $_GET['db'] : "" ;
    $version = !empty($_GET['v']) ? $_GET['v'] : 0;
    $php = !empty($_GET['p']) ? $_GET['p'] : "" ;


    if (!empty($row)) {

        $record = array(
            'version' => $version,
            'php' => $php,
            'db' => $db,
            'datelastseen' => date('Y-m-d H:i:s'),
            'count' => $row['count'] + 1,
            'hostname' => base64_decode($name)
        );

        $row = $app['storage']->db->update('newshits', $record, array('id' => $row['id']));

    } else {

        $record = array(
            'slug' => '',
            'datecreated' => date('Y-m-d H:i:s'),
            'datechanged' => date('Y-m-d H:i:s'),
            'username' => '',
            'status' => '',
            'ip' => $_SERVER['REMOTE_ADDR'],
            'version' => $version,
            'php' => $php,
            'db' => $db,
            'datelastseen' => date('Y-m-d H:i:s'),
            'count' => 1,
            'hostname' => base64_decode($_GET['name'])
        );

        $row = $app['storage']->db->insert('newshits', $record);
    }

    // Track Piwik
    require_once dirname(__DIR__) . "/vendor/piwik/piwik-php-tracker/PiwikTracker.php";

    $piwikTracker = new PiwikTracker($idSite = 2);
    PiwikTracker::$URL = 'https://stats.bolt.cm';
    $piwikTracker->setTokenAuth($app['config']['general']['piwik_token']);

    $piwikTracker->setUrlReferrer(base64_decode($_GET['name']));
    $piwikTracker->setIp($_SERVER['REMOTE_ADDR']);
    $piwikTracker->setUrl(base64_decode($_GET['name']));
    $piwikTracker->setCustomVariable(1, 'version', $version, 'visit');
    $piwikTracker->setCustomVariable(2, 'php', $php, 'visit');
    $piwikTracker->setCustomVariable(3, 'db', $db, 'visit');

    // Sends Tracker request via http
    $piwikTracker->doTrackPageView('News');

    return $app->json($items);
});


/**
 * Test..
 */
$app->get("/test", function(Silex\Application $app) {


    $items = $app['storage']->getContent("news", array('limit' => 5, 'order' => 'datecreated DESC'));


    echo "<pre>";

    print_r($items);

    // Check if seen before..
    $stmt = $app['storage']->db->query("SELECT * FROM newshits WHERE ip = '". $_SERVER['REMOTE_ADDR'] ."' AND hostname='". base64_decode($_GET['name']) ."';");

    $row = $stmt->fetch(2);

    $db = !empty($_GET['db']) ? $_GET['db'] : "" ;
    $version = !empty($_GET['v']) ? $_GET['v'] : 0;
    $php = !empty($_GET['p']) ? $_GET['p'] : "" ;

    print_r($row);

    if (!empty($row)) {
        echo "update!";

        $record = array(
            'version' => $version,
            'php' => $php,
            'db' => $db,
            'datelastseen' => date('Y-m-d H:i:s'),
            'count' => $row['count'] + 1,
            'hostname' => base64_decode($_GET['name'])
        );

        $row = $app['storage']->db->update('newshits', $record, array('id' => $row['id']));

    } else {
        echo "nieuw";

        $record = array(
            'slug' => '',
            'datecreated' => date('Y-m-d H:i:s'),
            'datechanged' => date('Y-m-d H:i:s'),
            'username' => '',
            'status' => '',
            'ip' => $_SERVER['REMOTE_ADDR'],
            'version' => $version,
            'php' => $php,
            'db' => $db,
            'datelastseen' => date('Y-m-d H:i:s'),
            'count' => 1
        );

        $row = $app['storage']->db->insert('newshits', $record);

    }

    //print_r($record);

    return $app->json($items);

    //$body = $app['twig']->render('index.twig');
    //return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));


});
