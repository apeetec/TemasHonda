<?php
// Ajuste o caminho para carregar o WordPress
$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);
require_once($path.'wp-load.php');

function processTerm($term, $taxonomy) {
    $termObj = get_term_by('name', $term, $taxonomy);
    if ($termObj) {
        return $termObj->slug;
    } else {
        $insertTerm = wp_insert_term($term, $taxonomy);
        if (!is_wp_error($insertTerm)) {
            return get_term_by('id', $insertTerm['term_id'], $taxonomy)->slug;
        }
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_FILES['csvFile']['error'] == 0) {
        $file = $_FILES['csvFile']['tmp_name'];
        $csvData = array_map(function($row) {
            return str_getcsv(mb_convert_encoding($row, 'UTF-8', 'ISO-8859-1'), ";");
        }, file($file));
    } else {
        echo 'Erro no upload do arquivo.';
        return;
    }

    $usuariosCadastrados = [];
    $usuariosNaoCadastrados = [];
    $usuariosJaExistentes = [];

    $metaboxes = $csvData[0];
    $batchSize = 50; // Tamanho do lote

    foreach (array_chunk(array_slice($csvData, 1), $batchSize) as $batch) {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        foreach ($batch as $userData) {
            $entry = array_combine($metaboxes, $userData);

            // Validação de dados
            if (empty($entry['user_login']) || empty($entry['user_email']) || empty($entry['user_pass'])) {
                $usuariosNaoCadastrados[] = $entry['user_login'];
                continue;
            }

            $email = $entry['user_email'];
            $user = get_user_by('email', $email);

            if ($user) {
                $user_id_exist = $user->ID;
                $entry['user_infos_empresas'] = processTerm($entry['user_infos_empresas'], 'unidades');

                foreach ($entry as $entradas => $keys) {
                    update_metadata('user', $user_id_exist, $entradas, $keys);
                }

                wp_set_password($entry['user_pass'], $user_id_exist);

                $usuariosJaExistentes[] = $entry['user_login'];
            } else {
                $entry['user_infos_empresas'] = processTerm($entry['user_infos_empresas'], 'unidades');
                $campos = $entry;
                $user_id = wp_insert_user($campos);

                if (!is_wp_error($user_id)) {
                    foreach ($entry as $entradas => $keys) {
                        update_metadata('user', $user_id, $entradas, $keys);
                    }

                    wp_set_password($entry['user_pass'], $user_id);

                    $usuariosCadastrados[] = $entry['user_login'];
                } else {
                    $usuariosNaoCadastrados[] = $entry['user_login'];
                }
            }
        }

        $wpdb->query('COMMIT');
    }

    echo '<div class="grid-div"><p>Usuários que já existiam cadastrados:</p> <p>' . implode('</p><p>', $usuariosJaExistentes) . '</p></div>';
    echo '<div class="grid-div"><p>Usuários cadastrados:</p> <p>' . implode('</p><p>', $usuariosCadastrados) . '</p></div>';
    echo '<div class="grid-div"><p>Usuários não cadastrados:</p> <p>' . implode('</p><p>', $usuariosNaoCadastrados) . '</p></div>';
}
?>
