<?php

namespace BoxtalShipping;

use BoxtalShipping\Model\BoxtalDeliveryMode;
use BoxtalShipping\Model\BoxtalDeliveryModeQuery;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Core\Translation\Translator;
use Thelia\Install\Database;
use Thelia\Log\Tlog;
use Thelia\Model\Country;
use Thelia\Model\LangQuery;
use Thelia\Model\Message;
use Thelia\Model\MessageQuery;
use Thelia\Model\State;
use Thelia\Module\AbstractDeliveryModuleWithState;

//non documenté nécessaire pour créer un hook
use Thelia\Core\Template\TemplateDefinition;

class BoxtalShipping extends AbstractDeliveryModuleWithState
{
    /** @var string */
    const DOMAIN_NAME = 'boxtalshipping';

    const BOXTAL_API_KEY_V3 = "boxtal_api_key_v3";
    const BOXTAL_API_SECRET_V3 = "boxtal_api_secret_v3";
    const BOXTAL_API_KEY_V1 = "boxtal_api_key_v1";
    const BOXTAL_API_SECRET_V1 = "boxtal_api_secret_v1";
    const RELAY_DELIVERY_TYPE = 'relay';
    const HOME_DELIVERY_TYPE = 'home';

    const SESSION_SELECTED_PICKUP_RELAY_ID = 'boxtal_selected_pickup_point_id';

    const BOXTAL_API_TOKEN_URL_V3 = 'https://api.boxtal.build/iam/account-app/token';
    const BOXTAL_API_TOKEN_URL_V1 = 'https://test.envoimoinscher.com/api/v1';
    const BOXTAL_COTATIONS_URL_V1 = 'https://test.envoimoinscher.com/api/v1/cotation?';
    const BOXTAL_CONTENT_CATEGORY_API_URL_V3 = 'https://api.boxtal.build/shipping/v3.1/content-category?language=fr';
    const BOXTAL_PARCEL_POINT_API_URL_V3 = "https://api.boxtal.build/shipping/v3.1/parcel-point";
    const BOXTAL_SHIPPING_ORDER_API_URL_V3 = 'https://api.boxtal.build/shipping/v3.1/shipping-order';


    const BOXTAL_FIRSTNAME = 'boxtal_firstname';
    const BOXTAL_LASTNAME = 'boxtal_lastname';
    const BOXTAL_DEFAULT_CATEGORY = 'boxtal_default_category';

    const TRACKING_MESSAGE_NAME = 'boxtal-tracking-message';

    /**
     * Defines how services are loaded in your modules
     *
     * @param ServicesConfigurator $servicesConfigurator
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode() . '\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()) . "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }

    public function postActivation(ConnectionInterface $con = null): void
    {
        // Look if module has already been activated 
        if (!self::getConfigValue('is_initialized', false)) {

            $database = new Database($con->getWrappedConnection());
            // Insert generated file
            $database->insertSql(null, [__DIR__ . '/Config/TheliaMain.sql']);

            $deliveryTypes = [
                ["CHRP-Chrono13", "Chronopost Chrono 13", "home"],
                ["CHRP-Chrono18", "Chronopost Chrono 18", "home"],
                ["CHRP-ChronoInternationalClassic", "Chrono Classic", "home"],
                ["CHRP-ChronoRelais", "Chrono Relais", "relay"],
                ["CHRP-ChronoRelaisEurope", "Chrono Relais Europe", "relay"],
                ["COPR-CoprRelaisDomicileNat", "Colis Privé Domicile", "home"],
                ["COPR-CoprRelaisRelaisNat", "Colis Privé Relais", "relay"],
                ["POFR-ColissimoExpert", "Colissimo Domicile - avec signature", "home"],
                ["POFR-ColissimoAccess", "Colissimo Domicile", "home"],
                ["MONR-Standard", "Mondial Relay Standard", "relay"],
                ["UPSE-StandardAP", "UPS Standard Access Point", "relay"],
                ["UPSE-Standard", "UPS Standard", "home"],
                ["DHLE-Express", "DHL Express", "home"],
                ["FEDX-Priority", "FedEx Priority", "home"],
                ["TNTE-Express", "TNT Express", "home"]
            ];

            foreach ($deliveryTypes as [$carrierCode, $title, $type]) {
                if (null === $this->isDeliveryTypeSet($carrierCode)) {
                    $this->setDeliveryType($carrierCode, $title, $type);
                }
            }

            if (null === MessageQuery::create()->findOneByName(self::TRACKING_MESSAGE_NAME)) {
                $message = new Message();
                $message
                    ->setName(self::TRACKING_MESSAGE_NAME)
                    ->setHtmlLayoutFileName('')
                    ->setHtmlTemplateFileName(self::TRACKING_MESSAGE_NAME . '.html')
                    ->setTextLayoutFileName('')
                    ->setTextTemplateFileName(self::TRACKING_MESSAGE_NAME . '.txt')
                ;

                $languages = LangQuery::create()->find();

                /** @var Lang $language */
                foreach ($languages as $language) {
                    $locale = $language->getLocale();
                    $message->setLocale($locale);

                    $message->setTitle(
                        Translator::getInstance()->trans('Shipment tracking information', [], self::DOMAIN_NAME, $locale)
                    );

                    $message->setSubject(
                        Translator::getInstance()->trans('Your order has been shipped', [], self::DOMAIN_NAME, $locale)
                    );
                }

