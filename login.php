<?php
session_start();

const MAX_ATTEMPTS = 5;
const BAN_DURATION = 3600; 
const BAN_THRESHOLD = 10;

$conn = new mysqli('localhost', 'root', '', 'bruteforce');

function isIPBlocked($ip) {
    $blockedIPs = file_get_contents('blocked_ips.txt');
    $blockedIPs = explode("\n", $blockedIPs);
    return in_array($ip, $blockedIPs);
}

function blockIP($ip) {
    file_put_contents('blocked_ips.txt', "$ip\n", FILE_APPEND);
}

if (isset($_POST['Enviar'])) {
    $email = $_POST['email'];
    $clave = $_POST['clave'];

    $clientIP = $_SERVER['REMOTE_ADDR'];
    if (isIPBlocked($clientIP)) {
        echo 'Cuenta bloqueda';
        exit;
    }

    if (isset($_SESSION["loginBan-$email"]) && $_SESSION["loginBan-$email"] > time()) {
        $remainingTime = ceil(($_SESSION["loginBan-$email"] - time()) / 60);
        echo 'Cuenta bloqueada';
        exit;
    }

    $failedAttempts = $_SESSION["failedAttempts-$email"] ?? 0;

    if ($failedAttempts >= MAX_ATTEMPTS) {
        $_SESSION["loginBan-$email"] = time() + BAN_DURATION;
        echo 'Cuenta bloqueda';
        blockIP($clientIP);
        exit;
    }

    $sql = "SELECT nombre,password FROM users WHERE nombre ='$email' and password ='$clave'";
    $execute = mysqli_query($conn, $sql);
    $resultado = mysqli_fetch_assoc($execute);

    $email_valido = $resultado['nombre'] ?? '';
    $clave_valido = $resultado['password'] ?? '';

    if ($email === $email_valido && $clave === $clave_valido) {
        $_SESSION['intento'] = 0;
        header('location: success.html');
    } else {
        $_SESSION["failedAttempts-$email"] = ++$failedAttempts;

        if ($failedAttempts >= BAN_THRESHOLD) {
            blockIP($clientIP);
        }

        echo 'Cuenta bloqueda';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login y Registro con HTML5 y CSS3</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Lato&display=swap" rel="stylesheet">
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Favicon -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <h1>Login</h1>
    <main>
        <article>
            <section>
                <div id="alert"></div>
                <form method="post">
        <input type="text" name="email" id="email" placeholder="Ingresar nombre" required>
        <br><br>

        <input type="password" name="clave" id="clave" placeholder="Ingresar Clave" required>
        <br><br>

        <input type="submit" value="Enviar" name="Enviar">
    </form>
            </section>
        </article>
    </main>
    
</body>
</html>
