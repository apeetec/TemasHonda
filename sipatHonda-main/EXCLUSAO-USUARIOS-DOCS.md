# 🗑️ Sistema de Exclusão em Massa de Usuários - Documentação Completa

## 🚨 AVISO CRÍTICO DE SEGURANÇA

⚠️ **OPERAÇÃO IRREVERSÍVEL** - Uma vez excluídos, os usuários NÃO podem ser recuperados!

🛡️ **BACKUP OBRIGATÓRIO** - Sempre faça backup completo antes de usar este sistema!

## 🎯 Visão Geral

O Sistema de Exclusão em Massa permite remover múltiplos usuários do WordPress de forma eficiente e segura através de arquivo CSV.

### ✅ Funcionalidades Principais
- **Exclusão em Lote**: Processa múltiplos usuários simultaneamente
- **Interface Dual**: Sistema de abas para importação e exclusão
- **Validação Robusta**: Verifica existência antes de excluir
- **Proteções de Segurança**: Impede exclusão de usuários críticos
- **Relatórios Detalhados**: Estatísticas completas do processo
- **Confirmação Dupla**: Duas confirmações antes da exclusão

## 🎨 Interface do Usuário

### Sistema de Abas
```
┌─────────────────────────────────────────┐
│  📥 Importar Usuários  │  🗑️ Excluir Usuários │
├─────────────────────────────────────────┤
│           Conteúdo Ativo                 │
└─────────────────────────────────────────┘
```

### Aba de Exclusão
1. **Aviso de Segurança** - Alertas em destaque vermelho
2. **Instruções de Uso** - Guia passo a passo
3. **Download Template** - Template CSV específico para exclusão
4. **Upload de Arquivo** - Drag & drop ou seleção manual
5. **Progresso em Tempo Real** - Barra de progresso dinâmica
6. **Resultados Detalhados** - Estatísticas e listas de resultados

## 📋 Formato do CSV de Exclusão

### Estrutura Básica
```csv
user_login;user_email
joao.silva;joao@empresa.com
maria.santos;
;carlos@empresa.com
```

### Colunas Aceitas
| Coluna | Obrigatória | Descrição | Exemplo |
|--------|-------------|-----------|---------|
| `user_login` | ❌ | Nome de usuário | `joao.silva` |
| `user_email` | ❌ | Email do usuário | `joao@empresa.com` |

**Nota**: Pelo menos UMA das colunas deve estar preenchida por linha.

### Exemplos de Uso

#### Exclusão por Login
```csv
user_login;user_email
joao.silva;
maria.santos;
carlos.oliveira;
```

#### Exclusão por Email
```csv
user_login;user_email
;joao@empresa.com
;maria@empresa.com
;carlos@empresa.com
```

#### Exclusão Mista
```csv
user_login;user_email
joao.silva;joao@empresa.com
;maria@empresa.com
carlos.oliveira;
```

#### Comentários no CSV
```csv
user_login;user_email
;; Este é um comentário - será ignorado
joao.silva;joao@empresa.com
;; Outro comentário
maria.santos;
```

## 🔒 Proteções de Segurança

### Usuários Protegidos
- ❌ **Usuário Atual**: Não pode excluir a si mesmo
- ❌ **Admin Principal**: Usuário ID #1 é protegido
- ❌ **Super Admins**: Em multisites, super admins são protegidos

### Validações de Permissão
- ✅ Verifica capability `delete_users`
- ✅ Valida nonce de segurança
- ✅ Confirma autenticação do usuário

### Confirmações Obrigatórias
1. **Primeira Confirmação**: "Você fez backup?"
2. **Segunda Confirmação**: "Tem certeza absoluta?"

## 🚀 Como Usar

### Passo 1: Preparação
1. **Faça backup completo** do site e banco de dados
2. **Liste os usuários** que deseja excluir
3. **Verifique permissões** de administrador

### Passo 2: Criar CSV
```bash
Opção A: Download do template
- Clique em "Download Template de Exclusão"
- Edite o arquivo com seus dados

Opção B: Criar manualmente
- Separador: ponto-vírgula (;)
- Codificação: UTF-8
- Extensão: .csv
```

### Passo 3: Upload e Validação
1. **Selecione a aba** "🗑️ Excluir Usuários"
2. **Faça upload** do CSV (drag & drop ou botão)
3. **Verifique** informações do arquivo
4. **Confirme** se está correto

### Passo 4: Execução
1. **Clique** no botão "🗑️ EXCLUIR USUÁRIOS"
2. **Confirme** as duas perguntas de segurança
3. **Aguarde** o processamento em lotes
4. **Analise** os resultados detalhados

### Passo 5: Verificação
1. **Revise relatório** de usuários excluídos
2. **Verifique erros** se houver
3. **Confirme exclusões** no wp-admin
4. **Documente** as mudanças realizadas

