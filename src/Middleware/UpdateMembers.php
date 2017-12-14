<?php

namespace D2ca\Middleware;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class UpdateMembers
{

    /**
     * Constructor
     *
     * @param array $settings
     */
    public function __construct()
    {
    }

    /**
     * UpdateMembers middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $session = new \SlimSession\Helper;
        $token = $session->get('token');
        $clan  = $session->get('clan');
        if (!empty($token) || !empty($clan)) {
            \D2ca\Helper::createClan($clan['group']);
            \D2ca\Helper::updateMembers($clan['group'], $token);
        }

        return $next($request, $response);
    }
}
