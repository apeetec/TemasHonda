<?php
/**
 * Template para a taxonomia 'categoria_videos'
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enfileira estilo compartilhado do dashboard para manter padrão visual
wp_enqueue_style( 'admin-usuarios-css', get_template_directory_uri() . '/css/admin-usuarios.css', array(), '1.0.0' );

get_header();

$term = get_queried_object();
if ( ! $term || is_wp_error( $term ) ) {
    echo '<div class="container"><p>' . esc_html__( 'Termo inválido.', 'text_domain' ) . '</p></div>';
    get_footer();
    exit;
}

$term_id = (int) $term->term_id;
?>

<main class="container">
	  	 <div class="banner">
        <img src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/BANNER-1.png">
    </div>
    <!-- Breadcrumbs e Navegação -->
    <div class="video-navigation mt-4">
        <div class="breadcrumbs">
            <a href="<?php if(user_can( $user_verify, 'administrator' )){echo esc_url( home_url('/') );} else {echo esc_url( home_url('categorias-dos-videos') );} ?>" class="breadcrumb-item">
                <i class="fas fa-home"></i> Início
            </a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current"><i class="fas fa-folder"></i> <?php echo esc_html( $term->name ); ?></span>
        </div>
        <div class="navigation-buttons">
            <button onclick="window.history.back()" class="btn-nav-back" title="Voltar">
                <i class="fas fa-arrow-left"></i> Voltar
            </button>
            <a href="<?php if(current_user_can('administrator')){echo esc_url( home_url('/') );} else {echo esc_url( home_url('categorias-dos-videos') );} ?>" class="btn-nav-home" title="Página Inicial">
                <i class="fas fa-home"></i> Início
            </a>
        </div>
    </div>

    <!-- Header aprimorado com ícones -->
    <header class="page-header dashboard-header taxonomy-header">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-folder" aria-hidden="true"></i>
            </div>
            <div>
                <h1 class="entry-title"><i class="fas fa-film" style="margin-right:12px; color:#1976d2;" aria-hidden="true"></i><?php echo esc_html( $term->name ); ?></h1>
                <?php if ( ! empty( $term->description ) ) : ?>
                    <div class="taxonomy-description text-muted"><i class="fas fa-info-circle" style="margin-right:6px;" aria-hidden="true"></i><?php echo wp_kses_post( wpautop( $term->description ) ); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="dashboard-actions">
            <div class="search-box-wrapper">
                <i class="fas fa-search search-icon" aria-hidden="true"></i>
                <input type="text" id="search-videos" placeholder="Buscar vídeos nesta categoria..." class="search-input-enhanced" style="width:280px;">
            </div>
        </div>
    </header>

    <!-- Card informativo -->
    <div class="info-card">
        <div class="info-card-icon">
            <i class="fas fa-video" aria-hidden="true"></i>
        </div>
        <div class="info-card-content">
            <h3>Vídeos da Categoria</h3>
            <p>Explore todos os vídeos disponíveis nesta categoria. Use a <strong>busca</strong> acima para encontrar rapidamente ou navegue pelos cards abaixo.</p>
        </div>
    </div>

    <?php if ( current_user_can( 'manage_options' ) ) : ?>
    <div class="dashboard-actions" style="margin-top:12px; display:flex; gap:8px; align-items:center;">
        <button id="toggle-selection-mode" class="btn-secondary">
            <i class="fas fa-check-square" style="margin-right:6px;"></i>Selecionar
        </button>
        <button id="select-all-action" class="btn-secondary">
            <i class="fas fa-list-check" style="margin-right:6px;"></i>Selecionar todos
        </button>
        <button id="delete-selected-videos" class="btn-danger">
            <i class="fas fa-trash" style="margin-right:6px;"></i>Excluir selecionados
        </button>
    </div>
    <?php endif; ?>

    <?php
    // Term media (pode ser attachment ID ou iframe)
    $term_video_attach = get_term_meta( $term_id, 'video_categoria', true );
    $term_iframe = get_term_meta( $term_id, 'iframe_video_termo', true );

    if ( ! empty( $term_video_attach ) || ! empty( $term_iframe ) ) :
        echo '<div class="term-media">';
        if ( is_numeric( $term_video_attach ) ) {
            $url = wp_get_attachment_url( (int) $term_video_attach );
            $mime = get_post_mime_type( (int) $term_video_attach );
            if ( $url && $mime && strpos( $mime, 'video/' ) === 0 ) {
                echo '<video controls style="max-width:100%; margin-bottom:1rem">';
                echo '<source src="' . esc_url( $url ) . '" type="' . esc_attr( $mime ) . '">';
                echo esc_html__( 'Seu navegador não suporta a tag de vídeo.', 'text_domain' );
                echo '</video>';
            }
        }

        if ( ! empty( $term_iframe ) ) {
            $allowed = array(
                'iframe' => array(
                    'src' => array(),
                    'width' => array(),
                    'height' => array(),
                    'frameborder' => array(),
                    'allow' => array(),
                    'allowfullscreen' => array(),
                ),
            );
            echo wp_kses( $term_iframe, $allowed );
        }
        echo '</div>';
    endif;

    // Grid de vídeos (server-side render, estilo semelhante ao template-page-categorias.php)
    $paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
    $args = array(
        'post_type' => 'videos',
        'posts_per_page' => 10,
        'paged' => $paged,
        'tax_query' => array(
            array(
                'taxonomy' => 'categoria_videos',
                'field'    => 'term_id',
                'terms'    => $term_id,
            ),
        ),
    );

    $q = new WP_Query( $args );

    if ( $q->have_posts() ) :
        echo '<div id="videos-cards-grid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; margin-top:16px;">';
        while ( $q->have_posts() ) : $q->the_post();
            ?>
            <a class="card-link" href="<?php the_permalink(); ?>">
                <div class="card">
                    <div class="card-image">
                        <?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium' ); } else { echo '<div class="card-placeholder"><i class="fa fa-video-camera" aria-hidden="true"></i></div>'; } ?>
                        <span class="card-play" aria-hidden="true"><i class="fa fa-play" aria-hidden="true"></i></span>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?php the_title(); ?></h3>
                        <p><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
                        <div class="meta"><?php echo get_the_date('d/m/Y'); ?></div>
                    </div>
                </div>
            </a>
            <?php
        endwhile;
        echo '</div>'; // #videos-cards-grid

        // Paginação
        $big = 999999999; // need an unlikely integer
        echo '<div class="pagination" style="margin-top:20px;">' . paginate_links( array(
            'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format' => '?paged=%#%',
            'current' => max( 1, $paged ),
            'total' => $q->max_num_pages,
        ) ) . '</div>';

        wp_reset_postdata();
    else :
        echo '<div class="empty-state"><p>' . esc_html__( 'Nenhum vídeo encontrado nesta categoria.', 'text_domain' ) . '</p></div>';
    endif;
    ?>
  <div class="rodape">
            <img src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/RODAPE-1.png">
        </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
// Animação GSAP nos cards de vídeos
if (typeof gsap !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        gsap.from('#videos-cards-grid > *', {
            opacity: 0,
            y: 30,
            duration: 0.5,
            stagger: 0.08,
            ease: 'power2.out'
        });
    });
}
</script>

<?php
get_footer();
