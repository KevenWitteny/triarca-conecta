<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['usuario_id'];
$stmt = $mysqli->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!(int)($user['is_admin'] ?? 0)) {
    http_response_code(403);
    exit('Apenas administradores podem enviar documentos.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$idCliente = (int)($_POST['id_cliente'] ?? 0);
$categoriaPrincipal = trim($_POST['categoria_principal'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$ano = trim($_POST['ano'] ?? date('Y'));
$mes = trim($_POST['mes'] ?? date('m'));

if ($idCliente <= 0 || !$categoriaPrincipal || !$categoria || empty($_FILES['arquivo']['name'])) {
    exit('Dados inválidos.');
}

$uploadDir = __DIR__ . '/uploads/' . $idCliente . '/' . $ano . '/' . $mes . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$nomeOriginal = basename($_FILES['arquivo']['name']);
$nomeSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nomeOriginal);
$caminhoFinal = $uploadDir . time() . '_' . $nomeSeguro;

if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminhoFinal)) {
    exit('Erro ao mover arquivo enviado.');
}

$stmt = $mysqli->prepare('INSERT INTO documentos (user_id, categoria_principal, categoria, nome_arquivo, caminho_arquivo, data_upload) VALUES (?, ?, ?, ?, ?, NOW())');
$stmt->bind_param('issss', $idCliente, $categoriaPrincipal, $categoria, $nomeOriginal, $caminhoFinal);
$stmt->execute();

header('Location: index.php?sucesso=1');
exit;
