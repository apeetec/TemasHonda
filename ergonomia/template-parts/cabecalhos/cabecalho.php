
    <article class="container">
        <div class="date-info">
            <p><span class="calendar"><i class="fas fa-calendar-alt"></i>&nbsp;<?php echo date("d/m",strtotime($data_inicio)); ?></span></p>
            <h4>&nbsp;&nbsp;|&nbsp;&nbsp;<strong><?php echo $term_name; ?></strong></h4>
        </div>
    </article>

    <article class="container">
        <?php
            if($today <= $data_inicio){
        ?>
            <p class="data-liberacao" id="data_liberacao">
                Data de liberação do video: <b><?php echo date("d/m",strtotime($data_inicio)); ?></b> às <b><?php echo str_replace(':00', 'h', date("H:i",strtotime($data_inicio))); ?></b>.
            </p>
        <?php
        }
        if(empty($checagem) && $today >= $data_inicio && $today <= $data_fim){   
        ?>
            <p class="space-top">
                <b>Atenção:</b> Você <b>não</b> está participando do sorteio do dia! Você não finalizou o envio da pesquisa abaixo.
            </p>
        <?php
            }
            else if(!empty($checagem) && $today >= $data_inicio && $today <= $data_fim){
        ?>
            <p id="yes" class="status">
                <b>Agradecemos a sua participação.</b> Você já está concorrendo ao sorteio de brindes do dia!
            </p>
        <?php
            }
        ?>
        <div class="divider"></div>
    </article>

<!-- Digitar codigo ou assistir presencialmente -->
<?php
// if(empty($checagem) && empty($checagem_children)){
?>
    <article class="container">
        <form action="" method="POST" class="space-top" id="form-check">
            <div id="box-escolha" class="escolha">
                <?php
                    if(empty($checagem) && $today >= $data_inicio){
                ?>
                <p id="digitar_codigo">
                    <label>
                        <input type="radio" name="presencial" value="Sim"/>
                        <span>Digitar código</span>
                    </label>
                </p>
                <p id="assistir">
                    <label>
                        <input type="radio" name="presencial" value="Não">
                        <span>Assistir</span>
                    </label>
                </p>
                <?php
                    }
                ?>
            </div>

            <div class="box-codigo <?php echo $sanitiza_term_name;?>">
                <p class="text">Digite o código de validação de 5 digitos fornecido na palestra presencial para acessar a pesquisa:</p>  
                <p class="text">Ao digitar o código corretamente, o video irá aparecer, porém, sua visualização <strong>não será obrigatória</strong>!</p>   
                <p class="row codigo">
                    <label class="s12 m6 l2 input-field"> 
                        <input type="text" name="codigo" data-error="wrong" placeholder="XXXXX" maxlength="7">           
                        <input class="input_codigo_oculto" id="teste" type="hidden" name="compare_codigo_presencial" value="<?php echo $codigo;?>" disabled>
                        <?php
                        foreach($children as $child){
                            $codigo_child = get_term_meta( $child, 'codigo', true );
                        ?> 
                        <input class="input_codigo_oculto" id="<?php echo 'video_'.$sanitiza_term_name.'_'.$child; ?>" type="hidden" name="compare_codigo_presencial" value="<?php echo $codigo_child;?>" disabled>
                        <?php
                        }
                        ?>
                    </label>
                </p>
            </div>     
        </form>
        <input type="hidden" id="id_usuario" name="Id_usuario" value="<?php echo $id_user;?>">
        <input type="hidden" id="categoria" name="categoria_video" value="<?php echo $sanitiza_term_name;?>">
        <div class="divider"></div>
    </article>
<?php
// }
?>
<!-- Atração e video tag -->
    <?php
    if($today >= $data_inicio && $today <= $data_fim){
        require_once( get_template_directory() . '/template-parts/cabecalhos/atracao_e_video.php' );
    }
    ?>
