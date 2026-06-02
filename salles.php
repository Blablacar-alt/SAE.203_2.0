<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Les salles</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
  
</head>
<body>

<nav>
  <a class="nav-logo" href="index.php">E-LLUSION</a>
  <ul class="nav-links">
    <li><a href="index.php">Accueil</a></li>
    <li><a href="salles.html">Les salles</a></li>
    <li><a href="inscription.php">S'inscrire</a></li>
    <li><a href="contact.html">Contact</a></li>
  </ul>
</nav>

<div class="salles-page">

  <div class="page-header">
    <div class="page-header-label">Exposition · 18 & 19 juin 2026</div>
    <h1>LES<br>SALLES<span>.</span></h1>
    <p>Quatre univers immersifs pour explorer les illusions du monde numérique</p>
  </div>

  <div class="dot-divider"></div>

  <div class="salles-list">

    <div class="salle-card">
      <div class="salle-numero" data-num="002">002</div>
      <span class="salle-tag">Salle 002</span>
      <h2>L'Envers du Décor</h2>
      <p class="subtitle">Immersion dans un monde où réalité et virtualité se confondent</p>
      <p>Comment l'illusion d'une société parfaite révèle-t-elle l'état de la nôtre ? Questionnement des façades que la société se construit pour masquer ses contradictions — réseaux, mode, consommation. Le numérique, en mettant en scène ces illusions, révèle l'état réel d'un monde qui se rêve parfait.</p>
      <p class="programme-label">// Au programme</p>
      <div class="programme-grid">
        <span class="programme-item">Installations interactives en réalité augmentée</span>
        <span class="programme-item">Projections immersives 360°</span>
        <span class="programme-item">Dispositifs tactiles et gestuels</span>
        <span class="programme-item">Expérience multi-sensorielle</span>
      </div>
      <a class="btn-reserver" href="inscription.php?salle=Salle%20002">Réserver cette salle </a>
    </div>

    <div class="salle-card">
      <div class="salle-numero" data-num="001">001</div>
      <span class="salle-tag">Salle 001</span>
      <h2>Horizon</h2>
      <p class="subtitle">Exploration de nos doubles virtuels et de notre présence en ligne</p>
      <p>Comment les objets numériques altèrent-ils notre perception du réel ? À travers trois œuvres interactives, exploration de la manière dont les technologies transforment notre rapport au corps, à l'image et à l'environnement.</p>
      <p class="programme-label">// Au programme</p>
      <div class="programme-grid">
        <span class="programme-item">Installations miroir et projection</span>
        <span class="programme-item">Analyse des données personnelles</span>
        <span class="programme-item">Visualisation de présence numérique</span>
        <span class="programme-item">Œuvres interactives sur les réseaux sociaux</span>
      </div>
      <a class="btn-reserver" href="inscription.php?salle=Salle%20001">Réserver cette salle </a>
    </div>

    <div class="salle-card">
      <div class="salle-numero" data-num="005">005</div>
      <span class="salle-tag">Salle 005</span>
      <h2>La pépinière</h2>
      <p class="subtitle">Jouer avec les sens et remettre en question ce que nous voyons</p>
      <p>Comment les objets numériques altèrent-ils notre perception du réel ? À travers trois œuvres interactives, exploration de la manière dont les technologies influencent notre perception, en transformant notre rapport au corps et à l'image.</p>
      <p class="programme-label">// Au programme</p>
      <div class="programme-grid">
        <span class="programme-item">Illusions d'optique interactives</span>
        <span class="programme-item">Jeux de lumières programmables</span>
        <span class="programme-item">Installations visuelles en mouvement</span>
        <span class="programme-item">Expériences de distorsion sensorielle</span>
      </div>
      <a class="btn-reserver" href="inscription.php?salle=Salle%20005">Réserver cette salle </a>
    </div>

    <div class="salle-card">
      <div class="salle-numero" data-num="021">021</div>
      <span class="salle-tag">Salle 021</span>
      <h2>Societ-e</h2>
      <p class="subtitle">Rencontrer vos œuvres influencées par la société</p>
      <p>Venez voir le résultat de la conformité de l'entreprise sur nous-mêmes. Des œuvres conçues pour illustrer comment notre univers numérique forge de nouvelles réalités — identité, reconnaissance sociale, regard d'autrui.</p>
      <p class="programme-label">// Au programme</p>
      <div class="programme-grid">
        <span class="programme-item">Interaction immersive de notre avatar</span>
        <span class="programme-item">Installations interactives collaboratives</span>
        <span class="programme-item">Création de notre petit nous</span>
        <span class="programme-item">Expérience de co-création visage déformé</span>
      </div>
      <a class="btn-reserver" href="inscription.php?salle=Salle%20021">Réserver cette salle </a>
    </div>

  </div>

  <div class="dot-divider"></div>

  <div class="cta-band">
    <h2>Prêt à vivre l'expérience ?</h2>
    <p>Inscrivez-vous dès maintenant pour réserver votre créneau</p>
    <a class="btn-primary" href="inscription.php">S'inscrire à l'exposition</a>
  </div>

</div>

<footer>
  <span>© 2026 E-LLUSION — MMI1 Université Savoie Mont Blanc</span>
  <div style="display:flex;gap:1.5rem;align-items:center">
    <a href="https://instagram.com/mmichambery" target="_blank">@mmichambery</a>
    <a href="https://www.iut-chy.univ-smb.fr/" target="_blank">Site MMI</a>
    <a href="admin.php" style="font-size:.72rem;color:#bbb;text-decoration:none;font-family:'Space Mono',monospace;">⚙ Admin</a>
  </div>
</footer>

</body>
</html>