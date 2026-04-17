<?php
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

/**
 * EME VZW PDF Generator
 * Genereert officiële Belgische formulieren met TCPDF + exacte PNG-achtergronden.
 * 
 * Wijzigingen:
 * - Dynamische paginering voor Luik B (bestuurs- + statutenwijzigingen)
 * - add_luik_b_continuation() wordt automatisch herhaald tot alle tekst past
 * - Statuutwijzigingen worden nu correct verwerkt als groot tekstblok
 */
class PdfGenerator {

    private \TCPDF $pdf;
    private string $bg_dir;
    private bool   $debug;
    private int    $current_page = 0;

    public function __construct( bool $debug = false ) {
        $this->bg_dir = trailingslashit( EME_VZW_DIR ) . 'assets/templates/';
        $this->debug  = $debug;

        $this->load_tcpdf();
        $this->init_pdf_engine();
    }

    /* ======================================================================
     *  INIT & LOADERS
     * ====================================================================== */

    private function load_tcpdf(): void {
        if ( class_exists( '\TCPDF' ) ) return;

        $candidates = [
            EME_VZW_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php',
            WP_PLUGIN_DIR . '/eme-vzw-helper/vendor/tecnickcom/tcpdf/tcpdf.php',
        ];
        foreach ( $candidates as $path ) {
            if ( file_exists( $path ) ) {
                require_once $path;
                return;
            }
        }
        throw new \RuntimeException( 'TCPDF library not found. Run: composer require tecnickcom/tcpdf' );
    }

    private function init_pdf_engine(): void {
        $this->pdf = new \TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
        $this->pdf->SetCreator( 'EME VZW Helper v' . EME_VZW_VERSION );
        $this->pdf->SetPrintHeader( false );
        $this->pdf->SetPrintFooter( false );
        $this->pdf->SetMargins( 0, 0, 0, true );
        $this->pdf->SetAutoPageBreak( false, 0 );
        $this->pdf->SetFont( 'helvetica', '', 9 );
        $this->pdf->SetTextColor( 0, 0, 0 );
    }

    /* ======================================================================
     *  PAGINA MANAGEMENT (FORMULIER I & II)
     * ====================================================================== */

