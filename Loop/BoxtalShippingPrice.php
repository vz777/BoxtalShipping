<?php

/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace BoxtalShipping\Loop;

use BoxtalShipping\Model\BoxtalPriceQuery;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

class BoxtalShippingPrice extends BaseLoop implements PropelSearchLoopInterface

{

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('area_id', null, true),
            Argument::createIntTypeArgument('delivery_mode_id', null, true)
        );
    }

    public function buildModelCriteria()
    {
        $areaId = $this->getAreaId();
        $deliveryModeId = $this->getDeliveryModeId();

        $areaPrices = BoxtalPriceQuery::create()
            ->filterByDeliveryModeId($deliveryModeId)
            ->filterByAreaId($areaId)
            ->orderByWeightMax();

        return $areaPrices;
    }

    public function parseResults(LoopResult $loopResult)
    {
        foreach ($loopResult->getResultDataCollection() as $price) {
            $loopResultRow = new LoopResultRow($price);
            $loopResultRow
                ->set('SLICE_ID', $price->getId())
                ->set('MAX_WEIGHT', $price->getWeightMax())
                ->set('MAX_PRICE', $price->getPriceMax())
                ->set('PRICE', $price->getPrice())
                ->set('FRANCO', $price->getFrancoMinPrice())
            ;
            $loopResult->addRow($loopResultRow);
        }
        return $loopResult;
    }
}
