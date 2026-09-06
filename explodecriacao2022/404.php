<?php
/**
 * Página de endereço não encontrado.
 *
 * Antes o tema redirecionava todo 404 para a página inicial, o que fazia um link
 * quebrado parecer que tinha funcionado e escondia o problema de quem precisava
 * diagnosticar. Agora o erro é mostrado com o código HTTP correto.
 */

get_header();
?>

<div class="main">
  <div class="container">
    <div class="content">

      <div class="aviso-etapa">
        <h2>Página não encontrada</h2>
        <p>O endereço que você acessou não existe ou foi removido.</p>
        <a class="botao-etapa" href="<?php echo esc_url( home_url( '/' ) ); ?>">Voltar para a página inicial</a>
      </div>

    </div>
  </div>
</div>

<?php
// Estilo compartilhado das caixas de mensagem
get_template_part( 'template-part/estilo-mensagens' );

get_footer();
