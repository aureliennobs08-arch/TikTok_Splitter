const $ = id => document.getElementById(id);
let job = null, files = [], source = 'yt', mode = 'count', connected = false;

// --- Compte TikTok ---
fetch('me.php').then(r => r.json()).then(d => {
  connected = d.connected;
  $('account').innerHTML = d.connected
    ? `${d.avatar ? `<img src="${d.avatar}">` : ''}<span>${d.name}</span><a class="btn sm alt" href="logout.php">Déconnexion</a>`
    : `<a class="btn sm" href="auth.php">Se connecter à TikTok</a>`;
});

// --- Onglets ---
document.querySelectorAll('[data-t]').forEach(b => b.onclick = () => {
  source = b.dataset.t;
  document.querySelectorAll('[data-t]').forEach(x => x.classList.toggle('on', x === b));
  $('pane-yt').hidden = source !== 'yt'; $('pane-file').hidden = source !== 'file';
});
document.querySelectorAll('[data-m]').forEach(b => b.onclick = () => {
  mode = b.dataset.m;
  document.querySelectorAll('[data-m]').forEach(x => x.classList.toggle('on', x === b));
  $('m-count').hidden = mode !== 'count'; $('m-duration').hidden = mode !== 'duration';
});
$('parts').oninput = e => $('partsV').textContent = e.target.value + ' parties';
$('secs').oninput = e => $('secsV').textContent = e.target.value + ' s';

// --- Drag & drop ---
const drop = $('drop');
drop.onclick = () => $('video').click();
drop.ondragover = e => { e.preventDefault(); drop.classList.add('over'); };
drop.ondragleave = () => drop.classList.remove('over');
drop.ondrop = e => { e.preventDefault(); drop.classList.remove('over'); $('video').files = e.dataTransfer.files; showFile(); };
$('video').onchange = showFile;
function showFile() { const f = $('video').files[0]; if (f) $('dropTxt').textContent = `🎬 ${f.name} (${(f.size / 1048576).toFixed(1)} Mo)`; }

const fmt = s => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`;

// --- Découpage ---
$('splitBtn').onclick = async () => {
  const f = $('video').files[0], url = $('url').value.trim();
  if (source === 'yt' && !url) return $('msg').textContent = '⚠️ Colle un lien YouTube.';
  if (source === 'file' && !f) return $('msg').textContent = '⚠️ Choisis un fichier vidéo.';
  const fd = new FormData();
  if (source === 'yt') fd.append('url', url); else fd.append('video', f);
  fd.append('mode', mode); fd.append('parts', $('parts').value); fd.append('seconds', $('secs').value);
  $('splitBtn').disabled = true; $('loader').hidden = false;
  $('msg').textContent = 'Téléchargement / découpage en cours… (peut prendre un moment)';
  try {
    const d = await (await fetch('split.php', { method: 'POST', body: fd })).json();
    if (d.error) throw new Error(d.error);
    job = d.job; files = d.files;
    $('msg').textContent = `✅ ${files.length} parties créées (durée totale ${fmt(d.duration)})`;
    render();
  } catch (e) { $('msg').textContent = '❌ ' + e.message; }
  $('splitBtn').disabled = false; $('loader').hidden = true;
};

function titleFor(i) {
  const t = $('title').value.trim();
  return $('num').checked ? `${t} (${i + 1}/${files.length})`.trim() : t;
}

function render() {
  $('results').hidden = false;
  $('resInfo').textContent = `${files.length} parties`;
  $('prog').style.width = '0';
  $('list').innerHTML = '';
  files.forEach((f, i) => {
    const el = document.createElement('div');
    el.className = 'part';
    el.innerHTML = `<div class="top"><b>Partie ${i + 1}</b><small>${fmt(f.start)} → ${fmt(f.end)} · ${(f.size / 1048576).toFixed(1)} Mo</small><span class="badge" id="st${i}">En attente</span></div>
      <input type="text" id="t${i}" value="${titleFor(i).replace(/"/g, '&quot;')}">
      <div class="acts"><button class="btn sm alt" data-prev>▶ Aperçu</button><button class="btn sm" data-pub>Publier</button></div>
      <div class="pv"></div>`;
    el.querySelector('[data-prev]').onclick = () => {
      const pv = el.querySelector('.pv');
      pv.innerHTML = pv.innerHTML ? '' : `<video controls autoplay src="preview.php?job=${job}&file=${f.name}"></video>`;
    };
    el.querySelector('[data-pub]').onclick = () => publish(i);
    $('list').appendChild(el);
  });
  $('allBtn').disabled = !connected;
  $('allBtn').textContent = connected ? '🚀 Tout publier sur TikTok' : '🔒 Connecte-toi à TikTok pour publier';
}

async function publish(i) {
  const st = $('st' + i);
  if (!connected) { st.textContent = 'Non connecté'; st.className = 'badge ko'; return false; }
  st.textContent = 'Envoi…'; st.className = 'badge';
  try {
    const d = await (await fetch('publish.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ job, file: files[i].name, title: $('t' + i).value, privacy: $('privacy').value,
        opts: { noComment: $('noComment').checked, noDuet: $('noDuet').checked, noStitch: $('noStitch').checked } })
    })).json();
    if (d.error) throw new Error(d.error);
    st.textContent = '✅ ' + d.status; st.className = 'badge ok';
    return true;
  } catch (e) { st.textContent = '❌ ' + e.message; st.className = 'badge ko'; return false; }
}

$('allBtn').onclick = async () => {
  $('allBtn').disabled = true;
  for (let i = 0; i < files.length; i++) {
    await publish(i);
    $('prog').style.width = ((i + 1) / files.length * 100) + '%';
    await new Promise(r => setTimeout(r, 2000));
  }
  $('allBtn').disabled = false;
};
