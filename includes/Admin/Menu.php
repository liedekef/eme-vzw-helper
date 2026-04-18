<?php
namespace EMEVzw\Admin;

defined( 'ABSPATH' ) || exit;

class Menu {
    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register' ] );
    }

    public static function register(): void {
        add_menu_page(
            __( 'EME VZW Helper', 'emevzwhelper' ),
            __( 'EME VZW Helper', 'emevzwhelper' ),
            'manage_options',
            'emevzwhelper',
            [ __CLASS__, 'page_dashboard' ],
            'dashicons-groups',
            57
        );

        foreach ( [
            [ 'emevzwhelper',              __( 'Dashboard', 'eme-vzw-helper' ),             'page_dashboard'   ],
            [ 'emevzwhelper-checklist',    __( 'Compliance Checklist', 'eme-vzw-helper' ),  'page_checklist'   ],
            [ 'emevzwhelper-publications', __( 'Publicaties', 'eme-vzw-helper' ),           'page_publications'],
            [ 'emevzwhelper-board',        __( 'Bestuur', 'eme-vzw-helper' ),               'page_board'       ],
            [ 'emevzwhelper-patrimony',    __( 'Staat van het Vermogen', 'eme-vzw-helper' ),'page_patrimony'   ],
            [ 'emevzwhelper-config',       __( 'Instellingen VZW', 'eme-vzw-helper' ),      'page_config'      ],
        ] as [ $slug, $title, $method ] ) {
            add_submenu_page( 'emevzwhelper', $title, $title, 'manage_options', $slug, [ __CLASS__, $method ] );
        }
    }

    private static function tpl( string $name, array $vars = [] ): void {
        extract( $vars, EXTR_SKIP ); // phpcs:ignore
        $f = EME_VZW_DIR . 'templates/' . $name . '.php';
        file_exists( $f ) ? include $f : print "<div class='wrap'><p>Template {$name} niet gevonden.</p></div>";
    }

    public static function page_dashboard():   void { self::tpl( 'dashboard' ); }
    public static function page_checklist():   void { self::tpl( 'checklist' ); }
    public static function page_publications():void { self::tpl( 'publications' ); }
    public static function page_board():       void { self::tpl( 'board' ); }
    public static function page_patrimony():   void { self::tpl( 'patrimony' ); }
    public static function page_config():      void { self::tpl( 'config' ); }
}
