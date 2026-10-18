<?php
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

/**
 * EME VZW PDF Generator
 * Genereert officiële Belgische formulieren met dompdf (meegeleverd met Events Made Easy)
 * + exacte PNG-achtergronden. Tekst wordt op vaste mm-coördinaten (CSS position:absolute)
 * bovenop de achtergrond geplaatst; de paginering van Luik B gebeurt hier, niet door dompdf.
 *
 * Wijzigingen:
 * - Dynamische paginering voor Luik B (bestuurs- + statutenwijzigingen)
 * - add_luik_b_continuation() wordt automatisch herhaald tot alle tekst past
 * - Statuutwijzigingen worden nu correct verwerkt als groot tekstblok
 */
class PdfGenerator {

    /** Globale kalibratie (mm): verschuift alle tekst t.o.v. de achtergrond. */
    private const X_OFFSET_MM = 0.0;
    private const Y_OFFSET_MM = 0.0;
    private const FONT        = 'Helvetica';

    private \Dompdf\Dompdf $dompdf;
    private string $bg_dir;
    private bool   $debug;
    private int    $current_page = 0;
    /** @var string[] HTML per pagina */
    private array  $pages = [];

    public function __construct( bool $debug = false ) {
        $this->bg_dir = trailingslashit( EME_VZW_DIR ) . 'assets/templates/';
        $this->debug  = $debug;

        $this->load_dompdf();
        $this->init_pdf_engine();
    }

    /* ======================================================================
     *  INIT & LOADERS
     * ====================================================================== */

    private function load_dompdf(): void {
        if ( class_exists( '\Dompdf\Dompdf' ) ) return;

        $autoload = WP_PLUGIN_DIR . '/events-made-easy/includes/dompdf/vendor/autoload.php';
        if ( ! file_exists( $autoload ) ) {
            throw new \RuntimeException( 'dompdf niet gevonden (verwacht via Events Made Easy): ' . $autoload );
        }
        require_once $autoload;
    }

    private function init_pdf_engine(): void {
        $options = new \Dompdf\Options();
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'isRemoteEnabled', false );
        $options->set( 'defaultFont', 'Helvetica' );
        // De achtergrond-PNG's staan in deze plugin, buiten dompdf's standaard chroot.
        $options->set( 'chroot', untrailingslashit( EME_VZW_DIR ) );

