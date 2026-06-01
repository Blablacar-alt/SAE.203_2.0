<?php include __DIR__ . '/includes/nav.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Accueil</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<main>

  <div class="hero">
    <h1 class="hero-title">E–LLUSION<span>.</span></h1>
    <p class="hero-sub">
      Une exposition d'art interactive et multimédia pensée par les étudiant·es
      <strong>MMI1</strong> de l'Université Savoie Mont Blanc.
    </p>
    <div class="hero-buttons">
      <button class="btn-primary" onclick="window.location='inscription.php'">S'inscrire →</button>
      <button class="btn-outline" onclick="window.location='salles.php'">Découvrir l'expo</button>
    </div>
    <div class="hero-meta">
      <span>📅 18 &amp; 19 juin 2026</span>
      <span>📍 IUT de Chambéry — USMB</span>
      <span>✦ 4 salles immersives</span>
    </div>
  </div>

  <div class="explorer-section">
    <div class="explorer-label">Explorer</div>
    <div class="cards">
      <div class="card" onclick="window.location='salles.php'">
        <div class="card-dot"></div>
        <h3>Présentation</h3>
        <p>Découvrez les 4 salles immersives et leurs univers uniques créés par les étudiants MMI1.</p>
      </div>
      <div class="card" onclick="window.location='inscription.php'">
        <div class="card-dot"></div>
        <h3>Participer</h3>
        <p>Inscrivez-vous pour vivre l'expérience E-LLUSION lors de l'exposition des 18 et 19 juin.</p>
      </div>
    </div>
  </div>

  <div class="about-section">
    <h2>Une expérience interactive et multimédia</h2>
    <p>E-LLUSION est une exposition conçue par les étudiants de première année du département MMI (Métiers du Multimédia et de l'Internet) de l'Université Savoie Mont Blanc. À travers 4 salles immersives, découvrez des installations interactives alliant art numérique, technologies multimédia et créativité. Chaque salle propose une expérience unique pensée pour questionner notre rapport au monde numérique et à la perception de la réalité.</p>
  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
