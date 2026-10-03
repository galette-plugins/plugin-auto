--
-- This file is part of Galette Auto plugin (https://galette.eu).
-- SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
-- SPDX-License-Identifier: GPL-3.0-or-later
--

-- Same foreign keys as MySQL: a member removal removes their vehicles
ALTER TABLE galette_auto_models
  DROP CONSTRAINT IF EXISTS galette_auto_models_id_brand_fkey,
  ADD CONSTRAINT galette_auto_models_id_brand_fkey FOREIGN KEY (id_brand)
    REFERENCES galette_auto_brands (id_brand) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE galette_auto_cars
  DROP CONSTRAINT IF EXISTS galette_auto_cars_id_color_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_cars_id_body_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_cars_id_state_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_cars_id_transmission_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_cars_id_finition_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_cars_id_model_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_cars_id_adh_fkey,
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
  DROP CONSTRAINT IF EXISTS galette_auto_history_id_car_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_history_id_adh_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_history_id_color_fkey,
  DROP CONSTRAINT IF EXISTS galette_auto_history_id_state_fkey,
  ADD CONSTRAINT galette_auto_history_id_car_fkey FOREIGN KEY (id_car)
    REFERENCES galette_auto_cars (id_car) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_history_id_adh_fkey FOREIGN KEY (id_adh)
    REFERENCES galette_adherents (id_adh) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_history_id_color_fkey FOREIGN KEY (id_color)
    REFERENCES galette_auto_colors (id_color) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT galette_auto_history_id_state_fkey FOREIGN KEY (id_state)
    REFERENCES galette_auto_states (id_state) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE galette_auto_pictures
  DROP CONSTRAINT IF EXISTS galette_auto_pictures_id_car_fkey,
  ADD CONSTRAINT galette_auto_pictures_id_car_fkey FOREIGN KEY (id_car)
    REFERENCES galette_auto_cars (id_car) ON DELETE CASCADE ON UPDATE CASCADE;

-- Foreign keys are not indexed by PostgreSQL
CREATE INDEX IF NOT EXISTS galette_auto_models_id_brand_idx ON galette_auto_models (id_brand);
CREATE INDEX IF NOT EXISTS galette_auto_cars_id_model_idx ON galette_auto_cars (id_model);
CREATE INDEX IF NOT EXISTS galette_auto_cars_id_adh_idx ON galette_auto_cars (id_adh);
CREATE INDEX IF NOT EXISTS galette_auto_history_id_adh_idx ON galette_auto_history (id_adh);