                $message->save();
            }
            self::setConfigValue('is_initialized', true);
            // Set module as initialized
            //self::setConfigValue('boxtal_is_initialized', false);
        }
    }
    /**
     * Vérifie si un type de livraison existe dans la table BoxtalDeliveryMode
     *
     * @param $code
     * @return BoxtalDeliveryMode
     */
    public function isDeliveryTypeSet($carrierCode)
    {
        return BoxtalDeliveryModeQuery::create()->findOneByCarrierCode($carrierCode);
    }

    /**
     * Ajoute type de livraison à la table BoxtalDeliveryMode
     *
     * @param $code
     * @param $title
     */

    public function setDeliveryType($carrierCode, $title, $deliveryType)
    {
        $newDeliveryType = new BoxtalDeliveryMode();
        try {
            $newDeliveryType
                ->setCarrierCode($carrierCode)
                ->setTitle($title)
                ->setDeliveryType($deliveryType)
                ->setFreeshippingActive(false)
                ->setFreeshippingFrom(null)
                ->save();
        } catch (\Exception $e) {
            Tlog::getInstance()->error("Erreur lors de l'ajout du mode de livraison : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute sql files in Config/update/ folder named with module version (ex: 1.0.1.sql).
     *
     * @param $currentVersion
     * @param $newVersion
     * @param ConnectionInterface $con
     */
    /*public function update($currentVersion, $newVersion, ConnectionInterface $con = null): void
    {
        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in(__DIR__.DS.'Config'.DS.'update');

        $database = new Database($con);

        /** @var \SplFileInfo $file *
        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }*/

    /**
     * This method is called by the Delivery loop, to check if the current module has to be displayed to the customer.
     * Override it to implements your delivery rules/
     *
     * If you return true, the delivery method will de displayed to the customer
     * If you return false, the delivery method will not be displayed
     *
     * @param Country $country the country to deliver to.
     *
     * @return boolean
     */

    public function isValidDelivery(Country $country, State $state = null)
    {
        // TODO: Implement isValidDelivery() method.
    }

    public function getPostage(Country $country, State $state = null)
    {
        // TODO: Implement getPostage() method.
    }

    public function getHooks()
    {
        return [
            [
                "type" => TemplateDefinition::BACK_OFFICE,
                "code" => "boxtal.pricing.display",
                "title" => "Boxtal pricing display",
                "description" => [
                    "fr_FR" => "Affiche les données de tarification Boxtal",
                    "en_US" => "Displays Boxtal pricing data",
                ],
                "active" => true
            ]
        ];
    }
}
