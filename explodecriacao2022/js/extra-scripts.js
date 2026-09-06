// Fecha menu no celular ao clicar em algum link
$('.navbar-nav li a').on('click', function(){
  if(!$( this ).hasClass('dropdown-toggle')){
      $('.navbar-collapse').collapse('hide');
  }
});

// Adiciona efeito scroll ao clicar nos links ancoras que nao possuem URL
// Adicionar classe 'notscrollable' para desativar essa funcao para um link especifico
$('a[href^="#"]').not('.notscrollable').click(function(){
    $('html, body').animate({
        scrollTop: $( $(this).attr('href') ).offset().top - 55
    }, 1000);
    return false;
});

// Adicione a classe notscrollable para evitar que o respectivo link role para o topo do site ao ser clicado
$('.notscrollable').click(function(event) {
    event.preventDefault();
});

// Dá scroll até a div se a URL tiver HASH
$(document).ready(function() {
    $('html, body').hide();

    if (window.location.hash) {
        setTimeout(function() {
            $('html, body').scrollTop(0).show();
            $('html, body').animate({
                scrollTop: $(window.location.hash).offset().top - 55
                }, 1000)
        }, 0);
    }
    else {
        $('html, body').show();
    }
});

// Máscara para telefone
var SPMaskBehavior = function (val) {
  return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
},
spOptions = {
  onKeyPress: function(val, e, field, options) {
      field.mask(SPMaskBehavior.apply({}, arguments), options);
    },clearIfNotMatch: true
};
// Adiciona a máscara do telefone para campos com a classe 'telefone'
$(document).ready(function(){
  $('input.telefone').mask(SPMaskBehavior, spOptions);
});
