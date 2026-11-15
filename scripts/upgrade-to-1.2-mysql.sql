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

-- Fields each vehicle type uses: hidden (0), optional (1) or required (2).
-- Cars keep the fields that were always required, and the required fields
-- preferences, which are removed; their defaults apply when never stored.
CREATE TABLE galette_auto_types_fields (
  id_type int unsigned NOT NULL,
  field varchar(30) NOT NULL,
  mode smallint NOT NULL,
  PRIMARY KEY (id_type, field),
  CONSTRAINT galette_auto_types_fields_id_type_fkey FOREIGN KEY (id_type)
    REFERENCES galette_auto_types (id_type) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO galette_auto_types_fields (id_type, field, mode) VALUES
  ((SELECT MIN(id_type) FROM galette_auto_types), 'first_registration_date', 2),
  ((SELECT MIN(id_type) FROM galette_auto_types), 'first_circulation_date', 2),
  ((SELECT MIN(id_type) FROM galette_auto_types), 'color', 2),
  ((SELECT MIN(id_type) FROM galette_auto_types), 'state', 2),
  ((SELECT MIN(id_type) FROM galette_auto_types), 'body', 2),
  ((SELECT MIN(id_type) FROM galette_auto_types), 'transmission', 2),
  ((SELECT MIN(id_type) FROM galette_auto_types), 'finition', 2);

INSERT INTO galette_auto_types_fields (id_type, field, mode)
SELECT t.id_type, f.field,
  CASE
    WHEN p.val_pref IS NULL THEN f.dflt
    WHEN p.val_pref IN ('1', 'true') THEN 2
    ELSE 1
  END
FROM galette_auto_types t
CROSS JOIN (
  SELECT 'name' AS field, 2 AS dflt UNION ALL
  SELECT 'registration', 2 UNION ALL
  SELECT 'fuel', 2 UNION ALL
  SELECT 'mileage', 1 UNION ALL
  SELECT 'seats', 1 UNION ALL
  SELECT 'horsepower', 1 UNION ALL
  SELECT 'engine_size', 1 UNION ALL
  SELECT 'chassis_number', 1 UNION ALL
  SELECT 'comment', 1
) f
LEFT JOIN galette_preferences p ON p.nom_pref = CONCAT('pref_auto_required_', f.field);

DELETE FROM galette_preferences WHERE nom_pref LIKE 'pref\_auto\_required\_%';
