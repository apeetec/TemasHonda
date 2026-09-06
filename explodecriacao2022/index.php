<?php
/**
 * Página inicial do colaborador.
 *
 * O que aparece aqui é decidido pela EMPRESA a que o colaborador pertence e pela
 * ETAPA em que essa empresa está. As duas coisas são configuradas em "Empresas" no painel.
 *
 * Antes esta decisão era feita comparando o nome do grupo com textos fixos
 * ("Grupo 1", "Grupo 1 MAO"...), o que deixava de fora qualquer grupo não previsto,
 * não funcionava para quem tinha mais de um grupo e falhava em silêncio: a pessoa
 * recebia uma página em branco, sem nenhuma indicação do problema.
 */

get_header();

// Colaborador logado
$user_id = get_current_user_id();

// Empresa e etapa vindas da configuração do painel
$empresa = function_exists( 'explode_empresa_do_usuario' ) ? explode_empresa_do_usuario( $user_id ) : null;
$etapa   = function_exists( 'explode_etapa_do_usuario' )   ? explode_etapa_do_usuario( $user_id )   : -1;

?>

<div class="main">
  <div class="container">
  	<div class="content">
      <?php

      // ETAPA 1 - Envio dos desenhos
      if ( 1 === $etapa ) {

        get_template_part( 'template-part/formularios' );

      }
      // ETAPA 2 - Votação
      // O envio para a página de votação acontece no hook template_redirect, antes de
      // qualquer saída. Este bloco atende quem chegar aqui mesmo assim.
      elseif ( 2 === $etapa ) { ?>

        <div class="aviso-etapa">
          <h2>A votação está aberta</h2>
          <p>Escolha os desenhos preferidos do seu grupo na página de votação.</p>
          <a class="botao-etapa" href="<?php echo esc_url( home_url( 'filtros' ) ); ?>">Ir para a votação</a>
        </div>

      <?php }
      // ETAPA 3 - Resultados
      elseif ( 3 === $etapa ) {

        get_template_part( 'template-part/resultados-cartaz' );

      }
      // ETAPA 4 - Aviso
      elseif ( 4 === $etapa ) {

        get_template_part( 'template-part/aviso' );

      }
      // ETAPA 0 - Aguardando: a empresa existe, mas ainda não liberou nenhuma etapa
      elseif ( 0 === $etapa ) { ?>

        <div class="aviso-etapa">
          <h2>Em breve</h2>
          <p>
            O concurso ainda não foi liberado para&nbsp;<strong> <?php echo esc_html( $empresa ? $empresa['nome'] : 'a sua empresa' ); ?></strong>.
            Volte mais tarde.
          </p>
        </div>

      <?php }
      // SEM EMPRESA - o grupo do colaborador não está vinculado a nenhuma empresa
      else { ?>

        <div class="aviso-etapa">
          <h2>Cadastro pendente</h2>
          <p>
            O seu grupo ainda não foi vinculado a uma empresa do concurso, por isso não há
            nada para exibir aqui. Procure o RH para regularizar o seu cadastro.
          </p>

          <?php
          // Para o administrador, mostra o diagnóstico e o atalho da correção
          if ( current_user_can( 'manage_options' ) ) :
            // Grupos que constam no cadastro deste colaborador
            $grupos_do_usuario = function_exists( 'explode_empresas_slugs_do_usuario' )
              ? explode_empresas_slugs_do_usuario( $user_id )
              : array();
            ?>
            <p class="aviso-etapa-admin">
              <strong>Diagnóstico para o administrador:</strong>
              <?php if ( empty( $grupos_do_usuario ) ) : ?>
                este usuário está sem grupo no campo <code>Grupo do funcionario</code>.
              <?php else : ?>
                o(s) grupo(s) <code><?php echo esc_html( implode( ', ', $grupos_do_usuario ) ); ?></code>
                não estão marcados em nenhuma empresa.
              <?php endif; ?>
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=explode-empresas' ) ); ?>">Configurar empresas</a>
            </p>
          <?php endif; ?>
        </div>

      <?php }
      ?>
	  </div>
  </div>
</div>

<!-- Estilo das mensagens de etapa desta página -->
<style>
  .aviso-etapa {
    max-width: 720px;
    margin: 0 auto;
    padding: 30px 34px;
    border-radius: 12px;
    border: 1px dashed rgba(255, 255, 255, .45);
    background: rgba(255, 255, 255, .07);
    text-align: center;
  }
  .aviso-etapa h2 { margin: 0 0 12px; }
  .aviso-etapa p { margin: 0 0 8px; font-size: 15px; line-height: 1.65; }
  .aviso-etapa .botao-etapa {
    display: inline-block;
    margin-top: 16px;
    padding: 12px 26px;
    border-radius: 8px;
    background: linear-gradient(90deg, #5b9b99, #54c5cf);
    color: #fff;
    font-weight: 700;
    text-decoration: none;
  }
  .aviso-etapa .aviso-etapa-admin {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid rgba(255, 255, 255, .25);
    font-size: 13px;
    opacity: .9;
  }
  .aviso-etapa .aviso-etapa-admin code {
    padding: 1px 6px;
    border-radius: 4px;
    background: rgba(0, 0, 0, .25);
  }
</style>

<?php get_footer(); ?>
