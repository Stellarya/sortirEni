-- fichier pour insérer des données pour rendre 
-- la page de sortie fonctionnelle 

INSERT INTO `etat` (`id`, `libelle`) VALUES
(1, 'Créée'),
(2, 'Ouverte'),
(3, 'Clôturée'),
(4, 'Activité en cours'),
(5, 'Passée'),
(6, 'Annulée');

INSERT INTO `lieu` (`id`, `ville_id`, `nom`, `rue`, `latitude`, `longitude`) VALUES
(1, 1, 'Parc du Thabor', 'Place Saint-Mélaine', 48.1141045, -1.6719802);

INSERT INTO `participant` (`id`, `est_rattache_a_id`, `nom`, `prenom`, `telephone`, `administrateur`, `actif`) VALUES
(1, 1, 'Derrien', 'Ronan', '0606060606', 1, 1),
(2, 1, 'Hostin', 'Erwan', '0606060606', 1, 1),
(3, 1, 'Evanno', 'Marie', '0606060606', 1, 1);

INSERT INTO `site` (`id`, `nom`) VALUES
(1, 'Chartres de Bretagne'),
(2, 'La Roche sur Yon'),
(3, 'Saint Herblain');

INSERT INTO `sortie` (`id`, `lieu_id`, `etat_id`, `site_id`, `organisateur_id`, `nom`, `date_heure_debut`, `duree`, `date_limite_inscription`, `nb_inscription_max`, `infos_sortie`, `url_photo`) VALUES
(1, 1, NULL, 1, 1, 'test', '2016-01-01 00:00:00', 65, '2016-01-01 00:00:00', 65, 'test', NULL),
(2, 1, 2, 1, 1, 'sfqdsq', '2018-01-01 00:00:00', 126, '2019-01-01 00:00:00', 123, 'qsd1532', NULL),
(3, 1, 1, 1, 1, 'qsdqsdq', '2021-02-01 02:04:00', 133, '2018-02-01 00:00:00', 56, 'qsd', NULL),
(4, 1, 2, 1, 1, 'Ceci est un test d\'affichage', '2021-05-28 00:00:00', 132, '2021-05-01 00:00:00', 10, 'Test pour savoir si l\'affichage fonctionne comme prévu avec plus de 50 caractères', NULL),
(5, 1, 1, 1, 1, 'TEesdfs', '2016-01-01 00:00:00', 65, '2016-01-01 00:00:00', 56, 'fqdqsd', NULL);

INSERT INTO `user` (`id`, `participant_id`, `username`, `password`, `email`) VALUES
(1, 1, 'admin', 'admin', 'admin@gmail.com');

INSERT INTO `ville` (`id`, `nom`, `code_postal`) VALUES
(1, 'Rennes', 35000),
(2, 'Brest', 29200),
(3, 'Vitré', 35500),
(4, 'Nantes', 44000),
(5, 'Niort', 79000),
(6, 'Quimper', 29000);