<?php

namespace BoxtalShipping\Hook;

use BoxtalShipping\BoxtalShipping;
use BoxtalShipping\Service\BoxtalPricingService;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Tools\URL;

class BackHook extends BaseHook
{

    protected $pricingService;

    public function __construct(BoxtalPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function onMainTopMenuTools(HookRenderBlockEvent $event)
    {
        $event->add(
            [
                'id' => 'Boxtal_Shipping_menu_tags',
                'class' => '',
                'url' => URL::getInstance()->absoluteUrl('/admin/boxtalshipping/bulk-shipment'),
                'title' => $this->trans('Boxtal Shipping', [], BoxtalShipping::DOMAIN_NAME)
            ]
        );
    }

    public function onModuleConfigJs(HookRenderEvent $event)
    {
        $event->add($this->render('module-config-js.html'));
    }

    public function onModuleConfiguration(HookRenderEvent $event)
    {
        $event->add($this->render('module-configuration.html'));
    }

    public function onBoxtalPricingDisplay(HookRenderEvent $event)
    {
        $pricingData = $this->pricingService->getGlobalPricingData();

        $event->add($this->render(
            'boxtalshipping/boxtal-pricing.html',
            ['pricing_data' => $pricingData]
        ));
    }
}
