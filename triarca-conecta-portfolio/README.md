# Triarca Conecta

Portal de gerenciamento documental desenvolvido em **PHP + MySQL**, com autenticação de usuários, painel administrativo, upload, visualização, filtro e exclusão de documentos por cliente.

## Preview

![Preview do Triarca Conecta](docs/mockup-site.png)

## Funcionalidades

- Login e logout de usuários
- Controle de acesso por administrador
- Upload de documentos por cliente
- Organização por categorias e subcategorias
- Filtros por nome do arquivo, ano e mês
- Visualização segura de documentos
- Exclusão de documentos pelo painel administrativo
- Layout responsivo com menu lateral

## Tecnologias utilizadas

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- Font Awesome

## Estrutura do projeto

```text
triarca-conecta-github/
├── index.php
├── login.php
├── logout.php
├── upload_documentos.php
├── ver_documento.php
├── excluir_documento.php
├── conexao.example.php
├── database.sql
├── css/
├── js/
├── docs/
└── uploads/
```

## Como rodar localmente

1. Importe o banco:

```bash
mysql -u root -p < database.sql
```

2. Copie o arquivo de exemplo da conexão:

```bash
cp conexao.example.php conexao.php
```

3. Edite o `conexao.php` com os dados do seu banco local.

4. Gere uma senha criptografada para o usuário admin, se precisar:

```bash
php -r "echo password_hash('123456', PASSWORD_DEFAULT);"
```

5. Inicie o servidor local:

```bash
php -S localhost:8000
```

6. Abra no navegador:

```text
http://localhost:8000/login.php
```

## Segurança

O arquivo `conexao.php` não deve ser enviado ao GitHub, pois pode conter dados reais do banco. Por isso, ele está no `.gitignore`.

Use somente o arquivo `conexao.example.php` como modelo público.

## Observação

Este projeto foi desenvolvido como um portal documental corporativo, podendo ser adaptado para condomínios, empresas, clientes e áreas administrativas.
