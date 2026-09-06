# 📋 Sistema de Importação de Usuários - Documentação

## 🚀 Visão Geral

Sistema avançado e refatorado para importação em massa de usuários no WordPress, desenvolvido especificamente para o tema ESG do projeto SIPAT.

### ✨ Principais Melhorias da Versão 2.0

- **Interface Moderna**: Design responsivo com feedback visual em tempo real
- **Segurança Aprimorada**: Validação robusta e sanitização de dados
- **Performance Otimizada**: Processamento em lotes com transações de banco
- **Tratamento de Erros**: Sistema completo de logs e relatórios
- **Usabilidade**: Drag & drop, barras de progresso e notificações
- **Manutenibilidade**: Código orientado a objetos e bem documentado

## 📁 Arquivos do Sistema

### 1. `pages/template-inserir.php`
- Template WordPress para a página de importação
- Interface de usuário moderna e responsiva
- Sistema de upload com drag & drop
- Barras de progresso e feedback visual

### 2. `sql/inserir-usuarios.php`
- Engine de processamento da importação
- Classe `UserImporter` com métodos organizados
- Validação, sanitização e processamento em lotes
- API JSON para comunicação com frontend

## 🔧 Funcionalidades

### ✅ **Validações Implementadas**
- Verificação de permissões (apenas administradores)
- Validação de tipos MIME e extensões de arquivo
- Limite de tamanho de arquivo (10MB)
- Validação de campos obrigatórios (user_login, user_email, user_pass)
- Verificação de formato de email e username

### 🔄 **Processamento**
- **Lotes Inteligentes**: Processa em grupos de 25 usuários
- **Transações**: Rollback automático em caso de erro
- **Encoding**: Conversão automática para UTF-8
- **Performance**: Otimizado para grandes volumes

### 👥 **Gestão de Usuários**
- **Usuários Existentes**: Atualiza dados se email já existe
- **Novos Usuários**: Cria automaticamente com validação
- **Taxonomias**: Cria unidades/empresas automaticamente
- **Meta Fields**: Suporte completo a campos personalizados

### 📊 **Relatórios**
- Contadores de usuários criados, atualizados e com erro
- Tempo de execução e uso de memória
- Lista detalhada de sucessos e falhas
- Interface visual organizada em cards

## 📝 Formato CSV Esperado

### Campos Obrigatórios:
```csv
user_login,user_email,user_pass
```

### Campos Opcionais:
```csv
first_name,last_name,user_infos_empresas,unidade_usuario,empresa_usuario
```

### Exemplo Completo:
```csv
user_login,user_email,user_pass,first_name,last_name,user_infos_empresas,unidade_usuario,empresa_usuario
joao.silva,joao@empresa.com,senha123,João,Silva,Administração,Unidade Centro,Empresa ABC
maria.santos,maria@empresa.com,senha456,Maria,Santos,RH,Unidade Norte,Empresa XYZ
```

## 🛠 Configurações Técnicas

### Limites do Sistema:
- **Tamanho máximo**: 10MB por arquivo
- **Tempo limite**: 5 minutos de execução
- **Memória**: 256MB
- **Lote**: 25 usuários por transação

### Tipos MIME Aceitos:
- `text/csv`
- `application/csv`
- `text/plain`

## 🔐 Segurança

### Controles Implementados:
- ✅ Verificação de permissões administrativas
- ✅ Validação de nonce (CSRF protection)
- ✅ Sanitização de todos os dados de entrada
- ✅ Validação de tipos de arquivo
- ✅ Escape de dados na saída

### Logs de Auditoria:
- Registro de todas as operações
- Tracking de usuários criados/atualizados
- Log detalhado de erros e exceções

## 🚀 Como Usar

### 1. **Acesso**
- Faça login como administrador
- Acesse a página "Inserir Usuários"

### 2. **Preparação**
- Faça backup completo do site e banco de dados
- Baixe o template CSV da página
- Preencha os dados dos usuários

### 3. **Importação**
- Arraste o arquivo CSV ou clique para selecionar
- Clique em "Iniciar Importação"
- Acompanhe o progresso em tempo real

### 4. **Verificação**
- Revise os resultados exibidos
- Verifique usuários criados vs. com erro
- Corrija problemas se necessário

## 🐛 Tratamento de Erros

### Tipos de Erro:
- **Upload**: Problemas com arquivo (tamanho, tipo, corrupção)
- **Validação**: Dados inválidos (email, username, senha)
- **Banco**: Conflitos de integridade ou problemas de conexão
- **Sistema**: Limites de memória ou tempo

### Recuperação:
- Transações com rollback automático
- Logs detalhados para debugging
- Continuidade mesmo com erros parciais

## 📈 Performance

### Otimizações:
- Processamento em lotes para reduzir overhead
- Transações de banco para consistência
- Gestão eficiente de memória
- Cache de operações repetitivas

### Monitoramento:
- Tempo de execução reportado
- Uso de memória trackado
- Progresso em tempo real
- Estatísticas completas

## 🔧 Manutenção

### Para Desenvolvedores:
```php
// Alterar tamanho do lote
private $batch_size = 50; // padrão: 25

// Alterar limite de tempo
private $max_execution_time = 600; // padrão: 300s

// Alterar tamanho máximo de arquivo  
private $max_file_size = 20 * 1024 * 1024; // padrão: 10MB
```

### Logs:
- Verifique logs do WordPress (`wp-content/debug.log`)
- Monitore logs do servidor web
- Use modo debug para informações detalhadas

## 📞 Suporte

Para problemas ou dúvidas:
1. Verifique os logs de erro
2. Valide o formato do CSV
3. Teste com arquivo menor primeiro
4. Verifique permissões do usuário

---

**Versão**: 2.0  
**Última Atualização**: Novembro 2025  
**Compatibilidade**: WordPress 5.0+, PHP 7.4+