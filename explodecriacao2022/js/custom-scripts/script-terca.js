// ################## Adiciona classes na confirmação se já assistiu ou não (Video #1)
    $('#terca input:radio[name="presencial"]').change(
        function(){
            if ($(this).is(':checked') && $(this).val() == 'Sim') {
                $('.codigo').show();     
                // $('.perguntas-page .content .questions').addClass('check-sim');       
            }
            else {
                $('.codigo').hide();
                $('.perguntas-page .content .questions').removeClass('check-sim'); 
            }
            if ($(this).is(':checked') && $(this).val() == 'Não') {
                $('#terca .video-box').show();
                $('#terca #video_1').show();
                $('#terca #video_2').show();

                $('#terca #video_box_two').hide();
                $('#terca #video_2').hide();

                $('.perguntas-page .content .questions').addClass('check-nao'); 
            }
            else if($(this).is(':checked') && $(this).val() == 'Não 2'){
                $('#terca #video_box_two').show();
                $('#terca #video_2').show();

                $('#terca #video_box_one').hide();
                $('#terca #video_1').hide();
            }
            else {
                // $('.video-box').hide();
                $('.perguntas-page .content .questions').removeClass('check-nao'); 
            }
            // Atualiza o campo oculto de cada form
            $('input[name="presencial_video"]').val($(this).val());
    });
// Colocar código
    $('#terca .codigo input').keyup(function () {
    if($(this).val() == 'SP2T5' || $(this).val() == 'sp2t5') {
        $('#confirmar_presencial_terca').val('Presencial');
        // $('.block-questions.block-1').addClass('presencial-sim');
        $('#terca .block-1').addClass('show');
        // $('#terca .nao').hide();
        $('#terca #video_1').hide();
        // Sumir com os videos e as questões videos 2
        $('#terca #video_box_one').hide();
        $('#terca .block-2').hide();
        $('#terca .atracao-2').hide();
        alert("Você liberou as perguntas do primeiro video");
        console.log("Deu certo");
    }
    else if($(this).val() == 'SP3T7' || $(this).val() == 'sp3t7'){
        $('#terca .block-2').addClass('show');
        $('#terca .block-2').show();
        $('#terca #video_box_two').hide();
        // $('#terca .nao').hide();
        // Sumir com os videos e as questões videos 1
        $('#terca #video_box_one').hide();
        $('#terca .block-1').hide();
        $('#terca .atracao-1').hide();     
        $('#terca #video_box_two').hide();
        alert("Você liberou as perguntas do segundo video");
        $('#confirmar_presencial_terca_segundo_video').attr('value', 'Presencial');
    }
    else {
        // $('.video-2 .all-questions').hide();
        $('.block-questions').removeClass('presencial-sim');
        console.log("Não deu certo");
    }
    });

// ################## PLAY VIDEO #1 AO CLICAR NO ICONE
$("#play_1_terca").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#terca #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $("#pause_1_terca").addClass('pause');
    // Play video
    $('#terca #video_1').get(0).play();
});
// ################## PAUSE VIDEO #1 AO CLICAR NO ICONE
$("#pause_1_terca").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#terca #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('play');
    // Play video
    $('#terca #video_1').get(0).pause();
});
// ################## PLAY VIDEO #2 AO CLICAR NO ICONE
$("#terca .play-video-two").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#terca #video_2").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('pause');
    // Play video
    $('#terca #video_2').get(0).play();
});
// ################## PAUSE VIDEO #2 AO CLICAR NO ICONE
$("#terca .pause-video-two").on('click',function(e){
    e.preventDefault();
    // Remove status atual do vídeo...
    $("#terca #video_2").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
    // Adiciona status de play
    $(".buttons-play-and-pause").addClass('play');
    // Play video
    $('#terca #video_2').get(0).pause();
});
// ################## SEGUNDA VIDEO #1 (PORCENTAGEM) ##################
// Script para vídeo, exibir conteúdo depois de segundos.
$("#terca #video_1").bind("timeupdate", function(){
    var currentTime = this.currentTime;
    var duration = this.duration;
    var watchPoint = Math.floor((currentTime/duration) * 100);
    console.log('Tempo atual: ', currentTime);
    console.log('Duração: ', currentTime);
    console.log('Watch point: ', watchPoint);
        // PORCENTAGEM SEM %
        if(watchPoint >= 99) {
            $('#terca .block-1').show();
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
$("#terca #video_2").bind("timeupdate", function(){
    var currentTime = this.currentTime;
    var duration = this.duration;
    var watchPoint = Math.floor((currentTime/duration) * 100);
    console.log('Tempo atual: ', currentTime);
    console.log('Duração: ', currentTime);
    console.log('Watch point: ', watchPoint);
    
        // PORCENTAGEM SEM %
        if(watchPoint >= 99) {
            $('#terca .block-2').show();
            // $('.video-1 .videos #aviso').hide();
            // $('.perguntas-page .content .questions').addClass('presencial-nao');
        }
        else {
            // $('.video-1 .videos #aviso').show();
            // $('.perguntas-page .content .questions').removeClass('presencial-nao');
        }
});

// ################## VALIDAÇÃO ANTES DE ENVIAR
// $("#form_terca_1").submit(function(){
//     var bloco_perguntas = $(this).closest('#terca .block-1');
//     var isValid;

//   // VALIDA SE NOTA FOI SELECIONADA
//   if(!bloco_perguntas.find('input#terca_radio_1').is(':checked')) {
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

// $("#form_terca_2").submit(function(){
//     var bloco_perguntas = $(this).closest('#terca .block-2');
//     var isValid;

//   // VALIDA SE NOTA FOI SELECIONADA
//   if(!bloco_perguntas.find('input#terca_radio_2').is(':checked')) {
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