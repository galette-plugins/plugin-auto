--
-- This file is part of Galette Auto plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for table galette_auto_bodies
DROP TABLE IF EXISTS galette_auto_bodies;
CREATE TABLE galette_auto_bodies (
  id_body int(11) NOT NULL AUTO_INCREMENT,
  body varchar(50) NOT NULL,
  PRIMARY KEY (id_body)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_brands
DROP TABLE IF EXISTS galette_auto_brands;
CREATE TABLE galette_auto_brands (
  id_brand int(11) NOT NULL AUTO_INCREMENT,
  brand varchar(50) NOT NULL,
  PRIMARY KEY (id_brand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_colors
DROP TABLE IF EXISTS galette_auto_colors;
CREATE TABLE galette_auto_colors (
  id_color int(11) NOT NULL AUTO_INCREMENT,
  color varchar(50) NOT NULL,
  PRIMARY KEY (id_color)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_finitions
DROP TABLE IF EXISTS galette_auto_finitions;
CREATE TABLE galette_auto_finitions (
  id_finition int(11) NOT NULL AUTO_INCREMENT,
  finition varchar(50) NOT NULL,
  PRIMARY KEY (id_finition)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_models
DROP TABLE IF EXISTS galette_auto_models;
CREATE TABLE galette_auto_models (
  id_model int(11) NOT NULL AUTO_INCREMENT,
  model varchar(50) NOT NULL,
  id_brand int(11) NOT NULL,
  PRIMARY KEY (id_model),
  FOREIGN KEY (id_brand) REFERENCES galette_auto_brands(id_brand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_states
DROP TABLE IF EXISTS galette_auto_states;
CREATE TABLE galette_auto_states (
  id_state int(11) NOT NULL AUTO_INCREMENT,
  state varchar(50) NOT NULL,
  PRIMARY KEY (id_state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_transmissions
DROP TABLE IF EXISTS galette_auto_transmissions;
CREATE TABLE galette_auto_transmissions (
  id_transmission int(11) NOT NULL AUTO_INCREMENT,
  transmission varchar(50) NOT NULL,
  PRIMARY KEY (id_transmission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_cars
DROP TABLE IF EXISTS galette_auto_cars;
CREATE TABLE galette_auto_cars (
  id_car int(11) NOT NULL AUTO_INCREMENT,
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
  id_color int(11) NOT NULL,
  id_body int(11) NOT NULL,
  id_state int(11) NOT NULL,
  id_transmission int(11) NOT NULL,
  id_finition int(11) NOT NULL,
  id_model int(11) NOT NULL,
  id_adh int(10) unsigned NOT NULL,
  PRIMARY KEY (id_car),
  FOREIGN KEY(id_color) REFERENCES galette_auto_colors (id_color) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_body) REFERENCES galette_auto_bodies (id_body) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_state) REFERENCES galette_auto_states (id_state) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_transmission) REFERENCES galette_auto_transmissions (id_transmission) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_finition) REFERENCES galette_auto_finitions (id_finition) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_model) REFERENCES galette_auto_models (id_model) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_adh) REFERENCES galette_adherents (id_adh) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_history
DROP TABLE IF EXISTS galette_auto_history;
CREATE TABLE galette_auto_history (
  id_car int(11) NOT NULL,
  id_adh int(10) unsigned NOT NULL,
  history_date datetime NOT NULL,
  car_registration varchar(10) NOT NULL,
  id_color int(11) NOT NULL,
  id_state int(11) NOT NULL,
  PRIMARY KEY (id_car,id_adh,history_date),
  FOREIGN KEY (id_car) REFERENCES galette_auto_cars (id_car) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_adh) REFERENCES galette_adherents (id_adh) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_color) REFERENCES galette_auto_colors (id_color) ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY (id_state) REFERENCES galette_auto_states (id_state) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table structure for table galette_auto_pictures
DROP TABLE IF EXISTS galette_auto_pictures;
CREATE TABLE galette_auto_pictures (
  id_car int(11) NOT NULL,
  picture mediumblob NOT NULL,
  format varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (id_car),
  FOREIGN KEY (id_car) REFERENCES galette_auto_cars (id_car) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
