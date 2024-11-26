<?php
// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "sistema_login");

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verifica se foi solicitado login ou cadastro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == "login") {
        // Login
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Busca o usuário no banco de dados
        $query = "SELECT * FROM usuarios WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verifica a senha
            if (password_verify($password, $user['password'])) {
                // Login bem-sucedido
                header("Location: bemvindo.php");
                exit();
            } else {
                echo "Senha incorreta!";
            }
        } else {
            echo "Usuário não encontrado!";
        }
    } elseif ($action == "signup") {
        // Cadastro
        $name = $_POST['name'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $cpf = $_POST['cpf'];

        // Valida CPF (formato básico)
        if (!preg_match("/^\d{11}$/", $cpf)) {
            die("CPF inválido!");
        }

        // Verifica se o usuário já existe
        $query = "SELECT * FROM usuarios WHERE username = ? OR cpf = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $cpf);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            die("Usuário ou CPF já cadastrado!");
        }

        // Criptografa a senha
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insere no banco de dados
        $query = "INSERT INTO usuarios (name, username, password, cpf) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $name, $username, $hashed_password, $cpf);

        if ($stmt->execute()) {
            // Cadastro bem-sucedido
            header("Location: bemvindo.php");
            exit();
        } else {
            echo "Erro ao cadastrar: " . $stmt->error;
        }
    }
}

$conn->close();
?>
