<div class="resultados">
	<div class="container">

		<h2 class="center">Resultado</h2>

    <br><br>

    <div class="result-loop">
            <div class="botoes">
              <a href="#grupo-1" class="single active">Grupo 1</a>
              <a href="#grupo-2" class="single">Grupo 2</a>
              <a href="#grupo-3" class="single">Grupo 3</a>
            </div>

            <div class="all">

              <!-- INICIO GRUPO 1 -->
              <div class="single" id="grupo-1" style="display: block;">

                <!-- CATEGORIA A -->
                <div class="content cat-a">
                  <h2>Categoria A</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 806;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <span id="pcd">PCD</span>
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 491;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->    

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 679;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->    

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 509;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->                   

                  </div>

                </div>
                <!-- CATEGORIA A -->

                <!-- CATEGORIA B -->
                <div class="content cat-b">
                  <h2>Categoria B</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 249;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <span id="pcd">PCD</span>
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 551;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 515;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 507;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                  </div>

                </div>
                <!-- CATEGORIA B -->

                <!-- CATEGORIA C -->
                <div class="content cat-c">
                  <h2>Categoria C</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 832;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 181;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 299;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 810;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                  </div>

                </div>
                <!-- CATEGORIA C -->

              </div>
              <!-- INICIO GRUPO 2 -->

              <!-- INICIO GRUPO 1 -->
              <div class="single" id="grupo-2">

                <!-- CATEGORIA A -->
                <div class="content cat-a">
                  <h2>Categoria A</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 359;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <span id="pcd">PCD</span>
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 167;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->    

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 248;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->          

                  </div>

                </div>
                <!-- CATEGORIA A -->

                <!-- CATEGORIA B -->
                <div class="content cat-b">
                  <h2>Categoria B</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 685;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 315;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 325;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                  </div>

                </div>
                <!-- CATEGORIA B -->

                <!-- CATEGORIA C -->
                <div class="content cat-c">
                  <h2>Categoria C</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 798;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 289;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 339;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->
                  
                  </div>

                </div>
                <!-- CATEGORIA C -->

              </div>
              <!-- INICIO GRUPO 2 -->

              <!-- INICIO GRUPO 3 -->
              <div class="single" id="grupo-3">

                <!-- CATEGORIA A -->
                <div class="content cat-a">
                  <h2>Categoria A</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 169;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <span id="pcd">PCD</span>
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 888;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->    

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 924;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->          

                  </div>

                </div>
                <!-- CATEGORIA A -->

                <!-- CATEGORIA B -->
                <div class="content cat-b">
                  <h2>Categoria B</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 441;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 730;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 754;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                  </div>

                </div>
                <!-- CATEGORIA B -->

                <!-- CATEGORIA C -->
                <div class="content cat-c">
                  <h2>Categoria C</h2>

                  <div class="desenhos">

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 840;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 363;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->

                    <!-- INICIO DESENHO -->
                    <?php
                    $id = 395;
                    $author_id = get_post($id)->post_author;
                    $matricula = get_user_by('id',$author_id)->user_login;
                    $author_name = get_user_meta($author_id,'first_name',true);
                    $unidade = get_post_meta($id,'desenhos_box_unidade',true);
                    $desenho = get_post_meta($id,'desenhos_box_desenho',true);
                    $desenho = str_replace('-150x150', '', $desenho);
                    $thumb_id = attachment_url_to_postid( $desenho );
                    $first_image_url = wp_get_attachment_image_src($thumb_id, 'medium');
                    // Pega a primeira imagem e utiliza a versão pequena
                    $desenho = $first_image_url[0];
                    ?>
                    <div class="block">
                      <div class="head">
                        <div class="desenho" style="background-image: url('<?php echo $desenho; ?>')"></div>
                      </div>
                      <div class="info">
                        <h3>Resp: <?php echo $author_name; ?></h3>
                        <h2><span id="mat"><?php echo $matricula; ?></span> - <span id="uni"><?php echo get_term( $unidade )->name; ?></span></h2>
                      </div>
                    </div>
                    <!-- FIM DESENHO -->
                  
                  </div>

                </div>
                <!-- CATEGORIA C -->

              </div>
              <!-- INICIO GRUPO 3 -->

            </div>
          </div>    

	</div>
</div>

<script>
$(".botoes a").on('click',function(){
  var grupo = $(this).attr('href');

  // ATIVA BOTAO
  $('.botoes a').removeClass('active');
  $(this).addClass('active');

  // EXIBE DIV
  $('.all .single').hide();
  $(grupo).fadeIn(300);
});
</script>