<?php

namespace BoxtalShipping\Controller\Admin;

use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Form\ShipmentForm;
use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use BoxtalShipping\Service\BoxtalShippingService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\AccessManager;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Model\OrderQuery;

class ShipmentController extends BaseAdminController

{
    private $boxtalShippingService;

    public function __construct(BoxtalShippingService $boxtalShippingService)
    {
        $this->boxtalShippingService = $boxtalShippingService;
    }

    /**
     * @Route("/admin/boxtalshipping/order", name="boxtal.bulk.orders")
     */
    public function processBulkShipmentAction(RequestStack $requestStack)
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'BoxtalShipping', AccessManager::UPDATE)) {
            return $response;
        }

        $request = $requestStack->getCurrentRequest();
        $form = $this->createForm(ShipmentForm::getName());
        $results = [];

        try {
            $data = $this->validateForm($form)->getData();
            $orderIds = $data['order_ids'];

            if (empty($orderIds)) {
                $results[] = "Aucune commande sélectionnée.";
                $results[] = $this->getTranslator()->trans("No order selected.", [], BoxtalShipping::DOMAIN_NAME);
            } else {
                $deliveryModes = BoxtalDeliveryModeQuery::create()->find();
                $availableModes = [];
                foreach ($deliveryModes as $mode) {
                    $availableModes[$mode->getCarrierCode()] = $mode->getTitle();
                }

                foreach ($orderIds as $orderId) {
                    try {
                        $order = OrderQuery::create()->findPk($orderId);
                        if (!$order) {
                            throw new \Exception("Order not found : {$orderId}");
                        }

                        $shippingMethodCode = $data["shipping_method_$orderId"] ?? null;

                        if (!isset($availableModes[$shippingMethodCode])) {
                            throw new \Exception("Méthode d'expédition non valide pour la commande {$orderId}");
                        }

                        $shippingMethod = $shippingMethodCode;
                        $length = $data["length_$orderId"] ?? 0;
                        $width = $data["width_$orderId"] ?? 0;
                        $height = $data["height_$orderId"] ?? 0;
                        $weight = $data["weight_$orderId"] ?? 0;
                        $relayPoint = $data["relay_point_$orderId"] ?? null;
                        $insured = isset($data["insured_$orderId"]) && $data["insured_$orderId"] === 'on';
                        $contentDescription = $data["content_description_$orderId"] ?? '';
                        $addressType = $data["address_type_$orderId"] ?? 'RESIDENTIAL';

                        if ($length <= 0 || $width <= 0 || $height <= 0 || $weight <= 0) {
                            throw new \Exception("Données invalides pour la commande {$orderId}");
                        }

                        $shipmentData = $this->boxtalShippingService->createShipment(
                            $order,
                            $shippingMethod,
                            $length,
                            $width,
                            $height,
                            $weight,
                            $relayPoint,
                            $insured,
                            $contentDescription,
                            $addressType
                        );

                        $shipmentResponse = $this->boxtalShippingService->sendShipmentToBoxtal(
                            $order,
                            $shippingMethod,
                            $length,
                            $width,
                            $height,
                            $weight,
                            $relayPoint,
                            $insured,
                            $contentDescription,
                            $addressType
                        );
                        if (isset($shipmentResponse['content'])) {
                            $results[] = [
                                'success' => true,
                                'message' => $this->getTranslator()->trans(
                                    "Order %ref% successfully shipped via %method%.",
                                    ['%ref%' => $order->getRef(), '%method%' => $availableModes[$shippingMethod]],
                                    BoxtalShipping::DOMAIN_NAME
                                ),
                                'shipmentInfo' => [
                                    'id' => $shipmentResponse['content']['id'] ?? '',
                                    'shipmentId' => $shipmentResponse['content']['shipmentId'] ?? '',
                                    'status' => $shipmentResponse['content']['status'] ?? '',
                                    'deliveryPrice' => $shipmentResponse['content']['deliveryPriceExclTax']['value'] ?? '',
                                    'currency' => $shipmentResponse['content']['deliveryPriceExclTax']['currency'] ?? '',
                                    'expectedTakingOverDate' => $shipmentResponse['content']['expectedTakingOverDate'] ?? '',
                                    'estimatedDeliveryDate' => $shipmentResponse['content']['estimatedDeliveryDate'] ?? ''
                                ]
                            ];
                        } else {
                            throw new \Exception("Unexpected response format from Boxtal API");
                        }
                    } catch (\Exception $e) {

                        $results[] = [
                            'success' => false,
                            'message' => $this->getTranslator()->trans(
                                "Error while shipping order %orderId%: %message%",
                                ['%orderId%' => $orderId, '%message%' => $e->getMessage()],
                                BoxtalShipping::DOMAIN_NAME
                            )
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $form->setErrorMessage($e->getMessage());
        }
        return $this->render('boxtalshipping/bulk-shipment', [
            'results' => $results
        ]);
    }
}
