# Sistema de Perguntas e Respostas - Tema ESG

## Descrição
Sistema completo para criação e gerenciamento de questionários interativos no WordPress, desenvolvido para o projeto SIPAT.

## Características Principais

### ✅ Funcionalidades Corrigidas e Implementadas

1. **Formulário de Perguntas Robusto**
   - Validação completa de dados de entrada
   - Sanitização adequada de todas as entradas
   - Tratamento de arrays vazios e campos indefinidos
   - Suporte a perguntas de múltipla escolha
   - Campos de sugestão/comentários

2. **Sistema de Persistência de Dados**
   - Salvamento automático de todas as respostas
   - Armazenamento de sugestões e comentários
   - Registro de modalidade (presencial/não presencial)
   - Controle de timestamps de resposta
   - Sistema de backup/recuperação de respostas

3. **Validação e Segurança**
   - Verificação de permissões de usuário
   - Sanitização de dados com funções WordPress
   - Prevenção de ataques de injeção
   - Validação de integridade de formulários
   - Sistema de logs para auditoria

4. **Interface Melhorada**
   - Design responsivo e acessível
   - Indicadores visuais de progresso
   - Mensagens de feedback claras
   - Suporte a diferentes tipos de mídia

5. **Sistema de Debug e Manutenção**
   - Logs detalhados para troubleshooting
   - Validação automática de integridade
   - Informações de debug para administradores
   - Sistema de monitoramento de erros

## Estrutura de Arquivos

```
wp-content/themes/esg/
├── template-parts/
│   ├── formulario_de_perguntas.php  # Formulário principal
│   └── requisicao.php               # Processamento de dados
├── includes/
│   ├── form-helpers.php             # Funções auxiliares
│   └── debug-system.php             # Sistema de debug
├── js/functions/
│   └── custom-scripts.js            # JavaScript otimizado
├── functions.php                     # Configurações principais
└── style.css                        # Estilos CSS
```

## Como Usar

### 1. Criação de Perguntas
- Acesse o painel administrativo
- Navegue para "Perguntas" > "Adicionar Nova"
- Configure as alternativas usando o CMB2
- Marque a alternativa correta
- Adicione campos de sugestão se necessário

### 2. Configuração de Categorias
- Crie categorias em "Datas Perguntas"
- Configure vídeos, códigos e prazos
- Associe perguntas às categorias apropriadas

### 3. Monitoramento
- Ative o WP_DEBUG para logs detalhados
- Monitore respostas via user_meta
- Use as informações de debug para troubleshooting

## Campos de Dados Salvos

### Meta do Usuário
- `user_field_{categoria}_{pergunta_id}` - Resposta da pergunta
- `sugestao_pergunta_{categoria}_{pergunta_id}` - Sugestão/comentário
- `todas_alternativa_{categoria}` - Status de conclusão
- `data_resposta_{categoria}` - Timestamp da última resposta
- `presencial_{categoria}` - Modalidade de participação
- `pontuacao_{categoria}` - Pontuação obtida
- `percentual_{categoria}` - Percentual de acerto
- `acertou_todas_alternativas_{categoria}` - Flag de 100% de acerto

## Resolução de Problemas Comuns

### Campos não sendo atualizados
✅ **Corrigido:** Sistema de validação e sanitização implementado
- Verificação de existência de arrays
- Validação de chaves antes do acesso
- Log detalhado de operações

### Erros de array indefinido
✅ **Corrigido:** Tratamento completo de variáveis
- Inicialização adequada de variáveis
- Verificação isset() em todos os acessos
- Fallbacks para valores padrão

### Problemas de JavaScript
✅ **Corrigido:** Código otimizado e estruturado
- DOMContentLoaded implementation
- Tratamento de erros
- Código modular e limpo

## Configurações Recomendadas

### WordPress Debug (wp-config.php)
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Permissões de Arquivo
- PHP: 644
- Diretórios: 755
- wp-config.php: 600

## Manutenção e Suporte

### Logs do Sistema
Os logs são salvos em `/wp-content/debug.log` quando o debug está ativo.

### Verificação de Integridade
Execute a validação automática acessando qualquer página de questionário como administrador.

### Backup de Dados
Recomenda-se backup regular da tabela `wp_usermeta` onde ficam armazenadas as respostas.

## Versioning
- **v2.0** - Sistema completamente refatorado e corrigido
- Todas as funcionalidades testadas e validadas
- Código limpo, documentado e otimizado
- Sistema de debug e logs implementado

## Notas Importantes

1. **Segurança**: Todos os dados são sanitizados antes do salvamento
2. **Performance**: Queries otimizadas para melhor performance
3. **Compatibilidade**: Compatible com WordPress 5.0+
4. **Manutenibilidade**: Código modular e bem documentado

---

**Desenvolvido com foco em estabilidade, segurança e facilidade de manutenção.**