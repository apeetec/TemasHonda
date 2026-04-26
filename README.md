# 🏍️ Temas Honda

Repositório de **temas WordPress personalizados** desenvolvidos para sites Honda. Este projeto centraliza os temas utilizados em diferentes projetos e portais da Honda, com foco em ergonomia, qualidade e engajamento de usuários.

---

## 📁 Estrutura do Repositório

```
TemasHonda/
├── ergonomia/          # Tema WordPress — Portal Ergonomia Honda
│   ├── css/            # Estilos complementares
│   ├── js/             # Scripts JavaScript
│   ├── img/            # Imagens do tema
│   ├── functions/      # Funções auxiliares modularizadas
│   ├── pages/          # Templates de páginas
│   ├── template-parts/ # Partes reutilizáveis de templates
│   ├── sql/            # Scripts SQL auxiliares
│   ├── functions.php   # Funções principais do tema
│   ├── header.php      # Cabeçalho do tema
│   ├── footer.php      # Rodapé do tema
│   ├── index.php       # Template principal
│   ├── page.php        # Template de página
│   ├── style.css       # Estilos principais + registro do tema
│   ├── taxonomy-datas_perguntas.php   # Taxonomy: datas de perguntas
│   └── taxonomy-datas_sorteados.php   # Taxonomy: datas de sorteados
├── esg/                # Tema/módulo ESG Honda (em desenvolvimento)
├── qualidadeptr_new/   # Tema Qualidade PTR Honda (em desenvolvimento)
├── connectrh/          # Tema Connect RH Honda (em desenvolvimento)
└── index.php           # Arquivo de segurança raiz
```

---

## 🎯 Temas Disponíveis

### 🏋️ Ergonomia Honda
Tema WordPress completo para o portal [ergonomiahonda.com.br](https://ergonomiahonda.com.br).

**Funcionalidades:**
- Sistema de perguntas e sorteios com taxonomias personalizadas (`datas_perguntas`, `datas_sorteados`)
- Grid responsivo com 5 colunas para navegação por categorias
- Integração com o framework **Materialize CSS** (modais, componentes UI)
- Importação/exportação de usuários via CSV
- Header com menu de navegação responsivo
- Footer customizado
- Funções modularizadas por responsabilidade

### 📋 Outros Módulos
| Módulo | Descrição | Status |
|---|---|---|
| `esg` | Portal ESG Honda | 🔧 Em desenvolvimento |
| `qualidadeptr_new` | Sistema de Qualidade PTR | 🔧 Em desenvolvimento |
| `connectrh` | Portal Connect RH | 🔧 Em desenvolvimento |

---

## 🛠️ Tecnologias Utilizadas

- **PHP** — Linguagem principal (templates e lógica WordPress)
- **WordPress** — CMS base para todos os temas
- **Materialize CSS** — Framework de UI utilizado no tema Ergonomia
- **JavaScript** — Interatividade e inicialização de componentes
- **CSS3** — Estilização customizada dos temas
- **MySQL / SQL** — Scripts auxiliares de banco de dados

---

## 🚀 Como Utilizar

### Pré-requisitos
- WordPress instalado e configurado
- PHP 7.4 ou superior
- Servidor web (Apache/Nginx)

### Instalação de um tema

1. Clone o repositório:
   ```bash
   git clone https://github.com/apeetec/TemasHonda.git
   ```

2. Copie a pasta do tema desejado para o diretório de temas do WordPress:
   ```bash
   cp -r TemasHonda/ergonomia /var/www/html/wp-content/themes/ergonomia
   ```

3. No painel do WordPress, acesse **Aparência → Temas** e ative o tema desejado.

---

## 📄 Documentação

Documentações internas disponíveis no repositório:

- [`ergonomia/CORRECOES-SISTEMA-PERGUNTAS.md`](ergonomia/CORRECOES-SISTEMA-PERGUNTAS.md) — Registro de correções no sistema de perguntas
- [`ergonomia/GUIA-DEBUG-PERGUNTAS.md`](ergonomia/GUIA-DEBUG-PERGUNTAS.md) — Guia de debug do sistema de perguntas

---

## 🤝 Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature: `git checkout -b feature/minha-feature`
3. Commit suas alterações: `git commit -m 'feat: adiciona minha feature'`
4. Push para a branch: `git push origin feature/minha-feature`
5. Abra um Pull Request

---

## 📝 Licença

Este projeto é de uso interno. Todos os direitos reservados à **Honda**.

---

*Desenvolvido por [@apeetec](https://github.com/apeetec)*