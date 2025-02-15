<?php

namespace BoxtalShipping\Loop;


use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use BoxtalShipping\Model\BoxtalPriceQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\CountryQuery;
use Thelia\Model\AreaQuery;
use Thelia\Model\CountryAreaQuery;

class BoxtalDeliveryMode extends BaseLoop implements ArraySearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('country_id', null, true)

        );
    }

    public function buildArray()
    {

        $countryId = $this->getCountryId();

        $country = CountryQuery::create()->findPk($countryId);
        if ($country === null) {
            return [];
        }

        /** @var Cart $cart */
        $cart = $this->requestStack
            ->getCurrentRequest()
            ->getSession()
            ->getSessionCart($this->dispatcher);

        if ($cart === null) {
            return [];
        }

        $cartWeight = $cart->getWeight();

        $areas = CountryAreaQuery::create()
            ->filterByCountryId($countryId)
            ->find();

        if ($areas->isEmpty()) {
            return [];
        }

        $deliveryModesQuery = BoxtalDeliveryModeQuery::create()
            ->filterByIsActive(true);

        $deliveryModes = $deliveryModesQuery->find();

        $results = [];

        foreach ($deliveryModes as $deliveryMode) {
            foreach ($areas as $area) {
                $price = BoxtalPriceQuery::create()
                    ->filterByAreaId($area->getAreaId())
                    ->filterByDeliveryModeId($deliveryMode->getId())
                    ->filterByWeightMax($cartWeight, Criteria::GREATER_EQUAL)
                    ->orderByWeightMax(Criteria::ASC)
                    ->findOne();

                if ($price) {
                    $results[] = [
                        'ID' => $deliveryMode->getId(),
                        'CARRIER_CODE' => $deliveryMode->getCarrierCode(),
                        'TITLE' => $deliveryMode->getTitle(),
                        'PRICE' => $price->getPrice(),
                        'AREA_ID' => $area->getAreaId(),
                        'DELIVERY_TYPE' => $deliveryMode->getDeliveryType()
                    ];
                    break;
                }
            }
        }

        return $results;
    }

    public function parseResults(LoopResult $loopResult)
    {
        foreach ($this->buildArray() as $item) {
            $loopResultRow = new LoopResultRow();
            foreach ($item as $key => $value) {
                $loopResultRow->set($key, $value);
            }
            $loopResult->addRow($loopResultRow);
        }
        return $loopResult;
    }
}
