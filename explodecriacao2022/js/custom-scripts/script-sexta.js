// ################## Adiciona classes na confirmação se já assistiu ou não (Video #1)
$('#sexta input:radio[name="presencial"]').change(
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
            $('#sexta .video-box').show();
            $('.perguntas-page .content .questions').addClass('check-nao'); 
        }
        else {
        	// $('.video-box').hide();
        	$('.perguntas-page .content .questions').removeClass('check-nao'); 
        }
        // Atualiza o campo oculto de cada form
        $('input[name="presencial_video"]').val($(this).val());
});

$('#sexta .codigo input').keyup(function () {
if($(this).val() == 'SP7T4' || $(this).val() == 'sp7t4') {
    $('#confirmar_presencial_sexta').val('Presencial');
    // $('.block-questions').addClass('presencial-sim');
    $('#sexta #video_box_one').hide();
    $('#sexta .nao').hide();
    $('#sexta .block-1').addClass('show');
    console.log("Deu certo");
}
else {
    // $('.video-2 .all-questions').hide();
    $('.block-questions').removeClass('presencial-sim');
    console.log("Não deu certo");
}

});

// Video
$("#sexta .play-video-one").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#sexta #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('pause');
    // Play video
    $('#sexta #video_1').get(0).play();
});
// ################## PAUSE VIDEO #1 AO CLICAR NO ICONE
$("#sexta .pause-video-one").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#sexta #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('play');
    // Play video
    $('#sexta #video_1').get(0).pause();
});
// Script para vídeo, exibir conteúdo depois de segundos.
$("#sexta #video_1").bind("timeupdate", function(){
    var currentTime = this.currentTime;
    var duration = this.duration;
    var watchPoint = Math.floor((currentTime/duration) * 100);
    console.log('Tempo atual: ', currentTime);
    console.log('Duração: ', currentTime);
    console.log('Watch point: ', watchPoint);
        // PORCENTAGEM SEM %
        if(watchPoint >= 99) {
            $('#sexta .block-1').show();
            // $('.video-1 .videos #aviso').hide();
            // $('.perguntas-page .content .questions').addClass('presencial-nao');
        }
        else {
            // $('.video-1 .videos #aviso').show();
            // $('.perguntas-page .content .questions').removeClass('presencial-nao');
        }
});


