<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require('../vendor/autoload.php');
require('./config/DbConfig.php');
require('./const/DbConst.php');

$app = new Silex\Application();
$app['debug'] = false;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/views',
));

// Register session service
$app->register(new Silex\Provider\SessionServiceProvider());

// Web handlers
$app->get('/', function (Request $request) use ($app) {
    $app['monolog']->addDebug('logging output.');

    $dbInfo = $app['session']->get('dbInfo');

    return $app['twig']->render('index.twig', array(
        'baseUrl' => $request->getBasePath() . "/index.php/dbConfig",
        'port' => DbConst::STR_PORT,
        'socket' => DbConst::STR_SOCKET,
        'message' => "",
        'dbInfo' => $dbInfo,
    ));
});

$app->post('/dbConfig/{port_socket}', function (Request $request, $port_socket) use ($app) {
    $app['monolog']->addDebug('logging output.');
    $app['twig']->addGlobal("basePath", $request->getBasePath());

    $username = $request->request->get('username');
    $password = $request->request->get('password');
    $host = $request->request->get('host');
    $port = $request->request->get('port');
    $dbname = $request->request->get('database');
    $unix_socket = $request->request->get('socket');

    $app['session']->set('dbInfo', array(
        'username' => $username,
        'password' => $password,
        'host' => $host,
        'port' => $port,
        'database' => $dbname,
        'socket' => $unix_socket,
    ));

    $dbConfig = new DbConfig();

    if ($port_socket == DbConst::STR_PORT) {
        $dbConfig->createDbConnection($username, $password, $host, $port, $dbname);
    } elseif ($port_socket == DbConst::STR_SOCKET) {
        $dbConfig->createDbConnection($username, $password, null, null, $dbname, $unix_socket);
    }

    $dbInfo = array();
    $message = "";
    if ($dbConfig->getDbConn() instanceof PDO) {
        $dbInfo['clientVer'] = $dbConfig->getDbConn()->getAttribute(PDO::ATTR_CLIENT_VERSION);
        $dbInfo['serverVer'] = $dbConfig->getDbConn()->getAttribute(PDO::ATTR_SERVER_VERSION);
        $dbInfo['connStatus'] = $dbConfig->getDbConn()->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    } else {
        $message = "PDO connection error";
    }

    return $app['twig']->render('dbConfig.twig', array(
        'dbInfo' => $dbInfo,
        'message' => $message,
    ));
});

$app->error(function (\Exception $e, Request $request, $code) {
    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return new Response($message);
});

$app->run();
