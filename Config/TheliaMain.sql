
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- boxtal_order_address
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `boxtal_order_address`;

CREATE TABLE `boxtal_order_address`
(
    `id` INTEGER NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_order_address_boxtal_order_address_id`
        FOREIGN KEY (`id`)
        REFERENCES `order_address` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- boxtal_address
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `boxtal_address`;

CREATE TABLE `boxtal_address`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `company` VARCHAR(255) NOT NULL,
    `address1` VARCHAR(255) NOT NULL,
    `address2` VARCHAR(255),
    `address3` VARCHAR(255),
    `zipcode` VARCHAR(10) NOT NULL,
    `city` VARCHAR(255) NOT NULL,
    `country_id` INTEGER NOT NULL,
    `relay_code` VARCHAR(10) NOT NULL,
    `delivery_mode_id` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `fi_address_boxtal_country_id` (`country_id`),
    INDEX `fi_address_boxtal_delivery_mode_id` (`delivery_mode_id`),
    CONSTRAINT `fk_address_boxtal_country_id`
        FOREIGN KEY (`country_id`)
        REFERENCES `country` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,
    CONSTRAINT `fk_address_boxtal_delivery_mode_id`
        FOREIGN KEY (`delivery_mode_id`)
        REFERENCES `boxtal_delivery_mode` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- boxtal_delivery_mode
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `boxtal_delivery_mode`;

CREATE TABLE `boxtal_delivery_mode`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255),
    `carrier_code` VARCHAR(55) NOT NULL,
    `delivery_type` VARCHAR(10) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `freeshipping_active` TINYINT(1),
    `freeshipping_from` FLOAT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- boxtal_freeshipping
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `boxtal_freeshipping`;

CREATE TABLE `boxtal_freeshipping`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `area_id` INTEGER NOT NULL,
    `delivery_mode_id` INTEGER NOT NULL,
    `active` TINYINT(1) NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_boxtal_freeshipping_area_id` (`area_id`),
    INDEX `fi_boxtal_freeshipping_delivery_mode_id` (`delivery_mode_id`),
    CONSTRAINT `fk_boxtal_freeshipping_area_id`
        FOREIGN KEY (`area_id`)
        REFERENCES `area` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,
    CONSTRAINT `fk_boxtal_freeshipping_delivery_mode_id`
        FOREIGN KEY (`delivery_mode_id`)
        REFERENCES `boxtal_delivery_mode` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- boxtal_price
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `boxtal_price`;

CREATE TABLE `boxtal_price`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `area_id` INTEGER NOT NULL,
    `delivery_mode_id` INTEGER NOT NULL,
    `weight_max` FLOAT NOT NULL,
    `price_max` FLOAT,
    `franco_min_price` FLOAT,
    `price` FLOAT NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_boxtal_price_area_id` (`area_id`),
    INDEX `fi_boxtal_price_delivery_mode_id` (`delivery_mode_id`),
    CONSTRAINT `fk_boxtal_price_area_id`
        FOREIGN KEY (`area_id`)
        REFERENCES `area` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,
    CONSTRAINT `fk_boxtal_price_delivery_mode_id`
        FOREIGN KEY (`delivery_mode_id`)
        REFERENCES `boxtal_delivery_mode` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- boxtal_carrier_zone
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `boxtal_carrier_zone`;

CREATE TABLE `boxtal_carrier_zone`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `delivery_mode_id` INTEGER NOT NULL,
    `area_id` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `boxtal_carrier_zone_u_ea6421` (`delivery_mode_id`, `area_id`),
    INDEX `fi_boxtal_carrier_zone_area_id` (`area_id`),
    CONSTRAINT `fk_boxtal_carrier_zone_delivery_mode_id`
        FOREIGN KEY (`delivery_mode_id`)
        REFERENCES `boxtal_delivery_mode` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_boxtal_carrier_zone_area_id`
        FOREIGN KEY (`area_id`)
        REFERENCES `area` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
