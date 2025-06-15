# PRD - CMDB (Configuration Management Database)

## 1. Visão Geral

### 1.1 Objetivo
O módulo CMDB fornece um repositório centralizado e confiável de todos os ativos de TI (Configuration Items - CIs), seus atributos, relacionamentos e dependências, permitindo melhor controle e tomada de decisão sobre a infraestrutura.

### 1.2 Valor de Negócio
- Visibilidade completa do inventário de TI
- Análise de impacto para mudanças planejadas
- Redução de incidentes por melhor compreensão das dependências
- Conformidade com ITIL e frameworks de governança
- Otimização de custos através do controle de ativos

### 1.3 Usuários-Alvo
- **Analistas de Configuração**: Manutenção dos CIs
- **Equipes de Mudança**: Análise de impacto
- **Gestores de TI**: Visão estratégica dos ativos
- **Auditores**: Conformidade e rastreabilidade

## 2. Funcionalidades Principais

### 2.1 Gestão de Configuration Items (CIs)

#### 2.1.1 Tipos de CIs Suportados
- **Hardware**: Servidores, desktops, notebooks, impressoras
- **Software**: Aplicações, sistemas operacionais, databases
- **Rede**: Switches, roteadores, firewalls, load balancers
- **Cloud**: VMs, containers, serviços PaaS/SaaS
- **Documentação**: Contratos, licenças, manuais
- **Pessoas**: Usuários, equipes, fornecedores

#### 2.1.2 Atributos dos CIs
```yaml
CI Base:
  - ID único
  - Nome
  - Tipo
  - Status (Ativo, Inativo, Em manutenção, Descontinuado)
  - Criticidade (Alta, Média, Baixa)
  - Localização física/lógica
  - Responsável
  - Data de aquisição
  - Custo
  - Ciclo de vida
  
Atributos Específicos por Tipo:
  Hardware:
    - Número de série
    - Modelo/Fabricante
    - Especificações técnicas
    - Garantia
  
  Software:
    - Versão
    - Licenças
    - Dependências
    - Requisitos
```

### 2.2 Relacionamentos e Dependências

#### 2.2.1 Tipos de Relacionamentos
- **Depende de**: Aplicação → Servidor
- **Hospeda**: Servidor → Aplicação
- **Conecta com**: Switch → Servidor
- **Usa**: Usuário → Aplicação
- **Gerencia**: Equipe → Servidor
- **Faz parte de**: VM → Cluster

#### 2.2.2 Visualização de Impacto
- Mapa visual de dependências
- Análise de impacto em cascata
- Simulação de falhas
- Identificação de single points of failure

### 2.3 Discovery e Importação

#### 2.3.1 Métodos de Discovery
- **Automático**: Agentes e scanners de rede
- **Semi-automático**: Importação via CSV/API
- **Manual**: Cadastro individual
- **Integração**: ServiceNow, Lansweeper, SCCM

#### 2.3.2 Reconciliação
- Detecção de duplicatas
- Merge de informações
- Validação de dados
- Auditoria de mudanças

### 2.4 Ciclo de Vida dos CIs

```
Planejado → Adquirido → Em Implementação → Ativo → Em Manutenção → Descontinuado → Removido
```

#### 2.4.1 Controles por Fase
- **Planejado**: Aprovações, orçamento
- **Ativo**: Monitoramento, atualizações
- **Descontinuado**: Plano de migração
- **Removido**: Descarte seguro, auditoria

### 2.5 Compliance e Auditoria

#### 2.5.1 Rastreabilidade
- Log completo de todas as alterações
- Histórico de relacionamentos
- Timeline de eventos por CI
- Snapshots de configuração

#### 2.5.2 Relatórios de Compliance
- Licenças em conformidade
- Ativos sem responsável
- CIs fora do padrão
- Expiração de garantias/contratos

## 3. Requisitos Técnicos

### 3.1 Performance
- Suporte para 100.000+ CIs
- Busca indexada < 1s
- Geração de mapa de impacto < 5s
- Import em lote de 10.000 itens < 2min

### 3.2 Integrações
- REST API para CRUD de CIs
- Webhooks para mudanças
- GraphQL para consultas complexas
- Conectores nativos (AD, AWS, Azure)

### 3.3 Segurança
- Controle granular de acesso por tipo de CI
- Criptografia de dados sensíveis
- Segregação por tenant
- Auditoria em conformidade com SOX

## 4. Requisitos de UX/UI

### 4.1 Interfaces Principais
- **Dashboard CMDB**: Visão geral do inventário
- **Explorador de CIs**: Busca e navegação
- **Editor de CI**: Formulário dinâmico por tipo
- **Mapa de Relacionamentos**: Visualização interativa
- **Centro de Importação**: Wizards de discovery

### 4.2 Visualizações
- Vista em árvore hierárquica
- Diagrama de rede interativo
- Tabela com filtros avançados
- Cards para visão rápida

## 5. Métricas de Sucesso

### 5.1 KPIs de Qualidade
- Acurácia dos dados > 95%
- CIs órfãos < 5%
- Tempo de atualização < 24h
- Cobertura de discovery > 90%

### 5.2 KPIs de Utilização
- Consultas diárias ao CMDB
- Análises de impacto realizadas
- Integrações ativas
- Redução de incidentes relacionados

## 6. Roadmap de Implementação

### Fase 1 - Fundação (3 meses)
- [ ] Modelo de dados core
- [ ] CRUD de CIs básicos
- [ ] Relacionamentos simples
- [ ] Import/Export CSV

### Fase 2 - Automação (3 meses)
- [ ] Discovery agents
- [ ] Integrações principais
- [ ] Reconciliação automática
- [ ] API completa

### Fase 3 - Inteligência (3 meses)
- [ ] ML para detecção de anomalias
- [ ] Predição de falhas
- [ ] Otimização de custos
- [ ] Digital twin

## 7. Riscos e Mitigações

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| Dados desatualizados | Alto | Discovery automatizado + processos |
| Resistência das equipes | Médio | Benefícios claros + treinamento |
| Complexidade de relacionamentos | Alto | Interface intuitiva + templates |
| Performance com volume | Alto | Arquitetura otimizada + cache |

## 8. Dependências

- Módulo de autenticação/autorização
- Sistema de notificações
- Infraestrutura de storage
- Ferramentas de discovery
- APIs dos sistemas integrados

## 9. Critérios de Aceite

- [ ] Cadastro completo de todos os tipos de CI
- [ ] Mapeamento de relacionamentos funcionando
- [ ] Discovery automático operacional
- [ ] Análise de impacto precisa
- [ ] Relatórios de compliance disponíveis
- [ ] Performance dentro dos SLAs
- [ ] Integração com Change Management