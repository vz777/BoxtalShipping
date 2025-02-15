<?php

namespace BoxtalShipping\Controller\Admin;

use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Form\ConfigurationForm;
use BoxtalShipping\Form\CarrierConfigurationForm;
use BoxtalShipping\Form\FrontDeliverySelectionForm;
use BoxtalShipping\Model\BoxtalCarrierZoneQuery;
use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;
use Thelia\Model\ConfigQuery;

class ConfigurationController extends BaseAdminController
{
    /**
     * @Route("/admin/module/BoxtalShipping/save", name="boxtal.config.save", methods={"POST"})
     */
    public function saveApiConfigAction()
    {

        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'BoxtalShipping', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm(ConfigurationForm::getName());

        try {
            $form = $this->validateForm($configurationForm);

            ConfigQuery::write(BoxtalShipping::BOXTAL_API_KEY_V3, $form->get('api_key_v3')->getData(), 1, 1);
            ConfigQuery::write(BoxtalShipping::BOXTAL_API_SECRET_V3, $form->get('api_secret_v3')->getData(), 1, 1);
            ConfigQuery::write(BoxtalShipping::BOXTAL_API_KEY_V1, $form->get('api_key_v1')->getData(), 1, 1);
            ConfigQuery::write(BoxtalShipping::BOXTAL_API_SECRET_V1, $form->get('api_secret_v1')->getData(), 1, 1);
            ConfigQuery::write(BoxtalShipping::BOXTAL_FIRSTNAME, $form->get('firstname')->getData(), 1, 1);
            ConfigQuery::write(BoxtalShipping::BOXTAL_LASTNAME, $form->get('lastname')->getData(), 1, 1);
            ConfigQuery::write(BoxtalShipping::BOXTAL_DEFAULT_CATEGORY, $form->get('default_category')->getData(), 1, 1);

            $response = new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/BoxtalShipping'));
            return $response;
        } catch (FormValidationException $e) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans("Boxtal configuration", [], BoxtalShipping::DOMAIN_NAME),
                $e->getMessage(),
                $configurationForm
            );
            return $this->render('module-configure', ['module_code' => 'BoxtalShipping']);
        }
    }

    /**
     * @Route("/admin/module/BoxtalShipping/save_carriers", name="boxtal.config.save_carriers1", methods={"POST"})
     */

    public function saveCarrierConfigAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'BoxtalShipping', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(CarrierConfigurationForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            $selectedCarriers = implode(',', $data['carrier_code']);

            $deliveryModes = BoxtalDeliveryModeQuery::create()->find();

            $selectedCarriers = is_array($selectedCarriers) ? $selectedCarriers : explode(',', $selectedCarriers);

            foreach ($deliveryModes as $mode) {
                $isActive = in_array($mode->getCarrierCode(), $selectedCarriers);
                $mode->setIsActive($isActive);
                $mode->save();
            }

            $response = new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/BoxtalShipping'));
            return $response;
        } catch (FormValidationException $e) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans("Boxtal Carrier configuration", [], BoxtalShipping::DOMAIN_NAME),
                $e->getMessage(),
                $form
            );
            return $this->render('module-configure', ['module_code' => 'BoxtalShipping']);
        }
    }

    /**
     * @Route("/admin/module/BoxtalShipping/save_front_shipping_method", name="boxtal.config.save_front_shipping", methods={"POST"})
     */

    public function saveFrontShippingMethodAction()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'BoxtalShipping', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(FrontDeliverySelectionForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            $deliveryModes = BoxtalDeliveryModeQuery::create()->find();

            foreach ($deliveryModes as $mode) {
                $carrierCode = $mode->getCarrierCode();
                $statusKey = 'BOXTAL_' . strtoupper($carrierCode) . '_STATUS';

                if (isset($data[$statusKey])) {
                    $mode->setIsActive($data[$statusKey]);
                    $mode->save();
                }
            }

            $response = new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/BoxtalShipping'));
            return $response;
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans(
                    "Error",
                    [],
                    BoxtalShipping::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
            return $this->render('module-configure', ['module_code' => 'BoxtalShipping']);
        }
    }

    /**
     * @Route("/admin/module/boxtalshipping/save_carrier_zone", name="boxtal.config.update_carrier_zone", methods={"POST"})
     */
    public function saveCarrierZoneAction(Request $request)
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'BoxtalShipping', AccessManager::UPDATE)) {
            $responseData['message'] = $this->getTranslator()->trans("Forbidden", [], BoxtalShipping::DOMAIN_NAME);
            return $this->jsonResponse(json_encode($responseData), 403);
        }

        $this->checkXmlHttpRequest();

        $responseData = [
            'success' => false,
            'message' => '',
        ];

        try {

            $data = json_decode($request->getContent(), true);
            $areaId = $data['area_id'] ?? null;
            $carrierId = $data['carrier_id'] ?? null;
            $action = $data['action'] ?? null;

            if (!$areaId || !$carrierId || !in_array($action, ['add', 'remove'])) {
                $responseData['message'] = $this->getTranslator()->trans("Invalid data", [], BoxtalShipping::DOMAIN_NAME);
                return $this->jsonResponse(json_encode($responseData), 400);
            }

            if ($action === 'add') {
                if (!BoxtalCarrierZoneQuery::create()
                    ->filterByAreaId($areaId)
                    ->filterByDeliveryModeId($carrierId)
                    ->exists()) {
                    $association = new \BoxtalShipping\Model\BoxtalCarrierZone();
                    $association->setAreaId($areaId);
                    $association->setDeliveryModeId($carrierId);
                    $association->save();
                }
                $responseData['success'] = true;
                $responseData['message'] = $this->getTranslator()->trans("Carrier successfully added to the zone", [], BoxtalShipping::DOMAIN_NAME);
            } elseif ($action === 'remove') {
                BoxtalCarrierZoneQuery::create()
                    ->filterByAreaId($areaId)
                    ->filterByDeliveryModeId($carrierId)
                    ->delete();
                $responseData['success'] = true;
                $responseData['message'] = $this->getTranslator()->trans("Carrier successfully removed from the zone", [], BoxtalShipping::DOMAIN_NAME);
            }
        } catch (\Exception $e) {
            $responseData['message'] = 'Erreur : ' . $e->getMessage();
        }

        return $this->jsonResponse(json_encode($responseData));
    }
}
