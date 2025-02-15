<?php

namespace BoxtalShipping\Service;

use BoxtalShipping\BoxtalShipping;

class BoxtalGetCategoriesService
{

    public function getContentCategories()
    {
        $tokenService = new BoxtalApiTokenService();
        $token = $tokenService->getBoxtalTokenApiv3();

        if (!$token) {
            throw new \Exception("Impossible de générer le token Boxtal");
        }

        $url = BoxtalShipping::BOXTAL_CONTENT_CATEGORY_API_URL_V3;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);

        $response = curl_exec($ch);

        return $response;

        if (curl_errno($ch)) {
            throw new \Exception('Erreur cURL : ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new \Exception("Erreur API Boxtal : Code HTTP $httpCode. Réponse : $response");
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}
