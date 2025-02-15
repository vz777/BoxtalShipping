<?php

namespace BoxtalShipping\Controller\Admin;

use Exception;
use Propel\Runtime\Map\TableMap;
use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Model\BoxtalPrice;
use BoxtalShipping\Model\BoxtalPriceQuery;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;

/**
 * @Route("/admin/module/BoxtalShipping/slice/", name="boxtal_shipping_slice_")
 */
class SliceController extends BaseAdminController
{
    /**
     * @Route("save", name="price_save", methods="POST")
     */
    public function saveSliceAction(RequestStack $requestStack, Translator $translator)
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), ['BoxtalShipping'], AccessManager::UPDATE)) {
            return $response;
        }

        $this->checkXmlHttpRequest();

        $responseData = [
            'success' => false,
            'message' => '',
            'slice' => null
        ];

        $messages = [];
        $response = null;

        try {
            $requestData = $requestStack->getCurrentRequest()->request;

            if (0 !== $id = (int)$requestData->get('id', 0)) {
                $slice = BoxtalPriceQuery::create()->findPk($id);
            } else {
                $slice = new BoxtalPrice();
            }

            if (0 !== $areaId = (int)$requestData->get('area', 0)) {
                $slice->setAreaId($areaId);
            } else {
                $messages[] = $translator->trans(
                    'The area is not valid',
                    [],
                    BoxtalShipping::DOMAIN_NAME
                );
            }

            if (0 !== $deliveryMode = intval($requestData->get("deliveryModeId", 0))) {
                $slice->setDeliveryModeId($deliveryMode);
            } else {
                $messages[] = $this->getTranslator()->trans(
                    "The delivery type is not valid",
                    [],
                    BoxtalShipping::DOMAIN_NAME
                );
            }

            $weightMax = $this->getFloatVal($requestData->get('weightMax'));
            if ($weightMax > 0) {
                $slice->setWeightMax($weightMax);
            } else {
                $messages[] = $translator->trans(
                    'The weight max value is not valid',
                    [],
                    BoxtalShipping::DOMAIN_NAME
                );
            }

            $price = $this->getFloatVal($requestData->get('price', 0));
            if ($price >= 0) {
                $slice->setPrice($price);
            } else {
                $messages[] = $translator->trans(
                    'The price value is not valid',
                    [],
                    BoxtalShipping::DOMAIN_NAME
                );
            }

            if (empty($messages)) {
                $slice->save();
                $messages[] = $translator->trans(
                    'Your slice has been saved',
                    [],
                    BoxtalShipping::DOMAIN_NAME
                );

                $responseData['success'] = true;
                $responseData['slice'] = $slice->toArray(TableMap::TYPE_STUDLYPHPNAME);
            }
        } catch (Exception $e) {
            $messages[] = $e->getMessage();
        }

        $responseData['message'] = $messages;

        return $this->jsonResponse(json_encode($responseData));
    }

    protected function getFloatVal($val, $default = -1)
    {
        if (preg_match("#^([0-9\.,]+)$#", $val, $match)) {
            $val = $match[0];
            if (strstr($val, ",")) {
                $val = str_replace(".", "", $val);
                $val = str_replace(",", ".", $val);
            }
            return (float)$val;
        }

        return $default;
    }

    /**
     * @Route("delete", name="price_delete", methods="POST")
     */
    public function deleteSliceAction(RequestStack $requestStack, Translator $translator)
    {
        $response = $this->checkAuth([], ['BoxtalShipping'], AccessManager::DELETE);

        if (null !== $response) {
            return $response;
        }

        $this->checkXmlHttpRequest();

        $responseData = [
            'success' => false,
            'message' => '',
            'slice' => null
        ];

        $response = null;

        try {
            $requestData = $requestStack->getCurrentRequest()->request;

            if (0 !== $id = (int)$requestData->get('id', 0)) {
                $slice = BoxtalPriceQuery::create()->findPk($id);
                $slice->delete();
                $responseData['success'] = true;
            } else {
                $responseData['message'] = $translator->trans(
                    'The slice has not been deleted',
                    [],
                    BoxtalShipping::DOMAIN_NAME
                );
            }
        } catch (Exception $e) {
            $responseData['message'] = $e->getMessage();
        }

        return $this->jsonResponse(json_encode($responseData));
    }
}
