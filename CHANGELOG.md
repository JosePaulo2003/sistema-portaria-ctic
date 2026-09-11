# Histórico de alterações

## 2026-09-11 — correção da Portaria

- Remoção de alunos, bolsistas e estagiários dos seletores de permissão manual da Portaria.
- Remoção desses três perfis do campo “Quem retirou” no registro manual, com os demais usuários agrupados por perfil.
- Bloqueio equivalente no servidor para impedir cadastro ou alteração por requisição direta.
- Organização dos usuários e das permissões por perfil.
- Permissões antigas desses perfis permanecem disponíveis somente para consulta e revogação.

## 2026-09-11

- Sincronização do código atual da VM com o repositório.
- Reforço do fluxo de permissão e retirada de chaves.
- Confirmação por senha temporária para aluno, bolsista e estagiário.
- Exceção operacional para Serviços Gerais.
- Alertas persistentes e minimizáveis na Portaria.
- Agrupamento dinâmico das retiradas por perfil.
- Autorizações de bolsistas e estagiários por professor.
- Melhorias de usabilidade, responsividade, calendários e guias.
- Sessões persistentes sem vínculo com IP ou User-Agent.
- Envio de credenciais ao aprovar solicitações de usuários.
- Recuperação de senha por código enviado via SMTP.
- Proteção do código com expiração, uso único e limite de tentativas.
- Novas migrações e documentação de configuração SMTP.
- Remoção de usuário e senha padrão dos seeds públicos.
