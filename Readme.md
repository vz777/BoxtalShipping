# Boxtal Shipping

## Description

### fr

Ce module permet d'intégrer Boxtal à votre boutique Thelia. Il comporte deux parties distinctes :

- Une partie front-office qui permet de proposer les transporteurs Boxtal de votre choix à vos clients.

- Une partie back-office qui facilite la gestion de vos affranchissements.

Si vous souhaitez uniquement utiliser la partie affranchissement, vous pouvez vous abstenir de configurer les zones de livraison.

### en

This module integrates Boxtal into your Thelia store. It comprises two distinct parts:

- A front-office component that allows you to offer your chosen Boxtal carriers to your customers.

- A back-office component that simplifies the management of your shipping labels.

If you only wish to use the shipping label functionality, you can refrain from configuring delivery zones.

## Installation

### Manually

- Copy the module into `<thelia_root>/local/modules/`
- Activate it in your Thelia administration panel

### Composer

Add it in your main Thelia composer.json file:

```
composer require your-vendor/boxtal-shipping-module:~1.0
```

## Usage

### Configuration (fr)

1. Création de compte Boxtal

- Créez un compte sur le site officiel de Boxtal : https://www.boxtal.com.
- Pour un environnement de test, utilisez : https://shipping.boxtal.build/.

2. Configuration pour l'affranchissement

   - Clés API V3: Indispensables pour utiliser la fonctionnalité d'affranchissement.
   - Infos expéditeur: Définissez un nom et un prénom. Ces informations seront utilisées comme nom et prénom par défaut pour l'adresse d'envoi et de retour de vos colis.
   - Catégorie par défaut (optionnel): Choisissez une catégorie de contenu par défaut.
   - Clés API V1: Néccessaires si vous souhaitez obtenir les prix des transporteurs.
   - Tarification: Si vous avez configuré vos clés API V1, un onglet dédié vous permet d'obtenir une estimation des prix des transporteurs.
   - Affranchissement: Pour affranchir, rdv dans l'onglet tools, puis 'Envois avec Boxtal'
   - En soit le module n'en a pas besoin mais afin d'avoir des numéro de tel cohérents il serait préferable d'avoir le module force phone d'installé
     https://github.com/thelia-modules/ForcePhone
     composer require thelia/force-phone-module

3. Configuration pour proposer les transporteurs coté Front
   - Activation des zones: Configurer les zones de manière globale pour le module.
   - Activaction des transporteurs: Dans l'onglet "Configuration des transporteurs", activez les transporteurs que vous souhaitez proposer à vos clients.
   - Configuration des zones: Dans ce même onglet, affinez la configuration pour chaque transporteur. Un export en CSV/JSON est possible.
   - Définition des prix: Configurez les tranches de prix en fonction du transporteur, du poids et de la zone de livraison.

### Configuration (en)

1. Boxtal Account Creation

- Create an account on the official Boxtal website: https://www.boxtal.com.
- For a test environment, use: https://shipping.boxtal.build/.

2. Shipping Label Configuration

   - API V3 Keys: Essential for using the shipping label functionality.
   - Sender Information: Define a first name and last name. This information will be used as the default name for the sender and return address of your packages.
   - Default Category (optional): Choose a default content category.
   - API V1 Keys: Necessary if you want to obtain carrier prices.
   - Pricing: If you have configured your API V1 keys, a dedicated tab allows you to get an estimate of carrier prices.
   - Shipping Labels: To create shipping labels, go to the "Tools" tab, then 'Shipments with Boxtal'
   - While not strictly necessary for the module, to ensure consistent phone numbers, it is preferable to have the Force Phone module installed:
     https://github.com/thelia-modules/ForcePhone
     composer require thelia/force-phone-module

