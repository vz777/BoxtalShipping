<?php

namespace BoxtalShipping\Hook;

use BoxtalShipping\BoxtalShipping;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use BoxtalShipping\Service\BoxtalRelaisService;
use Thelia\Core\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FrontHook extends BaseHook
{
    private $boxtalRelaisService;
    private $requestStack;

    public function __construct(BoxtalRelaisService $boxtalRelaisService, RequestStack $requestStack)
    {
        $this->boxtalRelaisService = $boxtalRelaisService;
        $this->requestStack = $requestStack;
    }

    public function onOrderInvoiceDeliveryAddress(HookRenderEvent $event)
    {
        $content = $this->render('delivery-address.html', $event->getArguments());
        $event->add($content);
    }

    public function onOrderDeliveryExtra(HookRenderEvent $event)
    {
        $this->getSession()->remove('BoxtalDeliveryType');
        $this->getSession()->remove(BoxtalShipping::SESSION_SELECTED_PICKUP_RELAY_ID);
        $this->getSession()->remove(BoxtalShipping::SESSION_SELECTED_PICKUP_RELAY_CODE);

        $addressId = $this->getRequest()->get('address_id', 0);

        $event->add(
            $this->render(
                'order-delivery-extra.html',
                [
                    'module_id' => BoxtalShipping::getModuleId(),
                    'address_id' => $addressId
                ]
            )
        );
    }

    /* would allow to display map
    public function onJavascriptInitialization(HookRenderEvent $event)
    {
        $event->add($this->render("boxtal-pickup-js.html"));
    }*/
}
