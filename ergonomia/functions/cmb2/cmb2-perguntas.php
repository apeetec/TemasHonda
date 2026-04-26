<?php

// Box dos grupos de alternativas
add_action( 'cmb2_admin_init', 'post_type_grupo_perguntas' );
    function post_type_grupo_perguntas() {
    // CBM2 padrão documentos
        $cmb = new_cmb2_box( array(
            'id'            => 'perguntas_infos',
            'title'         => 'Alternativas',
            'object_types'  => array( 'perguntas' ), // Post type
            // 'show_on_cb' => 'yourprefix_show_if_front_page', // function should return a bool value
            // 'context'    => 'normal',
            // 'priority'   => 'high',
            // 'show_names' => true, // Show field names on the left
            // 'cmb_styles' => false, // false to disable the CMB stylesheet
            'closed'     => false, // true to keep the metabox closed by default
            // 'classes'    => 'extra-class', // Extra cmb2-wrap classes
            // 'classes_cb' => 'yourprefix_add_some_classes', // Add classes through a callback.
        ) );
        $group_field_id = $cmb->add_field( array(
            'id'          => 'grupo_de_respostas',
            'type'        => 'group',
            'description' => __( 'Crie uma alternativa', 'cmb2' ),
            // 'repeatable'  => false, // use false if you want non-repeatable group
            'options'     => array(
                'group_title'       => __( 'Alternativa {#}', 'cmb2' ), // since version 1.1.4, {#} gets replaced by row number
                'add_button'        => __( 'Adicionar alternativa', 'cmb2' ),
                'remove_button'     => __( 'Remover alternativa', 'cmb2' ),
                'sortable'          => true,
                // 'closed'         => true, // true to have the groups closed by default
                // 'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'cmb2' ), // Performs confirmation before removing group.
            ),
        ) );
        $cmb->add_group_field( $group_field_id, array(
            'name' => 'Alternativa',
            'id'   => 'alternativa',
            'type' => 'text',
            'repeatable' => false, // Repeatable fields are supported w/in repeatable groups (for most types)
        ) );
        $cmb->add_group_field( $group_field_id, array(
            'name' => 'Alternativa correta',
            'desc' => 'Marque aqui caso essa seja a altenativa correta',
            'id'   => 'alternativa_correta',
            'type' => 'checkbox',
        ) );


    }

?>