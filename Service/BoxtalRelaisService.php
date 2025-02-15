<?php

namespace BoxtalShipping\Service;

use BoxtalShipping\BoxtalShipping;

class BoxtalRelaisService
{
    private $tokenService;

    public function getParcelPoints($street, $zipcode, $city, $countryCode, $searchNetworks)
    {
        $response = $this->makeApiRequest($street, $zipcode, $city, $countryCode, $searchNetworks);
        return $response;
    }

    private function makeApiRequest($street, $zipcode, $city, $countryCode, $searchNetworks)
    {
        $baseUrl = BoxtalShipping::BOXTAL_PARCEL_POINT_API_URL_V3;

        $tokenService = new BoxtalApiTokenService();
        $token = $tokenService->getBoxtalTokenApiv3();

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $queryParams = http_build_query([
            'street' => $street,
            'city' => $city,
            'postalCode' => $zipcode,
            'countryIsoCode' => $countryCode,
        ]);

        $buildedUrl = $baseUrl . '?' . $queryParams . $searchNetworks;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $buildedUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $result;
    }
}
