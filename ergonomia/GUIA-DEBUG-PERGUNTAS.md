# 🔍 GUIA DE DEBUG: SISTEMA DE PERGUNTAS

## 📖 INTRODUÇÃO

Este documento é um guia prático para usar o sistema de logs implementado no `custom-scripts.js` para diagnosticar problemas no sistema de perguntas.

---

## 🎯 TIPOS DE LOGS

O sistema usa prefixos para categorizar logs:

| Prefixo | Significado | Exemplo |
|---------|-------------|---------|
| `[INIT]` | Inicialização | `[INIT] Códigos válidos carregados: ["ABC123", "XYZ789"]` |
| `[MODO]` | Mudança de modo | `[MODO] Código presencial ativado` |
| `[INPUT]` | Input do usuário | `[INPUT] Código digitado: ABC123` |
| `[VALIDAÇÃO]` | Resultado da validação | `[VALIDAÇÃO] ✓ Código válido reconhecido: ABC123` |
| `[VIDEO]` | Eventos de vídeo | `[VIDEO 0] Progresso: 85%` |
| `[PERGUNTAS]` | Manipulação de perguntas | `[PERGUNTAS] ✓ Container exibido: perguntas_ABC123` |
| `[PRESENCIAL]` | Marcação de presencial | `[PRESENCIAL] Marcado como presencial` |
| `[AJAX]` | Requisições ao servidor | `[AJAX] ✓ Conclusão registrada` |
| `[DEBUG]` | Informações de debug | `[DEBUG] Verifique se existe um elemento com ID: perguntas_ABC123` |
| `[FALLBACK]` | Ação alternativa | `[FALLBACK] Exibindo todas as perguntas disponíveis` |

---

## 📋 CENÁRIOS COMUNS DE DEBUG

### Cenário 1: "Digitei o código mas as perguntas não aparecem"

**Como diagnosticar:**

1. Abra o Console (F12 → Console)
2. Digite o código
3. Procure por esta sequência de logs:

```
[INPUT] Código digitado: ABC123
[VALIDAÇÃO] ✓ Código válido reconhecido: ABC123
[VIDEO] Exibido: video_ABC123
[PERGUNTAS] ✓ Container exibido: perguntas_ABC123
[PRESENCIAL] Marcado como presencial
```

**Interpretação dos resultados:**

#### ✅ Log correto completo
```
[INPUT] Código digitado: ABC123
[VALIDAÇÃO] ✓ Código válido reconhecido: ABC123
[PERGUNTAS] ✓ Container exibido: perguntas_ABC123
```
**Status:** Sistema funcionando corretamente

---

#### ❌ Problema: Código não é reconhecido
```
[INPUT] Código digitado: ABC123
[VALIDAÇÃO] ✗ Código inválido: ABC123
```

**Causa:** Código não está no vetor de códigos válidos

**Solução:**
1. Procure o log de inicialização no topo do console:
   ```
   [INIT] Códigos válidos carregados: ["XYZ789", "DEF456"]
   ```
2. Verifique se `ABC123` está na lista
3. **Se NÃO está:** 
   - Código não foi cadastrado no WordPress
   - Vá para: WordPress Admin → Perguntas → Taxonomias → Datas Perguntas
   - Edite a categoria e cadastre o código
   - ⚠️ **IMPORTANTE:** Digite sempre em UPPERCASE sem espaços

4. **Se ESTÁ na lista mas ainda não valida:**
   - Pode haver diferença de case ou espaços
   - Exemplo: Vetor tem `"ABC123"` mas input tem `"abc123"` ou `" ABC123 "`
   - **Correção já implementada:** O sistema agora faz trim() e uppercase() automaticamente
   - Se ainda falhar, limpe o cache do navegador (Ctrl+Shift+Del)

---

#### ❌ Problema: Código reconhecido mas container não encontrado
```
[INPUT] Código digitado: ABC123
[VALIDAÇÃO] ✓ Código válido reconhecido: ABC123
[PERGUNTAS] ✗ Container NÃO encontrado: perguntas_ABC123
[DEBUG] Verifique se existe um elemento com ID: perguntas_ABC123
[FALLBACK] Exibindo todas as perguntas disponíveis
```

**Causa:** ID do container no HTML não corresponde ao esperado

**Solução:**
1. Vá para F12 → Elements (Elementos)
2. Procure (Ctrl+F) por: `box-perguntas`
3. Verifique o ID do elemento encontrado
4. Exemplo do que você deve ver:
   ```html
   <div class="box-perguntas" id="perguntas_ABC123">
   ```
