<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

$usuario = usuarioLogado();

$erro = '';
$duplicado = false;
$dados = [
    'nome' => '', 'categoria' => '', 'preco' => '', 'bairro' => '',
    'endereco' => '', 'descricao' => '',
];

/**
 * Obtém latitude e longitude automaticamente a partir do endereço e bairro
 * usando OpenStreetMap (Nominatim), com fallback automático.
 */
function obterCoordenadas(string $endereco, string $bairro = ''): array {
    $busca = trim($endereco);
    if ($bairro !== '' && stripos($endereco, $bairro) === false) {
        $busca .= ', ' . $bairro;
    }

    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($busca);
    $opts = [
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: BoraPlusApp/1.0 (admin-cadastro)\r\n",
            'timeout' => 3,
        ]
    ];

    $res = @file_get_contents($url, false, stream_context_create($opts));
    if ($res !== false) {
        $data = json_decode($res, true);
        if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
            return [(float)$data[0]['lat'], (float)$data[0]['lon']];
        }
    }

    // Se o endereço contiver menção a Curitiba ou PR
    if (stripos($busca, 'curitiba') !== false || stripos($busca, 'pr') !== false) {
        return [-25.4363, -49.2848];
    }

    // Fallback padrão: São Paulo (Centro)
    return [-23.5505, -46.6333];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($dados as $campo => $_) {
        $dados[$campo] = trim($_POST[$campo] ?? '');
    }
    $confirmarDuplicado = isset($_POST['confirmar_duplicado']) && $_POST['confirmar_duplicado'] === '1';

    // ---- Validação básica ----
    if ($dados['nome'] === '' || $dados['categoria'] === '' || $dados['preco'] === ''
        || $dados['bairro'] === '' || $dados['endereco'] === '' || $dados['descricao'] === '') {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!in_array($dados['preco'], ['1', '2', '3'], true)) {
        $erro = 'Selecione uma faixa de preço válida.';
    } else {
        // ---- Verifica duplicidade (mesmo nome + mesmo endereço) ----
        $stmtDup = $pdo->prepare('SELECT id FROM estabelecimentos WHERE nome = :nome AND endereco = :endereco LIMIT 1');
        $stmtDup->execute(['nome' => $dados['nome'], 'endereco' => $dados['endereco']]);
        $jaExiste = (bool)$stmtDup->fetch();

        if ($jaExiste && !$confirmarDuplicado) {
            $duplicado = true;
        } else {
            list($lat, $lng) = obterCoordenadas($dados['endereco'], $dados['bairro']);

            $insert = $pdo->prepare(
                'INSERT INTO estabelecimentos (nome, categoria, preco, bairro, endereco, lat, lng, descricao, usuario_id)
                 VALUES (:nome, :categoria, :preco, :bairro, :endereco, :lat, :lng, :descricao, :usuario_id)'
            );
            $insert->execute([
                'nome'       => $dados['nome'],
                'categoria'  => $dados['categoria'],
                'preco'      => (int)$dados['preco'],
                'bairro'     => $dados['bairro'],
                'endereco'   => $dados['endereco'],
                'lat'        => $lat,
                'lng'        => $lng,
                'descricao'  => $dados['descricao'],
                'usuario_id' => $usuario['id'],
            ]);

            header('Location: estabelecimento.php?id=' . $pdo->lastInsertId());
            exit;
        }
    }
}

