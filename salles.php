<?php
// Dynamic salles page
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sae203;charset=utf8', 'root', 'local', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erreur BDD : ' . $e->getMessage());
}

$salles = $pdo->query('SELECT id_salle, numero, nom_thematique FROM salle ORDER BY numero')->fetchAll();

// Pour chaque salle, récupérer le prochain créneau disponible
$creneaux_map = [];
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

<main>

  <div class="page-header">
    <h1>LES SALLES</h1>
    <p>Quatre univers immersifs pour explorer les illusions du monde numérique</p>
  </div>

  <div class="salles-list">
    <?php if (empty($salles)): ?>
      <p>Aucune salle trouvée en base de données.</p>
    <?php else: ?>
      <?php foreach ($salles as $s): ?>
        <div class="salle-card">
          <span class="salle-tag">Salle <?= htmlspecialchars($s['numero']) ?></span>
          <h2>Salle <?= htmlspecialchars($s['numero']) ?> — <?= htmlspecialchars($s['nom_thematique']) ?></h2>
          <p class="subtitle">Présentation de la salle <?= htmlspecialchars($s['numero']) ?></p>
          <p>Découvrez l'univers de la salle « <?= htmlspecialchars($s['nom_thematique']) ?> ».</p>
          <p class="programme-label">Au programme :</p>
          <div class="programme-grid">
            <span class="programme-item">Installations interactives</span>
            <span class="programme-item">Projections</span>
            <span class="programme-item">Expériences sensorielles</span>
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
    <?php endif; ?>
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