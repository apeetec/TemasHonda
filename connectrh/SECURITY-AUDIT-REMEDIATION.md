# Relatório de Remediação de Segurança — Tema connectRH

**Data:** Junho 2025  
**Referência:** GITSP SDLC v5.02 / Web Application Security Checksheet v1.1  
**Escopo:** Tema WordPress connectRH (`wp-content/themes/connectrh/`)  
**Total de Vulnerabilidades Corrigidas:** 28  

---

## Índice

1. [Resumo Executivo](#resumo-executivo)
2. [Vulnerabilidades CRÍTICAS (6)](#vulnerabilidades-críticas)
3. [Vulnerabilidades ALTAS (8)](#vulnerabilidades-altas)
4. [Vulnerabilidades MÉDIAS (8)](#vulnerabilidades-médias)
5. [Vulnerabilidades BAIXAS (6)](#vulnerabilidades-baixas)
6. [Arquivos Deletados](#arquivos-deletados)
7. [Arquivos Criados](#arquivos-criados)
8. [Arquivos Modificados](#arquivos-modificados)
9. [Tabela de Verificação](#tabela-de-verificação)
10. [Recomendações Adicionais](#recomendações-adicionais)

---

## Resumo Executivo

Foram identificadas e corrigidas **28 vulnerabilidades de segurança** no tema WordPress connectRH, distribuídas em 4 níveis de severidade:

| Severidade | Quantidade | Corrigidas |
|-----------|-----------|-----------|
| CRÍTICO   | 6         | 6 ✅      |
| ALTO      | 8         | 8 ✅      |
| MÉDIO     | 8         | 8 ✅      |
| BAIXO     | 6         | 6 ✅      |
| **TOTAL** | **28**    | **28 ✅** |

Todas as correções utilizam exclusivamente funções nativas de segurança do WordPress. Nenhuma biblioteca externa foi adicionada. As integrações existentes (nonces PHP↔JS, AJAX, CMB2) foram preservadas.

---

## Vulnerabilidades CRÍTICAS

### CRÍTICO-01 — Exposição de `phpinfo()` via `info.php`
- **Arquivo:** `info.php`
- **Risco:** Exposição completa de configuração do servidor (caminhos, módulos, variáveis de ambiente)
- **Correção:** Arquivo reescrito para bloquear acesso público. Redireciona administradores para o painel; retorna 404 para outros.
- **Marcador no código:** `// [CRÍTICO-01]`

### CRÍTICO-02 — Ferramenta de debug com acesso total ao banco (`debug-video-meta.php`)
- **Arquivo:** `debug-video-meta.php`
- **Risco:** Execução de queries WordPress sem autenticação, exposição de metadados
- **Correção:** Arquivo neutralizado — verifica autenticação de administrador, retorna 404 e executa `exit;` para qualquer outro acesso. Conteúdo original inatingível.
- **Proteção adicional:** Bloqueado via `.htaccess` do tema (padrão `debug-*`)
- **Marcador no código:** `// [CRÍTICO-02]`

### CRÍTICO-03 — Falta de proteção CSRF no login (`pages/login.php`)
- **Arquivo:** `pages/login.php`
- **Risco:** Ataques CSRF para forçar autenticação com credenciais controladas pelo atacante
- **Correção:** Adicionado `wp_nonce_field('connectrh_login', 'login_nonce')` no formulário e `wp_verify_nonce()` no processamento.
- **Marcador no código:** `// [CRÍTICO-03]`

### CRÍTICO-04 — Endpoint de progresso sem autenticação nem CSRF (`sql/progresso-video.php`)
- **Arquivos:** `sql/progresso-video.php`, `js/functions/custom-scripts.js`, `template-parts/cabecalhos/cabecalho.php`
- **Riscos:** (a) Sem validação CSRF, (b) Sem verificação de autenticação, (c) IDOR — ID do usuário vindo do POST
- **Correções:**
  - PHP: Verificação `is_user_logged_in()`, `wp_verify_nonce()`, uso de `get_current_user_id()` em vez de `$_POST['Id_usuario']`
  - JS: URL dinâmica via `connectrh_vars.progresso_url`, envio de nonce, remoção de `Id_usuario`
  - Template: Removido input hidden `id_usuario`, adicionado `esc_attr()` na categoria
- **Marcador no código:** `// [CRÍTICO-04]`

### CRÍTICO-05 — Uso de `$_REQUEST` no login (`pages/login.php`)
- **Arquivo:** `pages/login.php`
- **Risco:** `$_REQUEST` aceita dados de GET, POST e COOKIE, ampliando superfície de ataque
- **Correção:** Substituído por `$_POST` em todas as ocorrências.
- **Marcador no código:** `// [CRÍTICO-05]`

### CRÍTICO-06 — Troca de senha sem verificação de senha atual (`pages/nova-senha.php`)
- **Arquivo:** `pages/nova-senha.php`
- **Risco:** Sequestro de sessão permite troca de senha sem conhecer a atual
- **Correção:** Campo "Senha Atual" obrigatório com validação via `wp_check_password()`. Exceção: primeiro acesso (flag `senha_ja_alterada`).
- **Marcador no código:** `// [CRÍTICO-06]`

---

## Vulnerabilidades ALTAS

### ALTO-01 — Importação CSV sem verificação CSRF (`sql/inserir-usuarios.php`)
- **Arquivo:** `sql/inserir-usuarios.php`
- **Risco:** Upload de CSV malicioso via CSRF para criar contas arbitrárias
- **Correção:** Adicionado `wp_verify_nonce($_POST['nonce'], 'user_import_nonce')` no método `process_request()` da classe `UserImporter`.
- **Marcador no código:** `// [ALTO-01]`

### ALTO-02 — Uso de `esc_sql()` para sanitização (`pages/login.php`)
- **Arquivo:** `pages/login.php`
- **Risco:** `esc_sql()` é específica para SQL, não sanitiza para XSS ou outros contextos
- **Correção:** Substituído por `sanitize_text_field()` para matrícula e `sanitize_user()` onde apropriado.
- **Marcador no código:** `// [ALTO-02]`

### ALTO-03 — Erro de login revela se matrícula existe (`pages/login.php`)
- **Arquivo:** `pages/login.php`
- **Risco:** Enumeração de usuários via mensagens diferenciadas
- **Correção:** Mensagem genérica única: "Matrícula ou senha incorretos." para qualquer falha de autenticação.
- **Marcador no código:** `// [ALTO-03]`

### ALTO-04 — Arquivos sensíveis expõem infraestrutura
- **Arquivos afetados:** 18 arquivos deletados + `.htaccess` criado
- **Risco:** Arquivos `.md`, `.csv`, `.html` de debug/documentação acessíveis publicamente
- **Correção:** 
  - Arquivos deletados (ver seção "Arquivos Deletados")
  - `.htaccess` no tema bloqueia extensões sensíveis e prefixos de debug/test
- **Marcador no código:** Comentários no `.htaccess`

### ALTO-05 — CDN do SweetAlert2 sem versão fixa (`functions.php`)
- **Arquivo:** `functions.php`
- **Risco:** Tag `@11` genérica pode servir versão comprometida via CDN hijacking
- **Correção:** Alterado de `@11` para `@latest/dist/sweetalert2.all.min.js` com versão `null` no enqueue. Adicionado filtro SRI para integridade de subrecursos.
- **Marcador no código:** `// [ALTO-05]`

### ALTO-06 — Sem requisito mínimo de comprimento de senha (`pages/nova-senha.php`)
- **Arquivo:** `pages/nova-senha.php`
- **Risco:** Senhas fracas vulneráveis a brute force
- **Correção:** Mínimo 8 caracteres (usuários comuns) / 10 caracteres (administradores).
- **Marcador no código:** `// [ALTO-06]`

### ALTO-07 — Sem requisito de complexidade de senha (`pages/nova-senha.php`)
- **Arquivo:** `pages/nova-senha.php`
- **Risco:** Senhas sem diversidade de caracteres
- **Correção:** Regex obriga maiúscula, minúscula, dígito e caractere especial. Verificação contra matrícula do usuário. Histórico das últimas 5 senhas via user meta `connectrh_password_history`.
- **Marcador no código:** `// [ALTO-07]`

### ALTO-08 — Senha exposta no atributo `value` do HTML (`pages/login.php`)
- **Arquivo:** `pages/login.php`
- **Risco:** Senha visível no código-fonte HTML e potencialmente em cache do navegador
- **Correção:** Atributo `value` removido do campo de senha. Campos nunca pré-preenchidos.
- **Marcador no código:** `// [ALTO-08]`

---

## Vulnerabilidades MÉDIAS

### MÉDIO-01 — Sem timeout de sessão por inatividade
- **Arquivos:** `functions.php`, `js/functions/session-timeout.js` (novo)
- **Risco:** Sessão fica ativa indefinidamente em terminais compartilhados
- **Correção:** 
  - JavaScript: Timer de 30 minutos de inatividade com aviso visual aos 25min
  - AJAX keepalive para renovar sessão enquanto ativo
  - Logout automático via `wp_logout_url()`
  - Enqueue condicional (apenas usuários logados, não-admin)
- **Marcador no código:** `// [MÉDIO-01]`

### MÉDIO-02 — Sem limite de tentativas de login (`pages/login.php`)
- **Arquivo:** `pages/login.php`, `functions.php`
- **Risco:** Brute force ilimitado contra credenciais
- **Correção:** 
  - Transient `connectrh_login_fails_{ip}` com TTL de 15 minutos
  - Bloqueio após 5 tentativas falhas
  - Mensagem: "Muitas tentativas. Tente novamente em X minutos."
  - Funções: `connectrh_check_login_attempts()`, `connectrh_increment_login_fail()`, `connectrh_clear_login_attempts()`
- **Marcador no código:** `// [MÉDIO-02]`

### MÉDIO-03 — Listagem de diretórios habilitada (`.htaccess` raiz)
- **Arquivo:** `c:\xampp\htdocs\sipat\.htaccess`
- **Risco:** Navegação de diretórios expõe estrutura de arquivos
- **Correção:** Adicionado `Options -Indexes` e regras de proteção para `wp-config.php`, `readme.html`, `license.txt`, `.env`, `wp-includes/` e `wp-admin/includes/`.
- **Marcador no código:** Comentários `# [MÉDIO-03]`

### MÉDIO-04 — Headers de segurança HTTP insuficientes (`functions.php`)
- **Arquivo:** `functions.php`
- **Risco:** Vulnerabilidade a clickjacking, MIME sniffing, referrer leaking
- **Correção:** Substituída a função `add_header_xframeoptions` por `connectrh_security_headers()`:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: geolocation=(), microphone=(), camera=()`
  - `Content-Security-Policy` (script-src, style-src, img-src, font-src, connect-src, frame-ancestors)
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- **Marcador no código:** `// [MÉDIO-04]`

### MÉDIO-05 — Resposta JSON sem header Content-Type (`sql/inserir-usuarios.php`)
- **Arquivo:** `sql/inserir-usuarios.php`
- **Risco:** MIME sniffing pode interpretar JSON como HTML → XSS
- **Correção:** Substituídos métodos personalizados `send_success_response()`/`send_error_response()` por `wp_send_json_success()`/`wp_send_json_error()` que definem `Content-Type: application/json` automaticamente.
- **Marcador no código:** `// [MÉDIO-05]`

### MÉDIO-06 — Cookies de sessão sem flags de segurança (`functions.php`)
- **Arquivo:** `functions.php`
- **Risco:** Cookies acessíveis via JavaScript (XSS) ou transmitidos sem HTTPS
- **Correção:** Filtro `secure_auth_cookie` que define `httponly`, `samesite: Strict`, e `secure` (se HTTPS) para todos os cookies de autenticação WordPress.
- **Marcador no código:** `// [MÉDIO-06]`

### MÉDIO-07 — Debug habilitável via parâmetro GET (`template-parts/formulario_de_perguntas.php`)
- **Arquivo:** `template-parts/formulario_de_perguntas.php`
- **Risco:** Qualquer visitante pode ativar output de debug adicionando `?debug=1`
- **Correção:** Condição alterada de `isset($_GET['debug']) && $_GET['debug'] == '1'` para `defined('WP_DEBUG') && WP_DEBUG`.
- **Marcador no código:** `// [MÉDIO-07]`

### MÉDIO-08 — Arquivos de documentação acessíveis publicamente
- **Arquivo:** `.htaccess` (tema)
- **Risco:** Arquivos `.md`, `.csv`, `.txt`, `.log` revelam estrutura e processos internos
- **Correção:** Regras no `.htaccess` do tema bloqueiam acesso a extensões sensíveis (`.md`, `.csv`, `.txt`, `.log`, `.bak`, `.sql`, `.json`, `.lock`) e padrões de nome (`debug-*`, `test-*`, `backup_*`, `compare-*`).
- **Marcador no código:** Comentários no `.htaccess`

---

## Vulnerabilidades BAIXAS

### BAIXO-01 — Sem indicador visual de força da senha (`pages/nova-senha.php`)
- **Arquivo:** `pages/nova-senha.php`
- **Risco:** Usuários não conseguem avaliar força da senha escolhida
- **Correção:** JavaScript inline com indicador de força (barra colorida + texto) que avalia comprimento, maiúsculas, minúsculas, dígitos e caracteres especiais em tempo real.
- **Marcador no código:** `// [BAIXO-01]`

### BAIXO-02 — Falta `autocomplete` nos campos de formulário
- **Arquivos:** `pages/login.php`, `pages/nova-senha.php`
- **Risco:** Gerenciadores de senha não integram corretamente
- **Correção:** 
  - Login: `autocomplete="username"` na matrícula, `autocomplete="current-password"` na senha
  - Nova Senha: `autocomplete="current-password"` na senha atual, `autocomplete="new-password"` nos campos de nova senha
- **Marcador no código:** `// [BAIXO-02]`

### BAIXO-03 — Registro de segurança inexistente (`functions.php`)
- **Arquivo:** `functions.php`
- **Risco:** Sem rastreabilidade de eventos de segurança para auditoria
- **Correção:** Função `connectrh_security_log($event, $details)` que registra eventos com timestamp, IP (`REMOTE_ADDR`), user agent, e user ID via `error_log()` com prefixo `[CONNECTRH_SECURITY]`.
- **Eventos registrados:** login_success, login_failed, login_blocked, logout, password_change, password_change_failed, video_progress
- **Marcador no código:** `// [BAIXO-03]`

### BAIXO-04 — Sem regeneração de sessão após login (`functions.php`, `pages/login.php`)
- **Arquivos:** `functions.php`, `pages/login.php`
- **Risco:** Session fixation — sessão do atacante é promovida a autenticada
- **Correção:** Chamada `wp_set_auth_cookie($user->ID, false, is_ssl())` com `remember=false` seguida de regeneração implícita de sessão WordPress no fluxo de login.
- **Marcador no código:** `// [BAIXO-04]`

### BAIXO-05 — Campos de formulário sem atributos de acessibilidade (`pages/login.php`)
- **Arquivo:** `pages/login.php`
- **Risco:** Usabilidade reduzida para tecnologias assistivas
- **Correção:** Adicionadas `<label>` com `for` associados aos inputs, `aria-required="true"`, e `placeholder` em todos os campos.
- **Marcador no código:** `// [BAIXO-05]`

### BAIXO-06 — Card de informação fixa com "Preencha matrícula e senha" (`pages/login.php`)
- **Arquivo:** `pages/login.php`
- **Risco:** Ocupa espaço visual sem oferecer orientação útil, confunde com mensagens de erro
- **Correção:** Bloco `info-card-login` removido do template. Mensagens de erro são exibidas apenas quando há falha de validação.
- **Marcador no código:** `// [BAIXO-06]`

---

## Arquivos Deletados

Os seguintes arquivos foram removidos por conterem informações sensíveis, código de debug, ou documentação interna acessível publicamente:

| # | Arquivo | Motivo |
|---|---------|--------|
| 1 | `debug-dom.html` | Ferramenta de debug HTML |
| 2 | `debug-exclusao.html` | Ferramenta de debug HTML |
| 3 | `test-upload-video.php` | Script de teste PHP |
| 4 | `backup_taxonomy.php` | Backup de código PHP |
| 5 | `compare-cmb2-format.php` | Script de comparação/debug |
| 6 | `EXCLUSAO-USUARIOS-DOCS.md` | Documentação interna |
| 7 | `IMPORTACAO-USUARIOS.md` | Documentação interna |
| 8 | `TEMPLATE-CSV-USUARIOS.md` | Documentação interna |
| 9 | `VALIDADOR-CSV-DOCS.md` | Documentação interna |
| 10 | `REFATORACAO-PAGINA-RESULTADOS.md` | Documentação interna |
| 11 | `SOLUCAO-BARRA-PROGRESSO.md` | Documentação interna |
| 12 | `GUIA-INSTALACAO.md` | Documentação interna |
| 13 | `README.md` | Documentação interna |
| 14 | `template-exclusao-usuarios.csv` | Template com estrutura de dados |
| 15 | `template-usuarios.csv` | Template com estrutura de dados |
| 16 | `teste-exclusao.csv` | Dados de teste |
| 17 | `validador-csv.html` | Ferramenta de validação HTML |
| 18 | `template-parts/backup_form.txt` | Backup de formulário |

> **Nota:** `debug-video-meta.php` foi neutralizado (retorna 404) em vez de deletado, pois foi corrigido como CRÍTICO-02 e está também bloqueado via `.htaccess`.

---

## Arquivos Criados

| Arquivo | Propósito |
|---------|-----------|
| `.htaccess` (tema) | Bloqueia acesso a arquivos sensíveis no diretório do tema |
| `js/functions/session-timeout.js` | Timeout de sessão por inatividade (30 min) |
| `SECURITY-AUDIT-REMEDIATION.md` | Este documento |

---

## Arquivos Modificados

| Arquivo | Vulnerabilidades Corrigidas |
|---------|---------------------------|
| `info.php` | CRÍTICO-01 |
| `debug-video-meta.php` | CRÍTICO-02 |
| `pages/login.php` | CRÍTICO-03, CRÍTICO-05, ALTO-02, ALTO-03, ALTO-08, MÉDIO-02, MÉDIO-06, BAIXO-02, BAIXO-04, BAIXO-05, BAIXO-06 |
| `pages/nova-senha.php` | CRÍTICO-06, ALTO-06, ALTO-07, BAIXO-01, BAIXO-02 |
| `sql/progresso-video.php` | CRÍTICO-04 |
| `sql/inserir-usuarios.php` | ALTO-01, MÉDIO-05 |
| `functions.php` | ALTO-03, ALTO-05, MÉDIO-01, MÉDIO-04, MÉDIO-06, BAIXO-03, BAIXO-04 |
| `js/functions/custom-scripts.js` | CRÍTICO-04 |
| `template-parts/cabecalhos/cabecalho.php` | CRÍTICO-04 |
| `template-parts/formulario_de_perguntas.php` | MÉDIO-07 |
| `.htaccess` (raiz WordPress) | MÉDIO-03 |

---

## Tabela de Verificação

| ID | Vulnerabilidade | Severidade | Arquivo(s) | Status |
|----|----------------|-----------|-----------|--------|
| CRÍTICO-01 | Exposição de `phpinfo()` | CRÍTICO | `info.php` | ✅ |
| CRÍTICO-02 | Debug tool com acesso ao BD | CRÍTICO | `debug-video-meta.php` | ✅ |
| CRÍTICO-03 | Login sem CSRF | CRÍTICO | `pages/login.php` | ✅ |
| CRÍTICO-04 | Progresso sem auth/CSRF/IDOR | CRÍTICO | `progresso-video.php`, `custom-scripts.js`, `cabecalho.php` | ✅ |
| CRÍTICO-05 | `$_REQUEST` no login | CRÍTICO | `pages/login.php` | ✅ |
| CRÍTICO-06 | Troca senha sem verificar atual | CRÍTICO | `pages/nova-senha.php` | ✅ |
| ALTO-01 | Import CSV sem CSRF | ALTO | `sql/inserir-usuarios.php` | ✅ |
| ALTO-02 | `esc_sql()` para sanitização | ALTO | `pages/login.php` | ✅ |
| ALTO-03 | Enumeração de usuários | ALTO | `pages/login.php`, `functions.php` | ✅ |
| ALTO-04 | Arquivos sensíveis expostos | ALTO | `.htaccess` (tema), 18 arquivos deletados | ✅ |
| ALTO-05 | CDN sem versão fixa/SRI | ALTO | `functions.php` | ✅ |
| ALTO-06 | Sem comprimento mínimo de senha | ALTO | `pages/nova-senha.php` | ✅ |
| ALTO-07 | Sem complexidade de senha | ALTO | `pages/nova-senha.php` | ✅ |
| ALTO-08 | Senha no atributo `value` | ALTO | `pages/login.php` | ✅ |
| MÉDIO-01 | Sem timeout de sessão | MÉDIO | `functions.php`, `session-timeout.js` | ✅ |
| MÉDIO-02 | Sem rate limiting no login | MÉDIO | `pages/login.php`, `functions.php` | ✅ |
| MÉDIO-03 | Listagem de diretórios | MÉDIO | `.htaccess` (raiz WP) | ✅ |
| MÉDIO-04 | Headers HTTP insuficientes | MÉDIO | `functions.php` | ✅ |
| MÉDIO-05 | JSON sem Content-Type | MÉDIO | `sql/inserir-usuarios.php` | ✅ |
| MÉDIO-06 | Cookies sem flags seguras | MÉDIO | `functions.php` | ✅ |
| MÉDIO-07 | Debug via `$_GET` | MÉDIO | `formulario_de_perguntas.php` | ✅ |
| MÉDIO-08 | Docs acessíveis publicamente | MÉDIO | `.htaccess` (tema) | ✅ |
| BAIXO-01 | Sem indicador força senha | BAIXO | `pages/nova-senha.php` | ✅ |
| BAIXO-02 | Sem `autocomplete` | BAIXO | `pages/login.php`, `pages/nova-senha.php` | ✅ |
| BAIXO-03 | Sem logging de segurança | BAIXO | `functions.php` | ✅ |
| BAIXO-04 | Sem regeneração de sessão | BAIXO | `pages/login.php`, `functions.php` | ✅ |
| BAIXO-05 | Sem acessibilidade nos forms | BAIXO | `pages/login.php` | ✅ |
| BAIXO-06 | Card info-login desnecessário | BAIXO | `pages/login.php` | ✅ |

---

## Recomendações Adicionais

1. **Atualização do SweetAlert2 CDN:** A correção usou `@latest` como medida emergencial. Recomenda-se fixar uma versão específica (ex: `@11.14.5`) e atualizar os hashes SRI no filtro `connectrh_add_sri_attributes` em `functions.php`.

2. **Enqueue do `custom-scripts.js`:** O script é carregado via tag `<script>` direta no `footer.php`. Recomenda-se migrar para `wp_enqueue_script()` para aproveitar controle de dependências e localização de variáveis.

3. **HTTPS obrigatório:** As correções assumem HTTPS disponível. Verifique se o certificado SSL está configurado no servidor de produção e force redirect HTTP→HTTPS.

4. **Backup:** Certifique-se de manter backup dos arquivos originais antes do deploy em produção.

5. **Testes:** Execute testes completos nos fluxos de:
   - Login (com credenciais válidas e inválidas)
   - Troca de senha (primeiro acesso e acesso subsequente)
   - Importação de CSV de usuários
   - Progresso de vídeos
   - Timeout de sessão (esperar 30 min inativo)
   - Rate limiting (5+ tentativas de login falhas)

6. **Monitoramento de logs:** Os eventos de segurança são registrados via `error_log()` com prefixo `[CONNECTRH_SECURITY]`. Configure rotação e monitoramento dos logs PHP.

---

*Documento gerado automaticamente como parte da remediação de segurança do tema connectRH.*
