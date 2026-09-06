# ConnectRH — Documentação Técnica do Projeto

> **Objetivo deste documento:** Apresentar de forma clara e acessível as tecnologias, linguagens e a arquitetura do sistema ConnectRH para contexto de reunião.

---

## 1. O que é o ConnectRH?

O ConnectRH é uma **plataforma web interna** voltada para Recursos Humanos, construída como um tema personalizado do WordPress. O sistema permite:

- **Gestão de vídeos institucionais** organizados por categorias
- **Questionários/pesquisas** vinculados a eventos (ex: SIPAT)
- **Gerenciamento de usuários** com importação em massa via CSV
- **Dashboard administrativo** com KPIs e estatísticas em tempo real
- **Rastreamento de progresso** de visualização de vídeos por colaborador
- **Controle de acesso por perfil** (administrador vs. usuário comum)

---

## 2. Visão Geral das Tecnologias

| Camada | Tecnologia | Versão/Detalhes |
|--------|-----------|----------------|
| **CMS** | WordPress | Plataforma base (core) |
| **Servidor Web** | Apache (XAMPP) | Servidor local com PHP integrado |
| **Linguagem Backend** | PHP | Toda a lógica de servidor |
| **Banco de Dados** | MySQL/MariaDB | Via WordPress (`$wpdb`) |
| **Linguagem Frontend** | JavaScript (ES6) | Interatividade e AJAX |
| **Marcação** | HTML5 | Estrutura das páginas |
| **Estilização** | CSS3 | Layout e design visual |
| **Framework CSS** | Materialize CSS | Componentes visuais (cards, grids, sidenav) |
| **Biblioteca JS** | jQuery | Manipulação DOM e requisições AJAX |

---

## 3. Tecnologias em Detalhe

### 3.1 WordPress (CMS)

O WordPress serve como **base do sistema**, fornecendo:

| Recurso do WordPress | Como é usado no ConnectRH |
|----------------------|--------------------------|
| **Custom Post Types** | `perguntas` (questionários) e `videos` (conteúdo audiovisual) |
| **Taxonomias customizadas** | `datas_perguntas` (datas de eventos), `categoria_videos` (categorização de vídeos), `unidades` (unidades/filiais da empresa) |
| **User Meta** | Armazena respostas, progresso de vídeos, unidade do colaborador, status de senha |
| **AJAX (`admin-ajax.php`)** | Comunicação assíncrona entre frontend e backend |
| **Page Templates** | Cada página do sistema tem um template PHP dedicado |
| **Nonce/CSRF** | Proteção contra requisições forjadas |
| **Transients** | Cache temporário (ex: rate limiting de login) |
| **Hooks (actions/filters)** | Extensão do comportamento do WordPress sem alterar o core |

### 3.2 PHP (Backend)

O PHP processa toda a lógica de negócios:

- **Autenticação customizada** — Login por matrícula (não email), troca de senha com política de complexidade
- **Processamento de formulários** — Validação e salvamento de respostas de questionários
- **Importação de usuários** — Upload e parsing de arquivo CSV para criação em massa
- **Endpoints AJAX** — ~20 handlers para operações CRUD (criar, ler, atualizar, deletar)
- **Segurança** — Rate limiting, logging de eventos, sanitização de entrada de dados

### 3.3 JavaScript + jQuery (Frontend)

O JavaScript gerencia toda a interatividade da aplicação:

| Arquivo JS | Função |
|-----------|--------|
| `admin-usuarios.js` | CRUD de usuários, filtros, paginação, seleção em lote |
| `admin-videos.js` | CRUD de vídeos, upload, associação a categorias |
| `admin-categorias-videos.js` | CRUD de categorias de vídeos |
| `categorias-cards.js` | Exibição pública de categorias em cards interativos |
| `dashboard.js` | Dashboard com KPIs, gráficos, alertas e atividade recente |
| `taxonomia-videos.js` | Listagem e busca de vídeos por categoria |
| `custom-scripts.js` | Rastreamento de progresso de vídeo, interações gerais |
| `session-timeout.js` | Auto-logout por inatividade (30 min) |

**Padrão de comunicação:** Todas as operações usam **AJAX assíncrono** — a página não recarrega ao criar, editar ou excluir dados.

### 3.4 Materialize CSS (Framework de Interface)

