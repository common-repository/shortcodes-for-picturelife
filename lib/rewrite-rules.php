<?php
add_filter('rewrite_rules_array','pl_insert_rewrite_rules');
add_filter('query_vars','pl_insert_query_vars');
add_action('wp_loaded','pl_flush_rules');

function pl_flush_rules(){
    $rules = get_option( 'rewrite_rules' );
    
    if ( ! isset( $rules['pictures/([^\/]*)'] ) ) {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
    
    if ( ! isset( $rules['pictures'] ) ) {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
}

function pl_insert_rewrite_rules($rules)
{
    $newrules = array();
    $newrules['pictures/([^\/]*)'] 				= 'index.php?page_id=1353&album_id=$matches[1]';
    $newrules['pictures'] 						= 'index.php?page_id=1353&album_id=';
    return $newrules + $rules;
}

function pl_insert_query_vars( $vars )
{
    array_push($vars, 'album_id');
    return $vars;
}
?>