3. Configuration to Offer Carriers on the Front End
   - Zone Activation: Configure the zones globally for the module.
   - Carrier Activation: In the "Carrier Configuration" tab, activate the carriers you want to offer to your customers.
   - Zone Configuration: In the same tab, then refine the configuration for each carrier. A CSV/JSON export is possible.
   - Price Definition: Configure the price ranges based on the carrier, weight, and delivery zone.

## Hooks

- `module.configuration`: Handles the configuration page of the Boxtal Shipping module.
- `module.config-js`: Loads JavaScript for the module's configuration page.
- `order-edit.bill-delivery-address`: Customizes the delivery address display on the order edit page in the back office.
- `main.top-menu-tools`: Adds Boxtal Shipping tools to the top menu in the back office.
- `order-delivery.extra`: Adds the pickup point selection map to the delivery page.
- `order-invoice.delivery-address`: Displays the chosen pickup point information on the invoice page.
- `boxtal.pricing.display`: Displays Boxtal pricing data in the back office.

## Loops

### `boxtal.available.delivery.modes`

#### Description (fr)

Cette boucle récupère les modes de livraison Boxtal disponibles pour un pays donné, en tenant compte du poids du panier actuel. Cette boucle ne fonctionne que côté front lorsqu'un panier est présent. Les modes de livraison retournés sont filtrés pour n'inclure que ceux qui sont actifs. Le prix retourné est calculé en fonction du poids du panier actuel et de la tranche de prix correspondante trouvée.

#### Description (en)

This loop retrieves available Boxtal delivery modes for a given country, taking into account the current cart weight. This loop only functions on the front end when a cart is present. Returned delivery modes are filtered to include only those that are active. The returned price is calculated based on the current cart weight and the corresponding price range found.

#### Input Arguments

| Argument     | Description                                  | Requis |
| ------------ | -------------------------------------------- | ------ |
| `country_id` | L'ID du pays pour lequel récupérer les modes | Oui    |

#### Output Arguments

| Argument         | Description                                |
| ---------------- | ------------------------------------------ |
| `$ID`            | L'identifiant unique du mode de livraison  |
| `$CARRIER_CODE`  | Le code du transporteur                    |
| `$TITLE`         | Le titre du mode de livraison              |
| `$PRICE`         | Le prix calculé pour ce mode de livraison  |
| `$AREA_ID`       | L'identifiant de la zone de livraison      |
| `$DELIVERY_TYPE` | Le type de livraison (ex: 'home', 'relay') |

#### Example

```
{loop type="boxtal.available.delivery.modes" name="delivery_modes" country_id=$country_id}
    <div class="delivery-mode">
        <h3>{$TITLE}</h3>
        <p>Type: {$DELIVERY_TYPE}</p>
        <p>Prix: {$PRICE|format_money}</p>
    </div>
{/loop}
```

### `boxtal.active.delivery.modes`

#### Description (fr)

Cette boucle récupère les modes de livraison Boxtal actifs, triés par titre. Si aucun mode de livraison actif n'est trouvé, la boucle ne retournera aucun résultat.

#### Description (en)

This loop retrieves active Boxtal delivery modes, sorted by title. If no active delivery modes are found, the loop will return no results.

#### Output Arguments

| Argument         | Description                                |
| ---------------- | ------------------------------------------ |
| `$ID`            | L'identifiant unique du mode de livraison  |
| `$TITLE`         | Le titre du mode de livraison              |
| `$CARRIER_CODE`  | Le code du transporteur                    |
| `$DELIVERY_TYPE` | Le type de livraison (ex: 'home', 'relay') |

#### Example

```
{loop type="boxtal.active.delivery.modes" name="active_delivery_modes"}
    <div class="delivery-mode">
        <h3>{$TITLE}</h3>
        <p>Type: {$DELIVERY_TYPE}</p>
        <p>Code transporteur: {$CARRIER_CODE}</p>
    </div>
{/loop}
```

### `boxtal.delivery.address`

#### Description (fr)