    /**
     * Core: voeg een nieuwe A4-pagina toe met bijhorende achtergrond-PNG
     * Voegt automatisch kalibratierooster toe indien $this->debug = true
     */
    private function add_template_page( string $prefix, int $page_num ): self {
        $this->pdf->AddPage();
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
            $this->pdf->Image( $file, 0, 0, 210, 297, 'PNG', '', '', true, 300, '', false, false, 0, false, false, false );
        } elseif ( $this->debug ) {
            $this->pdf->SetXY( 10, 10 );
            $this->pdf->SetTextColor( 200, 0, 0 );
            $this->pdf->Write( 0, "⚠️ Achtergrond niet gevonden: " . basename( $file ) );
            $this->pdf->SetTextColor( 0, 0, 0 );
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
     *  VELD & TEKST HELPERS (MET PAGINERING)
     * ====================================================================== */

    /**
     * Vul een tekstblok met automatische paginering.
     * Splitst tekst per regel, print, en voegt Luik B vervolg toe indien nodig.
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
            $line = trim( $line );
            /*
            if ( $line === '' ) {
                $current_y += 5; // Kleine spatie voor lege regel
                continue;
            }*/

            // Check of regel nog past op huidige pagina
            if ( $current_y + $line_height_mm > $y_limit ) {
                $this->add_luik_b_continuation();
                $current_y = $y_reset;
            }

            // Print regel met MultiCell (ondersteunt wrapping)
            $this->pdf->SetXY( $x, $current_y );
            $this->pdf->SetFont( 'helvetica', '', $font_size );
            $this->pdf->MultiCell( $width, $line_height_mm, $line, 0, 'L', false, 1, '', '', true, 0, false, false, 0, 'T' );
            
            // Update Y naar positie waar TCPDF is geëindigd
            $current_y = $this->pdf->GetY();
        }
        return $this;
    }

    public function fill_field( float $x, float $y, string $text, float $width = 50, float $font_size = 10, string $align = 'L', bool $multiline = false ): self {
        $this->pdf->SetFont( 'helvetica', '', $font_size );
        $this->pdf->SetXY( $x, $y );
        
        if ( $multiline ) {
            $this->pdf->MultiCell( $width, 4, $text, 0, $align, false, 1, '', '', true, 0, true, true, 50, 'T' );
        } else {
            $this->pdf->Cell( $width, 5, $text, 0, 0, $align, false, '', 0, false, 'T', 'M' );
        }
        return $this;
    }

    public function fill_checkbox( float $x, float $y, bool $checked = true, float $size = 4 ): self {
        $this->pdf->SetDrawColor( 0, 0, 0 );
        $this->pdf->SetLineWidth( 0.2 );
        //$this->pdf->Rect( $x, $y, $size, $size, 'D' );
        if ( $checked ) {
            $this->pdf->SetLineWidth( 0.3 );
            $this->pdf->Line( $x + 0.5, $y + 0.5, $x + $size - 0.5, $y + $size - 0.5 );
            $this->pdf->Line( $x + 0.5, $y + $size - 0.5, $x + $size - 0.5, $y + 0.5 );
        }
        return $this;
    }

    public function add_calibration_grid( int $step_mm = 10 ): self {
        $this->pdf->SetDrawColor( 255, 0, 0 );
        $this->pdf->SetLineWidth( 0.1 );
        $this->pdf->SetFont( 'helvetica', '', 6 );
        $this->pdf->SetTextColor( 255, 0, 0 );

        for ( $x = $step_mm; $x < 210; $x += $step_mm ) {
            $this->pdf->Line( $x, 0, $x, 297 );
            $this->pdf->SetXY( $x + 0.5, 1 );
            $this->pdf->Write( 0, $x );
        }
        for ( $y = $step_mm; $y < 297; $y += $step_mm ) {
            $this->pdf->Line( 0, $y, 210, $y );
            $this->pdf->SetXY( 1, $y + 0.5 );
            $this->pdf->Write( 0, $y );
        }
        $this->pdf->SetDrawColor( 0, 0, 0 );
        $this->pdf->SetTextColor( 0, 0, 0 );
        $this->pdf->SetLineWidth( 0.2 );
        return $this;
    }

    /* ======================================================================
     *  OUTPUT
     * ====================================================================== */

    public function output( string $filename, string $dest = 'I' ): string|bool {
        // Voeg disclaimer footer toe op de laatste pagina
        //$this->pdf->SetFont( 'helvetica', 'I', 6 );
        //$this->pdf->SetTextColor( 100, 100, 100 );
        //$this->pdf->SetXY( 10, 290 );
        //$this->pdf->Write( 0, 'Gegenereerd door EME VZW Helper | Controleer altijd vóór indiening bij de griffie.' );
        return $this->pdf->Output( sanitize_file_name( $filename ), $dest );
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
        $this->fill_field( 100, 78, $config->vzw_number ?? '', 40 );
        $this->fill_field( 80, 84, $config->vzw_name ?? '', 80 );
        $this->fill_field( 90, 99, $config->vzw_legal_form ?? 'VZW', 30 );
        $this->fill_field( 80, 109, $config->street ?? '', 60 );
        $this->fill_field( 150, 109, $config->house_number ?? '', 60 );
        $this->fill_field( 175, 109, $config->house_bus ?? '', 60 );
        $this->fill_field( 85, 114, $config->postal_code ?? '', 30 );
        $this->fill_field( 135, 114, $config->city ?? '', 60 );
        $this->fill_field( 80, 120, $config->country ?? '', 60 );

        // ── PAGINA 2: LUIK B (START) ──
        $this->add_luik_b();
        
        // Identificatie header Luik B
        $this->fill_field( 80, 64, $config->vzw_number ?? '', 40 );
        $this->fill_field( 80, 72, $config->vzw_name ?? '', 80 );
        $this->fill_field( 80, 76, $config->vzw_shortname ?? '', 80 );
        $this->fill_field( 80, 82, $config->vzw_legal_form ?? 'VZW', 30 );
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
        $legal_form = strtolower( $config->vzw_legal_form ?? 'vzw' );

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
            $role_lower = strtolower( $m->role ?? '' );
            // Zoek naar voorzitter/president
            if ( preg_match( '/(voorzitter|president|chair)/i', $role_lower ) ) {
                $voorzitter = $m;
            }
            // Zoek naar secretaris
            if ( preg_match( '/(secretaris|secretary)/i', $role_lower ) ) {
                $secretaris = $m;
            }
        }

        $signatory_parts = [];
        if ( $voorzitter ) {
            $signatory_parts[] = sprintf( '%s %s, %s', $voorzitter->first_name, $voorzitter->last_name, 'bestuurder');
        }
        if ( $secretaris ) {
            $signatory_parts[] = sprintf( '%s %s, %s', $secretaris->first_name, $secretaris->last_name, 'bestuurder');
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
            $idx === 0 ? $this->add_luik_c_f2() : $this->add_luik_c_f2_cont();

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
                $this->fill_field( 65, $y, trim( $c->last_name . ' ' . $c->first_name ), 60 );
                //$this->fill_field( 140, $y, $c->role ?? 'Bestuurder', 40 );
                $this->fill_field( 140, $y, 'Bestuurder', 40 );
                $this->fill_field( 177, $y, ! empty( $pub->av_date ) ? date_i18n( 'd/m/Y', strtotime( $pub->av_date ) ) : '', 25, 10, 'C' );
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

    /**
     * Genereert een gecombineerde PDF met 3 exemplaren van Formulier I
     * gevolgd door 2 exemplaren van Formulier II, in één enkele PDF-output.
     *
     * @param int    $pub_id  Publicatie-ID
     * @param string $dest    TCPDF output-bestemming ('I', 'D', 'F', 'S', …)
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

        // 2 exemplaren van Formulier II
        for ( $i = 0; $i < 2; $i++ ) {
            $gen->build_form2_pages( $pub, $config, $changes );
        }

        return $gen->output( 'Formulieren.pdf', $dest );
    }
}
