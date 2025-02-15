<?php

namespace BoxtalShipping\Form;

use BoxtalShipping\Model\BoxtalCarrierZoneQuery;
use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Thelia\Form\BaseForm;
use Thelia\Model\AreaQuery;

class CarrierZoneForm extends BaseForm
{
    public function buildForm()
    {
        $areas = AreaQuery::create()->find();

        $deliveryModes = BoxtalDeliveryModeQuery::create()
            ->filterByIsActive(true)
            ->find();

        foreach ($areas as $area) {
            $carrierChoices = [];
            foreach ($deliveryModes as $mode) {
                $carrierChoices[$mode->getTitle()] = $mode->getId();
            }

            $associatedCarriers = BoxtalCarrierZoneQuery::create()
                ->filterByAreaId($area->getId())
                ->select('DeliveryModeId')
                ->find()
                ->toArray();

            $this->formBuilder
                ->add((string) $area->getId(), ChoiceType::class, [
                    'label' => $area->getName(),
                    'choices' => $carrierChoices,
                    'multiple' => true,
                    'expanded' => true,
                    'data' => $associatedCarriers,

                ]);
        }
    }

    public static function getName()
    {
        return 'boxtalshipping_carrier_zone_association';
    }
}
