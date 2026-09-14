<?php
require_once __DIR__ . '/includes/auth.php';

if (estaLogado()) {
    header('Location: perfil.php');
    exit;
}

$erro = '';
$dados = ['nome' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome            = trim($_POST['nome'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $senha           = (string)($_POST['senha'] ?? '');
    $confirmarSenha  = (string)($_POST['confirmarSenha'] ?? '');
    $dados['nome']  = $nome;
    $dados['email'] = $email;

    if ($nome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $erro = 'Já existe uma conta com esse e-mail.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (:nome, :email, :senha_hash)');
            $insert->execute([
                'nome'       => $nome,
                'email'      => $email,
                'senha_hash' => $hash,
            ]);

            session_regenerate_id(true);
            $_SESSION['usuario_id']    = (int)$pdo->lastInsertId();
            $_SESSION['usuario_nome']  = $nome;
            $_SESSION['usuario_email'] = $email;
            header('Location: perfil.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bora+ · Criar conta</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="screen">
    <div class="app-frame login-frame">

      <div class="login-hero login-hero--compact">
        <a class="wordmark" href="index.php">Bora<span>+</span></a>
      </div>

      <div class="card login-card">
        <h1>Criar conta</h1>
        <p class="login-card__sub">Leva menos de um minuto.</p>

        <?php if ($erro !== ''): ?>
        <div class="msg msg--error" role="alert"><?= h($erro) ?></div>
        <?php endif; ?>

        <form id="cadastroForm" method="post" action="cadastro-conta.php" novalidate>
          <div class="field">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" placeholder="Como podemos te chamar" autocomplete="name" required value="<?= h($dados['nome']) ?>">
          </div>
          <div class="field">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="voce@email.com" autocomplete="email" required value="<?= h($dados['email']) ?>">
          </div>
          <div class="field">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres" autocomplete="new-password" required minlength="6">
          </div>
          <div class="field">
            <label for="confirmarSenha">Confirmar senha</label>
            <input type="password" id="confirmarSenha" name="confirmarSenha" placeholder="Repita a senha" autocomplete="new-password" required minlength="6">
          </div>
          <button type="submit" class="btn btn--primary">Criar conta e entrar</button>
        </form>

        <p class="login-switch">
          Já tem conta? <a href="index.php">Fazer login</a>
        </p>
      </div>
    </div>
  </div>

</body>
</html>
