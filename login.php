<?php

define('SHIGOTO', '');
$config = include 'config.php';

session_start();
$pass = $config->password;
// $hash = password_hash($pass, PASSWORD_BCRYPT);
if (isset($_POST['password'])) {
    if (password_verify($_POST['password'], $pass)) {
        echo 'CORRECT';
        $_SESSION['auth'] = $_SERVER['REQUEST_TIME'];
        header("Location: ./index.php");
    } else {
        echo 'Incorrect password';
    }
}
?>

<head>
  <link href="style.css" rel="stylesheet">
</head>

<body>
  <div class="container">
    <div class="side">Shigoto</div>
    <div class="main">
      <form action="" method="POST">
        <input type="password" placeholder="password" id="password" name="password">
        <button>login</button>
      </form>
    </div>
  </div>
</body>