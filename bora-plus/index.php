<?php
require_once __DIR__ . '/includes/auth.php';

// Se já está logado, vai direto pro perfil.
if (estaLogado()) {
    header('Location: perfil.php');
    exit;
}

$erro = '';
$emailDigitado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');
    $emailDigitado = $email;

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']    = $usuario['id'];
            $_SESSION['usuario_nome']  = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            header('Location: perfil.php');
            exit;
        }

        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bora+ · Entrar</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="screen">
    <div class="app-frame login-frame">

      <div class="login-hero">
        <a class="wordmark" href="index.php">Bora<span>+</span></a>
        <p class="login-hero__tag">Descubra o que tá rolando perto de você hoje.</p>
      </div>

      <div class="card login-card">
        <h1>Entrar</h1>
        <p class="login-card__sub">Acesse sua conta para ver bares e eventos perto de você.</p>

        <?php if ($erro !== ''): ?>
        <div class="msg msg--error" role="alert"><?= h($erro) ?></div>
        <?php endif; ?>

        <form id="loginForm" method="post" action="index.php" novalidate>
          <div class="field">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="voce@email.com" autocomplete="email" required value="<?= h($emailDigitado) ?>">
          </div>
          <div class="field">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="••••••••" autocomplete="current-password" required>
          </div>
          <button type="submit" class="btn btn--primary">Entrar</button>
        </form>

        <p class="login-switch">
          Ainda não tem conta? <a href="cadastro-conta.php">Criar conta</a>
        </p>
      </div>

      <p class="login-demo-hint">Conectado ao banco de dados via PHP/PDO.</p>
    </div>
  </div>

</body>
</html>
