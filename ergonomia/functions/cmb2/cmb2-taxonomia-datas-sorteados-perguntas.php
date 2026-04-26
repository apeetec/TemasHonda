<?php
/**
 * Hook in and add a metabox to add fields to taxonomy terms
 */
function yourprefix_register_taxonomy_metabox_sorteados() {
	$prefix = 'campo_sorteados_';

	/**
	 * Metabox to add fields to categories and tags
	 */
	$cmb_term_sorteados = new_cmb2_box( array(
		'id'               => $prefix . 'edit',
		'title'            => esc_html__( 'Category Metabox', 'cmb2' ), // Doesn't output for term boxes
		'object_types'     => array( 'term' ), // Tells CMB2 to use term_meta vs post_meta
		'taxonomies'       => array( 'datas_sorteados', 'post_tag' ), // Tells CMB2 which taxonomies should have these fields
		// 'new_term_section' => true, // Will display in the "Add New Category" section
	) );
    
     $cmb_term_sorteados->add_field( array(
	'name' => '',
	'desc' => 'teste',
	'default' => '',
	'id' => 'codigo_sorteado',
	'type' => 'textarea_code'
) );

}
add_action( 'cmb2_admin_init', 'yourprefix_register_taxonomy_metabox_sorteados' );
?>