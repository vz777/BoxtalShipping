<?php

namespace BoxtalShipping\Form;

use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use BoxtalShipping\BoxtalShipping;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Thelia\Form\BaseForm;

class CarrierConfigurationForm extends BaseForm
{

    protected function buildForm()
    {
        $deliveryModes = BoxtalDeliveryModeQuery::create()->find();

        $carrierChoices = [];
        foreach ($deliveryModes as $mode) {
            $carrierChoices[$mode->getTitle()] = $mode->getCarrierCode();
        }

        $activeCarriers = BoxtalDeliveryModeQuery::create()
            ->filterByIsActive(true)
            ->select('CarrierCode')
            ->find()
            ->toArray();

        $this->formBuilder
            ->add('carrier_code', ChoiceType::class, [
                'label' => $this->translator->trans('Carrier', [], 'boxtalshipping'),
                'choices' => $carrierChoices,
                'multiple' => true,
                'expanded' => true,
                'data' => $activeCarriers,
            ]);

        foreach ($deliveryModes as $mode) {
            $this->formBuilder->add('is_active_' . $mode->getCarrierCode(), CheckboxType::class, [
                'label' => $this->translator->trans('Active', [], 'boxtalshipping'),
                'required' => false,
            ]);
        }
    }

    public static function getName()
    {
        return 'boxtalshipping_carrier_configuration';
    }
}
