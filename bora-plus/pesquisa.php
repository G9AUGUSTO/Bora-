<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

// ---- Opções dos filtros (vindas do banco) ----
$bairros    = $pdo->query('SELECT DISTINCT bairro FROM estabelecimentos ORDER BY bairro')->fetchAll(PDO::FETCH_COLUMN);
$categorias = $pdo->query('SELECT DISTINCT categoria FROM estabelecimentos ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);

// ---- Filtros recebidos via GET ----
$bairroFiltro    = trim($_GET['bairro'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');
$precoFiltro     = trim($_GET['preco'] ?? '');
$ordenar         = trim($_GET['ordenar'] ?? 'relevancia');
$userLat         = isset($_GET['lat']) && $_GET['lat'] !== '' ? (float)$_GET['lat'] : null;
$userLng         = isset($_GET['lng']) && $_GET['lng'] !== '' ? (float)$_GET['lng'] : null;

$where  = [];
$params = [];

if ($bairroFiltro !== '') {
    $where[] = 'bairro = :bairro';
    $params['bairro'] = $bairroFiltro;
}
if ($categoriaFiltro !== '') {
    $where[] = 'categoria = :categoria';
    $params['categoria'] = $categoriaFiltro;
}
if ($precoFiltro !== '' && in_array($precoFiltro, ['1', '2', '3'], true)) {
    $where[] = 'preco = :preco';
    $params['preco'] = (int)$precoFiltro;
}

$sql = 'SELECT id, nome, categoria, preco, bairro, endereco, lat, lng, descricao FROM estabelecimentos';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

// Ordenação simples direto no SQL (exceto distância, calculada em PHP)
switch ($ordenar) {
    case 'preco-asc':
        $sql .= ' ORDER BY preco ASC, nome ASC';
        break;
    case 'preco-desc':
        $sql .= ' ORDER BY preco DESC, nome ASC';
        break;
    case 'distancia':
        // ordenado depois em PHP se tivermos coordenadas do usuário
        $sql .= ' ORDER BY nome ASC';
        break;
    default:
        $sql .= ' ORDER BY criado_em DESC';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll();

// Calcula distância (Haversine, em km) se tivermos a localização do usuário
function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $raioTerra = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $raioTerra * $c;
}

if ($userLat !== null && $userLng !== null) {
    foreach ($resultados as &$r) {
        $r['distancia_km'] = distanciaKm($userLat, $userLng, (float)$r['lat'], (float)$r['lng']);
    }
    unset($r);

    if ($ordenar === 'distancia') {
        usort($resultados, fn($a, $b) => $a['distancia_km'] <=> $b['distancia_km']);
    }
}

$precoLabel = ['1' => '$ · Econômico', '2' => '$$ · Médio', '3' => '$$$ · Alto'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bora+ · Pesquisar</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="screen">
    <header class="topbar">
      <a class="wordmark" href="pesquisa.php">Bora<span>+</span></a>
      <a class="exit-link" href="perfil.php">Perfil</a>
    </header>

    <div class="app-frame app-frame--wide">
      <h1 class="page-title">Pra onde vamos hoje?</h1>
      <p class="page-sub">Filtre por bairro, preço ou categoria pra achar a melhor opção.</p>

      <form id="filtroForm" class="filter-bar card" method="get" action="pesquisa.php">
        <div class="field">
          <label for="bairro">Bairro</label>
          <select id="bairro" name="bairro">
            <option value="">Todos os bairros</option>
            <?php foreach ($bairros as $b): ?>
            <option value="<?= h($b) ?>" <?= $b === $bairroFiltro ? 'selected' : '' ?>><?= h($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="categoria">Categoria</label>
          <select id="categoria" name="categoria">
            <option value="">Todas as categorias</option>
            <?php foreach ($categorias as $c): ?>
            <option value="<?= h($c) ?>" <?= $c === $categoriaFiltro ? 'selected' : '' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="preco">Preço</label>
          <select id="preco" name="preco">
            <option value="">Qualquer preço</option>
            <option value="1" <?= $precoFiltro === '1' ? 'selected' : '' ?>>$ · Econômico</option>
            <option value="2" <?= $precoFiltro === '2' ? 'selected' : '' ?>>$$ · Médio</option>
            <option value="3" <?= $precoFiltro === '3' ? 'selected' : '' ?>>$$$ · Alto</option>
          </select>
        </div>
        <input type="hidden" id="lat" name="lat" value="<?= h($_GET['lat'] ?? '') ?>">
        <input type="hidden" id="lng" name="lng" value="<?= h($_GET['lng'] ?? '') ?>">
        <button type="submit" class="btn btn--primary filter-bar__submit">Aplicar filtro</button>
      </form>

      <div class="results-head">
        <span id="resultsCount" class="results-count"><?= count($resultados) ?> resultado(s)</span>
        <div class="field results-sort">
          <label for="ordenar">Ordenar</label>
          <select id="ordenar" name="ordenar" form="filtroForm">
            <option value="relevancia" <?= $ordenar === 'relevancia' ? 'selected' : '' ?>>Relevância</option>
            <option value="preco-asc" <?= $ordenar === 'preco-asc' ? 'selected' : '' ?>>Menor preço</option>
            <option value="preco-desc" <?= $ordenar === 'preco-desc' ? 'selected' : '' ?>>Maior preço</option>
            <option value="distancia" <?= $ordenar === 'distancia' ? 'selected' : '' ?>>Mais próximos de mim</option>
          </select>
        </div>
      </div>

      <div id="filterMsg" class="msg msg--info">
        <?= $ordenar === 'distancia' && $userLat === null
            ? 'Precisamos da sua localização para ordenar por proximidade. Permita o acesso quando o navegador pedir.'
            : '' ?>
      </div>

      <ul id="resultsList" class="results-list">
        <?php if (!$resultados): ?>
          <li class="results-empty">Nenhum estabelecimento encontrado com esses filtros.</li>
        <?php endif; ?>
        <?php foreach ($resultados as $r): ?>
        <li class="result-item">
          <a href="estabelecimento.php?id=<?= (int)$r['id'] ?>" class="result-card">
            <div class="result-card__head">
              <h3><?= h($r['nome']) ?></h3>
              <span class="result-card__preco"><?= h($precoLabel[(string)$r['preco']] ?? '') ?></span>
            </div>
            <p class="result-card__meta"><?= h($r['categoria']) ?> · <?= h($r['bairro']) ?></p>
            <?php if (isset($r['distancia_km'])): ?>
            <p class="result-card__dist"><?= number_format($r['distancia_km'], 1, ',', '.') ?> km de você</p>
            <?php endif; ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <nav class="tabbar">
      <div class="tabbar__inner">
        <a class="tabbar__item is-active" href="pesquisa.php"><span>🔍</span>Pesquisar</a>
        <a class="tabbar__item" href="mapa.php"><span>📍</span>Mapa</a>
        <a class="tabbar__item" href="perfil.php"><span>👤</span>Perfil</a>
      </div>
    </nav>
  </div>

  <script>
    // Se o usuário escolher "Mais próximos de mim" e ainda não tivermos a localização,
    // pedimos a localização do navegador e reenviamos o formulário com lat/lng.
    document.getElementById('ordenar').addEventListener('change', function () {
      var latField = document.getElementById('lat');
      if (this.value === 'distancia' && !latField.value && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
          document.getElementById('lat').value = pos.coords.latitude;
          document.getElementById('lng').value = pos.coords.longitude;
          document.getElementById('filtroForm').submit();
        }, function () {
          document.getElementById('filtroForm').submit();
        });
      } else {
        document.getElementById('filtroForm').submit();
      }
    });
  </script>
</body>
</html>
