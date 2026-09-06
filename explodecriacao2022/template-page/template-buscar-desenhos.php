<?php

/*
Template Name: Buscar desenhos
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
                  <th>Nome do filho</th>
                  <th>Protocolo</th>
                  <th>Responsável</th>
  
            </thead>
              <tbody>
                <?php
                $args = array(
                  'numberposts' => -1,
                  'post_type' => 'desenhos'
                );
                $desenhos = get_posts( $args );
                // print_r($desenhos);
                foreach($desenhos as $desenho){
                  $id = $desenho->ID;
                  $titulo = $desenho->post_title;
                  $protocolo = get_post_meta( $id, 'desenhos_box_protocolo', true );
                 // Pega os termos da taxonomia 'category', por exemplo
                $termos = wp_get_post_terms( $id, 'desenhos_cat', array( 'fields' => 'names' ) ); // Troque 'category' pela sua taxonomia
                ?>
                <tr>
                  <td><?php echo $titulo; ?></td>
                  <td><?php echo $protocolo ?></td>
                  <td><?php echo implode(', ', $termos); ?></td> <!-- Exibe os termos como uma lista separada por vírgula -->
                  <?php
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