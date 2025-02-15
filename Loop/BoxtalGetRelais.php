<?php

namespace BoxtalShipping\Loop;

use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use BoxtalShipping\Service\BoxtalRelaisService;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Log\Tlog;
use Thelia\Model\AddressQuery;
use Thelia\Model\CountryQuery;

class BoxtalGetRelais extends BaseLoop implements ArraySearchLoopInterface
{

    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createAnyTypeArgument("zipcode", ""),
            Argument::createAnyTypeArgument("city", ""),
            Argument::createAnyTypeArgument("carrier_code", ""),
            Argument::createAnyTypeArgument("address", ""),
            Argument::createAnyTypeArgument("country_id", "")
        );
    }

    public function buildArray()
    {
        $zipcode = $this->getZipcode();
        $city = $this->getCity();
        $carrierCode = $this->getCarrierCode();
        $address = $this->getAddress();
        $countryId = $this->getCountryId();

        if (empty($zipcode) || empty($city)) {
            $customer = $this->securityContext->getCustomerUser();
            if ($customer === null) {
                throw new \ErrorException('Customer not connected.');
            }

            $defaultAddress = AddressQuery::create()
                ->filterByCustomerId($customer->getId())
                ->filterByIsDefault(true)
                ->findOne();

            if ($defaultAddress === null) {
                throw new \ErrorException('No default address found for customer.');
            }

            $zipcode = $this->getZipcode();
            $city = $defaultAddress->getCity();
            $address = $defaultAddress->getAddress1();
            $countryCode = $defaultAddress->getCountry()->getIsoalpha2();
        } else {
            $countryCode = CountryQuery::create()
                ->findOneById($countryId)
                ->getIsoalpha2();
        }

        $carrierCodes = BoxtalDeliveryModeQuery::create()
            ->filterByDeliveryType('relay')
            ->filterByCarrierCode($carrierCode)
            ->filterByIsActive(true)
            ->find();

        if ($carrierCodes->isEmpty()) {
            throw new \ErrorException("No active relay carrier found for code: $carrierCode");
        }

        $searchNetworks = '';
        foreach ($carrierCodes as $carrier) {
            if ($carrier->getCarrierCode() === $carrierCode) {
                $searchNetworks .= '&searchNetworks=' . substr($carrier->getCarrierCode(), 0, 4);
            }
        }

        $boxtalRelaisService = new BoxtalRelaisService();

        $response = $boxtalRelaisService->getParcelPoints($address, $zipcode, $city, $countryCode, $searchNetworks);

        return json_decode($response, true);
    }

    public function parseResults(LoopResult $loopResult)
    {

        $resultData = $loopResult->getResultDataCollection();

        if (!isset($resultData['content']) || !is_array($resultData['content'])) {
            Tlog::getInstance()->error("Invalid data structure in GetRelais.php");
            return $loopResult;
        }

        foreach ($resultData['content'] as $item) {
            $loopResultRow = new LoopResultRow();

            $loopResultRow->set('RELAY_CODE', $item['parcelPoint']['code'] ?? '');
            $loopResultRow->set('NAME', $item['parcelPoint']['name'] ?? '');
            $loopResultRow->set('STREET', $item['parcelPoint']['location']['street'] ?? '');
            $loopResultRow->set('CITY', $item['parcelPoint']['location']['city'] ?? '');
            $loopResultRow->set('ZIPCODE', $item['parcelPoint']['location']['postalCode'] ?? '');
            $loopResultRow->set('COUNTRY', $item['parcelPoint']['location']['countryIsoCode'] ?? '');

            $distance = $item['distanceFromSearchLocation'] ?? 0;
            $formattedDistance = ($distance < 1000)
                ? $distance . ' m'
                : number_format($distance / 1000, 2, ',', ' ') . ' km';
            $loopResultRow->set('DISTANCE', $formattedDistance);

            $openingDays = $item['parcelPoint']['openingDays'] ?? [];
            $loopResultRow->set('OPENING_DAYS', json_encode($item['parcelPoint']['openingDays'] ?? []));

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
