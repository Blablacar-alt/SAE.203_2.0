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
<?php include __DIR__ . '/includes/nav.php'; ?>
<main class="home-page">
  <section class="hero-section">
    <div class="glitch-container">
      <div class="tech-tag">[ EXPOSITION MULTIMÉDIA ]</div>
      <h1 class="hero-title" data-text="E-LLUSION">E-LLUSION</h1>
      <p class="hero-tagline">Quand le virtuel redéfinit la perception du réel.</p>
    </div>
    
    <div class="hero-buttons">
      <a href="salles.php" class="btn-discover">Découvrir l'expo</a>
      <a href="inscription.php" class="btn-ticket">Réserver un billet</a>
    </div>
    
    <div class="vertical-axis"></div>
  </section>

  <section class="info-banner">
    <div class="banner-grid">
      <div class="info-block">
        <span class="info-label">DATE</span>
        <span class="info-value">18.06.2026</span>
      </div>
      <div class="info-block">
        <span class="info-label">HORAIRES</span>
        <span class="info-value">13H00 ─ 21H30</span>
      </div>
      <div class="info-block">
        <span class="info-label">LIEU</span>
        <span class="info-value">IUT CHAMBÉRY</span>
      </div>
      <div class="info-block countdown-block">
        <span class="info-label">LANCEMENT DANS</span>
        <span class="info-value" id="countdown">--J --H --M</span>
      </div>
    </div>
  </section>

  <section class="concept-section">
    <div class="concept-container">
      <div class="concept-visual">
        <div class="target-box">
          <div class="corner tl"></div>
          <div class="corner tr"></div>
          <div class="corner bl"></div>
          <div class="corner br"></div>
          <span class="visual-text">LIVE_DAT_01</span>
        </div>
      </div>
      
      <div class="concept-content">
        <h2 class="section-title">Le Concept</h2>
        <p class="concept-text">
          Plongez au cœur d’une expérience sensorielle inédite créée par les étudiants MMI de Chambéry. <strong>E-LLUSION</strong> fusionne l'art numérique, le sound design et le développement interactif pour briser les frontières de votre reality.
        </p>
        <p class="concept-text">
          À travers différents espaces thématiques, explorez des installations immersives où chaque pixel réagit à votre présence.
        </p>
      </div>
    </div>
  </section>
</main>

<script>
  const targetDate = new Date('June 18, 2026 13:00:00').getTime();
  
  function updateCountdown() {
    const now = new Date().getTime();
    const difference = targetDate - now;
    
    if (difference < 0) {
      document.getElementById('countdown').innerText = "PORTES OUVERTES";
      return;
    }
    
    const days = Math.floor(difference / (1000 * 60 * 60 * 24));
    const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    
    document.getElementById('countdown').innerText = `${days}J ${hours}H ${minutes}M`;
  }
  
  setInterval(updateCountdown, 60000);
  updateCountdown();
</script>


<?php include __DIR__ . '/includes/footer.php'; ?>

</section>


</body>
</html>