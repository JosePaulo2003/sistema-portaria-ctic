# SGRP - Sistema de Gestão de Recursos Pedagógicos

Sistema web em PHP puro, MVC simples, PDO e MySQL/MariaDB, compatível com XAMPP e caminho base `/sgrp`.

## Requisitos

- XAMPP com Apache, PHP 8.1+ e MySQL/MariaDB
- Extensão PDO MySQL habilitada
- Navegador moderno

## Instalação no XAMPP

1. Coloque este projeto em `C:\xampp\htdocs\sgrp`.
2. Crie o banco e as tabelas importando `database/schema.sql` no phpMyAdmin ou no terminal MySQL.
3. Importe os dados iniciais de `database/seeds.sql`.
4. Copie `.env.example` para `.env` e ajuste usuário/senha do banco, se necessário.
5. Acesse `http://localhost/sgrp`.

## Banco de dados

Banco padrão: `sgrp`

Arquivos:

- `database/schema.sql`: estrutura completa.
- `database/seeds.sql`: perfis, usuários demo, salas, itens, período, curso e disciplina.
- `database/create_demo_users.php`: gera um novo hash para a senha demo, se necessário.

No terminal do XAMPP, prefira importar com charset explícito:

```powershell
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source C:/xampp/htdocs/sgrp/database/schema.sql"
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source C:/xampp/htdocs/sgrp/database/seeds.sql"
```

## Usuários demo

Senha padrão para todos: `12345678`

- `desenvolvedor@sgrp.local`
- `administrativo@sgrp.local`
- `secretario@sgrp.local`
- `portaria@sgrp.local`
- `professor@sgrp.local`
- `bolsista@sgrp.local`
- `aluno@sgrp.local`
- `visitante@sgrp.local`
- `servicos@sgrp.local`

## UTF-8

Todos os arquivos foram criados em UTF-8. O banco usa `utf8mb4_unicode_ci`, o `index.php` envia `Content-Type: text/html; charset=UTF-8` e as páginas declaram `<meta charset="UTF-8">`.

## Observações

- As URLs usam `baseUrl()` dinâmica e não fixam `localhost`.
- O layout autenticado usa top bar horizontal, sem menu lateral.
- Todos os formulários POST incluem CSRF.
- Consultas usam PDO com prepared statements para entrada do usuário.
- Cards e menus são renderizados conforme o perfil autenticado.
