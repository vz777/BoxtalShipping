<?php

namespace BoxtalShipping\EventListeners;

use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Model\BoxtalAddressQuery;
use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use BoxtalShipping\Model\BoxtalOrderAddress;
use BoxtalShipping\Model\BoxtalPriceQuery;
use BoxtalShipping\Service\BoxtalRelaisService;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Delivery\DeliveryPostageEvent;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Log\Tlog;
use Thelia\Model\CountryQuery;
use Thelia\Model\OrderAddressQuery;


class SetDeliveryModule implements EventSubscriberInterface
{

    protected $requestStack;
    protected $boxtalRelaisService;

    public function __construct(RequestStack $requestStack, BoxtalRelaisService $boxtalRelaisService)
    {
        $this->requestStack = $requestStack;
        $this->boxtalRelaisService = $boxtalRelaisService;
    }

    protected function check_module($moduleId)
    {
        return $moduleId == BoxtalShipping::getModuleId();
    }

    public function processDeliveryPostageEvent(DeliveryPostageEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $valid = false;
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        $selectedDeliveryType = $request->get('boxtal-selected-delivery-type');

        $weight = $session->getSessionCart($dispatcher)->getWeight();

        if ($weight > 0) {
            $country = $event->getCountry();
            $areaId = $country->getAreaId();

            if ($areaId !== null) {
                if ($selectedDeliveryType === null) {
                    // Si aucun type de livraison n'est sélectionné, on cherche tous les modes de livraison disponibles
                    $deliveryModes = BoxtalDeliveryModeQuery::create()->find();

                    foreach ($deliveryModes as $deliveryMode) {
                        $priceQuery = BoxtalPriceQuery::create()
                            ->filterByAreaId($areaId)
                            ->filterByWeightMax($weight, Criteria::GREATER_EQUAL)
                            ->filterByDeliveryModeId($deliveryMode->getId())
                            ->orderByWeightMax(Criteria::ASC)
                            ->findOne();

                        if ($priceQuery !== null) {
                            $price = $priceQuery->getPrice();
                            $valid = true;

                            if ($deliveryMode->getCarrierCode() === $selectedDeliveryType || !$selectedDeliveryType) {
                                $event->setPostage($price);

                                Tlog::getInstance()->error("BoxtalShipping: Price found for mode " . $deliveryMode->getCarrierCode() . ": " . $price);
                                //break; //
                            }
                        }
                    }
                } else {
                    // Cas où un type de livraison spécifique est sélectionné
                    $deliveryMode = BoxtalDeliveryModeQuery::create()
                        ->filterByCarrierCode($selectedDeliveryType)
                        ->findOne();

                    if ($deliveryMode) {
                        $priceQuery = BoxtalPriceQuery::create()
                            ->filterByAreaId($areaId)
                            ->filterByWeightMax($weight, Criteria::GREATER_EQUAL)
                            ->filterByDeliveryModeId($deliveryMode->getId())
                            ->orderByWeightMax(Criteria::ASC)
                            ->findOne();

                        if ($priceQuery !== null) {
                            $price = $priceQuery->getPrice();
                            $valid = true;
                            if ($deliveryMode->getCarrierCode() === $selectedDeliveryType || !$selectedDeliveryType) {
                                $event->setPostage($price);

                                Tlog::getInstance()->error("BoxtalShipping: Price found for selected mode: " . $price);
                            }
                        } else {
                            Tlog::getInstance()->error("BoxtalShipping: No price found for the selected delivery mode");
                        }
                    } else {
                        Tlog::getInstance()->error("BoxtalShipping: Selected delivery mode not found");
                    }
                }
            } else {
                Tlog::getInstance()->error("BoxtalShipping: No area ID found for the country");
            }
        } else {
            Tlog::getInstance()->error("BoxtalShipping: Cart weight is 0 or negative");
        }

        $event->setValidModule($valid);
        Tlog::getInstance()->error("BoxtalShipping: Module validity set to: " . ($valid ? "true" : "false"));
        $event->stopPropagation();
    }

