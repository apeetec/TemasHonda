<?php
/* Template Name: Filtro */
get_header();

// ############### REGRAS DOS GRUPOS MAO NA VOTACAO ###############
// Verifica se o colaborador logado pertence a um grupo MAO
$mao_bloqueia_cat_a = function_exists( 'explode_usuario_e_mao' ) ? explode_usuario_e_mao() : false;
// Quantidade de desenhos que ele precisa escolher (2 para MAO, 3 para os demais)
$mao_max_votos = function_exists( 'explode_mao_max_votos' ) ? explode_mao_max_votos() : 3;
// Slug da categoria que fica fora da votacao
$mao_cat_bloqueada = function_exists( 'explode_mao_categoria_bloqueada' ) ? explode_mao_categoria_bloqueada() : 'categoria-a';
// ############### FIM DO CONTEXTO MAO ###############
?>

<div class="main">
  <div class="content">
    <?php 

    ############## ENVIA FOTOS
    if($_POST) {

      $ids_update = array();
      $ids_update[] = isset($_POST['desenho1']) ? $_POST['desenho1'] : '';
      $ids_update[] = isset($_POST['desenho2']) ? $_POST['desenho2'] : '';
      $ids_update[] = isset($_POST['desenho3']) ? $_POST['desenho3'] : '';

      // ATUALIZA STATUS DE VOTAÇÃO DO USUÁRIO
      update_user_meta(get_current_user_id(),'user_field_votacao','Sim');

      foreach($ids_update as $post) {
        if(empty($post)) continue;

        // GRUPOS MAO: a Categoria A sai por sorteio presencial e nao recebe voto
        if($mao_bloqueia_cat_a) {
          // Le os slugs dos termos do desenho votado
          $termos_voto = wp_get_post_terms( (int) $post, 'desenhos_cat', array( 'fields' => 'slugs' ) );
          // Descarta o voto quando o desenho estiver na categoria bloqueada
          if( ! is_wp_error($termos_voto) && function_exists('explode_mao_desenho_sem_voto') && explode_mao_desenho_sem_voto($termos_voto) ) {
            continue;
          }
        }

        $votos = get_post_meta($post,'desenhos_box_votos',true);
        if(empty($votos) || $votos == '') {
          update_post_meta($post,'desenhos_box_votos',1);
        }
        else {
          $votos = $votos + 1;
          update_post_meta($post,'desenhos_box_votos',$votos);
        }
      }

    }
    ############## ENVIA FOTOS

    $votacao_status = get_user_meta(get_current_user_id(),'user_field_votacao',true);

    if(!$_POST && $votacao_status == 'Sim') {  ?>

  <div class="votou">
    <div class="container">
    <p>Seus votos já foram computados! Aguarde a divulgação dos resultados.</p>
    </div>
  </div>

<?php } else { ?>

<div class="erro-full" id="aviso">
  <div class="in">
    <div class="content">
      <a href="#" class="notscrollable close">X</a>
        <p>Você não selecionou todos os desenhos!</p>
      </div>
  </div>
</div>

<div class="erro-full" id="maximo">
  <div class="in">
    <div class="content">
      <a href="#" class="notscrollable close">X</a>
        <p>Você só pode selecionar <?php echo (int) $mao_max_votos; ?> desenhos!</p>
      </div>
  </div>
</div>

<div class="erro-full" id="cat-aviso">
  <div class="in">
    <div class="content">
      <a href="#" class="notscrollable close">X</a>
        <p>Você só pode selecionar 1 desenho da <span></span>!</p>
      </div>
  </div>
</div>

<div class="body-filtros">
  <div class="filtros">
    <?php
    if($votacao_status == 'Sim') { ?>
    <div class="votou fim">
      <div class="container">
      <p>Votos enviados com sucesso! Aguarde a divulgação dos resultados.</p>
      </div>
    </div>
    <?php } else { ?>
    <div class="dependentes">
      <div class="container">
        <h2 id="desc">Clique para selecionar os desenhos</h2>

        <?php if($mao_bloqueia_cat_a) { ?>
        <!-- Aviso exclusivo dos grupos MAO sobre o sorteio presencial da Categoria A -->
        <div class="aviso-mao">
          <p>
            <strong>Categoria A não entra na votação do seu grupo.</strong>
            <!-- Os vencedores da Categoria A serão definidos por <strong>sorteio presencial na fábrica</strong>.
            Por isso você deve escolher <strong><?php echo (int) $mao_max_votos; ?> desenhos</strong>:
            um da Categoria B e um da Categoria C. -->
          </p>
        </div>
        <?php } ?>
        <br>


          <?php
          ############### LOOP DOS DESENHOS DOS FILHOS ###############
          $args = array(
              "post_type" => 'desenhos',
              "posts_per_page" => -1,
              'orderby' => 'date',
              'order' => 'ASC',
              'author' => get_current_user_id(),                  
          );
           
          $loop = new WP_Query( $args );  

          $dependentes_enviados = array();

          if ($loop->have_posts()) { ?>

             <h2>Desenhos de seus filhos</h2>
              <div class="all loop-drawings">

          <?php

              while ( $loop->have_posts() ) {
              $loop->the_post();

              $pcd = false;

              $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
              $desenho = str_replace('-150x150', '', $desenho);
              $desenhoBig = str_replace('1280x720', '', $desenho);
              $thumb_id = attachment_url_to_postid( $desenho );
              $first_image_url = wp_get_attachment_image_src($thumb_id, 'desenho_thumb');
              // Pega a primeira imagem e utiliza a versão pequena
              $desenho = $first_image_url ? $first_image_url[0] : '';

              $categorias = get_the_terms(get_the_ID(), 'desenhos_cat');
              $primary_cat_slug = 'sem-categoria';
              if($categorias && !is_wp_error($categorias)) {
                  // usa slug real da primeira categoria (se existir)
                  $primary_cat_slug = isset($categorias[0]->slug) ? sanitize_title($categorias[0]->slug) : sanitize_title($categorias[0]->name);
                  foreach($categorias as $info){
                      if($info->slug == 'pcd') {
                        $pcd = true;
                      }
                  }
              }

              ?>

              <?php
              // GRUPOS MAO: identifica se este desenho esta na categoria do sorteio presencial
              $slugs_deste_desenho = array();
              if($categorias && !is_wp_error($categorias)) {
                foreach($categorias as $info_slug) { $slugs_deste_desenho[] = $info_slug->slug; }
              }
              // Define se o desenho fica sem a opcao de voto
              $sem_voto = $mao_bloqueia_cat_a && function_exists('explode_mao_desenho_sem_voto') && explode_mao_desenho_sem_voto($slugs_deste_desenho);
              ?>
              <div class="single<?php echo $sem_voto ? ' sem-voto' : ''; ?>" data-categoria="<?php echo esc_attr($primary_cat_slug); ?>">
                <span id="cat"><?php the_title(); ?></span>
                <span id="cat"><?php echo (!empty($categorias) && !is_wp_error($categorias)) ? esc_html($categorias[0]->name) : 'Sem categoria'; if($pcd == true) { echo ' (PCD)'; } ?></span>
                <?php if($sem_voto) { ?>
                  <!-- Categoria sorteada presencialmente: exibe o desenho sem a opcao de voto -->
                  <label class="sem-voto-thumb" style="background-image: url('<?php echo esc_url($desenhoBig); ?>');"></label>
                  <span class="aviso-sorteio">Sorteio presencial na fábrica</span>
                <?php } else { ?>
                <label style="background-image: url('<?php echo esc_url($desenhoBig); ?>');">
                <input type="checkbox" data-img="<?php echo esc_url($desenhoBig); ?>" class="selected" name="<?php echo esc_attr($primary_cat_slug); ?>" data-id="<?php echo get_the_ID(); ?>">
                <span><i class="fas fa-check"></i></span>
              </label>
                <?php } ?>
            </div>
          
          <?php

              } ?>

              </div>

          <?php
          }
          ############### LOOP DOS DESENHOS DOS FILHOS ###############
          ?>                  
      </div>
    </div>

    <!-- Grupo funcionario -->
    <div class="container funcionario_grupo space-top">
      <?php
        
       $user_id = get_current_user_id();
        $funcionario_grupo_string = get_user_meta($user_id,'user_field_funcionario_grupo',true);
        
        $funcionario_groups = array();
        if ( ! empty( $funcionario_grupo_string ) ) {
            $funcionario_groups = array_filter( array_map( 'trim', explode( ',', $funcionario_grupo_string ) ) );
        }
        
        $access_granted = false; // Flag para saber se o usuário pertence a algum grupo permitido
        $displayed_groups_info = ''; // Variável para acumular todas as informações dos grupos permitidos
        
        // Lista de todos os grupos que têm informações associadas para exibição
        $allowed_groups_for_info = array('Grupo 1', 'Grupo 2', 'Grupo 3', 'Grupo 4', 'Grupo 5', 'Grupo 6', 'Grupo 7', 'Grupo 8', 'Grupo 9', 'Grupo 10');
        
        if ( ! empty( $funcionario_groups ) ) {
            // Percorre a lista de grupos permitidos para ver quais o usuário possui
            foreach ( $allowed_groups_for_info as $allowed_group ) {
                // Verifica se o usuário pertence a este grupo permitido
                if ( in_array( $allowed_group, $funcionario_groups ) ) {
                    $access_granted = true; // Marca que o acesso é concedido
                    
                    // Acumula as informações do grupo correspondente na variável $displayed_groups_info
                    switch ($allowed_group) {
                      case "Grupo 1":
                        $displayed_groups_info .= "<p><spam>Grupo 01:</spam><br> SUM-HAB | SUM-HAB-PAULINIA | SUM-HAB-ITAJAI | SUM-HAB-CARIACICA | SUM-HEN XANGRI-LA </p>";
                        break;
                      case "Grupo 2":
                        $displayed_groups_info .= "<p><spam>Grupo 02:</spam><br> SUM-HAB-ITIRAPINA </p>";
                        break;
                      case "Grupo 3":
                        $displayed_groups_info .= "<p><spam>Grupo 03:</spam><br> SAO-HDA-CT SUMARE | SAO-HDA SUMARE ADM | SAO-HAB-SUM-CO | SAO-HDA-SUM-PEÇAS | SAO-PREVIHONDA</p>";
                        break;
                      case "Grupo 4":
                        $displayed_groups_info .= "<p><spam>Grupo 04:</spam><br> SAO-CNH S. C. SUL | SAO-HSF-MORUMBI | SAO-HDA-RECIFE | SAO-HDA-MORUMBI | SAO-HDA-INDAIATUBA | SAO-BHB-MORUMBI | SAO-HDA-SP2 | SAO-HDA-JABOATAO</p>";
                        break;
                      case "Grupo 5":
                        $displayed_groups_info .= "<p><spam>Grupo 05:</spam><br> ESTAMPARIA | SOLDA</p>";
                        break;   
                      case "Grupo 6":
                        $displayed_groups_info .= "<p><spam>Grupo 06:</spam><br> FUNDIÇÃO | USINAGEM | MONT. MOTOR</p>";
                        break;
                      case "Grupo 7":
                        $displayed_groups_info .= "<p><spam>Grupo 07:</spam><br> PINTURA | INJ. PLÁSTICA</p>";
                        break;
                      case "Grupo 8":
                        $displayed_groups_info .= "<p><spam>Grupo 08:</spam><br> MONT. MOTOCICLETA | CTP</p>";
                        break;   
                      case "Grupo 9":
                        $displayed_groups_info .= "<p><spam>Grupo 09:</spam><br> LOGÍSTICA | INFRAESTRUTURA</p>";
                        break;
                      case "Grupo 10":
                        $displayed_groups_info .= "<p><spam>Grupo 10:</spam><br> CDT | RH | CQ | COMPRAS | TI | CONTROLADORIA | COMPRAS INDIRETAS | PLAIN | CONTROLE FABRIL | DIV. PEÇAS | CETH | SEGURANÇA FÁBRICA | MONO-C | AUDITORIA | PROJ. TAIKAI | COMERCIAL 2W | JURÍDICO | PROJ. MANUFATURA | RI</p>";
                        break;        
                    }
                    // NÃO usamos mais o 'break;' aqui, para que ele continue verificando outros grupos permitidos
                }
            }
        }
        
        // Se nenhum grupo permitido foi encontrado após a verificação, exibe a mensagem de erro
        if ( ! $access_granted ) {
            echo "Voce precisa participar de um grupo para ver estas informacoes, ou seu grupo faz parte de outro grupo que nao esta listado aqui.";
            $user_unidade = get_user_meta($user_id, 'user_field_unidade', true);
            $unidade_nome = get_term_by('term_id', $user_unidade, 'user_unidade');
           echo "<p>Unidade do usuário: " . esc_html($unidade_nome ? $unidade_nome->name : 'Não definida') . "</p>";
            // Opcional: Redirecionar para uma página de erro ou home page
            // wp_redirect( home_url('/pagina-de-erro-grupo/') ); exit;
        } else {
            // Se o acesso foi concedido (ou seja, $access_granted é true), exibe as informações acumuladas
            echo $displayed_groups_info;
        }

      ?>
    </div>

    <div class="outros">
      <div class="container">
        <div class="listagem">   

          <div class="coluna noselect" id="left">
            <form action="">
              <?php
              // As caixas de filtro sao montadas a partir do que este colaborador
              // realmente enxerga, definido em "Visibilidade" no painel.
              //
              // Antes elas eram fixas em Categoria A, B e C. Isso escondia da tela
              // qualquer desenho de outra categoria (a Categoria D dos grupos MAO,
              // por exemplo), porque o filtro so mostra o que bate com alguma caixa.
              $vis_categorias = function_exists( 'explode_visibilidade_categorias_para_tela' )
                  ? explode_visibilidade_categorias_para_tela( $user_id )
                  : array();
              $vis_grupos = function_exists( 'explode_visibilidade_grupos_para_tela' )
                  ? explode_visibilidade_grupos_para_tela( $user_id )
                  : array();
              ?>

              <h2>Categorias</h2>
              <?php if ( ! empty( $vis_categorias ) ) : ?>
                <?php foreach ( $vis_categorias as $vis_cat ) :
                  // Nos grupos MAO a categoria sorteada presencialmente ganha um aviso
                  $vis_sorteio = ( $mao_bloqueia_cat_a && $vis_cat['slug'] === $mao_cat_bloqueada );
                ?>
                  <div class="line">
                    <label>
                      <input type="checkbox" class="filtro-categoria"
                             name="filtro-<?php echo esc_attr( $vis_cat['slug'] ); ?>"
                             value="<?php echo esc_attr( $vis_cat['slug'] ); ?>" checked>
                      <span>
                        <?php echo esc_html( $vis_cat['nome'] ); ?>
                        <span id="desc"><?php
                          echo esc_html( $vis_cat['faixa'] );
                          if ( $vis_sorteio ) { echo ' (sorteio)'; }
                        ?></span>
                      </span>
                    </label>
                  </div>
                <?php endforeach; ?>
              <?php else : ?>
                <p class="filtro-vazio">Nenhuma categoria liberada para o seu grupo.</p>
              <?php endif; ?>

              <?php // A lista de grupos so faz sentido quando ha mais de um para separar
              if ( count( $vis_grupos ) > 1 ) : ?>
                <h2>Grupos</h2>
                <?php foreach ( $vis_grupos as $vis_grp ) : ?>
                  <div class="line">
                    <label>
                      <input type="checkbox" class="filtro-grupo"
                             name="filtro-<?php echo esc_attr( $vis_grp['slug'] ); ?>"
                             value="<?php echo esc_attr( $vis_grp['slug'] ); ?>" checked>
                      <span>
                        <?php echo esc_html( $vis_grp['nome'] ); ?>
                        <?php if ( $vis_grp['proprio'] ) : ?><span id="desc">o seu grupo</span><?php endif; ?>
                      </span>
                    </label>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </form>
          </div>
          <div class="coluna" id="right">
            <div class="loop-drawings">

<?php 
                      $args_desenhos_outros = array(
                        "post_type" => 'desenhos',
                        "posts_per_page" => -1,
                        'orderby' => 'rand', 
                        'author__not_in' => get_current_user_id(), // Desenhos de outros autores
                      );

                      $loop_outros = new WP_Query( $args_desenhos_outros );  

                      while ( $loop_outros->have_posts() ) {
                        $loop_outros->the_post();
                        
                        $desenho_meta = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
                        $desenho_thumbnail_url = '';
                        if($desenho_meta){
                            if(is_numeric($desenho_meta)){
                                $img_data = wp_get_attachment_image_src(intval($desenho_meta), 'desenho_thumb');
                                if($img_data) $desenho_thumbnail_url = $img_data[0];
                            } elseif(is_array($desenho_meta) && !empty($desenho_meta['id'])){
                                $img_data = wp_get_attachment_image_src($desenho_meta['id'], 'desenho_thumb');
                                if($img_data) $desenho_thumbnail_url = $img_data[0];
                            } elseif(is_string($desenho_meta)){
                                $desenho_thumbnail_url = $desenho_meta; // Tenta usar a URL direta se for string
                            }
                        }
                        // Tenta obter a imagem original maior para o lightbox
                        $desenhoBig_url = $desenho_thumbnail_url ? str_replace('-250x250', '', $desenho_thumbnail_url) : ''; // Ajuste o tamanho se necessário

                        $pcd = false;
                        $categorias_do_desenho = get_the_terms(get_the_ID(), 'desenhos_cat');
                        $desenho_categorias_slugs = array(); // Slugs das categorias do desenho

                        if($categorias_do_desenho && !is_wp_error($categorias_do_desenho)){
                            foreach($categorias_do_desenho as $cat_info){
                                // Usa o slug oficial da taxonomia (mais confiável)
                                $slug_use = isset($cat_info->slug) ? sanitize_title($cat_info->slug) : sanitize_title($cat_info->name);
                                $desenho_categorias_slugs[] = $slug_use; // Armazena o slug normalizado
                                
                                if($cat_info->slug == 'pcd') { // Mantém a verificação do slug 'pcd' como está
                                  $pcd = true;
                                }
                            }
                        }

                        // Decide se este desenho pode aparecer para este colaborador.
                        //
                        // A regra saiu daqui e passou a ser configurada em "Visibilidade" no
                        // painel: para cada grupo o administrador escolhe quais GRUPOS e quais
                        // CATEGORIAS ele enxerga. Antes isto era fixo no codigo: o colaborador
                        // so via os desenhos que tivessem o slug de um grupo dele, e nao havia
                        // como fazer um grupo enxergar outro sem editar PHP.
                        if ( function_exists( 'explode_visibilidade_pode_ver' ) ) {
                            // Aplica a configuracao do painel
                            $can_display_drawing = explode_visibilidade_pode_ver( $desenho_categorias_slugs, $user_id );
                        } else {
                            // Alternativa com a regra antiga, caso o modulo nao esteja carregado
                            $drawing_group_match = false;
                            // Percorre os grupos do colaborador
                            if ( ! empty( $funcionario_groups ) ) {
                                foreach ( $funcionario_groups as $user_group ) {
                                    // Normaliza o grupo do usuário para comparar com slugs
                                    $normalized_user_group = sanitize_title($user_group);
                                    // Encontrou o grupo entre os termos do desenho
                                    if ( in_array( $normalized_user_group, $desenho_categorias_slugs ) ) {
                                        $drawing_group_match = true;
                                        break;
                                    }
                                }
                            }
                            // Regra historica: grupo do colaborador ou desenho PCD
                            $can_display_drawing = ( $drawing_group_match || $pcd );
                        }

                        if ( $can_display_drawing && $desenho_thumbnail_url && $desenhoBig_url ) { // Só exibe se puder e se tiver imagem
                            $unidade_post_id = get_post_meta(get_the_ID(),'desenhos_box_unidade',true);
                            $unidade_slug = '';
                            if($unidade_post_id){
                                $term = get_term($unidade_post_id);
                                if($term && !is_wp_error($term)){
                                    $unidade_slug = sanitize_title($term->name);
                                }
                            }
                            $unidade_slug = !empty($unidade_slug) ? $unidade_slug : 'sem-unidade';

                            // Constrói a classe CSS para filtragem por unidade
                            $css_classes_array = array(
                                'single',
                                'unidade-' . $unidade_slug,
                            );

                            // Adiciona classes de categoria (slugs normalizados)
                            if ( ! empty( $desenho_categorias_slugs ) ) {
                                foreach ( $desenho_categorias_slugs as $cat_slug_normalized ) {
                                    $css_classes_array[] = $cat_slug_normalized; // Usa o slug normalizado como classe
                                    $css_classes_array[] = 'filtro-' . $cat_slug_normalized; // Classe para o filtro da esquerda
                                    if ( $cat_slug_normalized === 'pcd' ) { // Verifica se o slug normalizado é 'pcd'
                                        $css_classes_array[] = 'filtro-categoria-pcd';
                                    }
                                }
                            }

                            // GRUPOS MAO: a categoria do sorteio presencial nao entra na votacao
                            $sem_voto = false;
                            if( $mao_bloqueia_cat_a && function_exists('explode_mao_desenho_sem_voto') && explode_mao_desenho_sem_voto($desenho_categorias_slugs) ) {
                                $sem_voto = true;
                            }

                            // pega a primeira slug como data-categoria (fallback 'sem-categoria')
                            $data_categoria_attr = !empty($desenho_categorias_slugs) ? $desenho_categorias_slugs[0] : 'sem-categoria';
                            
                            // Adiciona a classe sem-voto se for o caso
                            if ( $sem_voto ) {
                                $css_classes_array[] = 'sem-voto';
                            }
                            ?>

                            <div class="<?php echo esc_attr(implode(' ', $css_classes_array)); ?>" data-categoria="<?php echo esc_attr($data_categoria_attr); ?>">
                                <span id="cat"><?php the_title(); ?></span>
                                <span id="cat"><?php 
                                    if ( ! empty( $desenho_categorias_slugs ) ) {
                                        echo ucfirst( str_replace('-', ' ', $desenho_categorias_slugs[0]) ); 
                                        if($pcd) { echo ' (PCD)'; }
                                    }
                                ?></span>
                                
                                <?php if ( $sem_voto ) : ?>
                                    <!-- Exibe apenas a imagem e o aviso de sorteio -->
                                    <a href="<?php echo esc_url($desenhoBig_url); ?>" data-toggle="lightbox" data-gallery="galeriastander" style="display:block; cursor:pointer;">
                                      <label class="sem-voto-thumb" style="background-image: url('<?php echo esc_url($desenhoBig_url); ?>'); width:100%; height:180px; display:block; background-size:cover; filter:grayscale(0.35); cursor:pointer;"></label>
                                    </a>
                                    <span class="aviso-sorteio" style="display:block; text-align:center; background: rgba(84, 197, 207, .18); color:#fff; border-radius:20px; font-size:11px; padding:4px; margin-top:8px;">Sorteio presencial na fábrica</span>
                                <?php else : ?>
                                    <a href="<?php echo esc_url($desenhoBig_url); ?>" data-toggle="lightbox" data-gallery="galeriastander">
                                      <label class="borderImg" style="background-image: url('<?php echo esc_url($desenho_thumbnail_url); ?>');"></label>
                                    </a>
                                    <div class="menuVotacao">
                                      <label id="voltoDesenho">                        
                                        <input type="checkbox" data-img="<?php echo esc_url($desenhoBig_url); ?>" class="selected" name="<?php echo esc_attr($data_categoria_attr); ?>" data-id="<?php echo get_the_ID(); ?>">
                                        <span><i class="fas fa-check"></i></span>
                                      </label>
                                      <p>Votar</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php
                        }
                      } // Fim do loop while $loop_outros
                      ?>
            </div>
            
          </div>
        </div>
      </div>
    </div>
    <?php } ?>
  </div>
</div>

<?php if($mao_bloqueia_cat_a) { ?>
<!-- ESTILO DOS AVISOS DE SORTEIO PRESENCIAL (GRUPOS MAO) -->
<style>
    .aviso-mao {
        margin: 0 0 22px;
        padding: 14px 18px;
        border-left: 4px solid #54c5cf;
        border-radius: 8px;
        background: rgba(84, 197, 207, .12);
    }
    .aviso-mao p { margin: 0; font-size: 14px; line-height: 1.6; }
    .body-filtros .single.sem-voto { opacity: .82; }
    .body-filtros .single.sem-voto .sem-voto-thumb {
        display: block;
        background-size: cover;
        background-position: center;
        filter: grayscale(.35);
        cursor: default;
    }
    .body-filtros .single .aviso-sorteio {
        display: block;
        margin-top: 8px;
        padding: 4px 10px;
        border-radius: 20px;
        background: rgba(84, 197, 207, .18);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        text-align: center;
    }
    #left form .line.line-bloqueada { opacity: .55; }
    #left form .line.line-bloqueada span { font-size: inherit; }
</style>
<?php } ?>

<script>
jQuery(function($){

  // Seleção das imagens (mantive sua lógica)
  $("input.selected").change(function() {

    var img = $(this).data('img');
    var id = $(this).data('id');

      if($( ".body-filtros input.selected:checked" ).length > <?php echo (int) $mao_max_votos; ?>) {
        $(this).prop('checked',false);
        $('.erro-full#maximo').show();          
      }
      else {

        // ####################### PERMITE APENAS 1 SELECAO DE CADA CATEGORIA #######################
        var numberOfChecked_cat_a = $('input[name="categoria-a"]:checked').length;
        // Nos grupos MAO a Categoria A nem chega a ser exibida para votacao
        if(numberOfChecked_cat_a > 1) {
          $('.erro-full#cat-aviso p span').text('Categoria A');
          $('.erro-full#cat-aviso').show();
          $(this).prop("checked", false);
          return false;
        }

        var numberOfChecked_cat_b = $('input[name="categoria-b"]:checked').length;
        if(numberOfChecked_cat_b > 1) {
          $('.erro-full#cat-aviso p span').text('Categoria B');
          $('.erro-full#cat-aviso').show();
          $(this).prop("checked", false);
          return false;
        }

        var numberOfChecked_cat_c = $('input[name="categoria-c"]:checked').length;
        if(numberOfChecked_cat_c > 1) {
          $('.erro-full#cat-aviso p span').text('Categoria C');
          $('.erro-full#cat-aviso').show();
          $(this).prop("checked", false);
          return false;
        }
        // ####################### FIM PERMITE APENAS 1 SELECAO DE CADA CATEGORIA #######################


        if(this.checked) {

          // ENCONTRA O PRIMEIRO E ADICIONA NA SELEÃO NO MENU
          $('.menu .filtros form .single input').each(function() {
            if ( this.value === '' ) {
                $(this).val(id);
                $(this).parent().css('background-image','url('+img+')');
                this.focus();
                return false;
            }
          });         
            
        }
        else {       

          // ENCONTRA O PRIMEIRO E ADICIONA NA SELEÇÃO NO MENU
          $('.menu .filtros form .single input').each(function(){
          var encontrado = $(this).val();
          if(encontrado == id) {
              $(this).val('');
              $(this).parent().css('background-image','');
              this.focus();
              return false;
            }
          }); 

        }
      }

  });

  // ERRO CASO ESTEJA VAZIOS
  $('.menu form').submit(function() {

    var erro = false;
    $('.menu .filtros form .single input').each(function() {
        if ( this.value === '' ) {
            erro = true;
        }
    });

    if(erro == true) {
      $('.erro-full#aviso').show();
      return false;
    }
    else {
        $('.msg-loading').fadeIn(500);
      }

  });

  // Ajustes iniciais de checkboxes
  $('.body-filtros .loop-drawings input[type=checkbox]').prop("checked", false);
  $('.coluna form input[type=checkbox]').prop("checked", true);
  $('.menu form .single input').removeAttr('value');

  // FILTRO DA BARRA LATERAL
  //
  // As caixas sao geradas no PHP a partir da configuracao de "Visibilidade", entao
  // podem ser categorias, grupos, ou os dois. As duas dimensoes sao combinadas com E:
  // o desenho aparece quando bate uma categoria marcada E um grupo marcado.
  //
  // Antes era tudo um unico OU, e o filtro ainda escondia qualquer desenho cuja
  // categoria nao tivesse caixa correspondente.
  function explodeAplicarFiltro(){

    // Valores marcados de cada dimensao
    var cats   = $('#left form .filtro-categoria:checked').map(function(){ return $(this).val(); }).get();
    var grupos = $('#left form .filtro-grupo:checked').map(function(){ return $(this).val(); }).get();

    // Existencia de cada dimensao na tela
    var temCats   = $('#left form .filtro-categoria').length > 0;
    var temGrupos = $('#left form .filtro-grupo').length > 0;

    // Percorre cada desenho decidindo se ele aparece
    $('.body-filtros .outros .single').each(function(){
      var $este = $(this);

      // A dimensao so restringe quando existe na tela
      var okCat   = !temCats;
      var okGrupo = !temGrupos;

      // Confere a categoria pelo data-categoria ou pelas classes do desenho
      if (temCats) {
        for (var i = 0; i < cats.length; i++) {
          if ($este.data('categoria') === cats[i] || $este.hasClass(cats[i]) || $este.hasClass('filtro-' + cats[i])) {
            okCat = true;
            break;
          }
        }
      }

      // Confere o grupo pelas classes do desenho
      if (temGrupos) {
        for (var j = 0; j < grupos.length; j++) {
          if ($este.hasClass(grupos[j]) || $este.hasClass('filtro-' + grupos[j])) {
            okGrupo = true;
            break;
          }
        }
      }

      // Mostra apenas quando as duas dimensoes aceitam
      $este.toggle(okCat && okGrupo);
    });
  }

  // Qualquer mudanca na barra lateral reaplica o filtro
  $('#left form input').on('change', explodeAplicarFiltro);

  // Aplica uma vez na carga, com todas as caixas marcadas
  explodeAplicarFiltro();

});
</script>

<?php
} // Fim else caso no tenha votado ainda
?>


<?php get_footer(); ?>
