# SGRP - Sistema de Gestao de Recursos de Portaria

Sistema web em PHP para controle de portaria, reservas de salas, retirada de chaves, movimentacao de itens e apoio aos fluxos administrativos do CTIC/CESIT.

## Principais recursos

- Autenticacao por perfil de acesso.
- Painel por area: portaria, secretaria, professor, administrativo, direcao, bolsista, aluno e visitante.
- Reserva e consulta de salas.
- Controle de retirada e devolucao de chaves.
- Controle de retirada de itens da portaria.
- Autorizacoes, advertencias, bloqueios e logs de auditoria.
- Integracao opcional com Google Forms via webhook token.

## Tecnologias

- PHP 8.1+
- MySQL ou MariaDB
- PDO com prepared statements
- Apache com `mod_rewrite`
- HTML, CSS e JavaScript sem framework obrigatorio

## Instalacao

1. Copie o projeto para o diretorio servido pelo Apache.
2. Copie `.env.example` para `.env`.
3. Ajuste as credenciais do banco e o `APP_BASE_PATH`, se necessario.
4. Importe a estrutura do banco.
5. Importe os dados iniciais.
6. Crie o primeiro usuario Desenvolvedor pelo script CLI.

Exemplo no XAMPP:

```powershell
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source C:/xampp/htdocs/sgrp/database/schema.sql"
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source C:/xampp/htdocs/sgrp/database/seeds.sql"
C:\xampp\php\php.exe scripts/create_developer_user.php "Desenvolvedor" "admin@example.local" "troque-por-uma-senha-forte"
```

Exemplo em Linux:

```bash
mysql --default-character-set=utf8mb4 -u sgrp_user -p sgrp < database/schema.sql
mysql --default-character-set=utf8mb4 -u sgrp_user -p sgrp < database/seeds.sql
php scripts/create_developer_user.php "Desenvolvedor" "admin@example.local" "troque-por-uma-senha-forte"
```

## Configuracao

As configuracoes locais ficam no arquivo `.env`, que nao deve ser versionado.

Variaveis principais:

- `APP_NAME`: nome exibido pelo sistema.
- `APP_ENV`: ambiente atual, como `local` ou `production`.
- `APP_DEBUG`: habilita ou desabilita mensagens de debug.
- `APP_BASE_PATH`: subdiretorio da aplicacao quando instalada fora da raiz do host.
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: conexao com o banco.
- `FORM_WEBHOOK_TOKEN`: token usado por integracoes externas.

## Banco de Dados

- `database/schema.sql`: estrutura completa do banco.
- `database/seeds.sql`: perfis e configuracoes iniciais.
- `database/mysql.sql`: estrutura e seed minimo em um unico arquivo.

Os seeds nao criam senha padrao. O primeiro usuario deve ser criado pelo script `scripts/create_developer_user.php`.

## Seguranca

- Nao versionar `.env`, logs, sessoes, backups, dumps reais ou uploads de usuarios.
- Trocar senhas de banco e usuarios antes de publicar ou demonstrar em ambiente real.
- Os formularios POST usam protecao CSRF.
- As consultas usam PDO com prepared statements.
- Acesso a `verificar_banco.php` e restrito a ambiente local ou debug.

## Estrutura

- `app/`: controllers, models, views, helpers e core MVC.
- `config/`: bootstrap, seguranca, sessao e banco.
- `database/`: scripts SQL de instalacao.
- `public/`: assets publicos e uploads controlados.
- `routes/`: rotas web.
- `scripts/`: utilitarios de setup e integracao.
