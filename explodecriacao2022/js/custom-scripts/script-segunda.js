// ################## Adiciona classes na confirmação se já assistiu ou não (Video #1)
$('#segunda input:radio[name="presencial"]').change(
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
            $('#segunda .video-box').show();
            $('.perguntas-page .content .questions').addClass('check-nao'); 
        }
        else {
        	// $('.video-box').hide();
        	$('.perguntas-page .content .questions').removeClass('check-nao'); 
        }
        // Atualiza o campo oculto de cada form
        $('input[name="presencial_video"]').val($(this).val());
});

$('#segunda .codigo input').keyup(function () {
    // if($(this).val() == 'SP6T1' || $(this).val() == 'sp6t1') {
    //     $('#confirmar_presencial').val('Presencial');
    //     $('.block-questions.block-1').addClass('presencial-sim');
    //     $('.block-1').show();
    //     $('.block-1').addClass('show');
    //     $('#segunda #video_1').hide();
    //     $('#segunda .nao').hide();
    //     // alert("Você liberou as perguntas do primeiro video");
    // }
    // else if($(this).val() == 'TESTE' || $(this).val() == 'teste'){
    //     $('.block-2').addClass('show');
    //     $('.block-2').show();
    //     $('#segunda .nao').hide();
    //     // alert("Você liberou as perguntas do segundo video");
    // }
    if($(this).val() == 'SP1T3' || $(this).val() == 'sp1t3'){
        $('#segunda .block-2').addClass('show');
        $('#segunda .block-2').show();
        $('#segunda .nao').hide();
        $("#segunda #video_2").hide();
        $('#confirmar_presencial_segundo_video').attr('value', 'Presencial');
        // alert("Você liberou as perguntas do segundo video");
    }
    else {
        // $('.video-2 .all-questions').hide();
        $('.block-questions').removeClass('presencial-sim');
        console.log("Não deu certo");
    }
});
// ################## PLAY VIDEO #1 AO CLICAR NO ICONE
    $("#play_video_segunda_1").on('click',function(e){
        e.preventDefault();
        // Remove status atual do vídeo...
        $("#segunda #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
        // Adiciona status de play
        // $(".buttons-play-and-pause").addClass('pause');
        // Play video
        $('#segunda #video_1').get(0).play();
    });
// ################## PAUSE VIDEO #1 AO CLICAR NO ICONE
    $("#pause_video_segunda_1").on('click',function(e){
        e.preventDefault();
        // Remove status atual do vídeo...
        $("#segunda #video_1").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
        // Adiciona status de play
        // $(".buttons-play-and-pause").addClass('play');
        // Play video
        $('#segunda #video_1').get(0).pause();
    });
// ################## PLAY VIDEO #2 AO CLICAR NO ICONE
    $("#play_video_segunda_2").on('click',function(e){
        e.preventDefault();
        // Remove status atual do vídeo...
        $("#segunda #video_2").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
        // Adiciona status de play
        // $(".buttons-play-and-pause").addClass('pause');
        // Play video
        $('#segunda #video_2').get(0).play();
    });
// ################## PAUSE VIDEO #2 AO CLICAR NO ICONE
    $("#pause_video_segunda_2").on('click',function(e){
        e.preventDefault();
        // Remove status atual do vídeo...
        $("#segunda #video_2").removeClass(function (index, css) { return (css.match (/\bvideoStatus\S+/g) || []).join(' '); });
        // Adiciona status de play
        // $(".buttons-play-and-pause").addClass('play');
        // Play video
        $('#segunda #video_2').get(0).pause();
    });
// ################## SEGUNDA VIDEO #1 (PORCENTAGEM) ##################
// Script para vídeo, exibir conteúdo depois de segundos.
$("#segunda #video_1").bind("timeupdate", function(){
    var currentTime = this.currentTime;
    var duration = this.duration;
    var watchPoint = Math.floor((currentTime/duration) * 100);
    console.log('Tempo atual: ', currentTime);
    console.log('Duração: ', currentTime);
    console.log('Watch point: ', watchPoint);
    
        // PORCENTAGEM SEM %
        if(watchPoint >= 99) {
            $('#segunda .block-1').show();
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
$("#segunda #video_2").bind("timeupdate", function(){
    var currentTime = this.currentTime;
    var duration = this.duration;
    var watchPoint = Math.floor((currentTime/duration) * 100);
    console.log('Tempo atual: ', currentTime);
    console.log('Duração: ', currentTime);
    console.log('Watch point: ', watchPoint);
    
        // PORCENTAGEM SEM %
        if(watchPoint >= 99) {
            $('#segunda .block-2').show();
            
            // $('.video-1 .videos #aviso').hide();
            // $('.perguntas-page .content .questions').addClass('presencial-nao');
        }
        else {
            // $('.video-1 .videos #aviso').show();
            // $('.perguntas-page .content .questions').removeClass('presencial-nao');
        }
});




// ################## VALIDAÇÃO ANTES DE ENVIAR
// $("#form_segunda").submit(function(){
//     var bloco_perguntas = $(this).closest('#segunda .block-2');
//     var isValid;

//   // VALIDA SE NOTA FOI SELECIONADA
//   if(!bloco_perguntas.find('input#segunda-radio').is(':checked')) {
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
