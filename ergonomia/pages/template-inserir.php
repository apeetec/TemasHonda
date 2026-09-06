<?php

/*
Template Name: Inserir
*/

get_header();

?>
<style>
  /* .content-text .container,.box-upload{padding: 0 2rem;} */
.content-text h1{font-size: 3rem;margin-top: 40px;}
.content-text .divider{height: 1px;background-color: #e0e0e0;display: block;width: 100%;margin: 15px 0;}
.content-text p{font-size: 1.3rem;line-height: 2.2rem;}
.box-text{margin-top: 20px;}
.box-upload{margin-top: 70px;}
#result{display: grid;grid-template-columns: 1fr 1fr 1fr;margin: 40px 0;}
.grid-div{height: 400px;overflow-y: auto;}
.grid-div p{border: 1px solid #e0e0e0;padding: 10px;}
.bnt-download{display: inline-block;padding: 20px;background-color: #8fcdff;font-weight: 400;margin-top: 40px;}
aside{display:none!important;}
.container{width: 100%!important;}
.row{display:block!important;}
</style>
<article class="content-text">
	<section class="container">
		<h1>
			Essa é a página de inserção de usuários novos
		</h1>
		<div class="divider"></div>
		<div class="box-text">
			<p>
					É de extrema importância que seja realizado o backup de todos os arquivos do site e seu banco de dados, caso não tenha efetuado ainda, sugiro que faça antes de efetuar a inserção dos usuários pois esse processo é definitivo e não há como interromper muito menos voltar à forma anterior!
			</p>
			<p>
			Usuários que não existem, são inseridos, e os que já existem os dados são atualizados, o mesmo se mede para Unidades, Setores e Empresas.
			</p>
			<p>
				Faça o download do CSV abaixo para que possa realizar a inserção dos usuários.
			</p>
		</div>
	</section>
	<section class="container">
		<a href="" download="lista" class="bnt-download">
			Download
		</a>
	</section>
</article>
<article class="box-upload">
	<section class="container">
		<form id="uploadForm" enctype="multipart/form-data" method="POST">
			<input type="file" name="csvFile" accept=".csv">
			<!-- <input type="submit" value="Enviar"> -->
			<input type="button" value="Enviar" onclick="uploadCSV()">
		</form>
	</section>
</article>
<article class="box-results">
	<section class="container">
		<div id="result">

		</div>
	</section>
</article>

<?php get_footer(); ?>
<script>
  function uploadCSV() {
  var formData = new FormData($('#uploadForm')[0]);

  $.ajax({
    url: '<?php bloginfo('template_url'); ?>/sql/inserir-usuarios.php',
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      timeout: 100000000, // timeout em milissegundos (10 segundos)
      success: function(response) {
          $('#result').html(response);
      },
      error:function(response){
        $('#result').html(response);
      }
      
  });
}



</script>