$categoriasDisponiveis = ['Bar', 'Choperia', 'Pub', 'Casa noturna', 'Rooftop', 'Lounge', 'Restaurante-bar'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bora+ · Cadastrar estabelecimento</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="screen">
    <header class="topbar">
      <a class="wordmark" href="pesquisa.php">Bora<span>+</span></a>
      <a class="exit-link" href="perfil.php">Perfil</a>
    </header>

    <div class="app-frame">
      <h1 class="page-title">Cadastrar estabelecimento</h1>
      <p class="page-sub">Como administrador, cadastre seu bar ou casa noturna para ganhar visibilidade na plataforma.</p>

      <div id="formMsg" class="msg msg--error"><?= h($erro) ?></div>

      <form id="cadastroEstabForm" class="card" method="post" action="admin-cadastro.php" novalidate>
        <div class="field">
          <label for="nome">Nome do estabelecimento *</label>
          <input type="text" id="nome" name="nome" placeholder="Ex.: Bar do Zé" required value="<?= h($dados['nome']) ?>">
        </div>

        <div class="field-row">
          <div class="field">
            <label for="categoria">Categoria *</label>
            <select id="categoria" name="categoria" required>
              <option value="">Selecione</option>
              <?php foreach ($categoriasDisponiveis as $cat): ?>
              <option <?= $cat === $dados['categoria'] ? 'selected' : '' ?>><?= h($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="preco">Faixa de preço *</label>
            <select id="preco" name="preco" required>
              <option value="">Selecione</option>
              <option value="1" <?= $dados['preco'] === '1' ? 'selected' : '' ?>>$ · Econômico</option>
              <option value="2" <?= $dados['preco'] === '2' ? 'selected' : '' ?>>$$ · Médio</option>
              <option value="3" <?= $dados['preco'] === '3' ? 'selected' : '' ?>>$$$ · Alto</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="bairro">Bairro *</label>
          <input type="text" id="bairro" name="bairro" placeholder="Ex.: Vila Madalena" required value="<?= h($dados['bairro']) ?>">
        </div>

        <div class="field">
          <label for="endereco">Endereço completo *</label>
          <input type="text" id="endereco" name="endereco" placeholder="Rua, número - bairro, cidade" required value="<?= h($dados['endereco']) ?>">
          <p class="field-hint">A localização no mapa é calculada automaticamente a partir deste endereço.</p>
        </div>

        <div class="field">
          <label for="descricao">Descrição *</label>
          <textarea id="descricao" name="descricao" placeholder="Conte o que torna o lugar especial" required><?= h($dados['descricao']) ?></textarea>
        </div>

        <input type="hidden" id="confirmarDuplicadoInput" name="confirmar_duplicado" value="0">
        <button type="submit" class="btn btn--primary">Salvar</button>
      </form>
    </div>

    <div id="duplicateModal" class="modal-overlay" style="<?= $duplicado ? 'display:flex;' : 'display:none;' ?>" <?= $duplicado ? '' : 'hidden' ?>>
      <div class="modal-card">
        <h2>Este estabelecimento já existe?</h2>
        <p>Já encontramos um estabelecimento cadastrado com esse mesmo nome e endereço. Deseja continuar mesmo assim?</p>
        <div class="modal-actions">
          <button type="button" id="cancelDuplicate" class="btn btn--ghost">Cancelar</button>
          <button type="button" id="confirmDuplicate" class="btn btn--primary">Cadastrar assim mesmo</button>
        </div>
      </div>
    </div>

    <nav class="tabbar">
      <div class="tabbar__inner">
        <a class="tabbar__item" href="pesquisa.php"><span>🔍</span>Pesquisar</a>
        <a class="tabbar__item" href="mapa.php"><span>📍</span>Mapa</a>
        <a class="tabbar__item" href="perfil.php"><span>👤</span>Perfil</a>
      </div>
    </nav>
  </div>

<script>
  var modal = document.getElementById('duplicateModal');
  var form  = document.getElementById('cadastroEstabForm');
  var confirmarInput = document.getElementById('confirmarDuplicadoInput');

  document.getElementById('cancelDuplicate').addEventListener('click', function () {
    modal.hidden = true;
    modal.style.display = 'none';
  });

  document.getElementById('confirmDuplicate').addEventListener('click', function () {
    confirmarInput.value = '1';
    form.submit();
  });
</script>
</body>
</html>