## 📊 Relatórios e Estatísticas

### Estatísticas Principais
- **Total Processado**: Linhas processadas do CSV
- **Usuários Excluídos**: Exclusões bem-sucedidas
- **Não Encontrados**: Usuários que não existiam
- **Erros**: Falhas durante o processo

### Informações Técnicas
- **Tempo de Execução**: Duração total do processo
- **Memória Utilizada**: Pico de uso de memória
- **Lotes Processados**: Número de lotes executados

### Tipos de Resultado

#### ✅ Usuários Excluídos
```
✅ Usuários Excluídos (3)
- Usuário excluído: joao.silva (joao@empresa.com)
- Usuário excluído: maria.santos (maria@empresa.com)
- Usuário excluído: carlos.oliveira (carlos@empresa.com)
```

#### ⚠️ Usuários Não Encontrados
```
⚠️ Usuários Não Encontrados (2)
- Usuário não encontrado: pedro.lima
- Usuário não encontrado: ana@empresa.com
```

#### ❌ Erros
```
❌ Erros (1)
- Não é possível excluir seu próprio usuário: admin
- Linha 5: Deve ter user_login ou user_email preenchido
```

## 🔧 Configurações Técnicas

### Limites do Sistema
```php
Tamanho máximo: 10MB
Lote de processamento: 25 usuários
Tempo limite: 5 minutos (300s)
Memória máxima: 512MB
```

### Tipos de Arquivo Aceitos
- `text/csv`
- `application/csv`  
- `text/plain`

### Codificação Recomendada
- **UTF-8** (preferencial)
- **UTF-8 BOM** (se necessário)

## 🐛 Solução de Problemas

### Erro: "Nenhum arquivo foi enviado"
**Causa**: Arquivo não selecionado ou corrompido
**Solução**: 
- Verifique se selecionou o arquivo
- Tente fazer upload novamente
- Verifique se o arquivo não está corrompido

### Erro: "Arquivo muito grande"
**Causa**: CSV excede 10MB
**Solução**:
- Divida em arquivos menores
- Remova colunas desnecessárias
- Processe em lotes menores

### Erro: "Token de segurança inválido"
**Causa**: Sessão expirou ou problema de autenticação
**Solução**:
- Recarregue a página
- Faça login novamente
- Limpe cache do navegador

### Erro: "Permissão insuficiente"
**Causa**: Usuário não tem permissão delete_users
**Solução**:
- Entre com usuário administrador
- Verifique roles e capabilities
- Contate administrador do sistema

### Aviso: "Usuário não encontrado"
**Causa**: user_login ou user_email não existe
**Solução**:
- Verifique ortografia dos dados
- Confirme se usuários existem
- Exporte lista atual de usuários

## 📱 Compatibilidade

### Navegadores Suportados
- ✅ Chrome 70+
- ✅ Firefox 65+
- ✅ Safari 12+
- ✅ Edge 79+

### WordPress
- ✅ WordPress 5.0+
- ✅ Multisite compatível
- ✅ PHP 7.4+ requerido

## 🚨 Melhores Práticas de Segurança

### Antes da Exclusão
1. **Backup Completo** - Site + banco de dados
2. **Teste em Staging** - Sempre teste primeiro
3. **Lista de Verificação** - Confirme usuários corretos
4. **Comunicação** - Avise usuários afetados

### Durante a Exclusão
1. **Monitor em Tempo Real** - Acompanhe o progresso
2. **Não Interrompa** - Deixe o processo terminar
3. **Anote Resultados** - Documente exclusões

### Após a Exclusão
1. **Verificação Manual** - Confirme exclusões no wp-admin
2. **Teste do Site** - Verifique funcionalidades
3. **Backup Pós-Exclusão** - Novo backup com estado atual
4. **Documentação** - Registre mudanças realizadas

## 🔄 Recuperação de Emergência

### Se algo der errado:
1. **Pare imediatamente** o processo se possível
2. **Restaure backup** mais recente
3. **Analise logs** de erro para entender o problema
4. **Contate suporte** se necessário

### Logs de Sistema
```bash
# WordPress Debug Log
wp-content/debug.log

# Logs do servidor
/var/log/apache2/error.log
/var/log/nginx/error.log
```

## 📞 Suporte

### Para Problemas Técnicos
- Verifique logs de erro do WordPress
- Ative WP_DEBUG para mais detalhes
- Consulte documentação do servidor

### Para Emergências
- Restaure backup imediatamente
- Desative plugins se necessário
- Contate administrador do sistema

---

**⚠️ LEMBRE-SE: Esta ferramenta é poderosa e irreversível. Use com extrema cautela!**

*Versão: 2.0*
*Última atualização: Dezembro 2024*