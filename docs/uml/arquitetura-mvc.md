# Arquitetura MVC

```mermaid
flowchart TB
    Browser["Navegador"]
    Entry["index.php"]
    Router["Router"]
    Controllers["Controllers"]
    Services["Servicos de dominio"]
    Models["Models"]
    Views["Views"]
    Database["MySQL/MariaDB"]
    SMTP["Servidor SMTP"]
    Helpers["Helpers e seguranca"]

    Browser --> Entry
    Entry --> Router
    Router --> Controllers
    Controllers --> Services
    Controllers --> Models
    Controllers --> Views
    Controllers --> Helpers
    Services --> Models
    Services --> SMTP
    Models --> Database
    Views --> Browser
```
