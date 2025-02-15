<?php

namespace BoxtalShipping\Service;

use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Service\BoxtalApiTokenService;

use Thelia\Model\ConfigQuery;

class BoxtalPricingService
{
    // cette méthode pourrait servir a faire une requete pour obtenir le prix d'un envoi précis
    /*public function getPricingData()
    {
        $credentials = $this->apiTokenService->getBoxtalTokenApiv1();
        $apiKey = ConfigQuery::read('boxtal_api_key_v1');
        $apiSecret = ConfigQuery::read('boxtal_api_secret_v1');

        $credentials = base64_encode($apiKey . ':' . $apiSecret);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://test.envoimoinscher.com/api/v1/cotation?" . http_build_query([
            'expediteur.pays' => 'FR',
            'expediteur.code_postal' => ConfigQuery::read('store_zipcode'),
            'expediteur.ville' => ConfigQuery::read('store_city'),
            'expediteur.type' => 'entreprise',
            'destinataire.pays' => 'FR',
            'destinataire.code_postal' => '13001',
            'destinataire.ville' => 'Marseille',
            'destinataire.type' => 'particulier',
            'colis_1.poids' => 10,
            'colis_1.longueur' => 10,
            'colis_1.largeur' => 10,
            'colis_1.hauteur' => 10,
            'code_contenu' => '10000'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials,
            'Acce: application/xml'
        ]);
        $response = curl_exec($ch);

        curl_close($ch);

        return ($response);
    }*/

    public function getGlobalPricingData()
    {
        $boxtalApiTokenService = new BoxtalApiTokenService();

        $credentials = $boxtalApiTokenService->getBoxtalTokenApiv1();

        $weightRanges = range(1, 30);
        $pricingData = [];

        foreach ($weightRanges as $weight) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, BoxtalShipping::BOXTAL_COTATIONS_URL_V1 . http_build_query([
                'expediteur.pays' => 'FR',
                'expediteur.code_postal' => ConfigQuery::read('store_zipcode'),
                'expediteur.ville' => ConfigQuery::read('store_city'),
                'expediteur.type' => 'entreprise',
                'destinataire.pays' => 'FR',
                'destinataire.code_postal' => '13001',
                'destinataire.ville' => 'Marseille',
                'destinataire.type' => 'particulier',
                'colis_1.poids' => $weight,
                'colis_1.longueur' => 10,
                'colis_1.largeur' => 10,
                'colis_1.hauteur' => 10,
                'code_contenu' => '10000'
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $credentials,
                'Accept: application/xml'
            ]);
            $response = curl_exec($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode === 400) {
                throw new \Exception('Requête invalide : Code 400');
            } elseif ($httpCode === 401) {
                throw new \Exception('Forbidden');
            } elseif ($httpCode === 500) {
                throw new \Exception('Erreur système');
            } elseif ($httpCode === 504) {
                throw new \Exception('Service indisponible');
            }

            $xml = simplexml_load_string($response);
            $array = json_decode(json_encode($xml), TRUE);

            if (isset($array['shipment']['offer'])) {
                foreach ($array['shipment']['offer'] as $offer) {
                    $operatorCode = $offer['operator']['code'];
                    $serviceName = $offer['service']['label'];
                    $price = $offer['price']['tax-inclusive'];
                    $pricingData[$operatorCode][$serviceName][$weight] = $price;
                }
            }
        }

        return json_encode($pricingData);
    }
}
