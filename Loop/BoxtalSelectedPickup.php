<?php

namespace BoxtalShipping\Loop;

use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;

class BoxtalSelectedPickup extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('id', null, true)
        );
    }

    public function buildModelCriteria()
    {
        $id = $this->getId();

        $query = BoxtalDeliveryModeQuery::create();
        $query->filterById($id);

        return $query;
    }

    public function parseResults(LoopResult $loopResult)
    {
        foreach ($loopResult->getResultDataCollection() as $deliveryMode) {
            $loopResultRow = new LoopResultRow($deliveryMode);
            $loopResultRow
                ->set("ID", $deliveryMode->getId())
                ->set("TITLE", $deliveryMode->getTitle())
                ->set("CARRIER_CODE", $deliveryMode->getCarrierCode())
                ->set("DELIVERY_TYPE", $deliveryMode->getDeliveryType())
                ->set("IS_ACTIVE", $deliveryMode->getIsActive())
                ->set("FREESHIPPING_ACTIVE", $deliveryMode->getFreeshippingActive())
                ->set("FREESHIPPING_FROM", $deliveryMode->getFreeshippingFrom());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
