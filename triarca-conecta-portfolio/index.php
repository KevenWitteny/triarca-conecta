<?php
session_start();
require 'conexao.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Em localhost/http, cookie_secure precisa ficar 0. Em produção HTTPS, deixe 1.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

function normalizarTexto($texto) {
    $texto = mb_strtolower((string)$texto, 'UTF-8');
    $texto = preg_replace('/[áàãâä]/u', 'a', $texto);
    $texto = preg_replace('/[éèêë]/u', 'e', $texto);
    $texto = preg_replace('/[íìîï]/u', 'i', $texto);
    $texto = preg_replace('/[óòõôö]/u', 'o', $texto);
    $texto = preg_replace('/[úùûü]/u', 'u', $texto);
    $texto = preg_replace('/[ç]/u', 'c', $texto);
    $texto = preg_replace('/[^a-z0-9]/', '', $texto);
    return $texto;
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['usuario_id'];

$stmt = $mysqli->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$is_admin = ($row = $userResult->fetch_assoc()) ? (int)$row['is_admin'] : 0;
$stmt->close();

$categorias = [
    'documentacao' => [
        'titulo' => 'Documentação Societária',
        'subcategorias' => [
            'cadastro_cnpj' => 'Cadastro Nacional da Pessoa Jurídica (CNPJ)',
            'contrato_estatuto' => 'Contrato ou Estatuto Social',
            'registro_junta_comercial' => 'Registro na Junta Comercial',
            'alvaras_licencas' => 'Alvarás e Licenças',
        ]
    ],
    'certidoes' => [
        'titulo' => 'Certidões Fiscais e Trabalhistas',
        'subcategorias' => [
            'certidoes_cnd' => 'CND – Receita Federal / PGFN',
            'certidoes_trabalhistas' => 'CNDT – Justiça do Trabalho',
            'certidoes_fgts' => 'CRF – FGTS',
            'certidoes_municipais' => 'Certidão Municipal',
            'certidoes_estaduais' => 'Certidão Estadual',
        ]
    ],
    'remuneracao' => [
        'titulo' => 'Remuneração e Benefícios',
        'subcategorias' => [
            'folha_pagamento' => 'Folhas de Pagamento',
            'recibos_salario' => 'Recibos de Salário',
            'beneficios' => 'Comprovantes de Benefícios (VA, VT etc.)',
        ]
    ],
    'fgts' => [
        'titulo' => 'Obrigações FGTS',
        'subcategorias' => [
            'grf' => 'GRF – Guias de Recolhimento',
            'declaracoes_fgts' => 'Declarações e Formulários',
            'relatorios_fgts' => 'Relatórios Mensais',
        ]
    ],
    'inss' => [
        'titulo' => 'Obrigações Previdenciárias (INSS)',
        'subcategorias' => [
            'gps' => 'GPS – Guias de Previdência Social',
            'comprovantes_pagamento' => 'Comprovantes de Pagamento',
            'relatorios_previdenciarios' => 'Relatórios Previdenciários',
        ]
    ],
    'comerciais' => [
        'titulo' => 'Contratos Comerciais',
        'subcategorias' => [
            'contrato_comercial' => 'Contratos de Prestação de Serviços',
            'termo_aditivo' => 'Termos Aditivos e Acordos Complementares',
            'sla' => 'Condições Comerciais e SLA',
        ]
    ],
    'colaboradores' => [
        'titulo' => 'Dossiê de Colaboradores',
        'subcategorias' => [
            'contrato_trabalho' => 'Contrato de Trabalho',
            'documentos_pessoais' => 'Documentos Pessoais',
            'exames' => 'Exames Médicos',
            'declaracoes_colaborador' => 'Declarações e Formulários',
            'rescisao' => 'Rescisões',
            'ferias' => 'Férias',
        ]
    ],
    'treinamento' => [
        'titulo' => 'Registros de Treinamento',
        'subcategorias' => [
            'fichas_treinamento' => 'Fichas de Treinamento',
            'certificados' => 'Certificados',
            'listas_presenca' => 'Listas de Presença',
            'conteudo_programatico' => 'Conteúdo Programático',
        ]
    ],
];

// Compatibilidade com nomes antigos que apareceram no arquivo original.
$aliasesSubcategoria = [
    'registro_junta' => 'registro_junta_comercial',
    'certidoes_negativas' => 'certidoes_cnd',
    'comprovantes_pagamento_inss' => 'comprovantes_pagamento',
];

$query = $mysqli->prepare("SELECT * FROM documentos WHERE user_id = ? ORDER BY data_upload DESC, id DESC");
$query->bind_param("i", $userId);
$query->execute();
$result = $query->get_result();

$documentos = [];
while ($doc = $result->fetch_assoc()) {
    $catPrincipal = $doc['categoria_principal'] ?? '';
    $subcat = $doc['categoria'] ?? '';

    if (isset($aliasesSubcategoria[$subcat])) {
        $subcat = $aliasesSubcategoria[$subcat];
    }

    $nomeArquivo = $doc['nome_arquivo'] ?? ($doc['titulo'] ?? 'Documento');

    $documentos[$catPrincipal][$subcat][] = [
        'id' => (int)$doc['id'],
        'nome' => $nomeArquivo,
        'caminho_arquivo' => $doc['caminho_arquivo'] ?? '',
        'data_upload' => $doc['data_upload'] ?? null,
    ];
}
$query->close();

function exibirDocumentos($documentos, $categoriaPrincipal, $subcategoria, $nomeSubcategoria) {
    $containerId = 'doc_' . md5($categoriaPrincipal . '_' . $subcategoria);
    $lista = $documentos[$categoriaPrincipal][$subcategoria] ?? [];

    echo "<section class='subcategoria-container' id='" . e($containerId) . "'>";
    echo "<h3>" . e($nomeSubcategoria) . "</h3>";

    echo "<div class='filtros-container'>";
    echo "<div class='filtros'>";
    echo "<input type='text' placeholder='Buscar nome do arquivo...' class='filtro-pesquisa'>";
    echo "<select class='filtro-ano'>";
    echo "<option value=''>Ano</option>";
    for ($ano = (int)date('Y'); $ano >= 2020; $ano--) {
        echo "<option value='" . e($ano) . "'>" . e($ano) . "</option>";
    }
    echo "</select>";

    echo "<select class='filtro-mes'>";
    echo "<option value=''>Mês</option>";
    $meses = [
        '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
        '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
        '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
    ];
    foreach ($meses as $num => $nome) {
        echo "<option value='" . e($num) . "'>" . e($nome) . "</option>";
    }
    echo "</select>";
    echo "<button type='button' onclick=\"aplicarFiltros('" . e($containerId) . "')\">Filtrar</button>";
    echo "<button type='button' onclick=\"baixarTodos('" . e($containerId) . "')\">Baixar todos</button>";
    echo "<button type='button' onclick=\"limparFiltros('" . e($containerId) . "')\">Limpar</button>";
    echo "</div>";
    echo "</div>";

    echo "<div class='resultados'>";
    echo "<div class='bloco-documentos-scroll'>";

    if (count($lista) > 0) {
        foreach ($lista as $doc) {
            $data = $doc['data_upload'] ? strtotime($doc['data_upload']) : false;
            $ano = $data ? date('Y', $data) : '';
            $mes = $data ? date('m', $data) : '';
            $dataFormatada = $data ? date('d/m/Y H:i', $data) : '';
            $nomeFiltro = normalizarTexto($doc['nome']);
            $link = 'ver_documento.php?id=' . urlencode((string)$doc['id']);

            echo "<div class='documento item-documento' data-nome='" . e($nomeFiltro) . "' data-ano='" . e($ano) . "' data-mes='" . e($mes) . "'>";
            echo "<div>";
            echo "<strong>" . e($doc['nome']) . "</strong>";
            if ($dataFormatada) {
                echo "<small>Enviado em " . e($dataFormatada) . "</small>";
            }
            echo "</div>";
            echo "<a href='" . e($link) . "' target='_blank' class='link-documento download-link'>Abrir</a>";
            echo "</div>";
        }
    }

    echo "</div>";
    echo "<p class='sem-documento'" . (count($lista) > 0 ? " style='display:none;'" : "") . ">❌ Nenhum documento disponível</p>";
    echo "</div>";
    echo "</section>";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação do Cliente</title>
    <link rel="stylesheet" href="triarcasite.css">
    <link rel="stylesheet" href="triarca.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Open Sans', Arial, sans-serif;
            background-image: url('https://kainanabreudebrito1749612218483.0710236.meusitehostgator.com.br/triarca4.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #0d1a2f;
            padding-bottom: 70px;
        }

        .topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 8px 15px;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .logo img { width: 73px; display: block; }

        .nav-area {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-area ul {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-area a {
            text-decoration: none;
            color: #0d1a2f;
            font-weight: bold;
        }

        .nav-area li a {
            position: relative;
            padding-bottom: 4px;
        }

        .nav-area li a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 2px;
            width: 0;
            background: #0d1a2f;
            transition: width .25s ease;
        }

        .nav-area li a:hover::after { width: 100%; }

        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #1a2d4a;
            color: white !important;
            padding: 10px 13px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 16px;
            white-space: nowrap;
        }

        .document-container {
            max-width: 1250px;
            margin: 35px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
        }

        .menu-lateral {
            background: rgba(255,255,255,0.92);
            border-radius: 14px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.12);
            height: fit-content;
            position: sticky;
            top: 105px;
        }

        .toggle-btn {
            width: 100%;
            padding: 12px;
            margin-bottom: 8px;
            background-color: #1a2d4a;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            text-align: left;
            transition: .2s ease;
        }

        .toggle-btn:hover,
        .toggle-btn.active {
            background-color: #006cb7;
            transform: translateX(3px);
        }

        .conteudo-dinamico {
            min-width: 0;
        }

        .conteudo-item {
            display: none;
        }

        .conteudo-item.active {
            display: block;
        }

        .categoria-titulo {
            background: rgba(255,255,255,0.92);
            border-radius: 12px;
            padding: 18px 22px;
            margin: 0 0 18px;
            color: #004070;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .subcategoria-container {
            margin-bottom: 24px;
            background: rgba(255,255,255,0.92);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .subcategoria-container h3 {
            margin: 0 0 14px;
            color: #004070;
        }

        .filtros {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .filtros input,
        .filtros select,
        .filtros button,
        .admin-form input,
        .admin-form select {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .filtro-pesquisa { flex: 1; min-width: 180px; }

        button,
        .filtros button {
            background-color: #006cb7;
            color: white;
            cursor: pointer;
            font-weight: bold;
            border: none;
            transition: background .2s ease;
        }

        button:hover,
        .filtros button:hover { background-color: #00518c; }

        .resultados {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .bloco-documentos-scroll {
            max-height: 280px;
            overflow-y: auto;
            padding-right: 10px;
            margin-top: 10px;
        }

        .bloco-documentos-scroll::-webkit-scrollbar { width: 7px; }
        .bloco-documentos-scroll::-webkit-scrollbar-thumb {
            background-color: #b9c5d1;
            border-radius: 8px;
        }
        .bloco-documentos-scroll::-webkit-scrollbar-track { background: #eef2f5; }

        .documento {
            padding: 12px 0;
            border-top: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .documento small {
            display: block;
            margin-top: 4px;
            color: #627083;
        }

        .documento.oculto { display: none !important; }

        .link-documento {
            display: inline-block;
            background-color: #006cb7;
            color: #fff;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            text-decoration: none;
            transition: background .2s ease;
            white-space: nowrap;
        }

        .link-documento:hover { background-color: #00518c; }

        .sem-documento {
            color: #777;
            font-weight: 700;
            margin: 12px 0 0;
        }

        .painel-admin {
            background: #fff;
            padding: 24px;
            margin: 30px auto;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 1000px;
        }

        .admin-form label {
            display: block;
            margin: 12px 0 6px;
            font-weight: bold;
        }

        .admin-form input,
        .admin-form select { width: 100%; }

        .btn-enviar {
            margin-top: 18px;
            padding: 11px 20px;
            background-color: #004070;
            color: white;
            border-radius: 6px;
        }

        .tabela-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
            color: #004070;
        }

        footer {
            width: 100%;
            padding: 15px;
            text-align: center;
            color: #000;
            background-color: rgba(255,255,255,0.85);
            font-weight: bold;
            position: fixed;
            bottom: 0;
            left: 0;
            z-index: 40;
        }

        @media (max-width: 900px) {
            .topo, .nav-area, .nav-area ul { flex-wrap: wrap; }
            .document-container { grid-template-columns: 1fr; }
            .menu-lateral { position: static; }
        }

        @media (max-width: 600px) {
            .filtros { flex-direction: column; align-items: stretch; }
            .documento { align-items: flex-start; flex-direction: column; }
            .link-documento { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

<header class="topo">
    <div class="logo">
        <a href="/">
            <img src="https://images.builderservices.io/s/cdn/v1.0/i/m?url=https%3A%2F%2Fstorage.googleapis.com%2Fproduction-hostgator-brasil-v1-0-2%2F242%2F1981242%2FjmH1mMVt%2F0f8abd6a93fc41c896c22890d350434d&methods=resize%2C500%2C5000" alt="Logo Triarca">
        </a>
    </div>

    <nav class="nav-area">
        <ul>
            <li><a href="/">Início</a></li>
            <li><a href="/a-triarca">A Triarca</a></li>
            <li><a href="/soluções">Soluções</a></li>
            <li><a href="/valor-bruto">Valor Bruto</a></li>
            <li><a href="/contato">Contato</a></li>
            <li><a href="/triarca-conecta">Triarca Conecta</a></li>
        </ul>
        <a href="https://wa.me/5562999938223" target="_blank" class="btn-whatsapp">
            <i class="fab fa-whatsapp"></i> Fale Conosco
        </a>
    </nav>
</header>

<main class="document-container">
    <aside class="menu-lateral">
        <?php $index = 0; ?>
        <?php foreach ($categorias as $catKey => $catData): ?>
            <button type="button" class="toggle-btn <?= $index === 0 ? 'active' : '' ?>" data-target="conteudo-<?= e($catKey) ?>">
                <?= e($catData['titulo']) ?>
            </button>
            <?php $index++; ?>
        <?php endforeach; ?>

        <?php if ($is_admin): ?>
            <button type="button" class="toggle-btn" onclick="document.getElementById('painel-admin').scrollIntoView({behavior:'smooth'});">
                ⚙️ Admin
            </button>
        <?php endif; ?>
    </aside>

    <section class="conteudo-dinamico">
        <?php $index = 0; ?>
        <?php foreach ($categorias as $catKey => $catData): ?>
            <div class="conteudo-item <?= $index === 0 ? 'active' : '' ?>" id="conteudo-<?= e($catKey) ?>">
                <h2 class="categoria-titulo"><?= e($catData['titulo']) ?></h2>
                <?php foreach ($catData['subcategorias'] as $subKey => $subNome): ?>
                    <?php exibirDocumentos($documentos, $catKey, $subKey, $subNome); ?>
                <?php endforeach; ?>
            </div>
            <?php $index++; ?>
        <?php endforeach; ?>
    </section>
</main>

<?php if ($is_admin): ?>
<section id="painel-admin" class="painel-admin">
    <h2 style="color:#004070;">📤 Enviar documento para cliente</h2>

    <?php if (isset($_GET['sucesso'])): ?>
        <p style="color:green;"><strong>Documento enviado com sucesso.</strong></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="upload_documentos.php" class="admin-form">
        <label for="id_cliente">ID do cliente:</label>
        <input type="number" id="id_cliente" name="id_cliente" required>

        <label for="categoria_principal">Categoria principal:</label>
        <select id="categoria_principal" name="categoria_principal" required>
            <option value="">Selecione...</option>
            <?php foreach ($categorias as $catKey => $catData): ?>
                <option value="<?= e($catKey) ?>"><?= e($catData['titulo']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="categoria">Tipo de documento:</label>
        <select id="categoria" name="categoria" required>
            <option value="">Selecione a categoria principal primeiro</option>
        </select>

        <label for="ano">Ano:</label>
        <select id="ano" name="ano" required>
            <?php for ($i = 2023; $i <= (int)date('Y') + 1; $i++): ?>
                <option value="<?= e($i) ?>" <?= $i === (int)date('Y') ? 'selected' : '' ?>><?= e($i) ?></option>
            <?php endfor; ?>
        </select>

        <label for="mes">Mês:</label>
        <select id="mes" name="mes" required>
            <?php
            $mesesForm = [
                '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
                '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
                '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
            ];
            foreach ($mesesForm as $num => $nome): ?>
                <option value="<?= e($num) ?>" <?= $num === date('m') ? 'selected' : '' ?>><?= e($nome) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="arquivo">Arquivo:</label>
        <input type="file" id="arquivo" name="arquivo" required>

        <button type="submit" name="enviar_documento" class="btn-enviar">📎 Enviar Documento</button>
    </form>
</section>

<section class="painel-admin">
    <h2 style="color:#004070;">📂 Lista de Documentos Enviados</h2>

    <?php
    $stmtDocs = $mysqli->query("SELECT * FROM documentos ORDER BY user_id, id DESC");
    $docsPorCliente = [];
    if ($stmtDocs) {
        while ($row = $stmtDocs->fetch_assoc()) {
            $docsPorCliente[$row['user_id']][] = $row;
        }
    }
    ?>

    <?php if (empty($docsPorCliente)): ?>
        <p class="sem-documento">Nenhum documento enviado.</p>
    <?php else: ?>
        <?php foreach ($docsPorCliente as $clienteId => $documentosCliente): ?>
            <div style="margin-bottom: 40px;">
                <h3 style="color:#1a2d4a;">👤 Cliente ID: <?= e($clienteId) ?></h3>
                <div class="tabela-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Arquivo</th>
                                <th>Categoria</th>
                                <th>Data</th>
                                <th>Documento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentosCliente as $doc): ?>
                                <?php
                                $nome = $doc['nome_arquivo'] ?? ($doc['titulo'] ?? 'Documento');
                                $data = !empty($doc['data_upload']) ? date('d/m/Y H:i', strtotime($doc['data_upload'])) : '-';
                                ?>
                                <tr>
                                    <td><?= e($doc['id']) ?></td>
                                    <td><?= e($nome) ?></td>
                                    <td><?= e(($doc['categoria_principal'] ?? '') . ' / ' . ($doc['categoria'] ?? '')) ?></td>
                                    <td><?= e($data) ?></td>
                                    <td>
                                        <a href="ver_documento.php?id=<?= urlencode((string)$doc['id']) ?>" target="_blank" style="color:green;font-weight:bold;">Ver documento</a>
                                    </td>
                                    <td>
                                        <form method="POST" action="excluir_documento.php" style="display:inline;">
                                            <input type="hidden" name="id_documento" value="<?= e($doc['id']) ?>">
                                            <button type="submit" onclick="return confirm('Deseja excluir este documento?')" style="color:red;background:none;border:none;cursor:pointer;font-weight:bold;">
                                                🗑 Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php endif; ?>

<footer>
    © 2025 Triarca Soluções Condominiais
</footer>

<script>
const categorias = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function normalizarTexto(str) {
    return (str || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]/g, '');
}

function aplicarFiltros(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const busca = normalizarTexto(container.querySelector('.filtro-pesquisa')?.value || '');
    const ano = container.querySelector('.filtro-ano')?.value || '';
    const mes = container.querySelector('.filtro-mes')?.value || '';
    const documentos = container.querySelectorAll('.documento');
    const mensagem = container.querySelector('.sem-documento');

    let visiveis = 0;

    documentos.forEach(doc => {
        const nome = doc.dataset.nome || '';
        const docAno = doc.dataset.ano || '';
        const docMes = doc.dataset.mes || '';

        const matchNome = !busca || nome.includes(busca);
        const matchAno = !ano || docAno === ano;
        const matchMes = !mes || docMes === mes;
        const mostrar = matchNome && matchAno && matchMes;

        doc.classList.toggle('oculto', !mostrar);
        if (mostrar) visiveis++;
    });

    if (mensagem) {
        mensagem.style.display = visiveis === 0 ? 'block' : 'none';
    }
}

function limparFiltros(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const busca = container.querySelector('.filtro-pesquisa');
    const ano = container.querySelector('.filtro-ano');
    const mes = container.querySelector('.filtro-mes');

    if (busca) busca.value = '';
    if (ano) ano.value = '';
    if (mes) mes.value = '';

    aplicarFiltros(containerId);
}

function baixarTodos(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const links = container.querySelectorAll('.documento:not(.oculto) .download-link');

    if (links.length === 0) {
        alert('Nenhum documento visível para baixar.');
        return;
    }

    links.forEach((link, index) => {
        const a = document.createElement('a');
        a.href = link.href;
        a.download = '';
        a.style.display = 'none';
        document.body.appendChild(a);

        setTimeout(() => {
            a.click();
            a.remove();
        }, index * 250);
    });
}

function ativarMenuLateral() {
    const botoes = document.querySelectorAll('.menu-lateral .toggle-btn[data-target]');
    const conteudos = document.querySelectorAll('.conteudo-item');

    botoes.forEach(botao => {
        botao.addEventListener('click', () => {
            const target = botao.dataset.target;

            botoes.forEach(b => b.classList.remove('active'));
            botao.classList.add('active');

            conteudos.forEach(item => {
                item.classList.toggle('active', item.id === target);
            });
        });
    });
}

function ativarFiltrosAutomaticos() {
    document.querySelectorAll('.subcategoria-container').forEach(container => {
        const id = container.id;
        container.querySelectorAll('.filtro-pesquisa, .filtro-ano, .filtro-mes').forEach(campo => {
            campo.addEventListener('input', () => aplicarFiltros(id));
            campo.addEventListener('change', () => aplicarFiltros(id));
        });
    });
}

function ativarSelectAdmin() {
    const principal = document.getElementById('categoria_principal');
    const categoria = document.getElementById('categoria');

    if (!principal || !categoria) return;

    principal.addEventListener('change', () => {
        const selecionada = principal.value;
        categoria.innerHTML = '<option value="">Selecione...</option>';

        if (!categorias[selecionada]) return;

        Object.entries(categorias[selecionada].subcategorias).forEach(([valor, texto]) => {
            const opt = document.createElement('option');
            opt.value = valor;
            opt.textContent = texto;
            categoria.appendChild(opt);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    ativarMenuLateral();
    ativarFiltrosAutomaticos();
    ativarSelectAdmin();
});
</script>

</body>
</html>
