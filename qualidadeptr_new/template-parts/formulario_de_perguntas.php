<article class="container">
    <div class="box-perguntas <?php if(!empty($checagem)){ echo 'ativo';} ?>" id="perguntas_<?php echo $codigo;?>">
        <form action="" method="post" name="<?php echo $sanitiza_term_name;?>">
            <input class="" type="hidden" name="slug_id" value="<?php echo $term_id;?>">
            <input class="presencial" id="presencial_<?php echo $codigo;?>" type="hidden" name="presencial[<?php echo $sanitiza_term_name;?>]" value="Não presencial">
            <?php
                $todasRespostacorretas = []; // array de todas as respsotas corretas
                $todasRespostausuario = []; // array de todas as respostas do usuario

                // Argumentos das perguntas com o id da categoria setado em "field"
                $args = [
                'post_type' => 'perguntas', // Tipo de post
                'tax_query' => [
                        [
                            'taxonomy' => 'datas_perguntas', // Nome da taxonomia
                            'field'    => 'term_id',       // Campo de busca ('slug', 'term_id' ou 'name')
                            'terms'    => $term_id, // Valor a buscar
                            'include_children' => false, //
                        ],
                    ],
                ];
                // Loop das questões
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    //recebe as respostas do usuário
                    $resposta_do_usuario = get_user_meta($id_user,'user_field_'.$sanitiza_term_name.'_'.$post_id,true);
                    // recebe todas as respostas do usuário dentro do array para comparar mais tarde
                    $todasRespostausuario[] = $resposta_do_usuario;
                    // Questão
                    $questao = get_the_title($post_id);
                    // Grupo de alternativas
                    $grupo_alternativas = get_post_meta($post_id,'grupo_de_respostas',true);
            ?>
                <p class="questao">
                    <?php echo $questao;?>
                </p>
                <?php
                // Loop de alternativas
                foreach ($grupo_alternativas as $alternativas => $entrada) {
                    $alternativa = $entrada['alternativa'];
                    // $alternativa_correta = !empty($entrada['alternativa_correta']) ? ' - Alternativa correta' : '';
                    $alternativa_correta = !empty($entrada['alternativa_correta']) ? '  ' : '';
                    if($alternativa == $resposta_do_usuario){ $msg = "- (Sua resposta)"; } else { $msg = "";}

                    if(!empty($entrada['alternativa_correta'])){
                        $todasRespostacorretas[] = $alternativa;
                    }
           
                ?>
                    <p>
                        <label>
                            <?php
                            if(empty($checagem)){
                            ?>
                            <input id="alternativa_<?php echo $post_id;?>" name="resp_video[<?php echo $post_id;?>]" type="radio" value="<?php echo $alternativa;?>" required/>
                            <?php
                            }
                            ?>
                            <span class="alternativa">
                                <?php echo $alternativa;?>
                                    <small class="">
                                        <?php if(!empty($checagem)){echo $msg;} ?>
                                    </small>
                                    <small class="teal-text darken-4"><?php if(!empty($checagem)){ echo $alternativa_correta;}?></small>
                            </span>
                        </label>
                    </p>
                <?php
                }               
                ?>
            <?php
                }
            } 
                wp_reset_postdata();
            ?>
            <?php
                if(empty($checagem)){
            ?>
<!--                 <div class="row" style="grid-gap:0;">
                    <div class="col s12 m12 l12">
                        <p class="questao">
                            Durante a semana da campanha da qualidade, que destacou os temas atitude, concentração e comunicação, quais foram os aprendizados mais transformadores que você obteve e como eles impactaram sua forma de trabalhar ?
                        </p>
                    </div>
                    <div class="input-field col s12">
                        <textarea id="textarea2" class="materialize-textarea" name="sugestao" maxlength="420" placeholder="Digite aqui" required="true"></textarea>
                    </div>
                </div> -->
                <input type="submit" name="<?php echo $sanitiza_term_name;?>" value="Enviar">
            <?php
                }
                // Irá checar se acertou todas e marcar o checked caso tenha acertado todas
                $comparadorRespostasCorretas = count($todasRespostacorretas);
                $corretaAbsoluta = null;
                for ($i=0; $i < count($todasRespostacorretas); $i++) {                 
                    if($todasRespostausuario[$i] == $todasRespostacorretas[$i]){
                        $corretaAbsoluta++;
                    }
                }
                if($corretaAbsoluta == $comparadorRespostasCorretas){
                    update_user_meta($id_user, 'acertou_todas_alternativas_'.$sanitiza_term_name, 'on');
                }                        
            ?>
        </form>
    </div>
</article>