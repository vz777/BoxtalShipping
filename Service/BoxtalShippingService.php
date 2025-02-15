<?php

namespace BoxtalShipping\Service;

use BoxtalShipping\BoxtalShipping;
use Thelia\Model\Order;
use Thelia\Model\ConfigQuery;
use Thelia\Model\OrderAddressQuery;
use Thelia\Model\CountryQuery;
use Thelia\Log\Tlog;

class BoxtalShippingService
{
    public function createShipment(Order $order, string $shippingMethod, $length, $width, $height, $weight, $relayPoint, $insured, $contentDescription, $addressType)
    {
        // il faut probablement lever des exceptions si les config query ne sont pas remplies

        $customerDeliveryAddress = OrderAddressQuery::create()->findPk($order->getDeliveryOrderAddressId());

        $number = $this->extractStreetNumber($customerDeliveryAddress->getAddress1());
        $street = trim($this->extractStreetName($customerDeliveryAddress->getAddress1()) . ' ' . $customerDeliveryAddress->getAddress2() . ' ' . $customerDeliveryAddress->getAddress3());
        $phone = $customerDeliveryAddress->getCellphone();

        if (null == $phone) {
            $phone = $customerDeliveryAddress->getPhone();
        }

        if (!$customerDeliveryAddress) {
            throw new \Exception("Aucune adresse de livraison trouvée pour la commande {$order->getId()}");
        }

        $customer = $order->getCustomer();

        if (empty($contentDescription)) {
            $contentDescription = ConfigQuery::read('boxtal_default_category');
        }

        $categoriesService = new BoxtalGetCategoriesService();
        $contentCategoriesJson = $categoriesService->getContentCategories();
        $contentCategoriesData = json_decode($contentCategoriesJson, true);

        $categoryId = '';
        $categoryDescription = '';
        if (json_last_error() === JSON_ERROR_NONE && isset($contentCategoriesData['content'])) {
            foreach ($contentCategoriesData['content'] as $category) {
                if ($category['id'] === $contentDescription) {
                    $categoryId = $category['id'];
                    $categoryDescription = $category['label'];
                    break;
                }
            }
        }

        if (empty($categoryId)) {
            throw new \Exception("Content category not found for ID : $contentDescription");
        }

        $shipmentData =
            [
                'insured' => $insured,
                'shipment' => [
                    'packages' => [
                        [
                            'type' => 'PARCEL',
                            'value' => [
                                'value' => $order->getTotalAmount(),
                                'currency' => 'EUR'
                            ],
                            'length' => $length,
                            'width' => $width,
                            'height' => $height,
                            'weight' => $weight,

                            'content' => [
                                'id' => $categoryId,
                                'description' => $categoryDescription
                            ],
                            'stackable' => true,
                            'externalId' => 'ORDER-' . $order->getId()
                        ]
                    ],
                    'toAddress' => [

                        'type' => $addressType,
                        'contact' => [
                            'email' => $customer->getEmail(),
                            'phone' => $phone,
                            'lastName' => $customerDeliveryAddress->getLastname(),
                            'firstName' => $customerDeliveryAddress->getFirstname()
                        ],

                        'location' => [
                            'countryIsoCode' => $customerDeliveryAddress->getCountry(),
                            'city' => $customerDeliveryAddress->getCity(),
                            'number' => $number,
                            'street' => $street,
                            'postalCode' => $customerDeliveryAddress->getZipcode(),
                            'countryIsoCode' => $customerDeliveryAddress->getCountry()->getIsoalpha2(),
                        ]

                    ],
                    'externalId' => 'ORDER-' . $order->getId(),

                    'fromAddress' => [
                        'type' => 'BUSINESS',
                        'contact' => [
                            'email' => ConfigQuery::read('store_email'),
                            'phone' => ConfigQuery::read('store_phone'),
                            //boxtal does not support company names over 35 chars
                            'company' => substr(ConfigQuery::read('store_name'), 0, 35),
                            'lastName' => ConfigQuery::read('boxtal_lastname'),
                            'firstName' => ConfigQuery::read('boxtal_firstname')
                        ],
                        'location' => [
                            'city' => ConfigQuery::read('store_city'),
                            'number' => $this->extractStreetNumber(ConfigQuery::read('store_address1')),
                            'street' => $this->extractStreetName(ConfigQuery::read('store_address2')),
                            'postalCode' => ConfigQuery::read('store_zipcode'),
                            'countryIsoCode' => CountryQuery::create()
                                ->findOneById(ConfigQuery::read('store_country'))
                                ->getIsoalpha2()
                        ]
                    ],

                    'returnAddress' => [
                        'type' => 'BUSINESS',
                        'contact' => [
                            'email' => ConfigQuery::read('store_email'),
                            'phone' => ConfigQuery::read('store_phone'),
                            //boxtal does not support company names over 35 chars
                            'company' => substr(ConfigQuery::read('store_name'), 0, 35),
                            'lastName' => ConfigQuery::read('boxtal_lastname'),
                            'firstName' => ConfigQuery::read('boxtal_firstname')
                        ],
                        'location' => [
                            'city' => ConfigQuery::read('store_city'),
                            'number' => $this->extractStreetNumber(ConfigQuery::read('store_address1')),
                            'street' => $this->extractStreetName(ConfigQuery::read('store_address1')),
                            'postalCode' => ConfigQuery::read('store_zipcode'),
                            'countryIsoCode' => CountryQuery::create()
                                ->findOneById(ConfigQuery::read('store_country'))
                                ->getIsoalpha2()
                        ],
                        //'additionalInformation' => ConfigQuery::read('store_return_additional_info', '')
                    ],
                    //'pickupPointCode' => $relayPoint,
                    //'dropOffPointCode' => '99438',
                    /*'customsDeclaration' => [
                    'reason' => 'SALE',
                    'articles' => [
                        [
                            'quantity' => 2,
                            'unitValue' => [
                                'value' => 15,
                                'currency' => 'EUR'
                            ],
                            'unitWeight' => 0.7,
                            'description' => 'Illustrated book for children',
                            //'tariffNumber' => '49030000',
                            'originCountry' => 'EEE',
                            //'packageExternalId' => 'XYZ12345'
                        ]
                    ]
                ]*/
                ],
                'labelType' => 'PDF_A4',
                'shippingOfferCode' =>  $shippingMethod,
                //'shippingOfferId' => $shippingMethod,
                'expectedTakingOverDate' => date('Y-m-d', strtotime('+1 day'))
            ];

        if ($relayPoint) {
            $shipmentData['shipment']['pickupPointCode'] = $relayPoint;
        }

        Tlog::getInstance()->debug("Pricing data: " . print_r($shipmentData, true));

        return $shipmentData;
    }

