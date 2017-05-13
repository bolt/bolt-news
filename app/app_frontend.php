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

    $name = getName();
    $db = !empty($_GET['db']) ? $_GET['db'] : "" ;
    $version = !empty($_GET['v']) ? $_GET['v'] : 0;
    $php = !empty($_GET['p']) ? $_GET['p'] : "" ;

    // Check if seen before..
    $stmt = $app['storage']->db->query("SELECT * FROM newshits WHERE ip = '". $_SERVER['REMOTE_ADDR'] ."' AND hostname='". $name ."';");

    $row = $stmt->fetch(2);

    if (!empty($row)) {

        $record = array(
            'version' => $version,
            'php' => $php,
            'db' => $db,
            'datelastseen' => date('Y-m-d H:i:s'),
            'count' => $row['count'] + 1,
            'hostname' => $name
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
            'hostname' => $name
        );

        $row = $app['storage']->db->insert('newshits', $record);
    }

    // Track Piwik
    $token = $app['config']['general']['piwik_token'];
    trackPiwik($token, $name, $version, $php, $db);

    return $app->json($items);
});

