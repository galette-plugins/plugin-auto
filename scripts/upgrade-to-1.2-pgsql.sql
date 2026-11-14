--
-- This file is part of Galette Auto plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Motorbikes, mopeds or trucks have no body, transmission or finition, and
-- may never have been registered: those properties become optional.
ALTER TABLE galette_auto_cars
  ALTER COLUMN car_first_registration_date DROP NOT NULL,
  ALTER COLUMN car_first_circulation_date DROP NOT NULL,
  ALTER COLUMN id_color DROP NOT NULL,
  ALTER COLUMN id_body DROP NOT NULL,
  ALTER COLUMN id_state DROP NOT NULL,
  ALTER COLUMN id_transmission DROP NOT NULL,
  ALTER COLUMN id_finition DROP NOT NULL;

ALTER TABLE galette_auto_history
  ALTER COLUMN id_color DROP NOT NULL,
  ALTER COLUMN id_state DROP NOT NULL;
