<?php
// Connexion BDD (pour les créneaux uniquement)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sae203;charset=utf8', 'root', 'local', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erreur BDD : ' . $e->getMessage());
}

// ── Salles définies en dur ───────────────────────────────────────
$salles = [
    ['id_salle' => 1, 'numero' => '001', 'nom_thematique' => 'Les Miroirs du Réel'],
    ['id_salle' => 2, 'numero' => '002', 'nom_thematique' => "L'Envers du Décor"],
    ['id_salle' => 3, 'numero' => '005', 'nom_thematique' => 'Données & Fantômes'],
    ['id_salle' => 4, 'numero' => '021', 'nom_thematique' => 'Le Théâtre des Algorithmes'],
];

// Descriptions détaillées par salle
$descriptions = [
    '001' => "Plongez dans un espace où la frontière entre monde réel et monde numérique se brouille. Des installations explorent comment la technologie redéfinit notre perception du réel.",
    '002' => "Comment l'illusion d'une société parfaite révèle-t-elle l'état de la nôtre ? Cette salle questionne les façades que la société se construit pour masquer ses contradictions, qu'il s'agisse du regard social sur les réseaux, du glamour de la mode ou de la mécanique de la consommation.",
    '005' => "Vos données racontent une histoire — mais laquelle ? Cette salle explore la collecte, le traitement et la marchandisation des données personnelles à travers des œuvres interactives.",
    '021' => "Découvrez les coulisses des algorithmes qui gouvernent nos vies : recommandations, filtres, biais. Une plongée critique et visuelle dans l'intelligence artificielle.",
];

$programmes = [
    '001' => ['Réalité augmentée', 'Installations miroirs', 'Expériences immersives'],
    '002' => ['Installations interactives', 'Projections immersives 360°', 'Dispositifs tactiles', 'Expérience multi-sensorielle'],
    '005' => ['Visualisation de données', 'Œuvres génératives', 'Ateliers participatifs'],
    '021' => ['Démonstrations IA', 'Visualisations algorithmiques', 'Débats interactifs'],
];

// Prochain créneau disponible par id_salle
$creneaux_map = [];
try {
    $stmt = $pdo->query(
        "SELECT c.id_creneau, c.id_salle, c.heure_debut, c.places_restante, d.date
         FROM creneau c
         JOIN date_expo d ON c.id_date = d.id_date
         WHERE c.places_restante > 0
         ORDER BY c.id_salle, d.date, c.heure_debut"
    );
    foreach ($stmt->fetchAll() as $r) {
        if (!isset($creneaux_map[$r['id_salle']])) {
            $creneaux_map[$r['id_salle']] = $r;
        }
    }
} catch (Exception $e) {
    // Si les créneaux ne sont pas encore en BDD, on ignore
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Les salles</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="salles-page">

  <div class="page-header">
    <h1>LES SALLES</h1>
    <p>Quatre univers immersifs pour explorer les illusions du monde numérique</p>
  </div>

  <div class="salles-list">
    <?php foreach ($salles as $s): ?>
      <div class="salle-card">
        <span class="salle-tag">Salle <?= $s['numero'] ?></span>
        <h2>Salle <?= $s['numero'] ?> — <?= htmlspecialchars($s['nom_thematique']) ?></h2>
        <p class="subtitle">Univers immersif · Exposition E-LLUSION 2026</p>
        <p><?= htmlspecialchars($descriptions[$s['numero']] ?? '') ?></p>
        <p class="programme-label">Au programme :</p>
        <div class="programme-grid">
          <?php foreach (($programmes[$s['numero']] ?? []) as $item): ?>
            <span class="programme-item"><?= htmlspecialchars($item) ?></span>
          <?php endforeach; ?>
        </div>
        <?php if (isset($creneaux_map[$s['id_salle']])): $c = $creneaux_map[$s['id_salle']]; ?>
          <a class="btn-reserver" href="inscription.php?creneau=<?= $c['id_creneau'] ?>">
            Réserver — <?= substr($c['heure_debut'], 0, 5) ?> (<?= $c['places_restante'] ?> pl.)
          </a>
        <?php else: ?>
          <a class="btn-reserver" href="inscription.php?salle=<?= urlencode('Salle ' . $s['numero']) ?>">
            Réserver cette salle →
          </a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="cta-band">
    <h2>Prêt à vivre l'expérience ?</h2>
    <p>Inscrivez-vous dès maintenant pour réserver votre créneau</p>
    <a class="btn-primary" href="inscription.php">S'inscrire à l'exposition</a>
  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>