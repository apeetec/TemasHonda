<!-- 
    ============================================================================
    FORMULÁRIO DE PERGUNTAS - TEMPLATE PART
    ============================================================================
    
    Este arquivo renderiza o container de perguntas para cada categoria.
    
    Variáveis esperadas (definidas no arquivo pai):
    - $codigo: Código de validação presencial
    - $checagem: Flag se o usuário já respondeu
    - $sanitiza_term_name: Nome sanitizado da categoria/termo
    - $term_id: ID do termo/categoria
    - $id_user: ID do usuário logado
    
    Estrutura:
    1. Container principal (.box-perguntas) com ID único baseado no código
    2. Formulário de envio das respostas
    3. Loop de perguntas e alternativas
    4. Botão de envio (oculto se já respondido)
    
    IMPORTANTE: O ID do container deve corresponder ao esperado pelo JS:
    - ID: perguntas_{codigo_sanitizado}
    - Classe 'ativo' quando liberado
    ============================================================================
-->
<?php
    // ========================================================================
    // SANITIZAÇÃO DO CÓDIGO PARA CONSISTÊNCIA COM JAVASCRIPT
    // ========================================================================
    // O código PHP precisa estar no mesmo formato que o JS espera
    // Aplica trim() e strtoupper() para garantir correspondência
    $codigo_sanitizado = trim(strtoupper($codigo));
?>
<article class="container">
    <!-- 
        Container de perguntas:
        - Classe 'box-perguntas': Oculto por padrão via CSS
        - Classe 'ativo': Adicionada quando liberado (via JS ou PHP se já respondido)
        - ID 'perguntas_{codigo}': Usado pelo JS para localizar o container correto
    -->
    <div class="box-perguntas <?php if(!empty($checagem)){ echo 'ativo';} ?>" id="perguntas_<?php echo $codigo_sanitizado;?>">
        <form action="" method="post" name="<?php echo $sanitiza_term_name;?>">
            <!-- Campo hidden: ID da categoria/termo para identificação no backend -->
            <input class="" type="hidden" name="slug_id" value="<?php echo $term_id;?>">
            
            <!-- 
                Campo hidden: Marca se o usuário foi presencial ou assistiu vídeo
                - Valor padrão: "Não presencial" (assistiu vídeo)
                - JavaScript altera para "Presencial" quando código válido é digitado
                - ID: presencial_{codigo} para localização pelo JS
            -->
            <input class="presencial" id="presencial_<?php echo $codigo_sanitizado;?>" type="hidden" name="presencial[<?php echo $sanitiza_term_name;?>]" value="Não presencial">
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
                    $alternativa_correta = !empty($entrada['alternativa_correta']) ? ' - Alternativa correta' : '';
                    // $alternativa_correta = !empty($entrada['alternativa_correta']) ? '  ' : '';
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