    private function extractStreetNumber($address)
    {
        preg_match('/^(\d+)/', $address, $matches);
        return isset($matches[1]) ? (int)$matches[1] : null;
    }

    private function extractStreetName($address)
    {
        return preg_replace('/^\d+\s*/', '', $address);
    }

    public function sendShipmentToBoxtal(Order $order, string $shippingMethod, $length, $width, $height, $weight, $relayPoint, $insured, $contentDescription, $addressType)
    {
        try {
            $tokenService = new BoxtalApiTokenService();
            $token = $tokenService->getBoxtalTokenApiv3();
            if (!$token) {
                throw new \Exception("Unable to generate Boxtal token");
            }

            $shippingData = $this->createShipment($order, $shippingMethod, $length, $width, $height, $weight, $relayPoint, $insured, $contentDescription, $addressType);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, BoxtalShipping::BOXTAL_SHIPPING_ORDER_API_URL_V3);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($shippingData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 200 || $httpCode === 201) {
                return json_decode($response, true);
            } else {
                $errorDetails = json_decode($response, true);
                if (isset($errorDetails['errors'])) {
                    $formattedError = $this->formatErrorMessage(json_encode($errorDetails['errors']));
                    throw new \Exception("Validation error : \n" . $formattedError);
                }

                throw new \Exception("Error during shipment creation. Code HTTP: " . $httpCode . ". Réponse : " . $response);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function formatErrorMessage($errorJson)
    {
        $errors = json_decode($errorJson, true);
        $formattedErrors = [];

        foreach ($errors as $error) {
            if (isset($error['message'])) {
                if (preg_match('/\$\.([\w\.]+)\s(.+)/', $error['message'], $matches)) {
                    $field = $matches[1];
                    $message = $matches[2];

                    $fieldName = ucwords(str_replace('.', ' ', $field));

                    $formattedMessage = $fieldName . ': ' . ucfirst($message);

                    $formattedErrors[] = $formattedMessage;
                } else {
                    $formattedErrors[] = ucfirst($error['message']);
                }
            }
        }

        return implode("\n", $formattedErrors);
    }
}
