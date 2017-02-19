<?php

namespace Bolt\Extension\Euwas\EuwasTestExt\Controller;

use Bolt\Extension\Bolt\Members\Exception\ConfigurationException;
use Bolt\Extension\Euwas\EuwasTestExt\Client;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for Dig Login routes.
 *
 * @author Euwas <euwas@outlook.com>
 */
class LoginController implements ControllerProviderInterface
{
    /** @var Application */
    protected $app;


    /** @var array The extension's configuration parameters */
    private $config;

    /**
     * Initiate the controller with Bolt Application instance and extension config.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $this->app = $app;

        /** @var ControllerCollection $ctr */
        $ctr = $app['controllers_factory'];

        $ctr->match('/login', [$this, 'callbackLogin']);
        $ctr->match('/listUsers', [$this, 'callbackListUsers']);
        $ctr->match('/koala/{type}', [$this, 'callbackKoalaCatching']);

        return $ctr;
    }

    /**
     * Handles GET and POST requests on /login and return a template.
     *
     * @param Request $request
     *
     * @return string
     */
    public function callbackLogin(Application $app, Request $request)
    {
        if ($request->isMethod('POST')) {
            $identification = $request->get("username");
            $password = $request->get("password");

            $client = $this->getClient();
            $response = null;
            $invalid_credentials = false;

            try {
                $response = $client->getToken($identification, $password);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $invalid_credentials = true;
            }

            // If login is successful
            if (!$invalid_credentials) {
                $redirectResponse = new RedirectResponse('https://dig.kb7.nl/forum/');
                $redirectResponse->headers->setCookie(new Cookie('flarum_remember', $response['token']));

                return $redirectResponse;
            } else {
                return $app['twig']->render('dig_member_login.twig', ['title' => 'Look at This Nice Template', 'invalid_credentials' => true], []);
            }
        }

        return $app['twig']->render('dig_member_login.twig', ['title' => 'Look at This Nice Template'], []);
    }

    public function callbackListUsers(Request $request)
    {
        $client = $this->getClient();

        $response = $client->load('users', 1);
        $jsonResponse = new JsonResponse();
        $jsonResponse->setData($response);

        return $jsonResponse;
    }

    /**
     * @param Request $request
     * @param string $type
     *
     * @return Response
     */
    public function callbackKoalaCatching(Request $request, $type)
    {
        if ($type === 'dropbear') {
            return new Response('Drop bear sighted!', Response::HTTP_OK);
        }

        return new Response('Koala in a tree!', Response::HTTP_OK);
    }

    private function getClient()
    {
        if (!isset($this->config['flarum']) || !isset($this->config['flarum']['url'])) {
            throw new ConfigurationException("Missing flarum url");
        }

        $client = new Client($this->config['flarum']['url'], $this->config['flarum']['token']);

        return $client;
    }
}