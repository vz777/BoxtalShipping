<?php

namespace BoxtalShipping\Form;

use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Service\BoxtalGetCategoriesService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;
use Thelia\Model\ConfigQuery;

class ConfigurationForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add('api_key_v3', TextType::class, [
                'label' => $this->translator->trans('API Key V3 (necessary for shipping)', [], BoxtalShipping::DOMAIN_NAME),
                'required' => false,
                'label_attr' => [
                    'for' => 'boxtal-api-key_v3'
                ],
                'attr' => [
                    'id' => 'boxtal-api-key'
                ],
                'data' => ConfigQuery::read('boxtal_api_key_v3')
            ])
            ->add('api_secret_v3', TextType::class, [
                'label' => $this->translator->trans('API Secret V3 (necessary for shipping)', [], BoxtalShipping::DOMAIN_NAME),
                'required' => false,
                'label_attr' => [
                    'for' => 'boxtal-api-secret_v3'
                ],
                'attr' => [
                    'id' => 'boxtal-api-secret_v3'
                ],
                'data' => ConfigQuery::read('boxtal_api_secret_v3')
            ])

            ->add('api_key_v1', TextType::class, [
                'label' => $this->translator->trans('API Key V1 (necessary for cotations)', [], BoxtalShipping::DOMAIN_NAME),
                'required' => false,
                'label_attr' => [
                    'for' => 'boxtal-api-key-v1'
                ],
                'attr' => [
                    'id' => 'boxtal-api-key-v1'
                ],
                'data' => ConfigQuery::read('boxtal_api_key_v1')
            ])
            ->add('api_secret_v1', TextType::class, [
                'label' => $this->translator->trans('API Secret V1  (necessary for cotations)', [], BoxtalShipping::DOMAIN_NAME),
                'required' => false,
                'label_attr' => [
                    'for' => 'boxtal-api-secret-v1'
                ],
                'attr' => [
                    'id' => 'boxtal-api-secret-v1'
                ],
                'data' => ConfigQuery::read('boxtal_api_secret_v1')
            ])
            ->add('firstname', TextType::class, [
                'label' => $this->translator->trans('First Name', [], BoxtalShipping::DOMAIN_NAME),
                'required' => false,
                'label_attr' => [
                    'for' => 'boxtal-firstname',
                    'placeholder' => $this->translator->trans('Enter your first name', [], BoxtalShipping::DOMAIN_NAME),
                    'help' => 'This field is used because a name is required for the start address and in the case of a return',
                    [],
                    BoxtalShipping::DOMAIN_NAME
                ],
                'attr' => ['id' => 'boxtal-firstname'],
                'data' => ConfigQuery::read('boxtal_firstname')
            ])
            ->add('lastname', TextType::class, [
                'label' => $this->translator->trans('Last Name', [], BoxtalShipping::DOMAIN_NAME),
                'required' => false,
                'label_attr' => [
                    'for' => 'boxtal-firstname',
                    'placeholder' => $this->translator->trans('Enter your first name', [], BoxtalShipping::DOMAIN_NAME),
                    'help' => 'This field is used because a name is required for the start address and in the case of a return',
                    [],
                    BoxtalShipping::DOMAIN_NAME

                ],
                'attr' => [
                    'id' => 'boxtal-lastname',
                ],
                'data' => ConfigQuery::read('boxtal_lastname'),
            ])

            ->add("default_category", ChoiceType::class, [
                'choices' => $this->getContentCategories(),
                'required' => true,
                'label' => $this->translator->trans('Content Type', [], BoxtalShipping::DOMAIN_NAME),
                'placeholder' => $this->translator->trans('Choose a content type', [], BoxtalShipping::DOMAIN_NAME),
                'data' => ConfigQuery::read('boxtal_default_category'),
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'expanded' => false,
                'multiple' => false,
                // data ne fonctionne pas
                'data' => ConfigQuery::read('boxtal_default_category')
            ]);
    }

    private function getContentCategories()
    {
        $categoriesService = new BoxtalGetCategoriesService();
        $contentCategoriesJson = $categoriesService->getContentCategories();
        $contentCategoriesData = json_decode($contentCategoriesJson, true);
        $contentChoices = [];

        if (json_last_error() === JSON_ERROR_NONE && isset($contentCategoriesData['content'])) {
            $contentCategories = $contentCategoriesData['content'];
            foreach ($contentCategories as $category) {
                $contentChoices[$category['label']] = $category['id'];
            }
        }

        return $contentChoices;
    }

    public static function getName()
    {
        return 'boxtal_configuration';
    }
}
