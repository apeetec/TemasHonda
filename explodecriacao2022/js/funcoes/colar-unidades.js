$(document).ready(function() {
    $('#campos_categorias').on('paste', function(event) {
      var textoColado = (event.originalEvent || event).clipboardData.getData('text');
      var rows = textoColado.split("\n").slice(0, 200); // Limitar a 200 linhas
      var table = $('<div/>');
      rows.forEach(function(row) {
        var cells = row.split("\t");
        var rowElement = $('<div />');
        cells.forEach(function(cell) {
          var input = $('<input type="text" class="' + cell + '" value="' + cell + '" name="cat[]">');
          var span = $('<span class="excluir">Excluir</span>');

          input.on('keydown', function(e) {
            if (e.which === 8 && $(this).val() === '') {
              $(this).parent().remove();
            }
          });
          span.on('click', function() {
            $(this).parent().find('input, span').remove();
          });
          var divContainer = $('<div class="' + cell + '">');
          divContainer.append(input, span);
          rowElement.append(divContainer);
        });
        table.append(rowElement);
      });
      $('#exibir_categorias').html(table);
      console.log(rows.length + ' linhas adicionadas');
    });
    $('#exibir_categorias').on('click', '.excluir', function() {
      $(this).parent().find('input, span').remove();
    });
  });