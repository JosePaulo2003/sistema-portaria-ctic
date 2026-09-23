# Guia de sobrevivência do próximo desenvolvedor

Este documento explica onde o SGRP guarda suas regras, quais partes exigem mais cuidado e como investigar problemas sem transformar produção em ambiente de testes involuntário. Os comentários no código complementam este guia; eles explicam o papel de cada arquivo e marcam as decisões menos óbvias.

## Comece por aqui

O SGRP usa PHP puro, MVC simples, PDO, MySQL/MariaDB, HTML, CSS e JavaScript. O ponto de entrada é `index.php`, a inicialização fica em `config/bootstrap.php` e as rotas estão em `routes/web.php`.

O fluxo normal é:

1. Apache encaminha a URL para `index.php`.
2. O bootstrap carrega segurança, sessão, banco, helpers e autoload.
3. O router encontra a rota e valida CSRF em todo POST comum.
4. O controller valida perfil e entrada.
5. Models consultam ou persistem dados; Services executam regras que atravessam mais de uma entidade.
6. A view recebe dados prontos e renderiza a resposta.

Se uma view começou a consultar banco, um model começou a emitir HTML ou um controller ganhou quinhentas linhas novas, a arquitetura não "ficou flexível". Ela só parou de reclamar em voz alta.

## Mapa dos diretórios

`app/Controllers`: recebe requisições, exige perfil, valida entrada e escolhe a resposta.

`app/Models`: concentra SQL e persistência. Use prepared statements; concatenar valor do usuário em SQL continua sendo uma péssima ideia mesmo quando o valor "parece confiável".

`app/Services`: regras de domínio com várias etapas, como SMTP, documento DOCX e confirmação de retirada.

`app/Views`: apresentação. As views podem formatar dados, mas não devem inventar autorização.

`app/Core`: infraestrutura do MVC. Uma alteração aqui afeta o sistema inteiro; teste como tal.

`app/Helpers/functions.php`: funções transversais de segurança, perfil, datas, auditoria e interface. Antes de criar outro helper quase igual, procure aqui — a duplicação provavelmente já tentou essa carreira.

`config`: ambiente, banco, sessão, CSP e bootstrap.

`database`: schema completo, seeds sem usuário padrão e patches incrementais.

`public`: CSS, JavaScript e imagens públicas.

`scripts`: migrações e tarefas operacionais executadas por CLI.

## Áreas difíceis e por que são difíceis

### Retirada de chaves

A regra principal está em `app/Services/RetiradaChaveService.php`.

Aluno, Aluno Bolsista e Estagiário usam solicitação própria e confirmação da Portaria. Outros perfis autorizados usam o fluxo automático. Serviços Gerais mantém seu fluxo específico.

O alerta guarda contexto em JSON. Isso permite evoluir sem criar uma coluna para cada detalhe, mas exige leitura tolerante a campos ausentes. Alertas antigos não são atualizados magicamente quando o PHP muda — infelizmente o banco ainda não acompanha reuniões de requisito.

A confirmação usa transação e `SELECT ... FOR UPDATE` para impedir duas entregas simultâneas da mesma chave. Não remova locks para "melhorar desempenho" sem um teste de concorrência que prove a segurança.

O campo manual “Quem retirou” exclui os três perfis com fluxo próprio. A restrição real deve existir no servidor; esconder opção no HTML é apenas decoração defensiva.

### Perfis e variações de texto

Use `comparableProfile()` ao comparar nomes de perfil. O banco possui histórico com acentos e codificações diferentes. Comparar texto cru recria bugs em que “Estagiario”, “Estagiário” e texto corrompido parecem pessoas juridicamente distintas para o PHP.

A fonte de verdade dos perfis com fluxo próprio fica em `User::perfilUsaFluxoProprioChave()`. Acrescente novos perfis ali e teste todos os seletores da Portaria.

### Permissões de sala

`PermissaoSala::usuarioTemAcesso()` combina situação, sala específica ou acesso total, início, expiração, dia da semana e janela de horário.

Há tratamento próprio para horários que atravessam meia-noite. Um acesso das 22h às 02h não cabe em um `BETWEEN` comum. O relógio não está errado só porque a consulta gostaria que o dia terminasse mais cedo.

`usuarioTemChaveAtribuida()` controla visibilidade da área; `usuarioTemAcesso()` decide autorização efetiva. Não una os dois comportamentos sem revisar todas as telas.

### Recuperação de senha

O código enviado por e-mail é armazenado como SHA-256, expira, tem uso único e limite de tentativas. A redefinição bloqueia a linha com `FOR UPDATE` e invalida todos os códigos pendentes depois da troca.

Nunca registre código, senha nova ou credencial SMTP em log. Para diagnosticar, registre etapa, destinatário mascarado e código de resposta do servidor.

### SMTP

`EmailService` implementa o protocolo diretamente por socket. A ordem `EHLO`, `STARTTLS`, novo `EHLO`, autenticação, envelope e `DATA` é importante.