5. Compare com o log:
   - **Se IDs são diferentes:** Problema de sanitização no PHP
   - **Se não existe nenhum elemento:** Template não foi carregado
6. **Correção já implementada:** PHP agora sanitiza códigos automaticamente
7. **Se problema persiste:**
   - Recarregue completamente (Ctrl+F5)
   - Limpe cache do WordPress (plugin de cache ou via admin)

---

### Cenário 2: "Assisti o vídeo mas as perguntas não aparecem"

**Como diagnosticar:**

1. Reproduza o vídeo
2. Observe o console enquanto assiste
3. Procure por:

```
[VIDEO 0] Progresso: 10%
[VIDEO 0] Progresso: 20%
...
[VIDEO 0] Progresso: 90%
[VIDEO 0] ✓ 99% atingido - Liberando perguntas
[PERGUNTAS] Container liberado (índice 0)
[AJAX] Enviando conclusão para servidor...
[AJAX] ✓ Conclusão registrada: {resposta}
```

**Interpretação dos resultados:**

#### ✅ Log correto completo
```
[VIDEO 0] Progresso: 99%
[VIDEO 0] ✓ 99% atingido - Liberando perguntas
[PERGUNTAS] Container liberado (índice 0)
```
**Status:** Sistema funcionando corretamente

---

#### ❌ Problema: Nenhum log de progresso aparece
```
(nada no console relacionado a VIDEO)
```

**Causa:** Event listener não foi registrado no elemento `<video>`

**Solução:**
1. Verifique no Elements se existe `<video>` na página
2. Confirme que está dentro de um `.video-box`:
   ```html
   <div class="video-box">
     <div class="wp-video">
       <video>...</video>
     </div>
   </div>
   ```
3. **Se estrutura está correta mas logs não aparecem:**
   - Erro no JavaScript bloqueou execução
   - Procure por erros em vermelho no console
   - Se encontrar, reporte o erro exato

---

#### ❌ Problema: Logs aparecem mas param antes de 99%
```
[VIDEO 0] Progresso: 85%
[VIDEO 0] Progresso: 86%
(para aqui, não chega em 99%)
```

**Causa:** Vídeo não está sendo reproduzido até o final

**Solução:**
1. Aguarde até o vídeo realmente terminar
2. Não pule para o final (seek), alguns navegadores não computam como "assistido"
3. Se vídeo trava/bufferiza: problema de internet ou servidor de vídeo

---

#### ❌ Problema: 99% atingido mas container não liberado
```
[VIDEO 0] ✓ 99% atingido - Liberando perguntas
[PERGUNTAS] Container não encontrado no índice: 0
```

**Causa:** Não existe `.box-perguntas` na página ou índice está incorreto

**Solução:**
1. Verifique no Elements quantos `.box-perguntas` existem
2. Use: `document.querySelectorAll('.box-perguntas')`
3. O índice no log deve corresponder à posição no array
4. **Se não existe nenhum:** Template `formulario_de_perguntas.php` não foi incluído
5. **Se existe mas índice errado:** 
   - Pode haver múltiplos vídeos na página
   - Verifique quantos `<video>` existem
   - Índice deve corresponder (primeiro vídeo = índice 0)

---

### Cenário 3: "AJAX não envia o progresso"

**Como diagnosticar:**

Procure por:
```
[AJAX] Enviando conclusão para servidor...
```

**Interpretação:**

#### ✅ Log completo correto
```
[AJAX] Enviando conclusão para servidor... {usuario: "123", categoria: "segunda"}
[AJAX] ✓ Conclusão registrada: {resposta do servidor}
```
**Status:** Funcionando corretamente

---

#### ❌ Problema: AJAX não é enviado
```
[VIDEO 0] ✓ 99% atingido - Liberando perguntas
(sem log de AJAX)
```

**Causa:** Inputs hidden não encontrados

**Solução:**
1. Verifique no Elements se existem:
   ```html
   <input type="hidden" id="id_usuario" value="123">
   <input type="hidden" id="categoria" value="segunda">
   ```
2. Se não existem: template `cabecalho.php` não foi incluído
3. Se existem mas AJAX não envia: procure erro no console

---

#### ❌ Problema: AJAX enviado mas erro 404
```
[AJAX] Enviando conclusão para servidor...
[AJAX] ✗ Erro ao registrar: 404 Not Found
```

**Causa:** URL do endpoint está incorreta

**Solução:**
1. Vá para `custom-scripts.js` linha ~205
2. Verifique:
   ```javascript
   url: 'https://www.campaq.com.br/wp-content/themes/Campaq/sql/progresso-video.php',
   ```
