# Fluxo de chave

```mermaid
stateDiagram-v2
    [*] --> Disponivel
    Disponivel --> Reservada: reserva aprovada
    Disponivel --> Retirada: retirada registrada
    Reservada --> Retirada: usuario retira
    Retirada --> Devolvida: portaria registra devolucao
    Retirada --> Advertida: devolucao irregular
    Advertida --> Bloqueada: regra configurada
    Devolvida --> Disponivel
    Bloqueada --> Disponivel: prazo encerrado
```