        $this->dompdf = new \Dompdf\Dompdf( $options );
        $this->dompdf->setPaper( 'A4', 'portrait' );
    }

    /* ======================================================================
     *  PAGINA MANAGEMENT (FORMULIER I & II)
     * ====================================================================== */

    /**
     * Core: voeg een nieuwe A4-pagina toe met bijhorende achtergrond-PNG
     * Voegt automatisch kalibratierooster toe indien $this->debug = true
     */
    private function add_template_page( string $prefix, int $page_num ): self {
        $this->pages[]      = '';
        $this->current_page = $page_num;

        $bg_file = sprintf( '%s%s_bg-%d.png', $this->bg_dir, $prefix, $page_num );
        $this->render_background( $bg_file );

        if ( $this->debug ) {
            $this->add_calibration_grid( 10 );
        }
        return $this;
    }

    private function render_background( string $file ): void {
        if ( file_exists( $file ) ) {
            $this->put( '<img class="bg" src="' . esc_attr( 'file://' . $file ) . '">' );
        } elseif ( $this->debug ) {
            $this->put_text( 10, 10, 190, 5, 'Achtergrond niet gevonden: ' . basename( $file ), 9, 'left', false, '#c80000' );
        }
    }

    // Formulier I
    public function add_luik_a(): self                { return $this->add_template_page( 'formulier1', 1 ); }
    public function add_luik_b(): self                { return $this->add_template_page( 'formulier1', 2 ); }
    public function add_luik_b_continuation(): self   { return $this->add_template_page( 'formulier1', 3 ); }

    // Formulier II
    public function add_luik_a_f2(): self             { return $this->add_template_page( 'formulier2', 1 ); }
    public function add_luik_c_f2(): self             { return $this->add_template_page( 'formulier2', 2 ); }
    public function add_luik_c_f2_cont(): self        { return $this->add_template_page( 'formulier2', 3 ); }

    /* ======================================================================
     *  LAAG-NIVEAU HELPERS
     * ====================================================================== */

    /** Voeg HTML toe aan de huidige pagina. */
    private function put( string $html ): void {
        $this->pages[ array_key_last( $this->pages ) ] .= $html;
    }

    /** mm → compacte CSS-waarde */
    private function mm( float $v ): string {
        return rtrim( rtrim( number_format( $v, 3, '.', '' ), '0' ), '.' ) . 'mm';
    }

    /** Tekstbreedte in mm (dompdf-fontmetrics, dezelfde als bij het renderen). */
    private function text_width_mm( string $text, float $font_size ): float {
        $fm = $this->dompdf->getFontMetrics();
        return $fm->getTextWidth( $text, $fm->getFont( self::FONT ), $font_size ) * 25.4 / 72;
    }

    /** Eén tekstvak op vaste positie. Hoogte = lijnhoogte, dus verticaal gecentreerd. */
    private function put_text( float $x, float $y, float $w, float $h, string $text, float $font_size, string $align = 'left', bool $wrap = false, string $color = '' ): void {
        $style = sprintf(
            'left:%s;top:%s;width:%s;height:%s;line-height:%s;font-size:%spt;text-align:%s;%s%s',
            $this->mm( $x + self::X_OFFSET_MM ), $this->mm( $y + self::Y_OFFSET_MM ),
            $this->mm( $w ), $this->mm( $h ), $this->mm( $h ),
            $font_size, $align,
            $wrap ? '' : 'white-space:nowrap;',
            $color ? 'color:' . $color . ';' : ''
        );
        $content = $wrap ? nl2br( esc_html( $text ) ) : esc_html( $text );
        $this->put( '<div class="f" style="' . esc_attr( $style ) . '">' . $content . '</div>' );
    }

    /**
     * Breek één regel af op de breedte (in mm). Enkel op gewone spaties:
     * een NBSP blijft bij zijn woord (zie handtekeningregel).
     */
    private function wrap_line( string $line, float $width, float $font_size ): array {
        if ( $line === '' ) return [ '' ];

        $out = [];
        $cur = '';
        foreach ( explode( ' ', $line ) as $word ) {
            $try = $cur === '' ? $word : $cur . ' ' . $word;
            if ( $cur !== '' && $this->text_width_mm( $try, $font_size ) > $width ) {
                $out[] = $cur;
                $cur   = $word;
            } else {
                $cur = $try;
            }
        }
        $out[] = $cur;
        return $out;
    }

    /* ======================================================================
     *  VELD & TEKST HELPERS (MET PAGINERING)
     * ====================================================================== */

    /**
     * Vul een tekstblok met automatische paginering.
     * Splitst tekst per regel, breekt af op de breedte, print, en voegt Luik B vervolg toe indien nodig.
     * 
     * @param string $text            Tekst om te printen (\n gescheiden)
     * @param float  $x               X-coördinaat (mm)
     * @param float  $y_start         Start Y op eerste pagina (Luik B start)
     * @param float  $y_limit         Maximale Y vóór page break (bijv. 270mm)
     * @param float  $y_reset         Start Y op vervolgpagina's (bijv. 25mm)
     * @param float  $width           Breedte van het tekstvak
     * @param float  $font_size       Lettergrootte
     */
    public function fill_text_auto_paginate( string $text, float $x, float $y_start, float $y_limit, float $y_reset, float $width = 180, float $font_size = 8 ): self {
        $lines = explode( "\n", $text );
        // Geschatte hoogte per regel in mm (font_size * 0.4 + marge)
        $line_height_mm = ( $font_size * 0.4 ) + 1.2; 
        $current_y = $y_start;

        foreach ( $lines as $line ) {
            // Elke afgebroken deelregel wordt apart op de limiet gecontroleerd,
            // zodat ook een lange alinea netjes over pagina's heen loopt.
            foreach ( $this->wrap_line( trim( $line ), $width, $font_size ) as $part ) {
                if ( $current_y + $line_height_mm > $y_limit ) {
                    $this->add_luik_b_continuation();
                    $current_y = $y_reset;
                }
                if ( $part !== '' ) {
                    $this->put_text( $x, $current_y, $width, $line_height_mm, $part, $font_size );
                }
                $current_y += $line_height_mm;
            }
        }
        return $this;
    }

    public function fill_field( float $x, float $y, string $text, float $width = 50, float $font_size = 10, string $align = 'L', bool $multiline = false ): self {
        $css_align = [ 'L' => 'left', 'C' => 'center', 'R' => 'right', 'J' => 'justify' ][ $align ] ?? 'left';
        $this->put_text( $x, $y, $width, $multiline ? 4 : 5, $text, $font_size, $css_align, $multiline );
        return $this;
    }

    public function fill_checkbox( float $x, float $y, bool $checked = true, float $size = 4 ): self {
        if ( $checked ) {
            // Kruisje van 0,3 mm, 0,5 mm van de rand (zoals voorheen met Line())
            $a   = 0.5;
            $b   = $size - 0.5;
            $svg = sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" width="%1$s" height="%1$s" viewBox="0 0 %1$s %1$s"><path d="M%2$s %2$s L%3$s %3$s M%2$s %3$s L%3$s %2$s" stroke="#000" stroke-width="0.3" fill="none"/></svg>',
                $size, $a, $b
            );
            $this->put( sprintf(
                '<img class="f" style="left:%s;top:%s;width:%s;height:%s" src="data:image/svg+xml;base64,%s">',
                $this->mm( $x + self::X_OFFSET_MM ), $this->mm( $y + self::Y_OFFSET_MM ),
                $this->mm( $size ), $this->mm( $size ),
                base64_encode( $svg )
            ) );
        }
        return $this;
    }

    public function add_calibration_grid( int $step_mm = 10 ): self {
        $h = '';
        for ( $x = $step_mm; $x < 210; $x += $step_mm ) {
            $h .= sprintf( '<div class="g" style="left:%dmm;top:0;height:297mm;border-left:0.1mm solid #f00"></div>', $x );
            $h .= sprintf( '<div class="gl" style="left:%smm;top:1mm">%d</div>', $x + 0.5, $x );
        }
        for ( $y = $step_mm; $y < 297; $y += $step_mm ) {
            $h .= sprintf( '<div class="g" style="top:%dmm;left:0;width:210mm;border-top:0.1mm solid #f00"></div>', $y );
            $h .= sprintf( '<div class="gl" style="left:1mm;top:%smm">%d</div>', $y + 0.5, $y );
        }
        $this->put( $h );
        return $this;
    }

    /* ======================================================================
     *  OUTPUT
     * ====================================================================== */

    /**
     * @param string $dest 'I' = inline, 'D' = download, 'S' = PDF als string
     */
    public function output( string $filename, string $dest = 'I' ): string|bool {
        $css = '@page{size:A4 portrait;margin:0}'
             . 'html,body{margin:0;padding:0}'
             . 'body{font-family:\'' . self::FONT . '\',sans-serif;color:#000}'
             . '.page{position:relative;width:210mm;height:296mm}'
             . '.bg{position:absolute;left:0;top:0;width:210mm;height:297mm}'
             . '.f{position:absolute;overflow:visible}'
             . '.g{position:absolute}'
             . '.gl{position:absolute;font-size:6pt;color:#f00}';

        $body = '';
        foreach ( $this->pages as $i => $page ) {
            $body .= '<div class="page"' . ( $i ? ' style="page-break-before:always"' : '' ) . '>' . $page . '</div>';
        }

        $this->dompdf->loadHtml( '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>' . $body . '</body></html>' );
        $this->dompdf->addInfo( 'Creator', 'EME VZW Helper v' . EME_VZW_VERSION );
        $this->dompdf->render();

        if ( $dest === 'S' ) {
            return $this->dompdf->output();
        }
        $this->dompdf->stream( sanitize_file_name( $filename ), [ 'Attachment' => $dest === 'D' ] );
        return true;
    }

    /* ======================================================================
     *  STATIC GENERATORS
     * ====================================================================== */

    /**
     * Bouw de pagina's van Formulier I in de huidige PDF-instantie (geen output).
     * Kan meerdere keren worden aangeroepen om meerdere exemplaren te genereren.
     */
    private function build_form1_pages( object $pub, object $config, array $changes ): void {

        // ── PAGINA 1: LUIK A ──
        $this->add_luik_a();
        $this->fill_field( 100, 77, $config->vzw_number ?? '', 40 );
        $this->fill_field( 80, 83, $config->vzw_name ?? '', 80 );
        $this->fill_field( 90, 99, $config->vzw_legal_form, 60 );
        $this->fill_field( 80, 108.5, $config->street ?? '', 60 );
        $this->fill_field( 150, 108.5, $config->house_number ?? '', 60 );
        $this->fill_field( 175, 108.5, $config->house_bus ?? '', 60 );
        $this->fill_field( 85, 114, $config->postal_code ?? '', 30 );
        $this->fill_field( 135, 114, $config->city ?? '', 60 );
        $this->fill_field( 80, 120, $config->country ?? '', 60 );
        $this->fill_checkbox( 60, 187);
        $this->fill_field( 90, 233, $config->email ?? '', 80 );

        // ── PAGINA 2: LUIK B (START) ──
        $this->add_luik_b();
        
        // Identificatie header Luik B
        $this->fill_field( 80, 64, $config->vzw_number ?? '', 40 );
        $this->fill_field( 80, 72, $config->vzw_name ?? '', 80 );
        $this->fill_field( 80, 76, $config->vzw_shortname ?? '', 80 );
        $this->fill_field( 80, 82, $config->vzw_legal_form, 60 );
        $address = ($config->street ?? '') . ' ' . ($config->house_number ?? '') . ' bus ' . ($config->house_bus ?? '')
                 . ', '. ($config->postal_code ?? '') . ' ' . ($config->city ?? '');
        $this->fill_field( 80, 87, $address , 80 );

        // Onderwerp akte
        $subjects = [];
        if ( array_filter( $changes, fn($c) => $c->change_type === 'B' ) ) $subjects[] = 'BENOEMING(EN)';
        if ( array_filter( $changes, fn($c) => $c->change_type === 'E' ) ) $subjects[] = 'ONTSLAG(NEMINGEN)';
        if ( ! empty( $pub->statute_text ) ) $subjects[] = 'WIJZIGING(EN) STATUTEN';
        $this->fill_field( 70, 97, implode( ' *** ', $subjects ), 125 );

        // ── TEKSTGEBIED LUIK B (MET AUTO-PAGINERING) ──
        // Verzamel alle tekstregels: eerst bestuurswijzigingen, dan statuten
        $all_text_lines = [];

        // 1. Introductietekst met VZW-gegevens en AV-info
        $av_date_formatted = ! empty( $pub->av_date ) ? date_i18n( 'd/m/Y', strtotime( $pub->av_date ) ) : '';
        $av_location = ! empty( $pub->av_location ) ? ' te ' . $pub->av_location : '';
        $legal_form = strtolower( $config->vzw_legal_short_form );

        $all_text_lines[] = sprintf(
            'De Algemene Vergadering van %s %s, bijeengekomen op %s%s, beraadslagend en beslissend overeenkomstig haar statuten,',
            $config->vzw_name ?? '',
            $legal_form,
            $av_date_formatted,
            $av_location
        );

        // Helper: format address as "street, number, postal city"
        $format_address = function( object $m ): string {
            $parts = [];

            // Straat + huisnummer (geen komma ertussen)
            $street_part = trim( ( $m->street ?? '' ) . ' ' . ( $m->house_number ?? '' ) );
            if ( $street_part ) {
                $parts[] = $street_part;
            }

            // Bus toevoegen indien aanwezig
            if ( ! empty( $m->house_bus ) ) {
                $parts[] = 'bus ' . $m->house_bus;
            }

            // Postal code + city
            $postal_city = trim( ( $m->postal_code ?? '' ) . ' ' . ( $m->city ?? '' ) );
            if ( $postal_city ) {
                $parts[] = $postal_city;
            }

            // Join: eerste delen met spatie, laatste deel met komma + spatie
            if ( count( $parts ) <= 2 ) {
                return implode( ', ', $parts );
            }

            // 3+ delen: "Straat 5 bus 3, 2800 Mechelen"
            $last = array_pop( $parts );
            return implode( ' ', $parts ) . ', ' . $last;
        };

        // 2. Eerst: ontslagen bestuurders
        $ontslagen = array_filter( $changes, fn($c) => $c->change_type === 'E' );
        if ( $ontslagen ) {
            $all_text_lines[] = 'heeft het ontslag aanvaard van volgende bestuurder' . ( count( $ontslagen ) > 1 ? 's' : '' ) . ':';
            $all_text_lines[] = '';
            foreach ( $ontslagen as $c ) {
                $all_text_lines[] = sprintf(
                    '- %s %s, %s, geboren te %s op %s',
                    $c->last_name,
                    $c->first_name,
                    $format_address( $c ),
                    $c->birth_place ?? '[onbekend]',
                    ! empty( $c->birth_date ) ? date_i18n( 'd/m/Y', strtotime( $c->birth_date ) ) : '[onbekend]'
                );
            }
            $all_text_lines[] = '';
        }

        // 3. Dan: nieuwe benoemingen
        $benoemingen = array_filter( $changes, fn($c) => $c->change_type === 'B' );
        if ( $benoemingen ) {
            if ( $ontslagen ) {
                $all_text_lines[] = 'En heeft volgende personen tot nieuwe bestuurders benoemd:';
            } else {
                $all_text_lines[] = 'heeft volgende personen tot nieuwe bestuurders benoemd:';
            }
            $all_text_lines[] = '';
            foreach ( $benoemingen as $c ) {
                $all_text_lines[] = sprintf(
                    '- %s %s, %s, geboren te %s op %s',
                    $c->last_name,
                    $c->first_name,
                    $format_address( $c ),
                    $c->birth_place ?? '[onbekend]',
                    ! empty( $c->birth_date ) ? date_i18n( 'd/m/Y', strtotime( $c->birth_date ) ) : '[onbekend]'
                );
            }
            $all_text_lines[] = '';
        }

        // Statutenwijzigingen (groot tekstveld)
        if ( ! empty( $pub->statute_text ) ) {
            if (empty($ontslagen) && empty($benoemingen)) {
                $all_text_lines[] = 'heeft beslist om nieuwe statuten goed te keuren die de voorgaande integraal zullen vervangen:';
            } else {
                $all_text_lines[] = 'Daarnaast heeft de Algemene Vergadering ook beslist om nieuwe statuten goed te keuren die de voorgaande integraal zullen vervangen:';
            }
            $all_text_lines[] = '';
            $stat_lines = explode( "\n", $pub->statute_text );
            foreach ( $stat_lines as $sl ) {
                $trimmed = trim( $sl );
                if ( $trimmed !== '' ) {
                    $all_text_lines[] = $trimmed;
                }
            }
        }
        
        // Vertegenwoordigingsbevoegde bestuurders (voorzitter + secretaris)
        //    Dit is VERPLICHT op Luik B, maar ZONDER handtekening (handtekening mag alleen achterkant laatste blad)
        $active_board = Config::get_active_board_members();
        $voorzitter = null;
        $secretaris = null;

        foreach ( $active_board as $m ) {
            $func_lower = strtolower( $m->function_title ?? '' );
            // Zoek naar voorzitter/president op basis van function_title
            if ( preg_match( '/(voorzitter|president|chair)/i', $func_lower ) ) {
                $voorzitter = $m;
            }
            // Zoek naar secretaris op basis van function_title
            if ( preg_match( '/(secretaris|secretary)/i', $func_lower ) ) {
                $secretaris = $m;
            }
        }

        $signatory_parts = [];
        if ( $voorzitter ) {
            $signatory_parts[] = sprintf( '%s %s, %s', $voorzitter->first_name, $voorzitter->last_name, $voorzitter->legal_capacity ?? 'bestuurder' );
        }
        if ( $secretaris ) {
            $signatory_parts[] = sprintf( '%s %s, %s', $secretaris->first_name, $secretaris->last_name, $secretaris->legal_capacity ?? 'bestuurder' );
        }

        // Voeg toe als er minstens 1 gevonden is
        if ( ! empty( $signatory_parts ) ) {
            $all_text_lines[] = ''; // Lege regel voor scheiding
            $all_text_lines[] = ''; // Lege regel voor scheiding
            $all_text_lines[] = ''; // Lege regel voor scheiding
            $all_text_lines[] = ''; // Lege regel voor scheiding
            // Gebruik UTF-8 non-breaking space (0xC2 0xA0) die NIET wordt samengevoegd
            $nb_space = "\xC2\xA0";
            $gap = str_repeat( $nb_space, 25 ); // ~15 NBSP = ruime horizontale spatie
            $all_text_lines[] = implode( $gap, $signatory_parts );
        }

        $full_text = implode( "\n", $all_text_lines );

        // Print met paginering: Start Y=115, Max Y=270, Vervolg start Y=25
        $this->fill_text_auto_paginate( $full_text, 40, 115, 250, 25, 150, 10 );
    }

    /**
     * Statische wrapper: genereert één exemplaar van Formulier I als PDF.
     */
    public static function generate_form1( int $pub_id, string $dest = 'I', bool $debug = false ): string|bool {
        $pub     = Publications::get( $pub_id );
        $config  = Config::get();
        $changes = Publications::get_board_changes( $pub_id );
        if ( ! $pub || ! $config ) throw new \RuntimeException( 'Publicatie of config niet gevonden.' );

        $gen = new self( $debug );
        $gen->build_form1_pages( $pub, $config, $changes );
        return $gen->output( 'Formulier_I.pdf', $dest );
    }

    /**
     * Bouw de pagina's van Formulier II in de huidige PDF-instantie (geen output).
     * Kan meerdere keren worden aangeroepen om meerdere exemplaren te genereren.
     */
    private function build_form2_pages( object $pub, object $config, array $changes ): void {
        $board_changes = array_filter( $changes, fn($c) => in_array( $c->change_type, ['B', 'E'], true ) );

        // ── PAGINA 1: LUIK A ──
        $this->add_luik_a_f2();
        $this->fill_field( 100, 72, $config->vzw_number ?? '', 40 );
        $this->fill_field( 90, 85, $config->vzw_name ?? '', 80 );

        // ── PAGINA 2 & HERVERVAL: LUIK C (Max 5 per blad) ──
        $chunks = array_chunk( $board_changes, 5 );
        foreach ( $chunks as $idx => $chunk ) {
            //$idx === 0 ? $this->add_luik_c_f2() : $this->add_luik_c_f2_cont();
            $this->add_luik_c_f2();

            $y_start = 135.5;
            $y_step  = 6.5;

            foreach ( $chunk as $i => $c ) {
                $y = $y_start + ( $i * $y_step );
                if ($c->change_type === 'B') {
                    $this->fill_checkbox( 13, $y);
                } else {
                    $this->fill_checkbox( 20.5, $y);
                }
                //$type_char = $c->change_type === 'B' ? 'B' : 'E';
                //$this->fill_field( 10, $y, $type_char, 8, 10, 'C' );

                $this->fill_field( 30, $y, $c->national_id ?? '', 25 );
                $this->fill_field( 64, $y, trim( $c->last_name . ' ' . $c->first_name ), 60 );
                $this->fill_field( 142, $y, Config::legal_capacity_label( $v->legal_capacity ?? 'bestuurder' ), 40 );
                $this->fill_field( 175, $y, ! empty( $pub->av_date ) ? date_i18n( 'd/m/Y', strtotime( $pub->av_date ) ) : '', 25, 10, 'C' );
            }
        }

        $this->fill_field( 130, 259, date_i18n( 'd/m/Y' ), 20 );
        $this->fill_field( 78, 259, $config->city ?? '', 20 );
    }

    /**
     * Statische wrapper: genereert één exemplaar van Formulier II als PDF.
     */
    public static function generate_form2( int $pub_id, string $dest = 'I', bool $debug = false ): string|bool {
        $pub     = Publications::get( $pub_id );
        $config  = Config::get();
        $changes = Publications::get_board_changes( $pub_id );
        if ( ! $pub || ! $config ) throw new \RuntimeException( 'Publicatie of config niet gevonden.' );

        $gen = new self( $debug );
        $gen->build_form2_pages( $pub, $config, $changes );
        return $gen->output( 'Formulier_II.pdf', $dest );
    }

    /* ======================================================================
     *  BESTUURSVERBOD VERKLARING (wet 4 mei 2023)
     * ====================================================================== */

    /**
     * Genereert de "Bijzondere Verklaring Bestuursverbod" als HTML-gebaseerde PDF via dompdf.
     * Één exemplaar per nieuw benoemde bestuurder (of alle bestuurswijzigingen van een publicatie).
     *
     * @param int    $pub_id  Publicatie-ID
     * @param string $dest    'I' = inline browser, 'D' = download, 'S' = string
     * @param bool   $debug   Debugmodus
     */
    public static function generate_bestuursverbod( int $pub_id, string $dest = 'I', bool $debug = false ): void {
        $pub     = Publications::get( $pub_id );
        $config  = Config::get();
        $changes = Publications::get_board_changes( $pub_id );

        if ( ! $pub || ! $config ) {
            throw new \RuntimeException( 'Publicatie of config niet gevonden.' );
        }

        // Render het HTML-template naar string
        ob_start();
        include EME_VZW_DIR . 'templates/pdf/bestuursverbod.php';
        $html = ob_get_clean();

        $full_html = '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';

        // Laad dompdf via Events Made Easy (zelfde aanpak als Patrimony)
        $dompdf_autoload = WP_PLUGIN_DIR . '/events-made-easy/includes/dompdf/vendor/autoload.php';

        if ( ! file_exists( $dompdf_autoload ) ) {
            // Fallback: stuur HTML direct naar browser (print-naar-PDF via browser)
            header( 'Content-Type: text/html; charset=UTF-8' );
            echo $full_html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        require_once $dompdf_autoload;

        $options = new \Dompdf\Options();
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'isRemoteEnabled', false );
        $options->set( 'defaultFont', 'Helvetica' );

        $pdf = new \Dompdf\Dompdf( $options );
        $pdf->loadHtml( $full_html );
        $pdf->setPaper( 'A4', 'portrait' );
        $pdf->render();

        $filename = 'Verklaring_Bestuursverbod_' . date( 'Ymd' ) . '.pdf'; //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

        $attachment = ( $dest === 'D' ) ? true : false;
        $pdf->stream( sanitize_file_name( $filename ), [ 'Attachment' => $attachment ] );
        exit;
    }

    /**
     * Genereert een gecombineerde PDF met 3 exemplaren van Formulier I
     * gevolgd door 2 exemplaren van Formulier II, in één enkele PDF-output.
     *
     * @param int    $pub_id  Publicatie-ID
     * @param string $dest    Output-bestemming ('I' inline, 'D' download, 'S' string)
     * @param bool   $debug   Kalibratierooster tonen
     */
    public static function generate_form( int $pub_id, string $dest = 'I', bool $debug = false ): string|bool {
        $pub     = Publications::get( $pub_id );
        $config  = Config::get();
        $changes = Publications::get_board_changes( $pub_id );
        if ( ! $pub || ! $config ) throw new \RuntimeException( 'Publicatie of config niet gevonden.' );

        $gen = new self( $debug );

        // 3 exemplaren van Formulier I
        for ( $i = 0; $i < 3; $i++ ) {
            $gen->build_form1_pages( $pub, $config, $changes );
        }

        if ($changes) {
            // 2 exemplaren van Formulier II
            for ( $i = 0; $i < 2; $i++ ) {
                $gen->build_form2_pages( $pub, $config, $changes );
            }
        }

        return $gen->output( 'Formulieren.pdf', $dest );
    }
}