Cette boucle récupère les informations d'adresse de livraison Boxtal et le mode de livraison associé. La boucle cherche d'abord un point relais sélectionné dans la session. Si aucun point relais n'est trouvé en session, elle utilise l'`order_address_id` ou l'`order_id` fourni. Les informations du mode de livraison sont incluses si elles sont disponibles.

#### Description (en)

This loop retrieves Boxtal delivery address information and the associated delivery mode. The loop first looks for a selected relay point in the session. If no relay point is found in the session, it uses the provided `order_address_id` or `order_id`. Delivery mode information is included if available.

#### Input Arguments

| Argument           | Description                   |
| ------------------ | ----------------------------- |
| `order_address_id` | L'ID de l'adresse de commande |
| `order_id`         | L'ID de la commande           |

#### Output Arguments

| Argument               | Description                                       |
| ---------------------- | ------------------------------------------------- |
| `$ID`                  | L'identifiant unique de l'adresse                 |
| `$COMPANY`             | Le nom de l'entreprise                            |
| `$ADDRESS1`            | Première ligne d'adresse                          |
| `$ADDRESS2`            | Deuxième ligne d'adresse (si disponible)          |
| `$ADDRESS3`            | Troisième ligne d'adresse (si disponible)         |
| `$ZIPCODE`             | Code postal                                       |
| `$CITY`                | Ville                                             |
| `$RELAY_CODE`          | Code du point relais (si applicable)              |
| `$COUNTRY_ID`          | L'ID du pays                                      |
| `$DELIVERY_MODE_ID`    | L'ID du mode de livraison                         |
| `$DELIVERY_MODE_TITLE` | Le titre du mode de livraison                     |
| `$CARRIER_CODE`        | Le code du transporteur                           |
| `$DELIVERY_TYPE`       | Le type de livraison                              |
| `$IS_ACTIVE`           | Indique si le mode de livraison est actif         |
| `$FREESHIPPING_ACTIVE` | Indique si la livraison gratuite est active       |
| `$FREESHIPPING_FROM`   | Montant à partir duquel la livraison est gratuite |

#### Example

```
{loop type="boxtal.delivery.address" name="delivery_address" order_id=$order_id}
    <div class="delivery-address">
        <h3>{$COMPANY}</h3>
        <p>{$ADDRESS1}</p>
        {if $ADDRESS2}<p>{$ADDRESS2}</p>{/if}
        {if $ADDRESS3}<p>{$ADDRESS3}</p>{/if}
        <p>{$ZIPCODE} {$CITY}</p>
        {if $RELAY_CODE}<p>Point Relais: {$RELAY_CODE}</p>{/if}

        <h4>Mode de livraison: {$DELIVERY_MODE_TITLE}</h4>
        <p>Type: {$DELIVERY_TYPE}</p>
        <p>Transporteur: {$CARRIER_CODE}</p>
    </div>
{/loop}
```

### `boxtal.prices`

#### Description (fr)

Cette boucle récupère les tranches de prix pour une zone et un mode de livraison Boxtal spécifiques. Cette boucle est utile pour afficher les différentes tranches de prix de livraison dans le back-office ou pour calculer le prix de livraison en fonction du poids du panier.

#### Description (en)

This loop retrieves the price ranges for a specific zone and Boxtal delivery mode. This loop is useful for displaying different delivery price ranges in the back office or for calculating the delivery price based on the cart's weight.

#### Input Arguments

| Argument           | Description                  | Requis |
| ------------------ | ---------------------------- | ------ |
| `area_id`          | L'ID de la zone de livraison | Oui    |
| `delivery_mode_id` | L'ID du mode de livraison    | Oui    |

#### Output Arguments