O [Materialize CSS](https://materializecss.com/) é um framework baseado no Material Design do Google, fornecendo:

- **Grid responsivo** — Layout adaptável a celular, tablet e desktop
- **Componentes prontos** — Cards, botões, modais, selects, sidenav
- **Sidenav fixa** — Menu lateral de navegação do painel administrativo
- **Classes utilitárias** — Espaçamento, alinhamento, cores

### 3.5 Bibliotecas Externas (via CDN)

| Biblioteca | Uso no projeto |
|-----------|---------------|
| **SweetAlert2** | Modais de confirmação, alertas bonitos, feedback de ações (substitui `alert()` nativo) |
| **Font Awesome 6** | Ícones em toda a interface (menus, botões, KPIs, etc.) |
| **GSAP 3** | Animações suaves na página de categorias (fade-in dos cards) |
| **Google Fonts** | Tipografias Montserrat e Inter para o design visual |
| **Slick.js** | Carrossel/slider de conteúdo |
| **js-cookie** | Gerenciamento de cookies (aviso LGPD) |

### 3.6 Plugin: CMB2 (Custom Meta Boxes)

O [CMB2](https://cmb2.io/) é um plugin WordPress que cria **campos personalizados** no painel administrativo:

- Campos nos perfis de usuário (unidade, empresa, respostas de pesquisas)
- Campos em posts de perguntas (alternativas, resposta correta, sugestão)
- Campos em taxonomias (vídeo da categoria, iframe, horários, atração)
- Campos em vídeos (grupo repetível com título + upload por seção)

> **Em termos simples:** O CMB2 permite criar "formulários extras" dentro do WordPress para armazenar dados específicos do negócio.

---

## 4. Arquitetura do Sistema

```
┌─────────────────────────────────────────────────────────┐
│                    NAVEGADOR DO USUÁRIO                  │
│  HTML + CSS (Materialize) + JavaScript (jQuery + AJAX)  │
└──────────────────────────┬──────────────────────────────┘
                           │ Requisições HTTP / AJAX
                           ▼
┌─────────────────────────────────────────────────────────┐
│                   SERVIDOR APACHE (XAMPP)                │
│                                                         │
│  ┌───────────────────────────────────────────────────┐  │
│  │              WORDPRESS (CMS Core)                  │  │
│  │                                                   │  │
│  │  ┌─────────────────────────────────────────────┐  │  │
│  │  │         TEMA CONNECTRH (Customizado)         │  │  │
│  │  │                                             │  │  │
│  │  │  • functions.php    → Lógica central        │  │  │
│  │  │  • pages/           → Templates de página   │  │  │
│  │  │  • sql/             → Endpoints de dados    │  │  │
│  │  │  • template-parts/  → Componentes visuais   │  │  │
│  │  │  • js/functions/    → Scripts interativos   │  │  │
│  │  │  • css/             → Estilos visuais       │  │  │
│  │  │  • includes/        → Helpers e debug       │  │  │
│  │  └─────────────────────────────────────────────┘  │  │
│  │                                                   │  │
│  │  ┌──────────────┐                                 │  │
│  │  │ Plugin CMB2  │ → Campos personalizados         │  │
│  │  └──────────────┘                                 │  │
│  └───────────────────────────────────────────────────┘  │
│                           │                              │
│                           ▼                              │
│  ┌───────────────────────────────────────────────────┐  │
│  │             BANCO DE DADOS MySQL/MariaDB           │  │
│  │  • wp_users / wp_usermeta  (usuários e respostas) │  │
│  │  • wp_posts / wp_postmeta  (vídeos e perguntas)   │  │
│  │  • wp_terms / wp_term_meta (categorias e datas)   │  │
│  │  • wp_options              (configurações)        │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## 5. Estrutura de Pastas do Tema

```
connectrh/
│
├── functions.php                  → Arquivo central (~2.400 linhas)
│                                    Registra post types, taxonomias, handlers AJAX,
│                                    segurança, enqueue de scripts
│
├── header.php                     → Cabeçalho HTML (meta tags, CSS, fontes)
├── footer.php                     → Rodapé HTML (scripts JS, cookie LGPD)
├── style.css                      → Estilos customizados do tema
├── index.php / page.php           → Templates base do WordPress
├── single-videos.php              → Página individual de vídeo (player)
├── taxonomy-categoria_videos.php  → Listagem de vídeos por categoria
│
├── pages/                         → Templates de página (10 arquivos)
│   ├── login.php                  → Tela de login (matrícula + senha)
│   ├── nova-senha.php             → Troca de senha com validação
│   ├── regulamento.php            → Aceite de regulamento (iframe PDF)
│   ├── template-dashboard.php     → Dashboard central com KPIs
│   ├── template-page-usuarios.php → Gestão de usuários (CRUD)
│   ├── template-inserir.php       → Importação de usuários via CSV
│   ├── template-page-categorias.php        → Visualização de categorias
│   ├── template-page-criar-categorias.php  → CRUD de categorias (admin)
│   ├── template-page-criar-video.php       → CRUD de vídeos (admin)
│   └── template-resultado.php     → Relatório de respostas dos usuários
│
├── sql/                           → Endpoints de processamento de dados
│   ├── inserir-usuarios.php       → Importação em massa (CSV → WordPress)
│   ├── excluir-usuarios.php       → Exclusão em massa
│   └── progresso-video.php        → Registro de progresso de vídeo
│
├── template-parts/                → Componentes reutilizáveis
│   ├── sidenav.php                → Menu lateral de navegação
│   ├── formulario_de_perguntas.php → Formulário de pesquisa/questionário
│   ├── requisicao.php             → Processamento de respostas
│   └── cabecalhos/                → Cabeçalhos de seções
│       ├── cabecalho.php          → Cabeçalho padrão
│       ├── atracao_e_video.php    → Player de vídeo da atração
│       └── progresso-video.php    → Barra de progresso
│
├── js/                            → JavaScript
│   ├── jquery.min.js              → jQuery (local)
│   ├── materialize.min.js         → Materialize CSS (local)
│   ├── slick.min.js               → Slick Carousel
│   ├── cookies.min.js             → js-cookie
│   ├── script.js                  → Inicializações Materialize
│   └── functions/                 → Scripts específicos por funcionalidade
│       ├── admin-usuarios.js      → CRUD de usuários
│       ├── admin-videos.js        → CRUD de vídeos
│       ├── admin-categorias-videos.js → CRUD de categorias
│       ├── categorias-cards.js    → Cards públicos de categorias
│       ├── dashboard.js           → Dashboard KPIs e gráficos
│       ├── taxonomia-videos.js    → Listagem por taxonomia
│       ├── custom-scripts.js      → Progresso de vídeo
│       └── session-timeout.js     → Timeout de sessão
│
├── css/                           → Folhas de estilo
│   ├── materialize.css            → Materialize (fonte)
│   ├── materialize.min.css        → Materialize (minificado)
│   └── admin-usuarios.css         → Estilos do painel administrativo
│
├── includes/                      → Código auxiliar PHP
│   ├── form-helpers.php           → Validação e salvamento de formulários
│   └── debug-system.php           → Sistema de logs estruturado
│
├── img/                           → Imagens (banners)
└── fonts/                         → Fontes (reservado)
```

---

## 6. Fluxos Principais

### 6.1 Login do Colaborador
```
Colaborador → Digita matrícula e senha → PHP valida com WordPress
    → Se correto: redireciona para categorias de vídeos
    → Se incorreto: mensagem genérica + rate limiting (5 tentativas)
    → Se primeiro acesso: redireciona para troca de senha obrigatória
```

### 6.2 Assistir Vídeo 
```
Colaborador → Escolhe categoria → Escolhe vídeo → Assiste vídeo

```

### 6.3 Gestão de Usuários (Admin)
```
Administrador → Dashboard → Gerenciar Usuários
    → Criar individual (modal SweetAlert2) → AJAX → PHP → Banco
    → Importar em massa (upload CSV) → PHP → Cria usuários em loop
    → Editar / Excluir (individual ou em lote) → AJAX → PHP → Banco
```

### 6.4 Dashboard Central (Admin)
```
Administrador → Home → Dashboard carrega KPIs via AJAX:
    → Total de usuários, vídeos, categorias
    → Atividade recente, alertas do sistema
    → Atalhos rápidos para todas as funcionalidades
```

---

## 7. Como os Dados são Armazenados

O WordPress utiliza um modelo **relacional flexível** com meta-dados:

| Dado | Tabela WordPress | Exemplo |
|------|-----------------|---------|
| Colaboradores | `wp_users` + `wp_usermeta` | Matrícula, unidade, respostas |
| Vídeos | `wp_posts` (type: `videos`) + `wp_postmeta` | Título, seções com upload |
| Categorias de vídeos | `wp_terms` (taxonomy: `categoria_videos`) | Nome, slug, descrição |
| Unidades/filiais | `wp_terms` (taxonomy: `unidades`) | Nome da unidade |


---

## 8. Segurança Implementada

| Proteção | Descrição |
|----------|-----------|
| **CSRF (Nonce)** | Tokens em todos os formulários e requisições AJAX |
| **Rate Limiting** | Bloqueio de login após 5 tentativas (15 min) |
| **Política de Senhas** | Mínimo 8 caracteres, complexidade obrigatória, histórico das últimas 5 |
| **Session Timeout** | Auto-logout após 30 min de inatividade |
| **Security Headers** | X-Frame-Options, CSP, HSTS, X-Content-Type-Options |
| **Cookies Seguros** | HttpOnly, SameSite, Secure |
| **Logging de Segurança** | Registro de login, logout, falhas, trocas de senha |
| **Sanitização** | Todas as entradas de dados são sanitizadas antes de uso |
| **Controle de Acesso** | Verificação de permissão em todas as páginas admin |
| **.htaccess** | Bloqueio de arquivos sensíveis e listagem de diretórios |

---

## 9. Resumo Rápido para Apresentação

> **"O ConnectRH é um sistema web construído sobre WordPress que funciona como uma plataforma interna de RH. Ele permite gerenciar vídeos institucionais, aplicar questionários aos colaboradores, importar usuários em massa e acompanhar a participação de cada um. O backend é PHP, o frontend é JavaScript com jQuery produzindo uma experiência dinâmica sem recarregar a página, e o visual usa Materialize CSS baseado no Material Design do Google. Todos os dados ficam no banco MySQL do WordPress, usando campos personalizados via o plugin CMB2."**

### Em números:
- **30** arquivos PHP
- **11** arquivos JavaScript (sem contar bibliotecas minificadas)
- **3** arquivos CSS personalizados
- **~20** endpoints AJAX para operações em tempo real
- **2** post types customizados (vídeos + perguntas)
- **3** taxonomias customizadas (categorias de vídeos, datas, unidades)
- **10** templates de página dedicados

---

*Documentação preparada para apresentação em reunião — Fevereiro 2026*
