# 🔍 Validador de CSV - Documentação Completa

## Visão Geral

O **Validador de CSV** é uma ferramenta standalone que permite validar arquivos CSV antes da importação de usuários, garantindo que todos os dados estejam corretos e formatados adequadamente.

## 🎯 Funcionalidades Principais

### ✅ Validações Automáticas
- **Estrutura do arquivo**: Verifica se é um CSV válido
- **Tamanho do arquivo**: Limite de 10MB
- **Campos obrigatórios**: user_login, user_email, user_pass
- **Formato de email**: Validação de sintaxe RFC compliant
- **Username**: Caracteres permitidos e tamanho mínimo
- **Duplicatas**: Detecta emails e logins duplicados
- **Campos desconhecidos**: Identifica colunas não reconhecidas

### 📊 Estatísticas Detalhadas
- Total de linhas processadas
- Linhas válidas vs linhas com erro
- Contador de avisos
- Análise de duplicatas

### 🎨 Interface Moderna
- Design responsivo e profissional
- Drag & Drop para upload de arquivos
- Feedback visual em tempo real
- Download do template integrado

## 🚀 Como Usar

### Método 1: Seleção de Arquivo
1. Clique no botão "Selecionar Arquivo"
2. Escolha seu arquivo CSV
3. Aguarde a validação automática

### Método 2: Drag & Drop
1. Arraste o arquivo CSV para a área indicada
2. Solte o arquivo
3. A validação iniciará automaticamente

### Método 3: Template
1. Clique em "Download Template CSV"
2. Preencha com seus dados
3. Valide antes de importar

## 📋 Campos Suportados

### Obrigatórios ✳️
| Campo | Tipo | Descrição | Exemplo |
|-------|------|-----------|---------|
| `user_login` | String | Nome de usuário único | `joao.silva` |
| `user_email` | Email | Email válido e único | `joao@empresa.com` |
| `user_pass` | String | Senha (min 6 caracteres) | `MinhaSenh@123` |

### Opcionais 📝
| Campo | Tipo | Descrição | Exemplo |
|-------|------|-----------|---------|
| `first_name` | String | Nome | `João` |
| `last_name` | String | Sobrenome | `Silva` |
| `display_name` | String | Nome exibição | `João Silva` |
| `user_infos_empresas` | String | Info empresa | `Matriz SP` |
| `unidade_usuario` | String | Unidade | `Administração` |
| `empresa_usuario` | String | Empresa | `Empresa ABC Ltda` |
| `comentario` | String | Observações | `Funcionário terceirizado` |
| `user_field_senha_alterada` | Sim/Não | Senha alterada | `Não` |
| `description` | String | Biografia | `Gerente de TI` |

## ⚠️ Regras de Validação

### Emails
- ✅ Formato válido: `usuario@dominio.com`
- ❌ Inválido: `usuario@`, `@dominio.com`, `usuario.dominio`
- ❌ Duplicatas não são permitidas

### Usernames
- ✅ Caracteres: letras, números, ponto, hífen, underscore
- ✅ Tamanho mínimo: 3 caracteres
- ❌ Espaços ou caracteres especiais
- ❌ Duplicatas não são permitidas

### Senhas
- ⚠️ Recomendado: mínimo 6 caracteres
- ✅ Aceita qualquer caractere
- 💡 Dica: use senhas seguras

### Campos Especiais
- `user_field_senha_alterada`: deve ser exatamente "Sim" ou "Não"
- Campos vazios são permitidos para opcionais
- Aspas duplas são tratadas automaticamente

## 🎨 Códigos de Status

### ✅ Sucesso (Verde)
- Arquivo válido e pronto para importação
- Todas as validações passaram
- Pode conter avisos não críticos

### ❌ Erro (Vermelho)
- Arquivo contém erros críticos
- Correção obrigatória antes da importação
- Campos obrigatórios ausentes ou inválidos

### ⚠️ Aviso (Amarelo)
- Problemas não críticos detectados
- Importação possível mas recomenda-se revisão
- Exemplos: senhas fracas, campos desconhecidos

## 🔧 Solução de Problemas

### Erro: "Apenas arquivos CSV são aceitos"
**Causa**: Extensão do arquivo incorreta
**Solução**: Salve o arquivo com extensão `.csv`

### Erro: "Arquivo muito grande"
**Causa**: Arquivo maior que 10MB
**Solução**: 
- Divida em arquivos menores
- Remova colunas desnecessárias
- Comprima dados duplicados

### Erro: "Campo obrigatório ausente"
**Causa**: Cabeçalho sem campos obrigatórios
**Solução**: Adicione as colunas: `user_login`, `user_email`, `user_pass`

### Erro: "Email inválido"
**Causa**: Formato de email incorreto
**Solução**: Use formato `nome@dominio.com`

### Erro: "User login inválido"
**Causa**: Caracteres não permitidos ou muito curto
**Solução**: Use apenas letras, números, `.`, `-`, `_` (min 3 chars)

### Erro: "Email/Login duplicado"
**Causa**: Valores repetidos no arquivo
**Solução**: Remova ou altere entradas duplicadas

## 💡 Dicas e Boas Práticas

### Para Excel Users
```
1. Crie sua planilha normalmente
2. File > Save As > CSV (Comma delimited)
3. Use ponto-vírgula como separador
4. Salve com codificação UTF-8
```

### Formatação de Dados
```csv
user_login;user_email;user_pass;first_name;last_name
joao.silva;joao@empresa.com;MinhaSenh@123;João;Silva
maria.santos;maria@empresa.com;Senha456;Maria;Santos
```

### Senhas Seguras
- Combine letras maiúsculas e minúsculas
- Inclua números e símbolos
- Evite dados pessoais óbvios
- Exemplo: `Emp2024@Seg!`

### Performance
- Mantenha arquivos abaixo de 1000 usuários por arquivo
- Teste com arquivo pequeno primeiro
- Valide antes de cada importação em massa

## 🔄 Integração com Sistema

### Fluxo Recomendado
1. **Preparar dados** → Criar/editar CSV
2. **Validar** → Usar este validador
3. **Corrigir** → Resolver erros encontrados
4. **Re-validar** → Confirmar correções
5. **Importar** → Usar template-inserir.php

### Automação
```javascript
// Para desenvolvedores: validação pode ser integrada via JavaScript
const validator = new CSVValidator();
const isValid = await validator.validateFile(file);
if (isValid) {
    // Prosseguir com importação
}
```

## 📱 Compatibilidade

### Navegadores Suportados
- ✅ Chrome 70+
- ✅ Firefox 65+
- ✅ Safari 12+
- ✅ Edge 79+

### Dispositivos
- 💻 Desktop/Laptop (Recomendado)
- 📱 Tablet (Funcional)
- 📱 Mobile (Básico)

## 🆘 Suporte

### Se o validador não funcionar:
1. Verifique se JavaScript está habilitado
2. Teste em navegador diferente
3. Limpe cache do navegador
4. Verifique console de erros (F12)

### Contato para Dúvidas
- Consulte documentação do sistema principal
- Verifique logs de erro no console
- Teste com arquivo template fornecido

---

*Última atualização: Dezembro 2024*
*Versão: 1.0*