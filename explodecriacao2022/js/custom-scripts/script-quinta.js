// ################## Adiciona classes na confirmação se já assistiu ou não (Video #1)
$('#quinta input:radio[name="presencial"]').change(
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
            $('#quinta #video_box_one').show();
            $('#quinta #video_box_two').hide();
            $('#quinta .atracao-1').show();
            $('#quinta .atracao-2').hide();
            $('#form_quinta_1').show();
            $('#form_quinta_2').hide();
            $('#quinta #video_2').get(0).pause();
            $('.perguntas-page .content .questions').addClass('check-nao'); 
        }
        else if($(this).is(':checked') && $(this).val() == 'Não 2'){
            $('#quinta #video_box_one').hide();
            $('#quinta .block-1').hide();
            $('#form_quinta_1').hide();
            $('#form_quinta_2').show();
            $('#quinta .atracao-1').hide();
            $('#quinta .atracao-2').show();
            $('#quinta #video_box_two').show();
            $('#quinta #video_1').get(0).pause();
        }
        else {
        	// $('.video-box').hide();
        	$('.perguntas-page .content .questions').removeClass('check-nao'); 
        }
        // Atualiza o campo oculto de cada form
        $('input[name="presencial_video"]').val($(this).val());
});


$('#quinta .codigo input').keyup(function () {
    if($(this).val() == 'SP5T8' || $(this).val() == 'sp5t8') {
        $('#confirmar_presencial_quinta').val('Presencial');
        $('#quinta .block-1').addClass('show');
        $('#quinta .nao-1').hide();//oculta o código do primeiro video
        $('#quinta #video_1').hide();//oculta só o primeiro video
        $('#form_quinta_1').show();
        $('#form_quinta_2').hide();
        // Sumir com os videos e as questões videos 2
        $('#quinta #video_box_one').hide();//oculta o primeiro video
        $('#quinta .block-2').hide();//oculta as perguntas do segundo video
        $('#quinta .atracao-2').hide();//oculta o campo de atração do segundo video
        $('#quinta #video_1').get(0).pause();
        $('#quinta #video_2').get(0).pause();
        alert("Você liberou as perguntas do primeiro video, só é permitido responder um formulário de cada vez");
          console.log("Deu certo");
        }
    else if($(this).val() == 'SP6T9' || $(this).val() == 'sp6t9'){
        $('#quinta .block-2').addClass('show');
        $('#quinta .block-2').show();//mostra as perguntas
        $('#quinta .nao-2').hide();//Oculta o campo de código
        $('#quinta #video_2').hide();//Oculta só o video
        $('#form_quinta_1').hide();
        $('#form_quinta_2').show();
        // Sumir com os videos e as questões videos 1
        $('#quinta #video_box_one').hide();
        $('#quinta .block-1').hide();
        $('#quinta .atracao-1').hide();     
        $('#quinta .atracao-2').show();    
        $('#quinta #video_box_two').hide();
        alert("Você liberou as perguntas do segundo video");
        $('#confirmar_presencial_quinta_video_2').attr('value', 'Presencial');
    }
    else {
        console.log("Não deu certo");
    } 
});

// ################## PLAY VIDEO #1 AO CLICAR NO ICONE
$("#play_quinta_1").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#quinta #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('pause');
    // Play video
    $('#quinta #video_1').get(0).play();
});
// ################## PAUSE VIDEO #1 AO CLICAR NO ICONE
$("#pause_quinta_1").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#quinta #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('play');
    // Play video
    $('#quinta #video_1').get(0).pause();
});
// ################## PLAY VIDEO #2 AO CLICAR NO ICONE
$("#play_quinta_2").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#quinta #video_2").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('pause');
    // Play video
    $('#quinta #video_2').get(0).play();
});
// ################## PAUSE VIDEO #2 AO CLICAR NO ICONE
$("#pause_quinta_2").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#quinta #video_2").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('play');
    // Play video
    $('#quinta #video_2').get(0).pause();
});
// ################## SEGUNDA VIDEO #1 (PORCENTAGEM) ##################
// Script para vídeo, exibir conteúdo depois de segundos.
$("#quinta #video_1").bind("timeupdate", function(){
    var currentTime = this.currentTime;
    var duration = this.duration;
    var watchPoint = Math.floor((currentTime/duration) * 100);
    console.log('Tempo atual: ', currentTime);
    console.log('Duração: ', currentTime);
    console.log('Watch point: ', watchPoint);
        // PORCENTAGEM SEM %
        if(watchPoint >= 99) {
            $('#quinta .block-1').show();
            // $('.video-1 .videos #aviso').hide();
            // $('.perguntas-page .content .questions').addClass('presencial-nao');
        }
        else {
            // $('.video-1 .videos #aviso').show();
            // $('.perguntas-page .content .questions').removeClass('presencial-nao');
        }
});
// ################## SEGUNDA VIDEO #2 (PORCENTAGEM) ##################
// Script para vídeo, exibir conteúdo depois de segundos.
$("#quinta #video_2").bind("timeupdate", function(){
    var currentTime = this.currentTime;
    var duration = this.duration;
    var watchPoint = Math.floor((currentTime/duration) * 100);
    console.log('Tempo atual: ', currentTime);
    console.log('Duração: ', currentTime);
    console.log('Watch point: ', watchPoint);
    
        // PORCENTAGEM SEM %
        if(watchPoint >= 99) {
            $('#quinta .block-2').show();
            // $('.video-1 .videos #aviso').hide();
            // $('.perguntas-page .content .questions').addClass('presencial-nao');
        }
        else {
            // $('.video-1 .videos #aviso').show();
            // $('.perguntas-page .content .questions').removeClass('presencial-nao');
        }
});