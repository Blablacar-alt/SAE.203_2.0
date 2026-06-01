-- Sample seed data for SAE.203_2.0
-- Insert two dates, four salles and a few créneaux with places

-- Dates
INSERT INTO date_expo (`date`) VALUES
  ('2026-06-18'),
  ('2026-06-19');

-- Salles
INSERT INTO salle (`numero`, `nom_thematique`) VALUES
  ('001', 'Horizon'),
  ('002', 'L\'Envers du Décor'),
  ('005', 'La pépinière'),
  ('021', 'Societ-e');

-- Créneaux (example times; ajustez id_salle/id_date selon auto-increment)
-- If your tables use AUTO_INCREMENT, these statements will assign ids automatically.
-- Find the actual ids in your DB (SELECT * FROM salle; SELECT * FROM date_expo;) and adapt if necessary.

-- For salle 001 on 2026-06-18
INSERT INTO creneau (id_salle, id_date, heure_debut, heure_fin, places_restante) VALUES
  ((SELECT id_salle FROM salle WHERE numero='001' LIMIT 1), (SELECT id_date FROM date_expo WHERE `date`='2026-06-18' LIMIT 1), '15:00:00', '15:30:00', 12),
  ((SELECT id_salle FROM salle WHERE numero='001' LIMIT 1), (SELECT id_date FROM date_expo WHERE `date`='2026-06-18' LIMIT 1), '16:00:00', '16:30:00', 12);

-- For salle 002 on 2026-06-18
INSERT INTO creneau (id_salle, id_date, heure_debut, heure_fin, places_restante) VALUES
  ((SELECT id_salle FROM salle WHERE numero='002' LIMIT 1), (SELECT id_date FROM date_expo WHERE `date`='2026-06-18' LIMIT 1), '15:30:00', '16:00:00', 10),
  ((SELECT id_salle FROM salle WHERE numero='002' LIMIT 1), (SELECT id_date FROM date_expo WHERE `date`='2026-06-18' LIMIT 1), '17:00:00', '17:30:00', 8);

-- For salle 005 on 2026-06-19
INSERT INTO creneau (id_salle, id_date, heure_debut, heure_fin, places_restante) VALUES
  ((SELECT id_salle FROM salle WHERE numero='005' LIMIT 1), (SELECT id_date FROM date_expo WHERE `date`='2026-06-19' LIMIT 1), '09:30:00', '10:00:00', 12),
  ((SELECT id_salle FROM salle WHERE numero='005' LIMIT 1), (SELECT id_date FROM date_expo WHERE `date`='2026-06-19' LIMIT 1), '10:30:00', '11:00:00', 12);

-- For salle 021 on 2026-06-19
INSERT INTO creneau (id_salle, id_date, heure_debut, heure_fin, places_restante) VALUES
  ((SELECT id_salle FROM salle WHERE numero='021' LIMIT 1), (SELECT id_date FROM date_expo WHERE `date`='2026-06-19' LIMIT 1), '09:30:00', '10:00:00', 6),
  ((SELECT id_salle FROM salle WHERE numero='021' LIMIT 1), (SELECT id_date FROM date_expo WHERE `date`='2026-06-19' LIMIT 1), '10:30:00', '11:00:00', 6);

-- End of seed
