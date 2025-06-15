# GitHub MCP Configuration - Setup Completo

## ✅ Status da Configuração

### Componentes Instalados
- ✅ GitHub MCP Server v2025.4.8
- ✅ Servidor configurado no Claude Code
- ✅ Dependências instaladas

## 🔑 Configuração do Token GitHub (OBRIGATÓRIA)

### 1. Criar Personal Access Token
1. Acesse: https://github.com/settings/tokens
2. Clique em "Generate new token" → "Generate new token (classic)"
3. Configure as seguintes permissões:

#### Permissões Necessárias:
- ✅ **repo** - Acesso completo a repositórios privados
- ✅ **workflow** - Atualizar workflows do GitHub Actions  
- ✅ **read:org** - Ler dados da organização
- ✅ **user:email** - Acesso ao endereço de email

#### Permissões Opcionais (Recomendadas):
- ✅ **issues** - Acesso a issues
- ✅ **pull_requests** - Acesso a pull requests
- ✅ **project** - Acesso a projetos

### 2. Configurar Token no Ambiente
```bash
# Edite o arquivo .env.mcp
nano /media/biily/DATA1/Workspace/tezio/.env.mcp

# Substitua 'seu_token_github_aqui' pelo token real
GITHUB_PERSONAL_ACCESS_TOKEN=ghp_sua_chave_real_aqui

# Carregue as variáveis
source /media/biily/DATA1/Workspace/tezio/.env.mcp
```

## 🚀 Funcionalidades Disponíveis

### Repositórios
```bash
# Listar repositórios
claude github repos

# Clonar repositório
claude github clone user/repo

# Criar repositório
claude github create-repo nome-do-repo
```

### Issues
```bash
# Listar issues
claude github issues user/repo

# Criar issue
claude github create-issue user/repo "Título" "Descrição"

# Fechar issue
claude github close-issue user/repo 123
```

### Pull Requests
```bash
# Listar PRs
claude github prs user/repo

# Criar PR
claude github create-pr user/repo "branch" "Título" "Descrição"

# Merge PR
claude github merge-pr user/repo 456
```

### Workflows & Actions
```bash
# Listar workflows
claude github workflows user/repo

# Executar workflow
claude github run-workflow user/repo workflow.yml

# Ver status dos workflows
claude github workflow-status user/repo
```

### Gestão de Projetos
```bash
# Listar projetos
claude github projects user/repo

# Adicionar issue ao projeto
claude github add-to-project project-id issue-number
```

## 📊 Comandos MCP Disponíveis

### Verificação
```bash
# Listar servidores MCP
claude mcp list

# Status do servidor GitHub
claude mcp get github

# Testar conectividade
npx @modelcontextprotocol/server-github --help
```

## 🛠️ Troubleshooting

### Problema: Token inválido
```bash
# Verificar se token está configurado
echo $GITHUB_PERSONAL_ACCESS_TOKEN

# Testar token manualmente
curl -H "Authorization: token $GITHUB_PERSONAL_ACCESS_TOKEN" https://api.github.com/user
```

### Problema: Permissões insuficientes
1. Verifique se o token tem as permissões necessárias
2. Regenere o token se necessário
3. Atualize o arquivo `.env.mcp`

### Problema: Servidor não responde
```bash
# Reiniciar servidor MCP
claude mcp restart github

# Verificar logs
claude mcp get github
```

## 📋 Casos de Uso Comuns

### CI/CD Integration
- Executar workflows automaticamente
- Monitorar status de builds
- Criar releases automaticamente

### Gestão de Issues
- Triagem automática de issues
- Criação de issues baseada em logs
- Linking automático entre issues e commits

### Code Review
- Análise automática de PRs
- Comentários inteligentes
- Aprovação baseada em critérios

### Project Management
- Sincronização com boards de projeto
- Atualização automática de status
- Relatórios de progresso

## ⚠️ Segurança

- **NUNCA** commite o token no Git
- Use `.env.mcp` que está no `.gitignore`
- Regenere tokens periodicamente
- Use tokens com permissões mínimas necessárias

## 📚 Recursos Adicionais

- **GitHub API Docs:** https://docs.github.com/en/rest
- **Personal Access Tokens:** https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token
- **MCP GitHub Server:** https://github.com/modelcontextprotocol/servers

---

**🔥 PRÓXIMO PASSO:** Configure seu GitHub Personal Access Token antes de usar!