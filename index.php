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

 <section id="accueil" class="active">

  <div class="hero">
    <div class="carousel-container">
      <div class="carousel-track">
        <img src="C:\xampp\htdocs\SAE.203_2.0\Image Caroussel\Image 1.jpg" alt="Image 1">
        <img src="C:\xampp\htdocs\SAE.203_2.0\Image Caroussel\Image 2.jpg" alt="Image 2">
        <img src="C:\xampp\htdocs\SAE.203_2.0\Image Caroussel\Image 3.webp" alt="Image 3">
        <img src="C:\xampp\htdocs\SAE.203_2.0\Image Caroussel\Image 4.jpg" alt="Image 4">
        <img src="C:\xampp\htdocs\SAE.203_2.0\Image Caroussel\Image 5.jpg" alt="Image 5">
        <img src="C:\xampp\htdocs\SAE.203_2.0\Image Caroussel\Image 6.webp" alt="Image 6">
      </div>
    </div>

    <div class="hero-content">
      <h1 class="hero-title">E–LLUSION<span>.</span></h1>
      <p class="hero-sub">
        Une exposition d'art interactive et multimédia pensée par les étudiant·es
        <strong>MMI1</strong> de l'Université Savoie Mont Blanc.
      </p>
      <div class="hero-buttons">
        <a href="inscription.php" class="btn-primary">S'inscrire →</a>
        <a href="salles.php" class="btn-outline">Découvrir l'expo</a>
      </div>
      <div class="hero-meta">
        <span>📅 18 &amp; 19 juin 2026</span>
        <span>📍 IUT de Chambéry — USMB</span>
        <span>✦ 4 salles immersives</span>
      </div>
    </div>
  </div>

  <div class="explorer-section">
    <div class="explorer-label">Explorer</div>
    <div class="cards">
      <a href="salles.php" class="card">
        <div class="card-dot"></div>
        <h3>Présentation</h3>
        <p>Découvrez les 4 salles immersives et leurs univers uniques créés par les étudiants MMI1.</p>
      </a>
      <a href="inscription.php" class="card">
        <div class="card-dot"></div>
        <h3>Participer</h3>
        <p>Inscrivez-vous pour vivre l'expérience E-LLUSION lors de l'exposition des 18 et 19 juin.</p>
      </a>
    </div>
  </div>

  <div class="about-section">
    <h2>Une expérience interactive et multimédia</h2>
    <p>E-LLUSION est une exposition conçue par les étudiants de première année du département MMI (Métiers du Multimédia et de l'Internet) de l'Université Savoie Mont Blanc. À travers 4 salles immersives, découvrez des installations interactives alliant art numérique, technologies multimédia et créativité. Chaque salle propose une expérience unique pensée pour questionner notre rapport au monde numérique et à la perception de la réalité.</p>
  </div>

</section>

<footer>
  <span>© 2026 E-LLUSION — MMI1 Université Savoie Mont Blanc</span>
  <div style="display:flex;gap:1.5rem">
    <a href="https://instagram.com/mmi_annecy" target="_blank">@mmichambery</a>
    <a href="https://www.mmi-annecy.fr" target="_blank">Site MMI</a>
  </div>
</footer>

</body>
</html>