3. Confirme que o arquivo PHP existe nesse caminho
4. Ajuste o caminho se necessário

---

#### ❌ Problema: jQuery não disponível
```
[AJAX] jQuery não disponível - registro não enviado
[AJAX/FETCH] ✓ Conclusão registrada: {resposta}
```

**Status:** Não é erro! Sistema usou Fetch API como fallback

---

## 🛠️ FERRAMENTAS DE DEBUG ADICIONAIS

### 1. Verificar códigos válidos carregados

**Console:**
```javascript
// Digite no console do navegador:
console.table(vetor);
```

**Resultado esperado:**
```
┌─────────┬──────────┐
│ (index) │  Values  │
├─────────┼──────────┤
│    0    │ "ABC123" │
│    1    │ "XYZ789" │
│    2    │ "DEF456" │
└─────────┴──────────┘
```

---

### 2. Verificar elementos na página

**Console:**
```javascript
// Listar todos os containers de perguntas:
document.querySelectorAll('.box-perguntas').forEach((el, i) => {
    console.log(i, el.id, el.classList.contains('ativo'));
});
```

**Resultado esperado:**
```
0 "perguntas_ABC123" false
1 "perguntas_XYZ789" false
```

---

### 3. Forçar exibição de perguntas (teste)

**Console:**
```javascript
// Forçar exibição de todas as perguntas:
document.querySelectorAll('.box-perguntas').forEach(el => {
    el.classList.add('ativo');
});
```

**Efeito:** Todas as perguntas ficam visíveis (útil para testar se problema é visual ou lógico)

---

### 4. Verificar inputs hidden

**Console:**
```javascript
// Listar todos os inputs de código:
document.querySelectorAll('.input_codigo_oculto').forEach((el, i) => {
    console.log(i, el.id, el.value);
});
```

**Resultado esperado:**
```
0 "teste" "ABC123"
1 "video_segunda_45" "XYZ789"
```

---

### 5. Simular digitação de código (teste)

**Console:**
```javascript
// Simular que usuário digitou código:
var input = document.querySelector('.codigo input[type=text]');
input.value = 'ABC123';
input.dispatchEvent(new KeyboardEvent('keyup'));
```

**Efeito:** Mesma ação de digitar código manualmente, útil para testar automaticamente

---

## 📊 MATRIZ DE PROBLEMAS x SOLUÇÕES

| Sintoma | Log observado | Causa provável | Solução |
|---------|---------------|----------------|---------|
| Perguntas não aparecem (código) | `[VALIDAÇÃO] ✗ Código inválido` | Código não cadastrado ou diferente | Cadastrar código no WP Admin (uppercase, sem espaços) |
| Perguntas não aparecem (código) | `[PERGUNTAS] ✗ Container NÃO encontrado` | ID do HTML diferente do JS | Já corrigido (sanitização), limpar cache |
| Perguntas não aparecem (vídeo) | Nenhum log de VIDEO | Event listener não registrado | Verificar estrutura HTML do vídeo |
| Perguntas não aparecem (vídeo) | `[VIDEO] Progresso: X%` para antes de 99% | Vídeo não reproduzido até final | Aguardar vídeo completar |
| Perguntas não aparecem (vídeo) | `[PERGUNTAS] Container não encontrado no índice` | `.box-perguntas` não existe | Verificar inclusão de `formulario_de_perguntas.php` |
| AJAX não envia | `[AJAX] Inputs ocultos não encontrados` | Faltam `#id_usuario` ou `#categoria` | Verificar inclusão de `cabecalho.php` |
| AJAX erro 404 | `[AJAX] ✗ Erro: 404` | URL do endpoint incorreta | Corrigir caminho em `custom-scripts.js` |
| Múltiplos envios AJAX | Muitos logs `[AJAX] Enviando...` | Flag `jaEnviouAjax` não funciona | Já corrigido na versão atual |

---

## 🎓 INTERPRETANDO A SEQUÊNCIA COMPLETA DE LOGS

### ✅ Fluxo PERFEITO (Código Presencial)

```
1. [INIT] Códigos válidos carregados: ["ABC123"]
2. [MODO] Código presencial ativado
3. [INPUT] Código digitado: ABC123
4. [VALIDAÇÃO] ✓ Código válido reconhecido: ABC123
5. [VIDEO] Exibido: video_ABC123
6. [PERGUNTAS] ✓ Container exibido: perguntas_ABC123
7. [PRESENCIAL] Marcado como presencial
```

**Significado:**
- Sistema inicializou com 1 código válido
- Usuário escolheu digitar código
- Digitou ABC123 corretamente
- Sistema localizou e exibiu vídeo e perguntas
- Marcou usuário como tendo participado presencialmente

