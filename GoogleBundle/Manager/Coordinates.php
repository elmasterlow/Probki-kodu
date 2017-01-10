<?php

namespace Application\Script\CoordinatesBundle\Manager;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

class Coordinates
{
    const URL = 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s';

    /** @var Client */
    protected $client;

    /** @var string */
    protected $apiKey;

    /** @var RequestStack */
    protected $requestStack;

    /** @var Router */
    protected $router;

    public function __construct($apiKey, RequestStack $requestStack, Router $router)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client();
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public function setCoordinates($address)
    {
        $response = $this->client->request('GET', sprintf(self::URL, $address, $this->apiKey));

        $url = $this->router->generate('homepage');
        $redirect = new RedirectResponse($url);

        $data = json_decode((string)$response->getBody(), true);
        $result = reset($data['results']);

        if ($data['status'] === 'OK' && isset($result['geometry'])) {
            $geometry = $result['geometry'];
            $cookieData = json_encode(['latitude' => $geometry['location']['lat'], 'longitude' => $geometry['location']['lng']]);
            // send cookie data
            $redirect->headers->setCookie(new Cookie('coordinates', $cookieData));
        }

        return $redirect;
    }

    public function getCoordinates()
    {
        $coordinates = ['latitude' => 52.2297700, 'longitude' => 21.0117800];
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        if ($request->cookies->has('coordinates')) {
            $cookieData = json_decode($request->cookies->get('coordinates'), true);
            $coordinates = [
                'latitude' => $cookieData['latitude'],
                'longitude' => $cookieData['longitude'],
            ];

            $condition = function ($value) {
                if (is_float($value)) {
                    return $value;
                }
                return false;
            };

            $coordinates = array_map($condition, $coordinates);
        }

        return $coordinates;
    }
}