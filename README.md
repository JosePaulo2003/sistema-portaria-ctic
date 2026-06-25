# SGRP - Sistema de Gestão de Recursos Pedagógicos

Sistema web em PHP puro, MVC simples, PDO e MySQL/MariaDB, compatível com XAMPP, Apache em Linux e caminho base configurável.

## Requisitos

- PHP 8.1+
- Apache com `mod_rewrite`
- MySQL ou MariaDB
- Extensão PDO MySQL habilitada
- Navegador moderno

## Instalação

1. Coloque este projeto no diretório do Apache.
2. Copie `.env.example` para `.env`.
3. Ajuste as credenciais do banco no `.env`.
4. Importe `database/schema.sql`.
5. Importe `database/seeds.sql`.
6. Acesse o endereço configurado no Apache.

No XAMPP:

```powershell
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source C:/xampp/htdocs/sgrp/database/schema.sql"
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source C:/xampp/htdocs/sgrp/database/seeds.sql"
```

Em Linux:

```bash
mysql --default-character-set=utf8mb4 -u sgrp_user -p sgrp < database/schema.sql
mysql --default-character-set=utf8mb4 -u sgrp_user -p sgrp < database/seeds.sql
```

## Banco de Dados

Banco padrão: `sgrp`

Arquivos:

- `database/schema.sql`: estrutura completa do banco.
- `database/seeds.sql`: perfis do sistema, configuração inicial e um único usuário Desenvolvedor.
- `database/mysql.sql`: estrutura completa junto com o seed mínimo.

## Usuário Inicial

O banco inicial cria somente um usuário Desenvolvedor.

- E-mail: `desenvolvedor@sgrp.local`
- Senha provisória: `Sgrp@2026!Trocar`

Troque a senha imediatamente após o primeiro acesso.

## Segurança

- Não versionar `.env`.
- Não versionar uploads, logs, sessões, backups ou dumps reais.
- Todos os formulários POST usam CSRF.
- Consultas usam PDO com prepared statements.
- A sessão autenticada é protegida contra reaproveitamento indevido.

## Observações

- As URLs usam `baseUrl()` dinâmica e não fixam `localhost`.
- O layout autenticado usa top bar horizontal.
- Cards e menus são renderizados conforme o perfil autenticado.
