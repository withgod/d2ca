<?php
require_once(realpath(__DIR__ . '/../src/bootstrap.php'));

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$config = [
	'settings' => [
		'displayErrorDetails' => IS_DEVELOPMENT,
	]
];

$app = new \Slim\App($config);
$container = $app->getContainer();

/**
 * https://www.slimframework.com/docs/concepts/middleware.html#how-does-middleware-work
 * LIFE: Last In First Executed
 */
$app->add(new \D2ca\Middleware\UpdateMembers());

$app->add(new \Slim\Middleware\Session([
    'name' => 'd2cm',
    'autorefresh' => false,
    'secure' => true,
    'lifetime' => '1 hour'
]));

$container['provider'] = function ($c) {
    return \D2ca\Helper::provider();
};
$container['logger'] = function($c) {
    return \D2ca\Helper::logger();
};

$container['session'] = function ($c) {
    return new \SlimSession\Helper();
};

$container['view'] = function ($c) {
    $opt = [];
    if (IS_PRODUCTION === TRUE) {
        $opt['cache'] = realpath(APP_ROOT . '/cache');
    }
    $view = new \Slim\Views\Twig(realpath(APP_ROOT . '/view'), $opt);
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new \Slim\Views\TwigExtension($c['router'], $basePath));

    $twig = $view->getEnvironment();
    $twig->addGlobal('env_log_level', getenv('LOG_LEVEL'));

    return $view;
};


$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");
    $this->db->doHoge();

    return $response;
});

$app->get('/groups/', function  (Request $request, Response $response) {

    $clans = \D2ca\Helper::getClans();
    return $this->view->render($response, 'groups.html', ['groups' => $clans]);
});

$app->get('/group/{groupid}', function  (Request $request, Response $response) {
    $groupid = $request->getAttribute('groupid');

    $clan = \D2ca\Helper::getClan($groupid);
    if (empty($clan)) {
        return $response->withStatus(404)->write('404 Not Found');
    }

    $members = \D2ca\Helper::getMembers($groupid);
    $updating = \D2ca\Helper::membersUpdateBatchCount($groupid) ? true : false;

    return $this->view->render($response, 'group.html', ['group' => $clan, 'members' => $members, 'updating' => $updating]);
});

$app->get('/logout', function (Request $request, Response $response) {
    $session = new \SlimSession\Helper;
    $session::destroy();
    return $response->write('logout');
});

$app->get('/login', function (Request $request, Response $response) {
    $session = new \SlimSession\Helper;

    $authorizationUrl = $this->provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $session->set('oauth2state', $this->provider->getState());
    $this->logger->debug('redirect to ' . $authorizationUrl);

    return $response->withRedirect($authorizationUrl, 307);
});

$app->get('/redirect', function (Request $request, Response $response) {
    $session = new \SlimSession\Helper;

    $state = $request->getParam('state');
    $this->logger->debug("state[$state]");
    if (empty($state) || ($state !== $session->get('oauth2state'))) {
        $session->destroy();
        $this->logger->error('oauth state error');
        return $response->withStatus(500)->write('Invalid Oauth State');
    } else {
        try {
            /* @var $accessToken League\OAuth2\Client\Token\AccessToken */
            $accessToken = $this->provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);

            $client = new \Destiny\Client(getenv('OAUTH_API_KEY'), $accessToken->getToken());
            /* @var $user \Destiny\Objects\GeneralUser */
            $user = $client->getCurrentBungieUser();
            $user_info = $client->getMembershipDataForCurrentUser();
            $duser_info = $user_info[0][0]; #i dont know multiple response.
            //$buser_info = $user_info[1];
            $profile = $client->getProfile($duser_info['membershipType'], $duser_info['membershipId'], 100, 200, 400);
            $clan = $client->getGroupV2User($duser_info['membershipType'], $duser_info['membershipId'])[0];


            $session->set('user', $user);
            $session->set('clan', $clan);
            $session->set('profile', $profile);
            $session->set('token', $accessToken->getToken());

            $this->logger->debug('/redirect ' . $user->displayName() . '(' . $user->membershipId() .')' . $clan['group']['name'] . '(' . $clan['group']['groupId'] . ')');
            return $response->withRedirect('/d2ca/', 307);

        } catch(Exception $e) {
            $this->logger->error($e->getMessage());
            return $response->withStatus(500)->write('oauth error [' . $e->getMessage() . ']');
        }
    }
});

$app->get('/', function (Request $request, Response $response) {
    $this->logger->debug('access to top');
    $user = FALSE;


    if ($this->session->exists('user')) {
        /* @var $user \Destiny\Objects\GeneralUser */
        $user = $this->session->get('user');
        $clan = $this->session->get('clan')['group'];
        \D2ca\Helper::updateMembers($clan, $this->session->get('token'));
        $this->logger->debug('user already logged in as[' . $user->displayName() . '(' . $user->membershipId() . ')]');
        $user = ['name' => $user->displayName(), 'clan_name' => $clan['name'], 'clan_id' => $clan['groupId']];
    }

    $clans = \D2ca\Helper::getClans();
    return $this->view->render($response, 'top.html', ['user' => $user, 'groups' => $clans]);
});

$app->run();
