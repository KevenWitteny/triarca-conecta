<?php
// Copie este arquivo para conexao.php e ajuste os dados do seu banco.
$host = 'localhost';
$usuario = 'SEU_USUARIO';
$senha = 'SUA_SENHA';
$banco = 'SEU_BANCO';

$mysqli = new mysqli($host, $usuario, $senha, $banco);

if ($mysqli->connect_errno) {
    die('Erro ao conectar ao banco: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
