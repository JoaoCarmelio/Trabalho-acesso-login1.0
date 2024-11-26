<?php
// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "sistema_login");

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Função para validar CPF
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);
    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se todos os dígitos são iguais (ex.: 11111111111)
    for ($i = 0; $i < 10; $i++) {
        if (str_repeat($i, 11) == $cpf) {
            return false;
        }
    }

    // Validação do dígito verificador
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
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

        // Valida CPF
        if (!validarCPF($cpf)) {
            die("CPF inválido!");
        }

        // Verifica se a senha é forte
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
            die("A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e caracteres especiais.");
        }

        // Verifica se o usuário ou CPF já existe
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
