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
    exit('Acesso negado.');
}

$id = (int)($_POST['id_documento'] ?? 0);
$stmt = $mysqli->prepare('SELECT caminho_arquivo FROM documentos WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if ($doc && is_file($doc['caminho_arquivo'])) {
    unlink($doc['caminho_arquivo']);
}

$stmt = $mysqli->prepare('DELETE FROM documentos WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

header('Location: index.php');
exit;
