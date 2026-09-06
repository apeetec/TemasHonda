<?php

/*
Template Name: Usuários
*/

get_header();

?>

<style>
.obs {display: none;}
</style>

<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" />
<link rel="stylesheet" type="text/css"  href="https://cdn.datatables.net/buttons/1.4.0/css/buttons.dataTables.min.css" />

<div class="custom-page resultados space-top">
  <div class="body space">
    <!-- <div class="container space-top-bottom">
      <a href="#" id="download_excel" class="btn-large">Download Excel</a>
    </div> -->
      <div class="container">
        <!-- <img class="loading" src="<?php bloginfo('template_url'); ?>/images/loading.gif" style="display: block;"> -->
        <div class="tabela-resultados" style="overflow-x:auto;">
          <table id="tabelaresultados" class="display white" name="resultados">
            <thead>
              <tr>
                  <th>Matrícula</th>
                  <th>E-mail</th>
                  <th>Nome</th>
                  <?php
                  // Uma dupla de colunas por dependente aceito pelo tema.
                  // Antes eram tres pares fixos, entao os dependentes seguintes
                  // simplesmente nao apareciam neste relatorio.
                  $max_dep = function_exists( 'explode_max_dependentes' ) ? explode_max_dependentes() : 6;
                  for ( $s = 1; $s <= $max_dep; $s++ ) : ?>
                    <th>Dependente #<?php echo esc_html( $s ); ?></th>
                    <th>Desenho <?php echo esc_html( $s ); ?></th>
                  <?php endfor; ?>
                  <th>Unidade</th>
                  <th>Grupo</th>
                  <th>Votou ?</th>
            </thead>
              <tbody>
                <?php
                $args = array(
                  'role__in' => 'subscriber',
                  'number' => -1
                );
                $user_query = get_users( $args );

                foreach($user_query as $user) {
                  $user_id = $user->ID;
                  $nome = get_user_meta($user_id,'first_name',true);
                  $email = get_user_meta($user_id, "email", true);
                  // Le nome e situacao do desenho de cada slot de dependente
                  $dependentes = array();
                  for ( $s = 1; $s <= $max_dep; $s++ ) {
                    $dependentes[ $s ] = array(
                      'nome'    => get_user_meta( $user_id, 'user_field_dependente_' . $s . '_nome', true ),
                      'desenho' => get_user_meta( $user_id, 'user_field_dependente_' . $s . '_desenho', true ),
                    );
                  }
                  $id_unidade = get_user_meta($user_id,'user_field_unidade',true);
                  $voto = get_user_meta($user_id,'user_field_votacao',true);
                  $grupo = get_user_meta($user_id,'user_field_funcionario_grupo',true);
                  $term = get_term( $id_unidade, 'user_unidade');
                  if(!empty($voto)){
                ?>
                <tr>
                  <td><?php echo $user->user_login; ?></td>
                  <td><?php echo $user->user_email;  ?></td>
                  <td><?php echo $nome; ?></td>
                  <?php foreach ( $dependentes as $dep ) : ?>
                    <td><?php echo esc_html( $dep['nome'] ); ?></td>
                    <td><?php echo esc_html( $dep['desenho'] ); ?></td>
                  <?php endforeach; ?>
                  <td><?php echo $term->name; ?></td>
                  <td><?php echo $grupo; ?></td>
                  <td><?php echo $voto; ?></td>
                  <?php
                  }
                }
                  ?>
                </tr>            
              </tbody>
          </table>
        </div>

      </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.27/build/pdfmake.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.27/build/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/buttons.flash.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.4.0/js/buttons.print.min.js"></script>
<script>
$(document).ready(function() {
    $('#tabelaresultados').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    } );
} );
</script>

<script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script> 

<script>
      $(function() {
        $("#download_excel").click(function(e){
        e.preventDefault();
        $("#tabelaresultados").table2excel({
          // Classes a serem excluidas durante a exportacao para excel
          exclude: ".noExl",
            name: "Resultados-SIPAT2021",
            filename: "Resultados-SIPAT2021", // Nome do arquivo
        }); 
         });
      });
    </script>

<?php get_footer(); ?>