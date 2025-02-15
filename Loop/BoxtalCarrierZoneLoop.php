<?php

namespace BoxtalShipping\Loop;

use BoxtalShipping\Model\BoxtalCarrierZoneQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Argument\Argument;

/**
 * Loop to check if a carrier is associated with a specific zone.
 */
class BoxtalCarrierZoneLoop extends BaseLoop implements PropelSearchLoopInterface
{

    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('delivery_mode_id', null, true),
            Argument::createIntTypeArgument('area_id', null, true)
        );
    }

    public function buildModelCriteria()
    {
        $query = BoxtalCarrierZoneQuery::create();

        if (null !== $this->getDeliveryModeId()) {
            $query->filterByDeliveryModeId($this->getDeliveryModeId());
        }

        if (null !== $this->getAreaId()) {
            $query->filterByAreaId($this->getAreaId());
        }

        return $query;
    }

    public function parseResults(LoopResult $loopResult)
    {
        /** @var \BoxtalShipping\Model\BoxtalCarrierZone $carrierZone */
        foreach ($loopResult->getResultDataCollection() as $carrierZone) {
            $loopResultRow = new LoopResultRow($carrierZone);

            $loopResultRow
                ->set('DELIVERY_MODE_ID', $carrierZone->getDeliveryModeId())
                ->set('AREA_ID', $carrierZone->getAreaId());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
