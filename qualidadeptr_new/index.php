<?php 
get_header(); 
$today = date('Y-m-d H:i:s');
$inicio = date('2025-05-12 08:00:00');
$fim = date('2025-05-16 08:00:00');
?>
<?php if($today >= $inicio){?>
<!-- <article class="infos">
 <section class="container" style="padding:0 1rem;">
    <div class="row">
        <div class="col s12 m12 l2 center"><a href="https://qualidadeptr.com.br/datas_perguntas/segunda-feira/" class="btn-large">vídeo de abertura</a></div>
        <div class="col s12 m12 l2 center"><a href="https://qualidadeptr.com.br/datas_perguntas/terca-feira/" class="btn-large">atitude</a></div>
        <div class="col s12 m12 l2 center"><a href="https://qualidadeptr.com.br/datas_perguntas/quarta-feira/" class="btn-large">concentração</a></div>
        <div class="col s12 m12 l2 center"><a href="https://qualidadeptr.com.br/datas_perguntas/quinta-feira/" class="btn-large">comunicaão</a></div>
        <div class="col s12 m12 l2 center"><a href="https://qualidadeptr.com.br/datas_perguntas/sexta" class="btn-large">fechamento</a></div>
        <div class="col s12 m12 l2 center"><a href="https://qualidadeptr.com.br/jogo-de-arrastar/" class="btn-large">interatividade</a></div>
		<div class="col s12 m12 l1 center"></div>
    </div>
 </section>
</article> -->
<?php }
// else{
//   echo '<h1 class="center-align">A campanha terá inicio no dia 12/05/2025</h1>';
// }
?>
<?php get_footer(); ?>