<?php

namespace BoxtalShipping\Loop;

use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

class BoxtalActiveDeliveryModes extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection();
    }

    public function buildModelCriteria()
    {
        return BoxtalDeliveryModeQuery::create()
            ->filterByIsActive(true)
            ->orderByTitle();
    }

    public function parseResults(LoopResult $loopResult)
    {
        foreach ($loopResult->getResultDataCollection() as $deliveryMode) {
            $loopResultRow = new LoopResultRow($deliveryMode);
            $loopResultRow
                ->set("ID", $deliveryMode->getId())
                ->set("TITLE", $deliveryMode->getTitle())
                ->set("CARRIER_CODE", $deliveryMode->getCarrierCode())
                ->set("DELIVERY_TYPE", $deliveryMode->getDeliveryType());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
