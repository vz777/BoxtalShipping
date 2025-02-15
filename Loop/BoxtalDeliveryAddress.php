<?php

namespace BoxtalShipping\Loop;

use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Model\BoxtalAddressQuery;
use BoxtalShipping\Model\BoxtalOrderAddressQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\OrderQuery;

class BoxtalDeliveryAddress extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('order_address_id'),
            Argument::createIntTypeArgument('order_id')
        );
    }

    public function buildModelCriteria()
    {
        $relayId = $this->getCurrentRequest()->getSession()->get(BoxtalShipping::SESSION_SELECTED_PICKUP_RELAY_ID);

        if ($relayId !== null) {
            return BoxtalAddressQuery::create()->filterById($relayId);
        }

        $orderAddressId = $this->getOrderAddressId();

        if ($orderAddressId !== null) {
            $boxtalOrderAddress = BoxtalOrderAddressQuery::create()->findOneById($orderAddressId);
            if ($boxtalOrderAddress) {
                return BoxtalAddressQuery::create()->filterByRelayCode($boxtalOrderAddress->getCode());
            } else {
            }
        }

        $orderId = $this->getOrderId();

        if ($orderId !== null) {
            $order = OrderQuery::create()->findPk($orderId);
            if ($order) {
                $deliveryAddressId = $order->getDeliveryOrderAddressId();
                $boxtalOrderAddress = BoxtalOrderAddressQuery::create()->findOneById($deliveryAddressId);
                if ($boxtalOrderAddress) {
                    return BoxtalAddressQuery::create()->filterByRelayCode($boxtalOrderAddress->getCode());
                } else {
                }
            } else {
            }
        }

        return null;
    }

    public function parseResults(LoopResult $loopResult)
    {
        /** @var AddressBoxtal $address */
        foreach ($loopResult->getResultDataCollection() as $address) {
            $loopResultRow = new LoopResultRow($address);

            $loopResultRow
                ->set('ID', $address->getId())
                ->set('COMPANY', $address->getCompany())
                ->set('ADDRESS1', $address->getAddress1())
                ->set('ADDRESS2', $address->getAddress2())
                ->set('ADDRESS3', $address->getAddress3())
                ->set('ZIPCODE', $address->getZipcode())
                ->set('CITY', $address->getCity())
                ->set('RELAY_CODE', $address->getRelayCode())
                ->set('COUNTRY_ID', $address->getCountryId());

            $deliveryMode = $address->getBoxtalDeliveryMode();

            if ($deliveryMode) {
                $loopResultRow
                    ->set('DELIVERY_MODE_ID', $deliveryMode->getId())
                    ->set('DELIVERY_MODE_TITLE', $deliveryMode->getTitle())
                    ->set('CARRIER_CODE', $deliveryMode->getCarrierCode())
                    ->set('DELIVERY_TYPE', $deliveryMode->getDeliveryType())
                    ->set('IS_ACTIVE', $deliveryMode->getIsActive())
                    ->set('FREESHIPPING_ACTIVE', $deliveryMode->getFreeshippingActive())
                    ->set('FREESHIPPING_FROM', $deliveryMode->getFreeshippingFrom());
            }

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