| Argument      | Description                                   |
| ------------- | --------------------------------------------- |
| `$SLICE_ID`   | L'identifiant unique de la tranche de prix    |
| `$MAX_WEIGHT` | Le poids maximum pour cette tranche de prix   |
| `$MAX_PRICE`  | Le prix maximum pour cette tranche de prix    |
| `$PRICE`      | Le prix de livraison pour cette tranche       |
| `$FRANCO`     | Le montant minimum pour la livraison gratuite |

#### Example

```
{loop type="boxtal.prices" name="shipping_prices" area_id=$area_id delivery_mode_id=$delivery_mode_id}
    <tr>
        <td>Jusqu'à {$MAX_WEIGHT} kg</td>
        <td>Jusqu'à {$MAX_PRICE|format_money} €</td>
        <td>{$PRICE|format_money} €</td>
        {if $FRANCO > 0}
            <td>Gratuit à partir de {$FRANCO|format_money} €</td>
        {/if}
    </tr>
{/loop}
```

### `boxtal.relay.points`

#### Description (fr)

Cette boucle récupère les points relais Boxtal disponibles pour une adresse donnée.

#### Description (en)

This loop retrieves the Boxtal relay points available for a given address.

#### Input Arguments

| Argument       | Description                                       |
| -------------- | ------------------------------------------------- |
| `zipcode`      | Le code postal pour la recherche de points relais |
| `city`         | La ville pour la recherche de points relais       |
| `carrier_code` | Le code du transporteur                           |

Si `zipcode` et `city` ne sont pas fournis, la boucle utilisera l'adresse par défaut du client connecté.

If `zipcode` and `city` are not provided, the loop will use the default address of the connected customer.

#### Output Arguments

| Argument        | Description                                                |
| --------------- | ---------------------------------------------------------- |
| `$RELAY_CODE`   | Le code unique du point relais                             |
| `$NAME`         | Le nom du point relais                                     |
| `$STREET`       | L'adresse du point relais                                  |
| `$CITY`         | La ville du point relais                                   |
| `$ZIPCODE`      | Le code postal du point relais                             |
| `$DISTANCE`     | La distance entre le point de recherche et le point relais |
| `$OPENING_DAYS` | Les jours et heures d'ouverture (format JSON)              |

#### Example

```
{loop type="boxtal.relay.points" name="relay_points" zipcode="75001" city="Paris"}
    <div class="relay-point">
        <h3>{$NAME}</h3>
        <p>{$STREET}, {$ZIPCODE} {$CITY}</p>
        <p>Distance : {$DISTANCE}</p>
        <p>Code : {$RELAY_CODE}</p>
    </div>
{/loop}
```

### `boxtal.carrier.zone`

#### Description (fr)

Cette boucle vérifie si un transporteur est associé à une zone spécifique.

#### Description (en)

This loop checks if a carrier is associated with a specific zone.

#### Input Arguments

| Argument           | Description               | Requis |
| ------------------ | ------------------------- | ------ |
| `delivery_mode_id` | L'ID du mode de livraison | Oui    |
| `area_id`          | L'ID de la zone           | Oui    |

#### Output Arguments

| Argument            | Description               |
| ------------------- | ------------------------- |
| `$DELIVERY_MODE_ID` | L'ID du mode de livraison |
| `$AREA_ID`          | L'ID de la zone           |

#### Example

```
{loop type="boxtal.carrier.zone" name="carrier_zone" delivery_mode_id=$delivery_mode_id area_id=$area_id}
    <p>Le transporteur {$DELIVERY_MODE_ID} est associé à la zone {$AREA_ID}</p>
{/loop}
```

## TODO

TODO : mettre de l'ajax sur tout les form de configuration
TODO : remettre la méthode pour le csv alternatif dans ExportController
TODO : dans le form de config generale, le select n'affiche pas la catégorie par défault via data
TODO : ameliorer la gestion des constantes
TODO : la méthode getPricingData dans BoxtalPricingService ne sert à rien pour le moment
TODO : la méthode dans ShipmentController n'est pas optimale, si l'utilisateur actualise la page, le form est renvoyé
