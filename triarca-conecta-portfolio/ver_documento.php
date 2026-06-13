<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['usuario_id'];

$stmt = $mysqli->prepare('SELECT caminho_arquivo, nome_arquivo, user_id FROM documentos WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

// Admin pode ver todos. Usuário comum só o próprio documento.
$stmtAdmin = $mysqli->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
$stmtAdmin->bind_param('i', $userId);
$stmtAdmin->execute();
$user = $stmtAdmin->get_result()->fetch_assoc();
$isAdmin = (int)($user['is_admin'] ?? 0);

if (!$isAdmin && (int)$doc['user_id'] !== $userId) {
    http_response_code(403);
    exit('Acesso negado.');
}

$caminho = $doc['caminho_arquivo'];
if (!is_file($caminho)) {
    http_response_code(404);
    exit('Arquivo não encontrado no servidor.');
}

$mime = mime_content_type($caminho) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($doc['nome_arquivo']) . '"');
readfile($caminho);
exit;
