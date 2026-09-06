// ################## Adiciona classes na confirmação se já assistiu ou não (Video #1)
$('#quarta input:radio[name="presencial"]').change(
    function(){
        if ($(this).is(':checked') && $(this).val() == 'Sim') {
            $('.codigo').show();     
            $('.perguntas-page .content .questions').addClass('check-sim');       
        }
        else {
        	$('.codigo').hide();
        	$('.perguntas-page .content .questions').removeClass('check-sim'); 
        }
        if ($(this).is(':checked') && $(this).val() == 'Não') {
            $('#quarta .video-box').show();
            $('.perguntas-page .content .questions').addClass('check-nao'); 
        }
        else {
        	// $('.video-box').hide();
        	$('.perguntas-page .content .questions').removeClass('check-nao'); 
        }
        // Atualiza o campo oculto de cada form
        $('input[name="presencial_video"]').val($(this).val());
});

$('#quarta .codigo input').keyup(function () {
if($(this).val() == 'SP4T5' || $(this).val() == 'sp4t5') {
    $('#confirmar_presencial_quarta').val('Presencial');
    // $('.block-questions').addClass('presencial-sim');
    $('#quarta .block-1').addClass('show');
    $('#quarta #video_1').hide();
    $('#quarta .nao').hide();
    console.log("Deu certo");
}
else {
    // $('.video-2 .all-questions').hide();
    // $('.block-questions').removeClass('presencial-sim');
    console.log("Não deu certo");
}
});
// Video
$("#play_quarta").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#quarta #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    // Play video
    $('#quarta #video_1').get(0).play();
});
// ################## PAUSE VIDEO #1 AO CLICAR NO ICONE
$("#pause_quarta").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#quarta #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('play');
    // Play video
    $('#quarta #video_1').get(0).pause();
});
// Script para vídeo, exibir conteúdo depois de segundos.
$("#quarta #video_1").bind("timeupdate", function(){
    var currentTime = this.currentTime;
    var duration = this.duration;
    var watchPoint = Math.floor((currentTime/duration) * 100);
    console.log('Tempo atual: ', currentTime);
    console.log('Duração: ', currentTime);
    console.log('Watch point: ', watchPoint);
        // PORCENTAGEM SEM %
        if(watchPoint >= 99) {
            $('#quarta .block-1').show();
            // $('.video-1 .videos #aviso').hide();
            // $('.perguntas-page .content .questions').addClass('presencial-nao');
        }
        else {
            // $('.video-1 .videos #aviso').show();
            // $('.perguntas-page .content .questions').removeClass('presencial-nao');
        }
});

// ################## VALIDAÇÃO ANTES DE ENVIAR
// $("#form_quarta").submit(function(){
//     var bloco_perguntas = $(this).closest('#quarta .block-1');
//     var isValid;

//   // VALIDA SE NOTA FOI SELECIONADA
//   if(!bloco_perguntas.find('input#quarta_radio').is(':checked')) {
//       isValid = false;
//   }

//   // SE A VALIDACAO CONTER ERROS, EXIBE MENSAGEM E NAO PROSSEGUE
//   if(isValid == false) {
//     $('#modal_segunda').modal('open');
//     console.log("Não marcou todas")
//       return false;
//   }
//   else {
//     console.log("marcou todas")
//   }

// });