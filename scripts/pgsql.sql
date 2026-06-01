--
-- This file is part of Galette Auto plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

DROP SEQUENCE IF EXISTS galette_auto_bodies_id_seq;
CREATE SEQUENCE galette_auto_bodies_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP SEQUENCE IF EXISTS galette_auto_brands_id_seq;
CREATE SEQUENCE galette_auto_brands_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP SEQUENCE IF EXISTS galette_auto_colors_id_seq;
CREATE SEQUENCE galette_auto_colors_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP SEQUENCE IF EXISTS galette_auto_finitions_id_seq;
CREATE SEQUENCE galette_auto_finitions_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP SEQUENCE IF EXISTS galette_auto_models_id_seq;
CREATE SEQUENCE galette_auto_models_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP SEQUENCE IF EXISTS galette_auto_transmissions_id_seq;
CREATE SEQUENCE galette_auto_transmissions_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP SEQUENCE IF EXISTS galette_auto_cars_id_seq;
CREATE SEQUENCE galette_auto_cars_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP SEQUENCE IF EXISTS galette_auto_states_id_seq;
CREATE SEQUENCE galette_auto_states_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

-- Table structure for table galette_auto_bodies
DROP TABLE IF EXISTS galette_auto_bodies CASCADE;
CREATE TABLE galette_auto_bodies (
  id_body integer DEFAULT nextval('galette_auto_bodies_id_seq'::text) NOT NULL,
  body character varying(50) NOT NULL,
  PRIMARY KEY (id_body)
);

-- Table structure for table galette_auto_brands
DROP TABLE IF EXISTS galette_auto_brands CASCADE;
CREATE TABLE galette_auto_brands (
  id_brand integer DEFAULT nextval('galette_auto_brands_id_seq'::text) NOT NULL,
  brand character varying(50) NOT NULL,
  PRIMARY KEY (id_brand)
);

-- Table structure for table galette_auto_colors
DROP TABLE IF EXISTS galette_auto_colors CASCADE;
CREATE TABLE galette_auto_colors (
  id_color integer DEFAULT nextval('galette_auto_colors_id_seq'::text) NOT NULL,
  color character varying(50) NOT NULL,
  PRIMARY KEY (id_color)
);

-- Table structure for table galette_auto_finitions
DROP TABLE IF EXISTS galette_auto_finitions CASCADE;
CREATE TABLE galette_auto_finitions (
  id_finition integer  DEFAULT nextval('galette_auto_finitions_id_seq'::text) NOT NULL,
  finition character varying(50) NOT NULL,
  PRIMARY KEY (id_finition)
);

-- Table structure for table galette_auto_models
DROP TABLE IF EXISTS galette_auto_models CASCADE;
CREATE TABLE galette_auto_models (
  id_model integer DEFAULT nextval('galette_auto_models_id_seq'::text) NOT NULL,
  model character varying(50) NOT NULL,
  id_brand integer NOT NULL REFERENCES galette_auto_brands (id_brand) ON DELETE NO ACTION ON UPDATE NO ACTION,
  PRIMARY KEY (id_model)
);

-- Table structure for table galette_auto_states
DROP TABLE IF EXISTS galette_auto_states CASCADE;
CREATE TABLE galette_auto_states (
  id_state integer DEFAULT nextval('galette_auto_states_id_seq'::text) NOT NULL,
  state character varying(50) NOT NULL,
  PRIMARY KEY (id_state)
);

-- Table structure for table galette_auto_transmissions
DROP TABLE IF EXISTS galette_auto_transmissions CASCADE;
CREATE TABLE galette_auto_transmissions (
  id_transmission integer DEFAULT nextval('galette_auto_transmissions_id_seq'::text) NOT NULL,
  transmission character varying(50) NOT NULL,
  PRIMARY KEY (id_transmission)
);

-- Table structure for table galette_auto_cars
DROP TABLE IF EXISTS galette_auto_cars CASCADE;
CREATE TABLE galette_auto_cars (
  id_car integer DEFAULT nextval('galette_auto_cars_id_seq'::text) NOT NULL,
  car_name character varying(50) NOT NULL,
  car_registration varchar(10) NOT NULL,
  car_first_registration_date date NOT NULL,
  car_first_circulation_date date NOT NULL,
  car_mileage integer DEFAULT NULL,
  car_comment text,
  car_creation_date date NOT NULL,
  car_chassis_number character varying(50) DEFAULT NULL,
  car_seats integer DEFAULT NULL,
  car_horsepower integer DEFAULT NULL,
  car_engine_size integer DEFAULT NULL,
  car_fuel integer DEFAULT NULL,
  id_color integer NOT NULL REFERENCES galette_auto_colors (id_color) ON DELETE NO ACTION ON UPDATE NO ACTION,
  id_body integer NOT NULL REFERENCES galette_auto_bodies (id_body) ON DELETE NO ACTION ON UPDATE NO ACTION,
  id_state integer NOT NULL REFERENCES galette_auto_states (id_state) ON DELETE NO ACTION ON UPDATE NO ACTION,
  id_transmission integer NOT NULL REFERENCES galette_auto_transmissions (id_transmission) ON DELETE NO ACTION ON UPDATE NO ACTION,
  id_finition integer NOT NULL REFERENCES galette_auto_finitions (id_finition) ON DELETE NO ACTION ON UPDATE NO ACTION,
  id_model integer NOT NULL REFERENCES galette_auto_models (id_model) ON DELETE NO ACTION ON UPDATE NO ACTION,
  id_adh integer NOT NULL REFERENCES galette_adherents (id_adh) ON DELETE NO ACTION ON UPDATE NO ACTION,
  PRIMARY KEY (id_car)
);

-- Table structure for table galette_auto_history
DROP TABLE IF EXISTS galette_auto_history;
CREATE TABLE galette_auto_history (
  id_car integer NOT NULL REFERENCES galette_auto_cars (id_car) ON DELETE NO ACTION ON UPDATE NO ACTION,
  id_adh integer NOT NULL REFERENCES galette_adherents (id_adh) ON DELETE NO ACTION ON UPDATE NO ACTION,
  history_date timestamp NOT NULL,
  car_registration character varying(10) NOT NULL,
  id_color integer NOT NULL REFERENCES galette_auto_colors (id_color) ON DELETE NO ACTION ON UPDATE NO ACTION,
  id_state integer NOT NULL REFERENCES galette_auto_states (id_state) ON DELETE NO ACTION ON UPDATE NO ACTION,
  PRIMARY KEY (id_car,id_adh,history_date)
);

-- Table structure for table galette_auto_pictures
DROP TABLE IF EXISTS galette_auto_pictures CASCADE;
CREATE TABLE galette_auto_pictures (
  id_car integer NOT NULL REFERENCES galette_auto_cars (id_car) ON DELETE CASCADE ON UPDATE CASCADE,
  picture bytea NOT NULL,
  format character varying(10) NOT NULL DEFAULT '',
  PRIMARY KEY (id_car)
);
