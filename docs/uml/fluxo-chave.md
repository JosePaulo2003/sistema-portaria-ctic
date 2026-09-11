# Fluxo de retirada e devolucao de chave

Alunos, estagiarios e bolsistas iniciam a solicitacao no proprio acesso. A Portaria
recebe um alerta persistente, que pode ser minimizado, e aceita ou recusa a entrega.
Para esses perfis, o codigo simples de quatro digitos fica visivel tanto para o
solicitante quanto para o agente. Os demais perfis autorizados usam retirada
automatica. Servicos Gerais mantem o fluxo proprio de trabalho.

A Portaria nao cadastra nem edita manualmente permissoes de Aluno, Aluno Bolsista
ou Estagiario. Esses perfis nao aparecem nos seletores do agente e seguem o fluxo
proprio de solicitacao/autorizacao; permissoes antigas ficam disponiveis apenas
para consulta ou revogacao.

```mermaid
sequenceDiagram
    actor Usuario
    participant Sistema
    participant Portaria
    participant Banco

    Usuario->>Sistema: solicita retirada de chave
    Sistema->>Banco: verifica permissoes_salas e bloqueios_chaves
    alt sem permissao ou bloqueado
        Sistema-->>Usuario: retirada negada
    else aluno, estagiario ou bolsista
        Sistema->>Banco: grava alerta e codigo temporario (10 min)
        Sistema-->>Usuario: exibe codigo de 4 digitos
        Sistema-->>Portaria: exibe alerta persistente e o mesmo codigo
        opt agente minimiza o alerta
            Portaria->>Sistema: minimiza sem encerrar
        end
        alt agente aceita
            Portaria->>Banco: registra retirada e encerra alerta
        else agente recusa
            Portaria->>Banco: registra recusa e encerra alerta
        end
    else demais perfis autorizados
        Sistema->>Banco: registra retirada automaticamente
    end
    Sistema-->>Portaria: agrupa retiradas abertas por perfil
    Portaria->>Banco: registra devolucao da chave
    alt devolucao irregular
        Portaria->>Banco: registra advertencia
        Banco->>Banco: cria bloqueio quando aplicavel
    end
    Sistema-->>Usuario: fluxo encerrado
```
