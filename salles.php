<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Les salles</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
  <style>
    /* ── Variables affiche ── */
    :root {
      --cyan: #00e5cc;
      --cyan-dark: #00b8a0;
      --cyan-light: #e0faf7;
      --ink: #0a0a0a;
      --ink-soft: #1a1a2e;
      --white: #ffffff;
      --off: #f5f5f0;
    }

    /* ── Page salles ── */
    body { background: var(--white); }

    .salles-page { position: relative; min-height: 100vh; overflow-x: hidden; }

    /* Fond avec motif pixel */
    .salles-page::before {
      content: '';
      position: fixed; inset: 0; z-index: 0;
      background-image:
        radial-gradient(circle at 80% 10%, rgba(0,229,204,.07) 0%, transparent 50%),
        radial-gradient(circle at 10% 90%, rgba(0,229,204,.05) 0%, transparent 40%);
      pointer-events: none;
    }

    /* ── Header ── */
    .page-header {
      position: relative; z-index: 1;
      text-align: left;
      padding: 5rem 3rem 2rem;
      max-width: 960px; margin: 0 auto;
      border-bottom: 1px dashed rgba(0,229,204,.3);
    }
    .page-header-label {
      font-family: 'Space Mono', monospace;
      font-size: .7rem; font-weight: 700;
      letter-spacing: .2em; text-transform: uppercase;
      color: var(--cyan-dark);
      display: flex; align-items: center; gap: .5rem;
      margin-bottom: 1rem;
    }
    .page-header-label::before {
      content: '';
      display: inline-block; width: 8px; height: 8px;
      background: var(--cyan); border-radius: 50%;
    }
    .page-header h1 {
      font-family: 'Space Mono', monospace;
      font-size: clamp(2.2rem, 6vw, 4rem);
      font-weight: 700; line-height: 1;
      color: var(--ink);
      letter-spacing: -.03em;
      margin-bottom: .8rem;
    }
    .page-header h1 span { color: var(--cyan); }
    .page-header p {
      font-size: 1rem; color: #666;
      max-width: 500px; line-height: 1.6;
    }

    /* ── Grid salles ── */
    .salles-list {
      position: relative; z-index: 1;
      padding: 3rem;
      max-width: 960px; margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
      gap: 1.5rem;
    }

    /* ── Carte salle style affiche ── */
    .salle-card {
      background: var(--white);
      border: 1px solid #e0e0e0;
      border-radius: 0;
      padding: 2rem;
      position: relative;
      transition: border-color .2s, box-shadow .2s;
      /* Effet crochet angulaire inspiré de l'affiche */
      clip-path: polygon(0 0, calc(100% - 16px) 0, 100% 16px, 100% 100%, 16px 100%, 0 calc(100% - 16px));
    }
    .salle-card::before {
      content: '';
      position: absolute; top: 0; left: 0;
      width: 28px; height: 28px;
      border-top: 2px solid var(--cyan);
      border-left: 2px solid var(--cyan);
    }
    .salle-card::after {
      content: '';
      position: absolute; bottom: 0; right: 0;
      width: 28px; height: 28px;
      border-bottom: 2px solid var(--cyan);
      border-right: 2px solid var(--cyan);
    }
    .salle-card:hover {
      border-color: var(--cyan);
      box-shadow: 4px 4px 0 var(--cyan-light);
    }

    /* Numéro de salle style glitch */
    .salle-numero {
      font-family: 'Space Mono', monospace;
      font-size: 3.5rem; font-weight: 700;
      color: var(--cyan-light);
      line-height: 1;
      margin-bottom: .5rem;
      position: relative;
      display: inline-block;
      user-select: none;
    }
    .salle-numero::after {
      content: attr(data-num);
      position: absolute; top: 2px; left: 2px;
      color: rgba(0,229,204,.2);
      z-index: -1;
    }
    .salle-tag {
      font-family: 'Space Mono', monospace;
      font-size: .65rem; font-weight: 700;
      letter-spacing: .12em; text-transform: uppercase;
      color: var(--cyan-dark);
      border: 1px solid var(--cyan);
      padding: .2rem .6rem;
      border-radius: 0;
      margin-bottom: 1rem;
      display: inline-block;
    }
    .salle-card h2 {
      font-family: 'Space Mono', monospace;
      font-size: 1rem; font-weight: 700;
      color: var(--ink);
      margin-bottom: .35rem;
      line-height: 1.4;
    }
    .salle-card .subtitle {
      font-size: .82rem; font-style: italic;
      color: #888; margin-bottom: 1rem;
      padding-left: .8rem;
      border-left: 2px solid var(--cyan);
    }
    .salle-card > p {
      font-size: .88rem; line-height: 1.75; color: #444;
      margin-bottom: 1.5rem;
    }
    .programme-label {
      font-family: 'Space Mono', monospace;
      font-size: .68rem; font-weight: 700;
      letter-spacing: .12em; text-transform: uppercase;
      color: #999; margin-bottom: .75rem;
    }
    .programme-grid {
      display: flex; flex-direction: column; gap: .35rem;
      margin-bottom: 1.5rem;
    }
    .programme-item {
      display: flex; align-items: flex-start; gap: .6rem;
      font-size: .82rem; color: #333; line-height: 1.4;
    }
    .programme-item::before {
      content: '›';
      font-family: 'Space Mono', monospace;
      color: var(--cyan-dark);
      font-weight: 700; flex-shrink: 0;
      margin-top: .05rem;
    }

    /* Bouton réserver style affiche */
    .btn-reserver {
      display: inline-flex; align-items: center; gap: .5rem;
      font-family: 'Space Mono', monospace;
      font-size: .75rem; font-weight: 700;
      letter-spacing: .06em; text-transform: uppercase;
      color: var(--ink);
      text-decoration: none;
      border-bottom: 2px solid var(--cyan);
      padding-bottom: .2rem;
      transition: color .2s, border-color .2s;
    }
    .btn-reserver::after { content: '→'; }
    .btn-reserver:hover { color: var(--cyan-dark); border-color: var(--ink); }

    /* ── CTA band ── */
    .cta-band {
      position: relative; z-index: 1;
      background: var(--ink);
      text-align: center;
      padding: 5rem 2rem;
      margin-top: 0;
      overflow: hidden;
    }
    .cta-band::before {
      content: 'E-LLUSION';
      position: absolute;
      font-family: 'Space Mono', monospace;
      font-size: 10rem; font-weight: 700;
      color: rgba(255,255,255,.03);
      top: 50%; left: 50%;
      transform: translate(-50%,-50%);
      white-space: nowrap; pointer-events: none;
    }
    .cta-band h2 {
      font-family: 'Space Mono', monospace;
      font-size: 1.6rem; font-weight: 700;
      color: var(--white); margin-bottom: .5rem;
    }
    .cta-band p { font-size: .9rem; color: #888; margin-bottom: 2rem; }
    .cta-band .btn-primary {
      display: inline-block;
      background: var(--cyan);
      color: var(--ink);
      font-family: 'Space Mono', monospace;
      font-size: .8rem; font-weight: 700;
      letter-spacing: .06em; text-transform: uppercase;
      padding: .85rem 2rem;
      text-decoration: none;
      border-radius: 0;
      border: 2px solid var(--cyan);
      transition: background .2s, color .2s;
      clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 10px, 100% 100%, 10px 100%, 0 calc(100% - 10px));
    }
    .cta-band .btn-primary:hover {
      background: transparent;
      color: var(--cyan);
    }

    /* Ligne décorative pointillée entre les sections */
    .dot-divider {
      position: relative; z-index: 1;
      height: 1px;
      background: repeating-linear-gradient(to right, var(--cyan) 0, var(--cyan) 4px, transparent 4px, transparent 12px);
      margin: 0 3rem;
      opacity: .25;
    }
  </style>
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