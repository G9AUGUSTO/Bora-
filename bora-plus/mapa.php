<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

$focarId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$estabFocado = null;
if ($focarId) {
    $stmt = $pdo->prepare('SELECT id, nome, categoria, preco, bairro, endereco, lat, lng, descricao FROM estabelecimentos WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $focarId]);
    $estabFocado = $stmt->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bora+ · Mapa</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="screen">
    <header class="topbar">
      <a class="wordmark" href="pesquisa.php">Bora<span>+</span></a>
      <a class="exit-link" href="perfil.php">Perfil</a>
    </header>

    <div class="app-frame app-frame--wide map-frame">
      <h1 class="page-title">O que tem perto de você</h1>
      <p class="page-sub">Toque em um marcador para ver o resumo do lugar.</p>

      <div id="permissionState" class="card permission-card">
        <div class="permission-icon">📍</div>
        <h2>Precisamos da sua localização</h2>
        <p>Para mostrar os estabelecimentos cadastrados perto de você no mapa, permita o acesso à sua localização.</p>
        <button id="allowLocationBtn" class="btn btn--primary">Permitir localização</button>
        <button id="defaultLocationBtn" type="button" class="btn btn--ghost" style="margin-top:10px;">Usar localização de São Paulo (Centro)</button>
        <p id="permissionError" class="msg msg--error"></p>
      </div>

      <div id="mapState" class="map-wrap" hidden>
        <div id="mapCanvas" class="map-canvas">
          <div class="map-canvas__grid"></div>
          <div id="userMarker" class="map-marker map-marker--user" title="Você está aqui">
            <span class="map-marker__dot"></span>
          </div>
        </div>

        <div id="summaryCard" class="summary-card" hidden>
          <button id="closeSummary" class="summary-card__close" aria-label="Fechar">×</button>
          <div id="summaryContent"></div>
        </div>
      </div>
    </div>

    <nav class="tabbar">
      <div class="tabbar__inner">
        <a class="tabbar__item" href="pesquisa.php"><span>🔍</span>Pesquisar</a>
        <a class="tabbar__item is-active" href="mapa.php"><span>📍</span>Mapa</a>
        <a class="tabbar__item" href="perfil.php"><span>👤</span>Perfil</a>
      </div>
    </nav>
  </div>

<script>
(function () {
  var permissionState = document.getElementById('permissionState');
  var permissionError = document.getElementById('permissionError');
  var allowBtn        = document.getElementById('allowLocationBtn');
  var defaultBtn      = document.getElementById('defaultLocationBtn');
  var mapState        = document.getElementById('mapState');
  var mapCanvas       = document.getElementById('mapCanvas');
  var summaryCard     = document.getElementById('summaryCard');
  var summaryContent  = document.getElementById('summaryContent');
  var closeSummaryBtn = document.getElementById('closeSummary');

  var precoLabel = { '1': '$ · Econômico', '2': '$$ · Médio', '3': '$$$ · Alto' };
  var KM_ATE_BORDA = 8;
  var DEFAULT_LAT = -23.5505;
  var DEFAULT_LNG = -46.6333;
  var estabFocado = <?= json_encode($estabFocado) ?>;

  closeSummaryBtn.addEventListener('click', function () {
    summaryCard.hidden = true;
  });

  allowBtn.addEventListener('click', function () {
    permissionError.textContent = '';
    if (!navigator.geolocation) {
      permissionError.textContent = 'Seu navegador não suporta geolocalização. Use a opção com localização padrão abaixo.';
      return;
    }
    navigator.geolocation.getCurrentPosition(function (pos) {
      onLocationOk(pos.coords.latitude, pos.coords.longitude);
    }, onLocationError, {
      enableHighAccuracy: true,
      timeout: 10000
    });
  });

  defaultBtn.addEventListener('click', function () {
    onLocationOk(DEFAULT_LAT, DEFAULT_LNG);
  });

  function onLocationError(err) {
    permissionError.textContent = 'Não foi possível obter sua localização (' + err.message + '). Clique no botão abaixo para usar a localização padrão de demonstração.';
  }

  function onLocationOk(lat, lng, focoId) {
    fetch('api/mapa_data.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        permissionState.hidden = true;
        mapState.hidden = false;
        renderMarkers(lat, lng, data.estabelecimentos || [], focoId);
      })
      .catch(function () {
        permissionError.textContent = 'Erro ao carregar estabelecimentos do banco de dados.';
      });
  }

  function showSummary(lugar) {
    var distTexto = lugar.distancia_km != null ? ('<p>' + lugar.distancia_km.toFixed(1).replace('.', ',') + ' km de você</p>') : '';
    summaryContent.innerHTML =
      '<h3>' + escapeHtml(lugar.nome) + '</h3>' +
      '<p class="result-card__meta">' + escapeHtml(lugar.categoria) + ' · ' + escapeHtml(lugar.bairro) +
      ' · ' + (precoLabel[String(lugar.preco)] || '') + '</p>' +
      distTexto +
      '<a class="btn btn--primary" href="estabelecimento.php?id=' + lugar.id + '">Ver detalhes</a>';
    summaryCard.hidden = false;
  }

  function renderMarkers(userLat, userLng, lugares, focoId) {
    // remove marcadores antigos (mantém grid e marcador do usuário)
    Array.prototype.slice.call(mapCanvas.querySelectorAll('.map-marker--lugar')).forEach(function (el) {
      el.remove();
    });

    var rect = mapCanvas.getBoundingClientRect();
    var raio = Math.min(rect.width, rect.height) / 2;

    lugares.forEach(function (lugar) {
      var dLat = lugar.lat - userLat;
      var dLng = lugar.lng - userLng;
      var kmLat = dLat * 111;
      var kmLng = dLng * 111 * Math.cos(userLat * Math.PI / 180);

      var xPx = (kmLng / KM_ATE_BORDA) * raio;
      var yPx = -(kmLat / KM_ATE_BORDA) * raio;

      xPx = Math.max(-raio + 14, Math.min(raio - 14, xPx));
      yPx = Math.max(-raio + 14, Math.min(raio - 14, yPx));

      var marker = document.createElement('button');
      marker.type = 'button';
      marker.className = 'map-marker map-marker--lugar' + (focoId && lugar.id == focoId ? ' is-active' : '');
      marker.style.left = 'calc(50% + ' + xPx + 'px)';
      marker.style.top  = 'calc(50% + ' + yPx + 'px)';
      marker.title = lugar.nome;
      marker.innerHTML = '<span class="map-marker__dot"></span>';

      marker.addEventListener('click', function () {
        showSummary(lugar);
      });

      mapCanvas.appendChild(marker);

      if (focoId && lugar.id == focoId) {
        showSummary(lugar);
      }
    });
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
  }

  // Se veio de um estabelecimento específico, abre direto
  if (estabFocado) {
    onLocationOk(Number(estabFocado.lat), Number(estabFocado.lng), estabFocado.id);
  }
})();
</script>
</body>
</html>
