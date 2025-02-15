<?php

namespace BoxtalShipping\Form;

use BoxtalShipping\BoxtalShipping;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Thelia\Form\BaseForm;

class ExportPricing extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add('export_type', ChoiceType::class, [
                'label' => $this->translator->trans('Export Type', [], BoxtalShipping::DOMAIN_NAME),
                'choices' => [
                    'CSV' => 'csv',
                    'JSON' => 'json'
                ],
                'required' => true
            ]);
    }

    public static function getName()
    {
        return 'boxtal_export_pricing';
    }
}