O serviço valida certificado TLS e protege cabeçalhos contra quebras de linha. Se o e-mail cair em spam, verifique SPF, DKIM, DMARC, DNS reverso, reputação do domínio e conteúdo. Adicionar mais exclamações ao assunto não é estratégia de entregabilidade, embora seja uma estratégia de desespero bastante popular.

### Sessões e proxy

`config/session.php` mantém retenção longa no servidor, mas usa cookie de sessão. Navegadores ainda podem encerrar o cookie.

`X-Forwarded-Proto` só é confiável quando vem de proxy controlado. Se a aplicação passar a ficar diretamente exposta, revise a confiança nesses cabeçalhos em `session.php` e `security.php`.

### Reservas e recorrência

Conflitos precisam ser verificados para cada ocorrência gerada. Teste limites exatos: início igual ao fim de outra reserva, virada de dia, data passada e recorrência longa.

Criação em lote deve permanecer transacional. Uma série com nove reservas salvas e a décima falhando não é "quase sucesso"; é uma agenda sabotada com progresso mensurável.

### Frontend

`public/js/app.js` depende de atributos `data-*` presentes nas views. Ao renomear um atributo, procure o nome no projeto inteiro.

Os alertas de retirada são atualizados por polling e usam uma assinatura do estado para evitar reconstrução inútil do DOM. Campo novo que muda o card precisa entrar nessa assinatura.

Os estilos são carregados em camadas. `usability-refresh.css` vem depois e pode sobrescrever regras de `app.css`. Antes de usar `!important`, confira especificidade, ordem e estado responsivo. Dívida CSS também cobra juros; só prefere recebê-los em pixels.

## Diagnóstico por sintoma

### Página em branco ou HTTP 500

1. Rode `php -l` no arquivo alterado.
2. Confira `storage/logs` e o log do Apache.
3. Verifique permissões de leitura do `.env` para o usuário do Apache.
4. Confirme extensões PHP exigidas pelo recurso.
5. Não habilite `APP_DEBUG` publicamente para "ver melhor". Atacantes também enxergam melhor.

### Formulário volta sem salvar

1. Confira método e rota em `routes/web.php`.
2. Verifique presença do `csrfField()` ou campo `_csrf`.
3. Confira `requireProfile()` no controller.
4. Inspecione mensagens flash e log do sistema.
5. Valide nomes dos inputs contra as chaves lidas em `$_POST`.

### Usuário aparece em lista indevida

1. Descubra qual array o controller envia à view.
2. Corrija a consulta/filtro no model, não apenas o `<option>`.
3. Replique a validação na ação POST.
4. Procure outros consumidores do mesmo método.
5. Teste variações de acento com `comparableProfile()`.

Esse roteiro existe porque esconder só no HTML já falhou antes. O bug deixou documentação; seria deselegante ignorá-la.

### Chave aparece disponível quando não deveria

1. Consulte movimentações abertas para a sala.
2. Consulte alertas pendentes para a mesma sala.
3. Verifique bloqueio do usuário.
4. Valide permissão, data, dia e horário.
5. Procure falha entre `beginTransaction()` e `commit()`.

### E-mail não chega

1. Teste DNS e porta SMTP a partir do servidor.
2. Confira TLS e certificado.
3. Confirme remetente e autenticação.
4. Consulte resposta SMTP sem registrar senha.
5. Verifique SPF, DKIM e DMARC.
6. Confira spam e reputação antes de culpar o PHP por toda a infraestrutura mundial de e-mail.

## Verificações antes de entregar

Execute:

```bash
find app config routes scripts -name '*.php' -print0 | xargs -0 -n1 php -l
node --check public/js/app.js
node --check public/js/guide.js
php scripts/verificar_seguranca.php
git diff --check
git status
```

Para mudanças de banco, faça backup, aplique o patch em cópia de teste e verifique schema e dados. Nunca use `database/schema.sql` em produção existente: ele recria tabelas. A palavra `DROP` não ganha delicadeza só porque está num arquivo bem identado.

## Regras de manutenção

Não versione `.env`, uploads, sessões, logs, backups ou dumps reais.

Não crie senha padrão em seed.

Não desative CSRF para consertar formulário.

Não use GET para ação destrutiva.

Não altere perfil apenas na interface; valide no servidor.

Não envie e-mail ou grave movimentação antes de validar toda a operação.

Não faça deploy sem backup e teste proporcional ao risco.

Não confunda ausência de erro na tela com sucesso. Às vezes o erro só foi discreto, o que é uma qualidade social e um defeito operacional.

## Onde registrar uma decisão nova

Regra de domínio: comentário próximo ao código e atualização deste guia.

Mudança de banco: novo patch incremental e registro no `CHANGELOG.md`.

Novo perfil: seeds, navegação, `requireProfile()`, filtros, rotas e documentação.

Novo e-mail: template texto e HTML, proteção de cabeçalho e teste de entregabilidade.

Novo comportamento visual: atributo `data-*` documentado no HTML e JavaScript.

O objetivo dos comentários é preservar o motivo, não narrar a sintaxe. O código já diz o que faz; o próximo desenvolvedor precisa saber por que faz assim e qual dragão acorda se mudar.