    public function isModuleBoxtalPickupPoint(OrderEvent $event)
    {
        if (!$this->check_module($event->getDeliveryModule())) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $relayCode = $request->request->get('boxtal_selected_pickup_point');
        $carrierCode = $request->request->get('selected_carrier_code');

        $deliveryMode = BoxtalDeliveryModeQuery::create()
            ->filterByCarrierCode($carrierCode)
            ->findOne();

        if (!$deliveryMode) {
            return;
        }

        if ($deliveryMode->getDeliveryType() === 'relay' && !empty($relayCode)) {
            $boxtalAddress = BoxtalAddressQuery::create()
                ->filterByRelayCode($relayCode)
                ->findOneOrCreate();
            $relayCountryCode = $request->request->all("relay_country")[$relayCode] ?? '';

            $country = CountryQuery::create()
                ->filterByIsoalpha2($relayCountryCode)
                ->findOne();
            $countryId = $country->getId();

            $boxtalAddress
                ->setCompany($request->request->all("relay_name")[$relayCode] ?? '')
                ->setAddress1($request->request->all("relay_street")[$relayCode] ?? '')
                ->setZipcode($request->request->all("relay_zipcode")[$relayCode] ?? '')
                ->setCity($request->request->all("relay_city")[$relayCode] ?? '')
                ->setRelayCode($relayCode)
                ->setDeliveryModeId($deliveryMode->getId())
                ->setCountryId($countryId)
                ->save();

            $request->getSession()->set(BoxtalShipping::SESSION_SELECTED_PICKUP_RELAY_ID, $boxtalAddress->getId());
        } else {
        }
    }

    public function updateDeliveryAddress(OrderEvent $event)
    {
        if ($this->check_module($event->getOrder()->getDeliveryModuleId())) {
            $request = $this->requestStack->getCurrentRequest();
            $session = $request->getSession();
            $boxtalAddressId = $session->get(BoxtalShipping::SESSION_SELECTED_PICKUP_RELAY_ID);

            if ($boxtalAddressId) {
                $boxtalAddress = BoxtalAddressQuery::create()
                    ->findPk($boxtalAddressId);

                if ($boxtalAddress === null) {
                    throw new \ErrorException('Erreur avec le module BoxtalPickupPoint. Veuillez réessayer.');
                }

                $orderAddress = OrderAddressQuery::create()
                    ->findPK($event->getOrder()->getDeliveryOrderAddressId());

                if ($orderAddress !== null) {
                    $orderAddress
                        ->setCompany($boxtalAddress->getCompany())
                        ->setAddress1($boxtalAddress->getAddress1())
                        ->setAddress2($boxtalAddress->getAddress2())
                        ->setAddress3($boxtalAddress->getAddress3())
                        ->setZipcode($boxtalAddress->getZipcode())
                        ->setCity($boxtalAddress->getCity())
                        ->save();

                    $boxtalOrderAddress = new BoxtalOrderAddress();
                    $boxtalOrderAddress
                        ->setId($orderAddress->getId())
                        ->setCode($boxtalAddress->getRelayCode())
                        ->save();
                }
            } else {
            }
        }
    }

    /**
     * Clear stored information once the order has been processed.
     *
     * @param OrderEvent $event
     * @param $eventName
     * @param EventDispatcherInterface $dispatcher
     */
    public function clearDeliveryData(OrderEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        // Clear the session context
        $session->remove(BoxtalShipping::SESSION_SELECTED_PICKUP_RELAY_ID);
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::getModuleEvent(
                TheliaEvents::MODULE_DELIVERY_GET_POSTAGE,
                BoxtalShipping::getModuleCode()
            ) => ["processDeliveryPostageEvent", 128],
            TheliaEvents::ORDER_SET_DELIVERY_MODULE => ['isModuleBoxtalPickupPoint', 128],
            TheliaEvents::ORDER_BEFORE_PAYMENT => ['updateDeliveryAddress', 255],
            TheliaEvents::ORDER_CART_CLEAR => ['clearDeliveryData', 256],
        ];
    }
}
