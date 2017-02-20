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

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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

    public function callbackLogin(Application $app, Request $request)
    {

        $options = array(
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'dig_login'
        );

        $form = $app['form.factory']->createBuilder(FormType::class, $options)
            ->add('username')
            ->add('password', PasswordType::class)
            ->add('submit', SubmitType::class, array('label' => 'Login'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $client = $this->getClient();
            $response = null;

            try {
                $response = $client->getToken($data['username'], $data['password']);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
            }

            // If login is successful
            if ($response != null) {
                $redirectResponse = new RedirectResponse($this->config['flarum']['url']);
                $redirectResponse->headers->setCookie(new Cookie('flarum_remember', $response['token']));

                return $redirectResponse;
            } else {
                $request->getSession()->getFlashBag()->add(
                    'notice',
                    'Invalid credentials!'
                );
            }
        }

        // display the form
        return $app['twig']->render('dig_index.twig', array('form' => $form->createView()));
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

        $client = new Client($this->config['flarum']['url']."api/", $this->config['flarum']['token']);

        return $client;
    }
}