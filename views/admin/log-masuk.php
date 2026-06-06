<?php
// login.php
include_once '../../config/config.php';
include_once '../../controllers/AuthController.php';
include_once '../../sessions/session.php';

$loginError = '';
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['login_csrf'], $postedToken)) {
        $loginError = 'Invalid request. Please try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (!login($username, $password)) {
            $loginError = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css" />
<style>
  /* Dark theme base */
  body {
  background-color: #121212;
  color: #e0e0e0;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  margin: 0;
  min-height: 100vh;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.container {
  width: 100%;
  max-width: 320px !important;
  background-color: #1e1e1e;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 0 15px rgba(0, 0, 0, 0.8);
  /* remove margin-top */
}


  input.input, 
  textarea.textarea {
    background-color: #333;
    color: #e0e0e0;
    border: 1px solid #555;
  }
  input.input::placeholder {
    color: #999;
  }
  input.input:focus {
    background-color: #444;
    border-color: #3273dc;
    color: #fff;
    outline: none;
  }
  button.button {
    background-color: #3273dc;
    color: white;
    border: none;
  }
  button.button:hover {
    filter: brightness(1.1);
  }
  /* Responsive tweaks */
  
  /* Responsive tweaks */
@media (max-width: 420px) {
  .container {
    padding: 1.5rem 1rem;
    /* remove margin-top */
    width: 90%;
  }
}
</style>
</head>
<body>
  <div class="container">
    <h1 class="title has-text-white has-text-centered">Login</h1>
    <?php if ($loginError !== ''): ?>
      <div class="notification is-danger is-light"><?php echo htmlspecialchars($loginError); ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['login_csrf']); ?>" />
      <div class="field">
        <label class="label has-text-light">Username</label>
        <div class="control">
          <input class="input" type="text" name="username" placeholder="Enter your username" required />
        </div>
      </div>
      <div class="field">
        <label class="label has-text-light">Password</label>
        <div class="control">
          <input class="input" type="password" name="password" placeholder="Enter your password" required />
        </div>
      </div>
      <div class="field mt-5">
        <div class="control">
          <button class="button is-fullwidth" type="submit">Sign In</button>
        </div>
      </div>
    </form>
  </div>
</body>
</html>
