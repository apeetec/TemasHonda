# 🚀 Guia de Instalação Completo - Sistema SIPAT

## 📋 Visão Geral do Sistema

Este guia cobre a instalação completa do sistema SIPAT (Semana Interna de Prevenção de Acidentes de Trabalho) com suas funcionalidades:

- ✅ **Sistema de Questionários** - Formulários dinâmicos com validação
- ✅ **Importação em Massa de Usuários** - Interface moderna com validação
- ✅ **Validador de CSV** - Ferramenta standalone de validação
- ✅ **Sistema de Progresso** - Acompanhamento de respostas
- ✅ **Códigos Presenciais** - Validação de participação

## 🛠️ Pré-requisitos

### Ambiente de Servidor
- **PHP 7.4+** (recomendado 8.0+)
- **MySQL 5.7+** ou MariaDB 10.3+
- **WordPress 5.0+** (recomendado 6.0+)
- **Apache/Nginx** com mod_rewrite habilitado

### Plugins Necessários
```bash
# Plugin obrigatório para meta fields
CMB2 (Custom Metaboxes 2)
https://wordpress.org/plugins/cmb2/
```

### Extensões PHP Requeridas
```ini
extension=mysqli
extension=pdo_mysql
extension=json
extension=mbstring
extension=fileinfo
extension=zip
```

## 📂 Estrutura de Arquivos

### Diretório do Theme
```
wp-content/themes/esg/
├── 📄 functions.php                    # Funções do WordPress
├── 📄 index.php                       # Template principal
├── 📄 header.php                      # Cabeçalho
├── 📄 footer.php                      # Rodapé
├── 📄 page.php                        # Template de páginas
├── 📄 style.css                       # Estilos principais
├── 📄 taxonomy-datas_perguntas.php    # Template taxonomia
├── 📄 validador-csv.html              # Validador standalone
├── 📄 template-usuarios.csv           # Template CSV
├── 📋 Documentações/
│   ├── IMPORTACAO-USUARIOS.md
│   ├── TEMPLATE-CSV-USUARIOS.md
│   └── VALIDADOR-CSV-DOCS.md
├── 📁 css/
│   ├── materialize.css
│   └── materialize.min.css
├── 📁 js/
│   ├── jquery.min.js
│   ├── materialize.min.js
│   ├── script.js
│   └── functions/
│       ├── custom-scripts.js
│       └── backup.js
├── 📁 pages/
│   ├── template-inserir.php           # Interface importação
│   └── outros templates...
├── 📁 sql/
│   └── inserir-usuarios.php           # Processador CSV
└── 📁 template-parts/
    ├── formulario_de_perguntas.php    # Formulário principal
    ├── requisicao.php                 # Processador formulário
    └── cabecalhos/
        └── arquivos de cabeçalho...
```

## 🔧 Instalação Passo a Passo

### 1. Preparação do WordPress

```bash
# 1. Instale o WordPress normalmente
# 2. Acesse wp-admin
# 3. Instale o plugin CMB2
```

### 2. Configuração do Theme

```bash
# Faça backup do theme atual (se existir)
cp -r wp-content/themes/theme-atual wp-content/themes/theme-atual-backup

# Ative o theme ESG
# Via wp-admin: Appearance > Themes > ESG > Activate
```

### 3. Configuração do Banco de Dados

Adicione estas configurações no `wp-config.php`:

```php
// Aumentar limites para importação
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('post_max_size', '50M');
ini_set('upload_max_filesize', '50M');

// Configurações de charset
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');
```

### 4. Configuração do Servidor

#### Apache (.htaccess)
```apache
# Adicione no .htaccess da raiz
RewriteEngine On

# Aumentar limites de upload
php_value memory_limit 512M
php_value max_execution_time 300
php_value post_max_size 50M
php_value upload_max_filesize 50M

# Headers de segurança
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx
```nginx
# Adicione no bloco server
client_max_body_size 50M;
client_body_timeout 300s;
client_header_timeout 300s;

