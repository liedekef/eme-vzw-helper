<?php
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

class Assets {
    public static function init(): void {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
    }

    public static function enqueue( string $hook ): void {
        if ( strpos( $hook, 'eme-vzw' ) === false ) return;
        wp_enqueue_style( 'eme-vzw', EME_VZW_URL . 'assets/css/admin.css', [], EME_VZW_VERSION );
        wp_enqueue_script( 'eme-vzw', EME_VZW_URL . 'assets/js/admin.js', [ 'jquery' ], EME_VZW_VERSION, true );
        wp_localize_script( 'eme-vzw', 'emeVzw', [
            'nonce'   => wp_create_nonce( 'eme_vzw_nonce' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'i18n'    => [
                'confirm_delete'     => __( 'Bestuurder deactiveren? Je kan dit ongedaan maken via "Heractiveer".', 'eme-vzw-helper' ),
                'confirm_reactivate' => __( 'Bestuurder terug activeren?', 'eme-vzw-helper' ),
                'confirm_purge'      => __( 'Bestuurder DEFINITIEF verwijderen? Dit kan niet ongedaan worden gemaakt.', 'eme-vzw-helper' ),
                'saving'         => __( 'Opslaan…', 'eme-vzw-helper' ),
                'saved'          => __( 'Opgeslagen', 'eme-vzw-helper' ),
                'print_form'     => __( 'Afdrukken als PDF', 'eme-vzw-helper' ),
            ],
        ] );
    }
}