---

### ✅ Fluxo PERFEITO (Assistir Vídeo)

```
1. [INIT] Códigos válidos carregados: ["ABC123"]
2. [MODO] Assistir vídeo completo ativado
3. [VIDEO 0] Progresso: 10%
4. [VIDEO 0] Progresso: 20%
   ... (logs a cada 10%)
5. [VIDEO 0] Progresso: 90%
6. [VIDEO 0] ✓ 99% atingido - Liberando perguntas
7. [PERGUNTAS] Container liberado (índice 0)
8. [AJAX] Enviando conclusão para servidor... {usuario: "123", categoria: "segunda"}
9. [AJAX] ✓ Conclusão registrada: success
```

**Significado:**
- Sistema inicializou com 1 código válido
- Usuário escolheu assistir vídeo
- Vídeo foi reproduzido até 99%
- Perguntas foram liberadas
- Progresso foi registrado no servidor com sucesso

---

### ❌ Fluxo COM ERRO (Exemplo 1)

```
1. [INIT] Códigos válidos carregados: ["XYZ789"]
2. [MODO] Código presencial ativado
3. [INPUT] Código digitado: ABC123
4. [VALIDAÇÃO] ✗ Código inválido: ABC123
```

**Problema identificado:** Usuário digitou código que não está cadastrado

---

### ❌ Fluxo COM ERRO (Exemplo 2)

```
1. [INIT] Códigos válidos carregados: ["ABC123"]
2. [MODO] Código presencial ativado
3. [INPUT] Código digitado: ABC123
4. [VALIDAÇÃO] ✓ Código válido reconhecido: ABC123
5. [VIDEO] Não encontrado: video_ABC123
6. [PERGUNTAS] ✗ Container NÃO encontrado: perguntas_ABC123
7. [DEBUG] Verifique se existe um elemento com ID: perguntas_ABC123
8. [FALLBACK] Exibindo todas as perguntas disponíveis
```

**Problema identificado:** ID no HTML não corresponde (cache antigo ou sanitização falhou)

---

## 📝 BOAS PRÁTICAS DE DEBUG

1. **Sempre limpe o console antes de testar**
   - Clique em "🚫 Clear console" ou Ctrl+L
   - Evita confusão com logs antigos

2. **Teste um cenário por vez**
   - Não misture "Código" e "Assistir" no mesmo teste
   - Recarregue a página entre testes (F5)

3. **Documente o que encontrar**
   - Copie logs relevantes (botão direito → Copy)
   - Anote o comportamento observado
   - Facilita reportar bugs

4. **Use filtros do console**
   - Filtre por: `[PERGUNTAS]` para ver só logs de perguntas
   - Filtre por: `✗` para ver só erros
   - Filtre por: `[VALIDAÇÃO]` para debug de códigos

5. **Teste com diferentes navegadores**
   - Chrome, Firefox, Edge
   - Alguns problemas são específicos de browser

---

## 🚀 DICAS AVANÇADAS

### Monitorar eventos em tempo real

```javascript
// Cole no console para ver TODOS os eventos do sistema:
const originalLog = console.log;
console.log = function(...args) {
    if (args[0] && typeof args[0] === 'string' && args[0].startsWith('[')) {
        originalLog.apply(console, args);
        // Aqui você pode adicionar lógica personalizada
        // Por exemplo, enviar para servidor de logs
    } else {
        originalLog.apply(console, args);
    }
};
```

### Debugger Breakpoints

Adicione breakpoints no código para pausar execução:

```javascript
// No custom-scripts.js, linha da validação:
if (vetor.includes(valorDigitado)) {
    debugger; // ← Adicione esta linha
    console.log('[VALIDAÇÃO] ✓ Código válido reconhecido:', valorDigitado);
```

Quando código for válido, browser pausará e você pode inspecionar variáveis.

---

## 📞 QUANDO ESCALAR O PROBLEMA

Escale para desenvolvedor se:

1. ✅ Seguiu todos os passos deste guia
2. ✅ Verificou logs e identificou problema
3. ✅ Problema persiste após:
   - Limpar cache (navegador + WordPress)
   - Testar em navegador diferente
   - Recarregar página completamente (Ctrl+F5)

**Ao reportar, inclua:**
- Print do console completo
- Print do HTML (Elements) mostrando IDs
- Navegador e versão
- Passos exatos para reproduzir
- Categoria/código testado

---

**Última atualização:** 24/02/2026  
**Versão do guia:** 1.0  
**Sistema:** WordPress + CMB2 + Custom Scripts
