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
