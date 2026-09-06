<?php
/**
 * Página inicial do colaborador.
 *
 * O que aparece aqui é decidido pela EMPRESA a que o colaborador pertence e pela
 * ETAPA em que essa empresa está. As duas coisas são configuradas em "Empresas" no painel.
 *
 * Este arquivo atende SOMENTE a página inicial. Buscas, arquivos, posts avulsos e
 * qualquer outra consulta caem no index.php, que é neutro — antes tudo desembocava
 * neste mesmo código e exibia o formulário de envio fora de lugar.
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
            O concurso ainda não foi liberado para
            <strong><?php echo esc_html( $empresa ? $empresa['nome'] : 'a sua empresa' ); ?></strong>.
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

<?php
// Estilo compartilhado das caixas de mensagem
get_template_part( 'template-part/estilo-mensagens' );

get_footer();
