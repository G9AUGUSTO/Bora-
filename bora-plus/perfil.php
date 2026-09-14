<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

$usuario = usuarioLogado();

// Busca dados atualizados diretamente do banco
$stmt = $pdo->prepare('SELECT nome, email FROM usuarios WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $usuario['id']]);
$dadosUsuario = $stmt->fetch() ?: $usuario;

$inicial = mb_strtoupper(mb_substr($dadosUsuario['nome'], 0, 1, 'UTF-8'), 'UTF-8');

// Estabelecimentos que esse usuário cadastrou
$stmtMeus = $pdo->prepare('SELECT id, nome, categoria, bairro FROM estabelecimentos WHERE usuario_id = :id ORDER BY criado_em DESC');
$stmtMeus->execute(['id' => $usuario['id']]);
$meusEstabelecimentos = $stmtMeus->fetchAll();
$totalCadastrados = count($meusEstabelecimentos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bora+ · Meu perfil</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="screen">
    <header class="topbar">
      <a class="wordmark" href="pesquisa.php">Bora<span>+</span></a>
      <form method="post" action="logout.php" style="display:inline;">
        <button id="logoutBtn" type="submit" class="exit-link" style="background:none;border:1px solid var(--surface-line);cursor:pointer;">Sair</button>
      </form>
    </header>

    <div class="app-frame">
      <div class="profile-head">
        <div class="profile-avatar" id="profileAvatar"><?= h($inicial) ?></div>
        <h1 id="profileNome"><?= h($dadosUsuario['nome']) ?></h1>
        <p id="profileEmail"><?= h($dadosUsuario['email']) ?></p>
      </div>

      <div class="card profile-card" style="margin-bottom: 20px;">
        <h2>Visão geral da sua conta</h2>
        <p>Você está logado na plataforma Bora+. Use o menu abaixo para pesquisar estabelecimentos, ver o mapa de lugares próximos ou cadastrar novos estabelecimentos.</p>
        <p><strong><?= $totalCadastrados ?></strong> estabelecimento(s) cadastrado(s) por você.</p>
        <a class="btn btn--primary" href="admin-cadastro.php" style="margin-top: 10px;">Cadastrar novo estabelecimento</a>
      </div>

      <?php if ($totalCadastrados > 0): ?>
      <h2 style="font-size: 1.1rem; margin: 20px 0 10px;">Seus estabelecimentos cadastrados</h2>
      <ul class="results-list" style="margin-bottom: 24px;">
        <?php foreach ($meusEstabelecimentos as $m): ?>
        <li class="result-item">
          <a href="estabelecimento.php?id=<?= (int)$m['id'] ?>" class="result-card">
            <div class="result-card__head">
              <h3><?= h($m['nome']) ?></h3>
            </div>
            <p class="result-card__meta"><?= h($m['categoria']) ?> · <?= h($m['bairro']) ?></p>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <nav class="tabbar">
      <div class="tabbar__inner">
        <a class="tabbar__item" href="pesquisa.php"><span>🔍</span>Pesquisar</a>
        <a class="tabbar__item" href="mapa.php"><span>📍</span>Mapa</a>
        <a class="tabbar__item is-active" href="perfil.php"><span>👤</span>Perfil</a>
      </div>
    </nav>
  </div>

</body>
</html>
