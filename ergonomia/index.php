<?php get_header(); ?>

<article>
    <section class="container">
        <div class="five-grid">

            <?php
            $terms = [
                10 => 'https://www.campaq.com.br/wp-content/uploads/2026/02/dias_01.jpg',
                5  => 'https://www.campaq.com.br/wp-content/uploads/2026/02/dias_03.jpg',
				13  => 'https://www.campaq.com.br/wp-content/uploads/2026/02/dias_05-1.jpg',
                6  => 'https://www.campaq.com.br/wp-content/uploads/2026/02/dias_05-1.jpg',
                11 => 'https://www.campaq.com.br/wp-content/uploads/2026/02/dias_07-1.jpg',
                8  => 'https://www.campaq.com.br/wp-content/uploads/2026/02/dias_09.jpg',
            ];

            foreach ($terms as $term_id => $image_url) :

                $term_link = get_term_link($term_id, 'datas_perguntas');

                if (!is_wp_error($term_link)) :
            ?>
                <div class="col s12 m3 l2">
                    <a href="<?php echo esc_url($term_link); ?>">
                        <img src="<?php echo esc_url($image_url); ?>"
                             alt="Data <?php echo esc_attr($term_id); ?>"
                             class="responsive-img">
                    </a>
                </div>
            <?php
                endif;
            endforeach;
            ?>

        </div>
    </section>
</article>

<?php get_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Verifica se o Materialize está carregado
    if (typeof M !== 'undefined') {

        var elems = document.querySelectorAll('.modal');

        var instances = M.Modal.init(elems, {
            onOpenEnd: function () {
                // código opcional ao abrir modal
            },
            onCloseEnd: function () {
                var video = document.getElementById("videoId");
                if (video) {
                    video.pause();
                }
            },
            dismissible: false
        });

        // Abre o primeiro modal automaticamente
        if (instances.length > 0) {
            instances[0].open();
        }
    }

});
</script>
