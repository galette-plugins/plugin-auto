--
-- This file is part of Galette Auto plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Align schema with PostgreSQL one: utf8mb4, unsigned identifiers, same
-- foreign keys on both engines. A member removal removes their vehicles.
-- Foreign keys names depend on the MySQL version that created them, and
-- MySQL cannot drop them conditionally: tables are rebuilt. Foreign keys
-- are added once every identifier has its new type, with checks enabled:
-- without them, MariaDB records ON DELETE RESTRICT as NO ACTION.
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE galette_auto_bodies_new (
  id_body int(10) unsigned NOT NULL AUTO_INCREMENT,
  body varchar(50) NOT NULL,
  PRIMARY KEY (id_body)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_bodies_new (id_body, body)
SELECT id_body, body
FROM galette_auto_bodies;

CREATE TABLE galette_auto_brands_new (
  id_brand int(10) unsigned NOT NULL AUTO_INCREMENT,
  brand varchar(50) NOT NULL,
  PRIMARY KEY (id_brand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_brands_new (id_brand, brand)
SELECT id_brand, brand
FROM galette_auto_brands;

CREATE TABLE galette_auto_colors_new (
  id_color int(10) unsigned NOT NULL AUTO_INCREMENT,
  color varchar(50) NOT NULL,
  PRIMARY KEY (id_color)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_colors_new (id_color, color)
SELECT id_color, color
FROM galette_auto_colors;

CREATE TABLE galette_auto_finitions_new (
  id_finition int(10) unsigned NOT NULL AUTO_INCREMENT,
  finition varchar(50) NOT NULL,
  PRIMARY KEY (id_finition)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_finitions_new (id_finition, finition)
SELECT id_finition, finition
FROM galette_auto_finitions;

CREATE TABLE galette_auto_models_new (
  id_model int(10) unsigned NOT NULL AUTO_INCREMENT,
  model varchar(50) NOT NULL,
  id_brand int(10) unsigned NOT NULL,
  PRIMARY KEY (id_model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_models_new (id_model, model, id_brand)
SELECT id_model, model, id_brand
FROM galette_auto_models;

CREATE TABLE galette_auto_states_new (
  id_state int(10) unsigned NOT NULL AUTO_INCREMENT,
  state varchar(50) NOT NULL,
  PRIMARY KEY (id_state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_states_new (id_state, state)
SELECT id_state, state
FROM galette_auto_states;

CREATE TABLE galette_auto_transmissions_new (
  id_transmission int(10) unsigned NOT NULL AUTO_INCREMENT,
  transmission varchar(50) NOT NULL,
  PRIMARY KEY (id_transmission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_transmissions_new (id_transmission, transmission)
SELECT id_transmission, transmission
FROM galette_auto_transmissions;

CREATE TABLE galette_auto_cars_new (
  id_car int(10) unsigned NOT NULL AUTO_INCREMENT,
  car_name varchar(50) NOT NULL,
  car_registration varchar(10) NOT NULL,
  car_first_registration_date date NOT NULL,
  car_first_circulation_date date NOT NULL,
  car_mileage int(10) DEFAULT NULL,
  car_comment text,
  car_creation_date date NOT NULL,
  car_chassis_number varchar(50) DEFAULT NULL,
  car_seats int(1) DEFAULT NULL,
  car_horsepower int(4) DEFAULT NULL,
  car_engine_size int(11) DEFAULT NULL,
  car_fuel int(2) DEFAULT NULL,
  id_color int(10) unsigned NOT NULL,
  id_body int(10) unsigned NOT NULL,
  id_state int(10) unsigned NOT NULL,
  id_transmission int(10) unsigned NOT NULL,
  id_finition int(10) unsigned NOT NULL,
  id_model int(10) unsigned NOT NULL,
  id_adh int(10) unsigned NOT NULL,
  PRIMARY KEY (id_car)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_cars_new (id_car, car_name, car_registration, car_first_registration_date, car_first_circulation_date, car_mileage, car_comment, car_creation_date, car_chassis_number, car_seats, car_horsepower, car_engine_size, car_fuel, id_color, id_body, id_state, id_transmission, id_finition, id_model, id_adh)
SELECT id_car, car_name, car_registration, car_first_registration_date, car_first_circulation_date, car_mileage, car_comment, car_creation_date, car_chassis_number, car_seats, car_horsepower, car_engine_size, car_fuel, id_color, id_body, id_state, id_transmission, id_finition, id_model, id_adh
FROM galette_auto_cars;

CREATE TABLE galette_auto_history_new (
  id_car int(10) unsigned NOT NULL,
  id_adh int(10) unsigned NOT NULL,
  history_date datetime NOT NULL,
  car_registration varchar(10) NOT NULL,
  id_color int(10) unsigned NOT NULL,
  id_state int(10) unsigned NOT NULL,
  PRIMARY KEY (id_car,id_adh,history_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_history_new (id_car, id_adh, history_date, car_registration, id_color, id_state)
SELECT id_car, id_adh, history_date, car_registration, id_color, id_state
FROM galette_auto_history;

CREATE TABLE galette_auto_pictures_new (
  id_car int(10) unsigned NOT NULL,
  picture mediumblob NOT NULL,
  format varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (id_car)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_pictures_new (id_car, picture, format)
SELECT id_car, picture, format
FROM galette_auto_pictures;

DROP TABLE galette_auto_bodies, galette_auto_brands, galette_auto_colors, galette_auto_finitions,
  galette_auto_models, galette_auto_states, galette_auto_transmissions, galette_auto_cars,
  galette_auto_history, galette_auto_pictures;

RENAME TABLE galette_auto_bodies_new TO galette_auto_bodies,
  galette_auto_brands_new TO galette_auto_brands,
  galette_auto_colors_new TO galette_auto_colors,
  galette_auto_finitions_new TO galette_auto_finitions,
  galette_auto_models_new TO galette_auto_models,
  galette_auto_states_new TO galette_auto_states,
  galette_auto_transmissions_new TO galette_auto_transmissions,
  galette_auto_cars_new TO galette_auto_cars,
  galette_auto_history_new TO galette_auto_history,
  galette_auto_pictures_new TO galette_auto_pictures;

SET FOREIGN_KEY_CHECKS=1;

ALTER TABLE galette_auto_models
  ADD CONSTRAINT galette_auto_models_id_brand_fkey FOREIGN KEY (id_brand)
    REFERENCES galette_auto_brands (id_brand) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE galette_auto_cars
  ADD CONSTRAINT galette_auto_cars_id_color_fkey FOREIGN KEY (id_color)
    REFERENCES galette_auto_colors (id_color) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_cars_id_body_fkey FOREIGN KEY (id_body)
    REFERENCES galette_auto_bodies (id_body) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_cars_id_state_fkey FOREIGN KEY (id_state)
    REFERENCES galette_auto_states (id_state) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_cars_id_transmission_fkey FOREIGN KEY (id_transmission)
    REFERENCES galette_auto_transmissions (id_transmission) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_cars_id_finition_fkey FOREIGN KEY (id_finition)
    REFERENCES galette_auto_finitions (id_finition) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_cars_id_model_fkey FOREIGN KEY (id_model)
    REFERENCES galette_auto_models (id_model) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_cars_id_adh_fkey FOREIGN KEY (id_adh)
    REFERENCES galette_adherents (id_adh) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE galette_auto_history
  ADD CONSTRAINT galette_auto_history_id_car_fkey FOREIGN KEY (id_car)
    REFERENCES galette_auto_cars (id_car) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_history_id_adh_fkey FOREIGN KEY (id_adh)
    REFERENCES galette_adherents (id_adh) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_history_id_color_fkey FOREIGN KEY (id_color)
    REFERENCES galette_auto_colors (id_color) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_history_id_state_fkey FOREIGN KEY (id_state)
    REFERENCES galette_auto_states (id_state) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE galette_auto_pictures
  ADD CONSTRAINT galette_auto_pictures_id_car_fkey FOREIGN KEY (id_car)
    REFERENCES galette_auto_cars (id_car) ON DELETE CASCADE ON UPDATE CASCADE;
