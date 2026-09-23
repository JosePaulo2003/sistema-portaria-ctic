# SGRP — Sistema de Gestão de Recursos de Portaria

Sistema web do CTIC/CESIT para controle de usuários, permissões, reservas de salas, retirada de chaves e itens e atendimento da Portaria.

## Recursos atuais

- Autenticação e painéis específicos por perfil.
- Cadastro manual e solicitações de novos usuários via Google Forms/webhook.
- Aprovação de solicitação com envio das credenciais por e-mail.
- Recuperação de senha por código de uso único enviado por e-mail.
- Sessão persistente, sem encerramento por mudança de IP, Wi-Fi ou atualização do navegador.
- Reserva comum, recorrente e acadêmica de salas, com calendário de disponibilidade.
- Controle de retirada e devolução de chaves e itens.
- Solicitação obrigatória de permissão de chave ao CTIC/CESIT.
- Confirmação pela Portaria com senha temporária para aluno, bolsista e estagiário.
- Retirada automática para os demais perfis e fluxo próprio para Serviços Gerais.
- Alertas de retirada minimizáveis e persistentes até aceitação, recusa ou fechamento.
- Lista de retiradas agrupada dinamicamente por perfil.
- Autorizações de bolsistas/estagiários por professor e período.
- Advertências, bloqueios, relatórios e logs de auditoria.
- Guias contextuais e interface responsiva.

## Tecnologias e requisitos

- PHP 8.1 ou superior, com `pdo_mysql`, `mbstring`, `openssl` e `sockets`.
- MySQL ou MariaDB.
- Apache com `mod_rewrite`.
- Navegador moderno.
- Servidor SMTP com TLS para os e-mails transacionais.

O projeto usa PHP puro, MVC simples, PDO, HTML, CSS e JavaScript, sem dependência obrigatória do Composer.

## Instalação

1. Coloque o projeto no diretório servido pelo Apache.
2. Copie `.env.example` para `.env`.
3. Configure aplicação, banco, webhook e SMTP no `.env`.
4. Importe `database/schema.sql` e `database/seeds.sql`.
5. Crie o primeiro usuário Desenvolvedor pelo script CLI.

```bash
cp .env.example .env
mysql --default-character-set=utf8mb4 -u sgrp_user -p sgrp < database/schema.sql
mysql --default-character-set=utf8mb4 -u sgrp_user -p sgrp < database/seeds.sql
php scripts/create_developer_user.php "Desenvolvedor" "admin@example.local" "use-uma-senha-forte-com-12-caracteres"
```

`database/mysql.sql` contém estrutura e dados iniciais em um único arquivo. Os seeds não criam usuário ou senha padrão.

## Configuração

Variáveis principais do `.env`:

- `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_BASE_PATH` e `APP_URL`.
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` e `DB_TIMEZONE`.
- `FORM_WEBHOOK_TOKEN` para as integrações de cadastro.
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME` e `MAIL_PASSWORD`.
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` e `MAIL_TIMEOUT`.

Use senha de aplicativo ou credencial SMTP exclusiva. Nunca coloque valores reais no `.env.example`.

## Atualização de bancos existentes

As migrações incrementais estão em `database/patch_*.sql`. Os scripts correspondentes em `scripts/migrar_*.php` carregam as credenciais do `.env` e preservam os dados existentes.

Migrações mais recentes:

- retirada por pessoa sem cadastro;
- autorizações de bolsistas e estagiários;
- recuperação de senha por e-mail;
- recuperação por código com limite de tentativas.

Faça backup do banco e dos arquivos antes de aplicar migrações em produção.

## Segurança

- `.env`, uploads, logs, sessões, backups e dumps reais são ignorados pelo Git.
- Senhas são armazenadas com `password_hash`.
- Códigos de recuperação ficam no banco somente como hash, expiram em 30 minutos e aceitam até cinco tentativas.
- Formulários POST usam CSRF e consultas usam prepared statements.
- Cookies autenticados usam `HttpOnly` e `SameSite`.
- A sessão é regenerada no login.
- O SMTP usa TLS com validação do certificado.

O envio da senha inicial por e-mail faz parte do fluxo operacional atual. Recomenda-se que o usuário a altere após o primeiro acesso.

## Estrutura

- `app/`: controllers, models, services, views, helpers e núcleo MVC.
- `config/`: bootstrap, segurança, sessão e configurações.
- `database/`: estrutura, seeds e migrações SQL.
- `docs/`: documentação funcional e UML.
- `public/`: CSS, JavaScript, imagens e uploads protegidos.
- `routes/`: mapa de rotas HTTP.
- `scripts/`: migrações, verificações e integrações.

## Manutenção

Leia `docs/GUIA_DESENVOLVEDOR.md` antes de alterar fluxos de autenticação,
permissão, retirada, reserva ou e-mail. O guia reúne o mapa dos módulos,
decisões difíceis e roteiros de diagnóstico; os arquivos de código também têm
comentários de manutenção próximos aos pontos mais sensíveis.

## Publicação

Antes de enviar alterações ao GitHub:

```bash
git status
git diff --check
find app config routes scripts -name '*.php' -print0 | xargs -0 -n1 php -l
```

Não copie o `.env` nem dados de produção para o repositório.