# Headers de segurança
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options DENY;
add_header X-XSS-Protection "1; mode=block";
```

### 5. Verificação de Permissões

```bash
# Linux/macOS
chmod 755 wp-content/themes/esg/
chmod 644 wp-content/themes/esg/*.php
chmod 644 wp-content/themes/esg/template-usuarios.csv
chmod 755 wp-content/uploads/

# Windows (via PowerShell como Admin)
icacls "wp-content\themes\esg" /grant "IIS_IUSRS:(OI)(CI)RX"
icacls "wp-content\uploads" /grant "IIS_IUSRS:(OI)(CI)F"
```

## ⚙️ Configuração Inicial

### 1. Criação de Post Type e Taxonomia

O `functions.php` automaticamente criará:
- **Post Type**: `perguntas`
- **Taxonomy**: `datas_perguntas`
- **Meta Fields**: Via CMB2

### 2. Criação de Páginas

Crie estas páginas no wp-admin:

```
📄 Página: "Questionário SIPAT"
   Template: page.php
   Slug: questionario-sipat

📄 Página: "Importar Usuários"
   Template: template-inserir.php
   Slug: importar-usuarios

📄 Página: "Validar CSV"
   Link: /wp-content/themes/esg/validador-csv.html
```

### 3. Configuração de Usuários

```php
// Crie um usuário administrador para SIPAT
// Role: Administrator
// Username: sipat-admin
// Email: sipat@empresa.com
```

## 🧪 Testes de Funcionamento

### 1. Teste do Questionário

```bash
# Acesse: /questionario-sipat
# ✅ Verificar: Formulário carrega
# ✅ Verificar: JavaScript funciona
# ✅ Verificar: Submissão salva dados
# ✅ Verificar: Validação de código presencial
```

### 2. Teste de Importação

```bash
# Acesse: /importar-usuarios
# ✅ Verificar: Interface carrega
# ✅ Verificar: Upload de arquivo funciona
# ✅ Verificar: Validação CSV funciona
# ✅ Verificar: Importação cria usuários
```

### 3. Teste do Validador

```bash
# Acesse: /wp-content/themes/esg/validador-csv.html
# ✅ Verificar: Página carrega
# ✅ Verificar: Upload funciona
# ✅ Verificar: Validação detecta erros
# ✅ Verificar: Download template funciona
```

## 🐛 Solução de Problemas

### Erro: "Plugin CMB2 não encontrado"
```bash
# Solução:
1. wp-admin > Plugins > Add New
2. Buscar: "CMB2"
3. Install > Activate
```

### Erro: "Memory limit exceeded"
```php
// Adicione no wp-config.php
ini_set('memory_limit', '512M');

// Ou no .htaccess
php_value memory_limit 512M
```

### Erro: "File upload failed"
```apache
# Aumente limites no .htaccess
php_value upload_max_filesize 50M
php_value post_max_size 50M
```

### Erro: "JavaScript não funciona"
```html
<!-- Verificar se jQuery está carregado -->
<!-- Abrir F12 > Console para ver erros -->
<!-- Verificar se arquivos JS existem -->
```

### Erro: "CSS não carrega"
```php
// Verificar enqueue no functions.php
wp_enqueue_style('materialize-css', get_template_directory_uri() . '/css/materialize.min.css');
```

## 📊 Monitoramento e Logs

### 1. Debug WordPress

```php
// wp-config.php - Apenas em desenvolvimento
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 2. Logs de Importação

```php
// Os logs são salvos automaticamente em:
wp-content/debug.log

// Para visualizar:
tail -f wp-content/debug.log
```

### 3. Monitoramento de Performance

```bash
# Verificar uso de memória
grep "memory" wp-content/debug.log

# Verificar tempo de execução
grep "execution" wp-content/debug.log
```

## 🔒 Configurações de Segurança

### 1. Permissões de Arquivo

```bash
# Diretórios: 755
find wp-content/themes/esg -type d -exec chmod 755 {} \;

# Arquivos: 644
find wp-content/themes/esg -type f -exec chmod 644 {} \;
```

### 2. Validação de Entrada

O sistema inclui:
- ✅ Sanitização de dados
- ✅ Validação CSRF
- ✅ Escape de saída
- ✅ Validação de tipos de arquivo

### 3. Headers de Segurança

```php
// Adicionados automaticamente pelo sistema
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
```

## 📈 Otimização de Performance

### 1. Cache

```php
// wp-config.php
define('WP_CACHE', true);
define('ENABLE_CACHE', true);
```

### 2. Compressão

```apache
# .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/javascript
</IfModule>
```

### 3. Otimização de Banco

```sql
-- Execute periodicamente
OPTIMIZE TABLE wp_posts;
OPTIMIZE TABLE wp_postmeta;
OPTIMIZE TABLE wp_users;
OPTIMIZE TABLE wp_usermeta;
```

## 🚀 Deploy para Produção

### 1. Checklist Pré-Deploy

- [ ] Backup completo do site
- [ ] Teste em ambiente de staging
- [ ] Verificar todos os links
- [ ] Testar importação com dados reais
- [ ] Verificar permissões de arquivo
- [ ] Configurar SSL/HTTPS
- [ ] Teste de performance

### 2. Configurações de Produção

```php
// wp-config.php - Produção
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('DISALLOW_FILE_EDIT', true);
```

### 3. Monitoramento

- Configurar backup automático
- Monitorar logs de erro
- Verificar atualizações de segurança
- Testar funcionamento semanalmente

## 📞 Suporte e Manutenção

### Documentações Incluídas
- `IMPORTACAO-USUARIOS.md` - Guia completo de importação
- `TEMPLATE-CSV-USUARIOS.md` - Documentação do template
- `VALIDADOR-CSV-DOCS.md` - Manual do validador

### Atualizações Futuras
- Manter WordPress atualizado
- Atualizar plugins regularmente
- Revisar logs mensalmente
- Fazer backup antes de mudanças

---

*Sistema SIPAT - Versão 2.0*
*Última atualização: Dezembro 2024*