<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ClipSplit — Découpe & publie sur TikTok</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header><div class="logo">✂️ Clip<span>Split</span></div><div id="account"></div></header>
<main>
  <p class="sub">Découpe une vidéo en plusieurs parties et publie-les sur TikTok.</p>

  <section class="card">
    <h2><i>1</i> Source</h2>
    <div class="tabs"><button class="tab on" data-t="yt">🔗 Lien YouTube</button><button class="tab" data-t="file">📁 Fichier</button></div>
    <div id="pane-yt"><input type="url" id="url" placeholder="https://www.youtube.com/watch?v=..."></div>
    <div id="pane-file" hidden>
      <div class="drop" id="drop"><span id="dropTxt">Glisse ta vidéo ici ou clique pour choisir</span><input type="file" id="video" accept="video/*" hidden></div>
    </div>
  </section>

  <section class="card">
    <h2><i>2</i> Découpage</h2>
    <div class="tabs"><button class="tab on" data-m="count">Nombre de parties</button><button class="tab" data-m="duration">Durée par partie</button></div>
    <div id="m-count" class="rng"><input type="range" id="parts" min="2" max="30" value="3"><b id="partsV">3 parties</b></div>
    <div id="m-duration" class="rng" hidden><input type="range" id="secs" min="10" max="600" step="5" value="60"><b id="secsV">60 s</b></div>
  </section>

  <section class="card">
    <h2><i>3</i> Publication</h2>
    <label>Titre commun <input type="text" id="title" placeholder="Mon super contenu #fyp #pourtoi"></label>
    <label class="chk"><input type="checkbox" id="num" checked> Ajouter « (1/N) » à la fin de chaque titre</label>
    <label>Visibilité
      <select id="privacy">
        <option value="SELF_ONLY">🔒 Privé (obligatoire tant que l'app n'est pas auditée)</option>
        <option value="PUBLIC_TO_EVERYONE">🌍 Public</option>
        <option value="MUTUAL_FOLLOW_FRIENDS">👥 Amis</option>
      </select>
    </label>
    <div class="row">
      <label class="chk"><input type="checkbox" id="noComment"> Commentaires off</label>
      <label class="chk"><input type="checkbox" id="noDuet"> Duo off</label>
      <label class="chk"><input type="checkbox" id="noStitch"> Stitch off</label>
    </div>
    <button class="btn" id="splitBtn">✂️ Découper la vidéo</button>
    <div class="bar" id="loader" hidden><div></div></div>
    <p id="msg"></p>
  </section>

  <section class="card" id="results" hidden>
    <h2><i>4</i> Parties <small id="resInfo"></small></h2>
    <div class="grid" id="list"></div>
    <div class="bar"><div id="prog"></div></div>
    <button class="btn" id="allBtn">🚀 Tout publier sur TikTok</button>
  </section>
</main>
<script src="app.js"></script>
</body>
</html>
