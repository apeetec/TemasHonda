<?php

add_action( 'cmb2_admin_init', 'yourprefix_register_taxonomy_metabox' );
/**
 * Hook in and add a metabox to add fields to taxonomy terms
 */
function yourprefix_register_taxonomy_metabox() {
	$prefix = 'campo_';

	/**
	 * Metabox to add fields to categories and tags
	 */
	$cmb_term = new_cmb2_box( array(
		'id'               => $prefix . 'edit',
		'title'            => esc_html__( 'Category Metabox', 'cmb2' ), // Doesn't output for term boxes
		'object_types'     => array( 'term' ), // Tells CMB2 to use term_meta vs post_meta
		'taxonomies'       => array( 'datas_perguntas', 'post_tag' ), // Tells CMB2 which taxonomies should have these fields
		// 'new_term_section' => true, // Will display in the "Add New Category" section
	) );
    $cmb_term->add_field( array(
        'name'    => 'Atração',
        'desc'    => '',
        'default' => '',
        'id'      => 'atracao',
        'type'    => 'text_medium'
    ) );
    $cmb_term->add_field( array(
        'name'    => 'Tema',
        'desc'    => '',
        'default' => '',
        'id'      => 'tema',
        'type'    => 'text_medium'
    ) );
    $cmb_term->add_field( array(
        'name'    => 'Código presencial',
        'desc'    => '',
        'default' => '',
        'id'      => 'codigo',
        'type'    => 'text_medium'
    ) );
    $cmb_term->add_field( array(
        'name' => 'Time zone',
        'id'   => 'wiki_test_timezone',
        'type' => 'select_timezone',
    ) );
    $cmb_term->add_field( array(
        'name' => 'Insira o horário que você deseja que inicie a pesquisa',
        'id'   => 'horario_inicio',
        'type' => 'text_datetime_timestamp',
        // 'timezone_meta_key' => 'wiki_test_timezone',
        // 'date_format' => 'Y-m-d', // Formato da data para o admin
        // 'time_format' => 'H:i',   // Formato da hora para o admin
    ) );
    $cmb_term->add_field( array(
        'name' => 'Insira o horário que você deseja que finalize a pesquisa',
        'id'   => 'horario_fim',
        'type' => 'text_datetime_timestamp',
    ) );
    $cmb_term->add_field( array(
        'name'    => 'Video',
        'desc'    => '',
        'id'      => 'video_categoria',
        'type'    => 'file',
        // Optional:
        'options' => array(
            'url' => false, // Hide the text input for the url
        ),
        'text'    => array(
            'add_upload_file_text' => 'Adicionar video' // Change upload button text. Default: "Add or Upload File"
        ),

    ) );
    $cmb_term->add_field( array(
        'name' => 'Iframe do video',
        'desc' => 'Cole aqui o iframe do video',
        'default' => '',
        'id' => 'iframe_video_termo',
        'type' => 'textarea_code'
    ) );

}


?>