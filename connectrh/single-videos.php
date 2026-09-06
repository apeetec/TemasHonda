<?php
/**
 * Single template for the 'videos' post type
 * File: single-videos.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enfileira CSS do dashboard para consistência visual
wp_enqueue_style( 'admin-usuarios-css', get_template_directory_uri() . '/css/admin-usuarios.css', array(), '1.0.0' );

get_header();

if ( have_posts() ) : while ( have_posts() ) : the_post();
    $post_id = get_the_ID();
    $terms = get_the_terms( $post_id, 'categoria_videos' );
    $groups = get_post_meta( $post_id, 'video_group', true );
?>

<main class="container single-video-container mt-4 mb-4">
		 <div class="banner">
        <img src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/BANNER-1.png">
    </div>
    <!-- Breadcrumbs e Navegação -->
    <div class="video-navigation">
        <div class="breadcrumbs">
            <a href="<?php if(user_can( $user_verify, 'administrator' )){echo esc_url( home_url('/') );} else {echo esc_url( home_url('categorias-dos-videos') );} ?>" class="breadcrumb-item">
                <i class="fas fa-home"></i> Início
            </a>
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
                <a href="<?php echo esc_url( get_term_link( $terms[0] ) ); ?>" class="breadcrumb-item">
                    <i class="fas fa-folder"></i> <?php echo esc_html( $terms[0]->name ); ?>
                </a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
            <span class="breadcrumb-current"><?php the_title(); ?></span>
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

    <div class="single-video-layout">
        <!-- Coluna Principal: Vídeo e Conteúdo -->
        <article id="post-<?php the_ID(); ?>" <?php post_class('video-main-content'); ?>>
            <header class="entry-header video-header-enhanced">
                <div class="video-title-wrapper">
                    <div class="video-icon-badge">
                        <i class="fas fa-play-circle" aria-hidden="true"></i>
                    </div>
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </div>
                
                <?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
                <div class="video-categories">
                    <i class="fas fa-tags" style="color:#1976d2; margin-right:8px;" aria-hidden="true"></i>
                    <?php foreach ( $terms as $term ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="video-chip">
                            <i class="fas fa-folder" aria-hidden="true"></i> <?php echo esc_html( $term->name ); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="video-meta-info">
                    <span class="meta-item"><i class="fas fa-calendar-alt"></i> <?php echo get_the_date('d/m/Y'); ?></span>
                    <span class="meta-item"><i class="fas fa-clock"></i> <?php echo human_time_diff( get_the_time('U'), current_time('timestamp') ) . ' atrás'; ?></span>
                </div>
            </header>

            <?php if ( ! empty( $groups ) && is_array( $groups ) ) : ?>
                <div class="video-sections-wrapper">
                    <?php foreach ( $groups as $index => $section ) : 
                        $titulo = ! empty( $section['titulo_secao_do_video'] ) ? $section['titulo_secao_do_video'] : '';
                        $upload = ! empty( $section['upload'] ) ? $section['upload'] : '';
                        if ( empty( $upload ) ) continue;
                    ?>
                        <section class="video-section-card">
                            <?php if ( $titulo ) : ?>
                                <h2 class="video-section-title">
                                    <div class="section-number"><?php echo ($index + 1); ?></div>
                                    <i class="fas fa-play-circle" aria-hidden="true"></i> <?php echo esc_html( $titulo ); ?>
                                </h2>
                            <?php endif; ?>

                            <div class="video-player-wrapper">
                                <?php
                                // Render do vídeo: attachment ID | URL | iframe | oEmbed
                                if ( is_numeric( $upload ) ) {
                                    $attach_id = (int) $upload;
                                    $url = wp_get_attachment_url( $attach_id );
                                    $mime = get_post_mime_type( $attach_id );
                                    if ( $url && $mime && strpos( $mime, 'video/' ) === 0 ) {
                                        echo '<video controls preload="metadata">';
                                        echo '<source src="' . esc_url( $url ) . '" type="' . esc_attr( $mime ) . '">';
                                        echo esc_html__( 'Seu navegador não suporta a tag de vídeo.', 'text_domain' );
                                        echo '</video>';
                                    }
                                } elseif ( is_string( $upload ) && ! empty( $upload ) ) {
                                    $trim = trim( $upload );
                                    if ( strpos( $trim, '<iframe' ) !== false ) {
                                        $allowed = array(
                                            'iframe' => array(
                                                'src' => array(), 'width' => array(), 'height' => array(),
                                                'frameborder' => array(), 'allow' => array(), 'allowfullscreen' => array(),
                                            ),
                                        );
                                        echo '<div class="video-embed">' . wp_kses( $trim, $allowed ) . '</div>';
                                    } else {
                                        $filetype = wp_check_filetype( wp_basename( $trim ) );
                                        if ( ! empty( $filetype['type'] ) && strpos( $filetype['type'], 'video/' ) === 0 ) {
                                            echo '<video controls preload="metadata">';
                                            echo '<source src="' . esc_url( $trim ) . '" type="' . esc_attr( $filetype['type'] ) . '">';
                                            echo esc_html__( 'Seu navegador não suporta a tag de vídeo.', 'text_domain' );
                                            echo '</video>';
                                        } else {
                                            $embed = wp_oembed_get( $trim );
                                            if ( $embed ) {
                                                echo '<div class="video-embed">' . $embed . '</div>';
                                            }
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="empty-state video-empty-state">
                    <div class="empty-state-icon"><i class="fas fa-video" style="font-size:64px; color:#ddd;"></i></div>
                    <h3><?php esc_html_e( 'Nenhuma seção de vídeo cadastrada', 'text_domain' ); ?></h3>
                    <p style="color:#999;">Este vídeo ainda não possui conteúdo disponível.</p>
                </div>
            <?php endif; ?>

            <?php if ( get_the_content() ) : ?>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <?php endif; ?>

        </article>

        <!-- Sidebar: Vídeos Relacionados -->
        <aside class="video-sidebar">
            <div class="sidebar-widget">
                <h3 class="widget-title">
                    <i class="fas fa-film" aria-hidden="true"></i> Vídeos Relacionados
                </h3>

                <?php
                // Query: outros vídeos da mesma categoria
                $related_args = array(
                    'post_type' => 'videos',
                    'posts_per_page' => 6,
                    'post__not_in' => array( $post_id ),
                    'orderby' => 'date',
                    'order' => 'DESC',
                );

                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    $term_ids = wp_list_pluck( $terms, 'term_id' );
                    $related_args['tax_query'] = array(
                        array(
                            'taxonomy' => 'categoria_videos',
                            'field' => 'term_id',
                            'terms' => $term_ids,
                        ),
                    );
                }

                $related_query = new WP_Query( $related_args );

                if ( $related_query->have_posts() ) :
                    echo '<div class="related-videos-list">';
                    while ( $related_query->have_posts() ) : $related_query->the_post();
                        $thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'medium' ) : '';
                        ?>
                        <a href="<?php the_permalink(); ?>" class="related-video-item">
                            <div class="related-video-thumb">
                                <?php if ( $thumb_url ) : ?>
                                    <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>">
                                <?php else : ?>
                                    <div class="related-video-placeholder">
                                        <i class="fa fa-video-camera"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="related-play-icon"><i class="fa fa-play"></i></span>
                            </div>
                            <div class="related-video-info">
                                <h4><?php the_title(); ?></h4>
                                <span class="related-video-date"><?php echo get_the_date( 'd/m/Y' ); ?></span>
                            </div>
                        </a>
                        <?php
                    endwhile;
                    echo '</div>';
                    wp_reset_postdata();
                else :
                    echo '<p class="no-related">' . esc_html__( 'Nenhum vídeo relacionado encontrado.', 'text_domain' ) . '</p>';
                endif;
                ?>
            </div>
        </aside>
    </div>
	   <div class="rodape">
            <img src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/RODAPE-1.png">
        </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
// Animações GSAP para single video
if (typeof gsap !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        // Animação do header
        gsap.from('.video-header-enhanced', {
            opacity: 0,
            y: -30,
            duration: 0.6,
            ease: 'power2.out'
        });
        
        // Animação das seções de vídeo
        gsap.from('.video-section-card', {
            // opacity: 0,
            y: 30,
            duration: 0.5,
            stagger: 0.15,
            ease: 'power2.out',
            delay: 0.2
        });
        
        // Animação da sidebar
        gsap.from('.video-sidebar', {
            opacity: 0,
            x: 30,
            duration: 0.5,
            ease: 'power2.out',
            delay: 0.3
        });
        
        // Animação dos vídeos relacionados
        gsap.from('.related-video-item', {
            opacity: 0,
            x: -20,
            duration: 0.4,
            stagger: 0.08,
            ease: 'power2.out',
            delay: 0.5
        });
    });
}
</script>

<?php
endwhile; endif;
get_footer();

