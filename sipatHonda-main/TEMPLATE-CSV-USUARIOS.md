# 📋 Template CSV para Importação de Usuários

## 🎯 **Visão Geral**
Este template CSV contém todos os campos necessários para importação de usuários no sistema SIPAT. Use este arquivo como base para criar suas importações em massa.

---

## 📊 **Estrutura do CSV**

### 🔴 **Campos Obrigatórios**
| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| `user_login` | Nome de usuário único | `joao.silva` |
| `user_email` | Email único do usuário | `joao.silva@empresa.com` |
| `user_pass` | Senha do usuário | `senha123` |

### 🟡 **Campos Opcionais - Informações Pessoais**
| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| `first_name` | Primeiro nome | `João` |
| `last_name` | Sobrenome | `Silva` |
| `display_name` | Nome de exibição | `João Silva` |
| `description` | Descrição/Cargo | `Desenvolvedor Senior` |

### 🟢 **Campos Opcionais - Organizacionais**
| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| `user_infos_empresas` | Unidade/Setor (slug) | `administracao` |
| `unidade_usuario` | Nome da unidade | `Unidade Centro` |
| `empresa_usuario` | Nome da empresa | `Empresa ABC` |

### 🔵 **Campos Opcionais - Sistema**
| Campo | Descrição | Valores Aceitos |
|-------|-----------|----------------|
| `user_field_senha_alterada` | Se a senha foi alterada | `Sim` / `Não` |
| `comentario` | Comentários sobre o usuário | Texto livre |

---

## ⚠️ **Regras Importantes**

### **1. Formato do Arquivo**
- **Separador**: Ponto e vírgula (`;`)
- **Encoding**: UTF-8
- **Extensão**: `.csv`
- **Tamanho máximo**: 10MB

### **2. Validações**
- **Email**: Deve ser um email válido e único
- **User Login**: Deve ser único, sem espaços ou caracteres especiais
- **Senha**: Obrigatória, mínimo recomendado 6 caracteres

### **3. Taxonomias (Unidades)**
- Se a unidade em `user_infos_empresas` não existir, será criada automaticamente
- Use nomes simples, sem acentos para melhor compatibilidade
- Exemplos: `administracao`, `recursos-humanos`, `tecnologia`

---

## 📝 **Exemplo Prático**

```csv
user_login;user_email;user_pass;first_name;last_name;display_name;user_infos_empresas;unidade_usuario;empresa_usuario;comentario;user_field_senha_alterada;description
joao.silva;joao.silva@empresa.com;senha123;João;Silva;João Silva;administracao;Unidade Centro;Empresa ABC;Funcionário exemplar;Não;Colaborador administrativo
maria.santos;maria.santos@empresa.com;senha456;Maria;Santos;Maria Santos;recursos-humanos;Unidade Norte;Empresa XYZ;Gerente experiente;Sim;Gerente de RH
```

---

## 🔧 **Dicas de Preenchimento**

### **Para Usuários Novos:**
1. Defina `user_field_senha_alterada` como `Não`
2. Use uma senha padrão que será alterada no primeiro login
3. Preencha nome completo e cargo para identificação

### **Para Usuários Existentes:**
1. Use o mesmo `user_email` existente
2. Os dados serão atualizados automaticamente
3. A senha será alterada se fornecida

### **Para Unidades/Setores:**
1. Use nomes padronizados e consistentes
2. Evite acentos e caracteres especiais em `user_infos_empresas`
3. Mantenha consistência entre `user_infos_empresas` e `unidade_usuario`

---

## ✅ **Checklist Antes da Importação**

- [ ] ✅ Fiz backup completo do site e banco de dados
- [ ] ✅ Validei todos os emails no arquivo
- [ ] ✅ Verifiquei se não há duplicatas de `user_login`
- [ ] ✅ Confirmei que o arquivo está em UTF-8
- [ ] ✅ Testei com um arquivo menor primeiro
- [ ] ✅ Revisei os nomes das unidades/empresas

---

## 🚨 **Problemas Comuns**

### **1. "Email já existe"**
- **Causa**: Email duplicado no sistema
- **Solução**: Use email único ou remova o usuário existente

### **2. "Username inválido"**
- **Causa**: Caracteres especiais ou espaços no `user_login`
- **Solução**: Use apenas letras, números, pontos e hífens

### **3. "Erro de encoding"**
- **Causa**: Arquivo não está em UTF-8
- **Solução**: Salve o CSV em UTF-8 no Excel/LibreOffice

### **4. "Linha com erro"**
- **Causa**: Número incorreto de colunas
- **Solução**: Verifique se todas as linhas têm o mesmo número de campos

---

## 📞 **Suporte**

Para dúvidas sobre o template:

1. **Consulte a documentação**: `IMPORTACAO-USUARIOS.md`
2. **Teste com arquivo pequeno**: Importe 2-3 usuários primeiro
3. **Verifique logs**: Use o modo debug para mais informações
4. **Valide formato**: Use ferramentas online para validar CSV

---

**📁 Arquivo**: `template-usuarios.csv`  
**🔄 Versão**: 2.0  
**📅 Atualizado**: Novembro 2025