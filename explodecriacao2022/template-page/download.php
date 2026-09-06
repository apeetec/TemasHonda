<?php

/* Template Name: Download Desenhos */

get_header();
$args = array(
  "post_type" => 'desenhos',
  "posts_per_page" => 4,
  'orderby' => 'menu_order',
  'order' => 'ASC',
  // 'offset'=> 600
);
$posts = get_posts( $args);
?>
                <a class="download" onclick="generateZIP()">
                    Baixe todos
                </a>
                <button class="selectAll" id="selectAllButton">Selecionar todos</button>
<div class="main">
  <div class="content">
    <div class="container">
      <div class="all loop-drawings" style="display:grid;grid-template-columns:repeat(3,1fr);">
<?php
        foreach ($posts as $post){
              $id = $post->ID;
              $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
              $desenho = str_replace('-150x150', '', $desenho);
              $desenhoBig = str_replace('1280x720', '', $desenho);
              $thumb_id = attachment_url_to_postid( $desenho );
              $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
?>
      <div class="box_img">
        <img class="thumb" src="<?php echo $desenho;?>" alt="">
        <?php
           echo '<h2>' . get_the_title($postInfo['post']) . '</h2>';
           ?>
      </div>
      <?php
        }
      ?>
      </div>
    </div>
  </div>
</div>

<?php get_footer(); ?>
<script>
    $(document).ready(function() {
      // Quando o botão for clicado
      $("button").click(function() {

        // Adicionar a classe .thumbChecked aos mesmos elementos
        $(".thumb").addClass("thumbChecked");
      });
    });
  </script>