# Recuperação de senha por e-mail

O SGRP envia um código individual de redefinição, válido por 30 minutos e utilizável uma única vez. A senha nunca é enviada por e-mail e o código original não é armazenado no banco.

## Configuração SMTP

Acrescente ao `.env` da aplicação:

```dotenv
APP_URL=http://sgrp
MAIL_HOST=smtp.exemplo.br
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=conta@exemplo.br
MAIL_PASSWORD="senha-ou-senha-de-aplicativo"
MAIL_FROM_ADDRESS=conta@exemplo.br
MAIL_FROM_NAME="SGRP - CTIC/CESIT"
MAIL_TIMEOUT=15
```

`APP_URL` deve ser o endereço pelo qual os usuários realmente acessam o sistema, sem barra no final. Em servidores que atendem por HTTPS, use obrigatoriamente `https://`.

Valores aceitos em `MAIL_ENCRYPTION`:

- `tls`: STARTTLS, normalmente na porta 587;
- `ssl`: TLS desde a conexão, normalmente na porta 465;
- `none`: sem criptografia, apenas para um relay interno confiável.

Para Gmail ou Microsoft 365, use uma senha de aplicativo ou credencial SMTP própria. Não reutilize a senha pessoal da conta institucional.

## Proteções aplicadas

- resposta igual para e-mail existente ou inexistente;
- no máximo 3 solicitações por usuário e 10 por IP a cada hora;
- código aleatório de 8 caracteres, armazenado somente como SHA-256;
- expiração em 30 minutos e invalidação após o uso;
- no máximo cinco tentativas por código;
- um novo pedido invalida códigos anteriores;
- troca de senha e consumo do token dentro da mesma transação;
- TLS com validação do certificado do servidor SMTP.
