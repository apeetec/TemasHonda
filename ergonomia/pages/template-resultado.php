<?php

/* Template Name: Users */

get_header();

?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css">
<?php
if ( current_user_can( 'administrator' ) ) {

?>
<section>
    <article class="container">
        <h1>
            Todos os usuários
        </h1>
    </article>
    <article class="container">
        <table id="consultar_usuarios" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Nome</th>
                    <th>Unidade</th>
                    <th>Segunda Pergunta 1</th>
                    <th>Segunda Pergunta 2</th>
                    <th>Segunda Pergunta 3</th>
                    <th>Segunda Pergunta 4</th>
                    <th>Terça pergunta 1</th>
                    <th>Terça Pergunta 2</th>
                    <th>Quarta pergunta 1</th>
                    <th>Quarta Pergunta 2</th>
                    <th>Quarta Pergunta 3</th>
                    <th>Quinta pergunta 1</th>
                    <th>Quinta Pergunta 2</th>
                    <th>Quinta Pergunta 3</th>
                    <th>Sexta pergunta 1</th>
                    <th>Sexta Pergunta 2</th>
                    <th>Sexta Pergunta 3</th>
                    <!-- <td>horario</td> -->
                </tr>
            </thead>
            <tbody>
                <?php
                         $args = array(
                            'role' => 'subscriber',
                        );
                        $users_loop = get_users($args);
                        $current_user_login = get_user_by('id',get_current_user_id())->user_login;
                        $user_infos = get_user_by('id',get_current_user_id());
                        $role = $user_infos->roles[0];
                        $login = $user_infos->user_login;
                        $login = str_replace('.', '_', $login);
                        foreach($users_loop as $user) {
                            $user_id = $user->ID;
                            $useremail = $user->user_email;
                            $nome = get_user_meta($user_id,'first_name',true);
                            $unidade = get_user_meta($user_id,'user_field_unidade',true);        
                            $segunda_video_1_resp_1 = get_user_meta($user_id,'user_field_segunda-feira_272',true);         
                            $segunda_video_1_resp_2 = get_user_meta($user_id,'user_field_segunda-feira_273',true); 
                            $segunda_video_1_resp_3 = get_user_meta($user_id,'user_field_segunda-feira_6728',true);
                            $segunda_video_1_resp_4 = get_user_meta($user_id,'user_field_segunda-feira_6781',true);  
                            // $data_segunda = get_user_meta($user_id,'horario_da_data_de_segunda-feira',true);  
                            
                            $terca_video_resp_1 = get_user_meta($user_id,'user_field_terca-feira_274',true); 
                            $terca_video_resp_2 = get_user_meta($user_id,'user_field_terca-feira_275',true);   


                            $quarta_video_resp_1 = get_user_meta($user_id,'user_field_quarta-feira_276',true);     
                            $quarta_video_resp_2 = get_user_meta($user_id,'user_field_quarta-feira_279',true);   
                            $quarta_video_resp_3 = get_user_meta($user_id,'user_field_quarta-feira_6734',true); 

                            $quinta_video_resp_1 = get_user_meta($user_id,'user_field_quinta-feira_280',true);  
                            $quinta_video_resp_2 = get_user_meta($user_id,'user_field_quinta-feira_281',true);  
                            $quinta_video_resp_3 = get_user_meta($user_id,'user_field_quinta-feira_6787',true); 

                            $sexta_video_resp_1 = get_user_meta($user_id,'user_field_sexta-feira_283',true);       
                            $sexta_video_resp_2 = get_user_meta($user_id,'user_field_sexta-feira_6740',true);     
                            $sexta_video_resp_3 = get_user_meta($user_id,'user_field_sexta-feira_6741',true);              
                                                                         
                            $email = get_user_meta($user_id,'email',true);
                            $user_login = get_user_by( 'id', $user_id )->user_login;
                            $user_login = str_replace('.', '_', $user_login);  
                            if(!$segunda_video_1_resp_1 == '' && !$segunda_video_1_resp_2 == '' && !$segunda_video_1_resp_3 == '' && !$segunda_video_1_resp_4 == '')
                ?>
            <tr>
                <td>
                  <?php echo $user_login;?>
                </td>
			    <td>
                    <?php echo $nome;?>
                </td>
                <td>
                    <?php echo $unidade;?>
                </td>   
                <td>
                    <?php echo $segunda_video_1_resp_1;?>
                </td>   
                <td>
                    <?php echo $segunda_video_1_resp_2;?>
                </td>   
                <td>
                    <?php echo $segunda_video_1_resp_3;?>
                </td>   
                <td>
                    <?php echo $segunda_video_1_resp_4;?>
                </td> 
            <!-- Terça -->
                <td>
                    <?php echo $terca_video_resp_1;?>
                </td>   
                <td>
                    <?php echo $terca_video_resp_2;?>
                </td>   
 
            <!-- Quarta -->
                <td>
                    <?php echo $quarta_video_resp_1;?>
                </td>   
                <td>
                    <?php echo $quarta_video_resp_2;?>
                </td>   
                <td>
                    <?php echo $quarta_video_resp_3;?>
                </td>   
            <!--Quinta  -->
                <td>
                    <?php echo $quinta_video_resp_1;?>
                </td>   
                <td>
                    <?php echo $quinta_video_resp_2;?>
                </td>   
                <td>
                    <?php echo $quinta_video_resp_3;?>
                </td> 
            <!-- Sexta -->
                <td>
                    <?php echo $sexta_video_resp_1;?>
                </td>   
                <td>
                    <?php echo $sexta_video_resp_2;?>
                </td>   
                <td>
                    <?php echo $sexta_video_resp_3;?>
                </td>
                <!-- <td>
                    <?php echo $data_segunda;?>
                </td>    -->
        
		    </tr>
            <?php
                        }
            ?>
            </tbody>
        </table>
    </article>
</section>
<?php
}
else{
    echo 'você não tem permissão para acessar essa página';
}
?>
<?php get_footer(); ?>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://datatables.net/extensions/buttons/examples/initialisation/export.html"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script language="javascript">
// let table = new DataTable('#consultar_usuarios', {
//     dom: 'Bfrtip',
//     buttons: [
//         {
//             extend: 'copy',
//             text: 'Copiar tabela'
//         },
//         {
//             extend: 'excel',
//             text: 'Baixar Excel'
//         },
//         {
//             extend: 'csv',
//             text: 'Baixar CSV'
//         },
//         {
//             extend: 'pdf',
//             text: 'Baixar PDF'
//         },
//         {
//             extend: 'print',
//             text: 'Imprimir'
//         },
//     ]     
// });
$(document).ready(function () {
    $('#consultar_usuarios').DataTable({
        dom: 'Bfrtip',
        buttons: [
        {
            extend: 'copy',
            text: 'Copiar tabela'
        },
        {
            extend: 'excel',
            text: 'Baixar Excel'
        },
        {
            extend: 'csv',
            text: 'Baixar CSV'
        },
        {
            extend: 'pdf',
            text: 'Baixar PDF'
        },
        {
            extend: 'print',
            text: 'Imprimir'
        },
    ],
    scrollX: false, 
    autoWidth: true,
    language: {
              url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Portuguese-Brasil.json'
            }, 
    "lengthMenu": [[50, 100, 200, 500, 2500, 5000], [50, 100, 200, 500, 2500, 5000]],   
    });
});
</script>