<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT id, nome, categoria, preco, bairro, endereco, lat, lng, descricao FROM estabelecimentos WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$estab = $stmt->fetch();

$precoLabel = ['1' => '$ · Econômico', '2' => '$$ · Médio', '3' => '$$$ · Alto'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bora+ · <?= $estab ? h($estab['nome']) : 'Estabelecimento' ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="screen">
    <header class="topbar">
      <a class="wordmark" href="pesquisa.php">Bora<span>+</span></a>
      <a class="exit-link" href="pesquisa.php">← Voltar</a>
    </header>

    <div class="app-frame" id="detailFrame">
      <?php if (!$estab): ?>
        <div class="msg msg--error">Estabelecimento não encontrado.</div>
        <a class="btn btn--ghost" href="pesquisa.php">Voltar para a pesquisa</a>
      <?php else: ?>
        <h1 class="page-title"><?= h($estab['nome']) ?></h1>
        <p class="page-sub"><?= h($estab['categoria']) ?> · <?= h($estab['bairro']) ?> · <?= h($precoLabel[(string)$estab['preco']] ?? '') ?></p>

        <div class="card">
          <h2>Sobre o lugar</h2>
          <p><?= nl2br(h($estab['descricao'])) ?></p>
        </div>

        <div class="card">
          <h2>Endereço</h2>
          <p><?= h($estab['endereco']) ?></p>
          <a class="btn btn--ghost"
             target="_blank" rel="noopener"
             href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($estab['lat'] . ',' . $estab['lng']) ?>">
             Ver rota no Google Maps
          </a>
        </div>

        <a class="btn btn--primary" href="mapa.php?id=<?= (int)$estab['id'] ?>">Ver no mapa</a>
      <?php endif; ?>
    </div>

    <nav class="tabbar">
      <div class="tabbar__inner">
        <a class="tabbar__item" href="pesquisa.php"><span>🔍</span>Pesquisar</a>
        <a class="tabbar__item" href="mapa.php"><span>📍</span>Mapa</a>
        <a class="tabbar__item" href="perfil.php"><span>👤</span>Perfil</a>
      </div>
    </nav>
  </div>

</body>
</html>
