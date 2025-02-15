<?php

namespace BoxtalShipping\Form;

use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use BoxtalShipping\Service\BoxtalGetCategoriesService;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Thelia\Form\BaseForm;
use Thelia\Model\OrderQuery;
use Thelia\Model\ConfigQuery;

class ShipmentForm extends BaseForm
{
    private $categoriesService;

    protected function buildForm()
    {
        $this->formBuilder
            ->add('order_ids', CollectionType::class, [
                'entry_type' => IntegerType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ]);

        $orders = OrderQuery::create()->find();

        $deliveryModes = BoxtalDeliveryModeQuery::create()->find();
        $shippingMethods = [];
        foreach ($deliveryModes as $mode) {
            $shippingMethods[$mode->getTitle()] = $mode->getCarrierCode();
        }

        $categoriesService = new BoxtalGetCategoriesService();
        $contentCategoriesJson = $categoriesService->getContentCategories();
        $contentCategoriesData = json_decode($contentCategoriesJson, true);

        $contentChoices = [];

        if (json_last_error() === JSON_ERROR_NONE && isset($contentCategoriesData['content'])) {
            $contentCategories = $contentCategoriesData['content'];
            foreach ($contentCategories as $category) {
                $contentChoices[$category['label']] = $category['id'];
            }
        } else {
            $this->formBuilder->addError(new FormError('Erreur lors du chargement des catégories de contenu. Veuillez réessayer plus tard.'));
        }

        foreach ($orders as $order) {
            if ($order->isPaid()) {
                $orderId = $order->getId();
                $this->formBuilder
                    ->add(
                        "shipping_method_$orderId",
                        ChoiceType::class,
                        [
                            'choices' => $shippingMethods,
                            'required' => true,
                            'label' => $this->translator->trans('Shipping method - Order %orderId', ['%orderId' => $orderId], BoxtalShipping::DOMAIN_NAME),
                            'placeholder' => $this->translator->trans('Choose a shipping method', [], BoxtalShipping::DOMAIN_NAME)
                        ]
                    )->add(
                        "height_$orderId",
                        NumberType::class,
                        [
                            'required' => false,
                            'label' => $this->translator->trans('Height (cm)', [], BoxtalShipping::DOMAIN_NAME),
                            'scale' => 2
                        ]
                    )->add(
                        "length_$orderId",
                        NumberType::class,
                        [
                            'required' => false,
                            'label' => $this->translator->trans('Length (cm)', [], BoxtalShipping::DOMAIN_NAME),
                            'scale' => 2
                        ]
                    )->add(
                        "width_$orderId",
                        NumberType::class,
                        [
                            'required' => false,
                            'label' => $this->translator->trans('Width (cm)', [], BoxtalShipping::DOMAIN_NAME),
                            'scale' => 2
                        ]
                    )->add(
                        "weight_$orderId",
                        NumberType::class,
                        [
                            'required' => true,
                            'label' => $this->translator->trans('Weight (kg)', [], BoxtalShipping::DOMAIN_NAME),
                            'scale' => 2
                        ]
                    )->add(
                        "relay_point_$orderId",
                        TextType::class,
                        [
                            'required' => false,
                            'label' => $this->translator->trans('Relay point code (if applicable)', [], BoxtalShipping::DOMAIN_NAME),
                        ]
                    )->add(
                        "insured_$orderId",
                        CheckboxType::class,
                        [
                            'required' => false,
                            'label' => $this->translator->trans('Insurance', [], BoxtalShipping::DOMAIN_NAME),
                            'data' => false,
                        ]
                    )->add(
                        "content_description_$orderId",
                        ChoiceType::class,
                        [
                            'choices' => $contentChoices,
                            'required' => true,
                            'label' => $this->translator->trans('Content type', [], BoxtalShipping::DOMAIN_NAME),
                            'placeholder' => $this->translator->trans('Choose a content type', [], BoxtalShipping::DOMAIN_NAME),
                            'data' => ConfigQuery::read('boxtal_default_category'),
                        ]
                    )->add("address_type_$orderId", ChoiceType::class, [
                        'choices' => [
                            $this->translator->trans('Business', [], BoxtalShipping::DOMAIN_NAME) => 'BUSINESS',
                            $this->translator->trans('Residential', [], BoxtalShipping::DOMAIN_NAME) => 'RESIDENTIAL',
                        ],
                        'required' => true,
                        'label' => $this->translator->trans('Address type', [], BoxtalShipping::DOMAIN_NAME),
                        'data' => 'RESIDENTIAL',
                    ]);;
            }
        }
    }

    public static function getName()
    {
        return 'boxtal_shipment_form';
    }
}
