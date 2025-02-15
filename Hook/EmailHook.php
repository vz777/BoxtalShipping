<?php

/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace BoxtalShipping\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class EmailHook extends BaseHook
{
    protected function renderAddressTemplate(HookRenderEvent $event, $htmlMode = false)
    {
        $event->add(
            $this->render(
                'boxtalshipping/order-delivery-address.html',
                [
                    'module_id' => $event->getArgument('module'),
                    'order_id' => $event->getArgument('order'),
                    'html_mode' => $htmlMode ? '1' : '0'
                ]
            )
        );
    }

    public function onDeliveryAddressText(HookRenderEvent $event)
    {
        $this->renderAddressTemplate($event, false);
    }

    public function onDeliveryAddressHtml(HookRenderEvent $event)
    {
        $this->renderAddressTemplate($event, true);
    }
}
