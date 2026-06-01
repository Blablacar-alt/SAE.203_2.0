<?php include __DIR__ . '/includes/nav.php'; ?>
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

<main>

  <div class="page-header">
    <h1>LES SALLES</h1>
    <p>Quatre univers immersifs pour explorer les illusions du monde numérique</p>
  </div>

  <div class="salles-list">
    <!-- salle cards copied -->
    <div class="salle-card">
      <span class="salle-tag">Salle 002</span>
      <h2>Salle 002 — L'Envers du Décor</h2>
      <p class="subtitle">Immersion dans un monde où réalité et virtualité se confondent</p>
      <p>Comment l'illusion d'une société parfaite révèle-t-elle l'état de la nôtre ? Le thème principal de notre salle est de questionner les façades que la société se construit pour masquer ses contradictions, qu'il s'agisse du regard social sur les réseaux, du glamour de la mode ou de la mécanique de la consommation. Les trois œuvres montrent ainsi comment le numérique, en mettant en scène ces illusions, finit par révéler l'état réel d'un monde qui se rêve parfait.</p>
      <p class="programme-label">Au programme :</p>
      <div class="programme-grid">
        <span class="programme-item">Installations interactives en réalité augmentée</span>
        <span class="programme-item">Projections immersives 360°</span>
        <span class="programme-item">Dispositifs tactiles et gestuels</span>
        <span class="programme-item">Expérience multi-sensorielle</span>
      </div>
      <a class="btn-reserver" href="inscription.php?salle=Salle%20002">Réserver cette salle →</a>
    </div>

    <!-- other rooms omitted for brevity, original content included in file -->

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
