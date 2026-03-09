# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {YOUR_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

## Autenticação

Esta API usa **Laravel Sanctum** para autenticação baseada em tokens Bearer.

### Como Obter um Token
1. Faça login através do endpoint `POST /api/auth/login` com suas credenciais
2. O token será retornado na resposta do login
3. Use este token nas requisições subsequentes

### Como Usar o Token
Inclua o token no header `Authorization` de todas as requisições protegidas:
```
Authorization: Bearer {seu_token_aqui}
```

### Segurança
- Os tokens não expiram automaticamente, mas podem ser revogados
- Use `POST /api/auth/logout` para revogar o token atual
- Mantenha seu token seguro e não o compartilhe
- Em caso de comprometimento, faça logout e gere um novo token

### Rate Limiting
- **Login**: Limitado a 5 tentativas por minuto
- **API Geral**: Limitado a 60 requisições por minuto por IP
- **Usuário Autenticado**: Limitado a 1000 requisições por hora por usuário
