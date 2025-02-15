<?php

namespace BoxtalShipping\Service;

use BoxtalShipping\BoxtalShipping;
use Thelia\Model\ConfigQuery;

class BoxtalApiTokenService
{
    public function getBoxtalTokenApiv1()
    {
        $apiKey = ConfigQuery::read('boxtal_api_key_v1');
        $apiSecret = ConfigQuery::read('boxtal_api_secret_v1');

        $credentials = base64_encode($apiKey . ':' . $apiSecret);

        return $credentials;
    }

    public function getBoxtalTokenApiv3()
    {
        $apiKey = ConfigQuery::read('boxtal_api_key');
        $apiSecret = ConfigQuery::read('boxtal_api_secret');

        $credentials = base64_encode($apiKey . ':' . $apiSecret);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, BoxtalShipping::BOXTAL_API_TOKEN_URL_V3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Basic ' . $credentials
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $tokenData = json_decode($response, true);
            if (!isset($tokenData['accessToken'])) {
                throw new \Exception("Invalid Boxtal API response :" . json_encode($response));
            }
            return $tokenData['accessToken'];
        } else {
            throw new \Exception("Error when retrieving Boxtal token: HTTP Code {$httpCode}");
        }
    }
}
