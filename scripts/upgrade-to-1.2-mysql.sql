--
-- This file is part of Galette Auto plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Motorbikes, mopeds or trucks have no body, transmission or finition, and
-- may never have been registered: those properties become optional.
ALTER TABLE galette_auto_cars
  MODIFY car_first_registration_date date DEFAULT NULL,
  MODIFY car_first_circulation_date date DEFAULT NULL,
  MODIFY id_color int(10) unsigned DEFAULT NULL,
  MODIFY id_body int(10) unsigned DEFAULT NULL,
  MODIFY id_state int(10) unsigned DEFAULT NULL,
  MODIFY id_transmission int(10) unsigned DEFAULT NULL,
  MODIFY id_finition int(10) unsigned DEFAULT NULL;

ALTER TABLE galette_auto_history
  MODIFY id_color int(10) unsigned DEFAULT NULL,
  MODIFY id_state int(10) unsigned DEFAULT NULL;

-- Vehicle types: existing vehicles are cars
CREATE TABLE galette_auto_types (
  id_type int unsigned NOT NULL AUTO_INCREMENT,
  type varchar(50) NOT NULL,
  PRIMARY KEY (id_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_types (type) VALUES ('Car');

ALTER TABLE galette_auto_cars ADD id_type int unsigned DEFAULT NULL;
UPDATE galette_auto_cars SET id_type = (SELECT MIN(id_type) FROM galette_auto_types);
ALTER TABLE galette_auto_cars
  MODIFY id_type int unsigned NOT NULL,
  ADD CONSTRAINT galette_auto_cars_id_type_fkey FOREIGN KEY (id_type)
    REFERENCES galette_auto_types (id_type) ON DELETE RESTRICT ON UPDATE CASCADE;
