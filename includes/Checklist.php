<?php
namespace EMEVzw;

defined( 'ABSPATH' ) || exit;

/**
 * VZW Compliance Checklist.
 *
 * Defines all checklist items with weights (0–3), groups them into
 * "Algemeen" and "Dit boekjaar", stores answers per fiscal year,
 * and calculates the compliance score.
 */
class Checklist {

    public static function init(): void {
        add_action( 'wp_ajax_eme_vzw_save_check',     [ __CLASS__, 'ajax_save_check' ] );
        add_action( 'wp_ajax_eme_vzw_get_score',      [ __CLASS__, 'ajax_get_score' ] );
    }

    /* ------------------------------------------------------------------
     * CHECKLIST DEFINITION
     * Every item: key, label, weight (0–3), group, sub_questions[]
     * ------------------------------------------------------------------ */

    /**
     * Sub-question types: 'yesno' = ja/nee radio, 'date' = datumveld, 'number' = getal, 'text' = vrije tekst.
     *
     * @return array<string, array{label: string, weight: int, group: string, sub_questions: array<array{label:string,type:string}>, help: string}>
     */
    public static function get_definitions(): array {
        return [
            // ── ALGEMEEN ───────────────────────────────────────────────
            'micro_vzw' => [
                'label'         => __( 'Deze vereniging is een Micro VZW.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => __( 'Een micro-VZW heeft minder dan 5 FTE, balanstotaal < €350.000 én omzet < €700.000.', 'eme-vzw-helper' ),
                'sub_questions' => [],
            ],
            'single_entry_bookkeeping' => [
                'label'         => __( 'Het volstaat om een enkelvoudige boekhouding te voeren.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => __( 'Micro-VZW\'s mogen enkelvoudige boekhouding voeren (KB 29/04/2019).', 'eme-vzw-helper' ),
                'sub_questions' => [],
            ],
            'statutes_wvv' => [
                'label'         => __( 'De statuten zijn opgemaakt volgens de regels van het WVV.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => __( 'Wetboek van Vennootschappen en Verenigingen, van kracht sinds 1 mei 2019.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Bevatten de statuten de verplichte vermeldingen (art. 2:5 WVV)?', 'eme-vzw-helper' ),      'type' => 'yesno' ],
                    [ 'label' => __( 'Is de doelstelling van de VZW duidelijk omschreven?', 'eme-vzw-helper' ),                   'type' => 'yesno' ],
                    [ 'label' => __( 'Is de zetel correct vermeld?', 'eme-vzw-helper' ),                                          'type' => 'yesno' ],
                ],
            ],
            'vzw_name_correct' => [
                'label'         => __( 'De statutaire benaming van de vereniging is correct ingevuld.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => __( 'Controleer KBO en alle officiële documenten.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Komt de naam overeen met de KBO-registratie?', 'eme-vzw-helper' ), 'type' => 'yesno' ],
                ],
            ],
            'address_correct' => [
                'label'         => __( 'De maatschappelijke zetel en andere contactgegevens zijn correct ingevuld.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => '',
                'sub_questions' => [
                    [ 'label' => __( 'Is het adres in de KBO up-to-date?', 'eme-vzw-helper' ),            'type' => 'yesno' ],
                    [ 'label' => __( 'Is het adres in de statuten overeenstemmend?', 'eme-vzw-helper' ),  'type' => 'yesno' ],
                ],
            ],
            'court_correct' => [
                'label'         => __( 'De Ondernemingsrechtbank is correct ingevuld.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => __( 'Bevoegde rechtbank op basis van maatschappelijke zetel.', 'eme-vzw-helper' ),
                'sub_questions' => [],
            ],
            'no_vat' => [
                'label'         => __( 'De BTW-status van de vereniging is gekend en correct ingesteld.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => __( 'Geef in de Instellingen aan of de VZW BTW-plichtig is. Indien BTW-plichtig, zijn periodieke BTW-aangiften verplicht.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Is de BTW-status (plichtig / niet-plichtig) correct ingesteld in de plugin?', 'eme-vzw-helper' ), 'type' => 'yesno' ],
                ],
            ],
            'enterprise_number_correct' => [
                'label'         => __( 'Het ondernemingsnummer van de vereniging is correct.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => __( 'Te verifiëren via de KBO-website.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Stemt het nummer overeen met het nummer in de KBO?', 'eme-vzw-helper' ),               'type' => 'yesno' ],
                    [ 'label' => __( 'Is het nummer vermeld op alle officiële correspondentie?', 'eme-vzw-helper' ),          'type' => 'yesno' ],
                ],
            ],
            'statutes_no_derogation' => [
                'label'         => __( 'De statuten wijken niet af van de standaard wettelijke voorwaarden.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => '',
                'sub_questions' => [],
            ],
            'board_composition_correct' => [
                'label'         => __( 'Het Bestuur is correct samengesteld.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'general',
                'help'          => __( 'Min. 3 bestuurders, minder dan AV-leden.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Zijn er minstens 3 bestuurders?', 'eme-vzw-helper' ),                                   'type' => 'yesno' ],
                    [ 'label' => __( 'Is het aantal bestuurders altijd kleiner dan het aantal AV-leden?', 'eme-vzw-helper' ), 'type' => 'yesno' ],
                ],
            ],
            // ── DIT BOEKJAAR ───────────────────────────────────────────
            'board_mandate_valid' => [
                'label'         => __( 'Het huidige Bestuur heeft een geldig mandaat.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'De einddatum van het mandaat is in de statuten bepaald.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Tot wanneer loopt het mandaat van het huidige bestuur?', 'eme-vzw-helper' ),            'type' => 'date' ],
                    [ 'label' => __( 'Is het mandaat nog niet verlopen?', 'eme-vzw-helper' ),                                 'type' => 'yesno' ],
                ],
            ],
            'av_composition_correct' => [
                'label'         => __( 'De Algemene Vergadering is correct samengesteld.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'AV moet minstens 3 leden bevatten.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Hoeveel werkende leden zijn er?', 'eme-vzw-helper' ),                                   'type' => 'number' ],
                    [ 'label' => __( 'Is het ledenregister up-to-date?', 'eme-vzw-helper' ),                                  'type' => 'yesno' ],
                    [ 'label' => __( 'Is er een onderscheid tussen werkende en toegetreden leden?', 'eme-vzw-helper' ),       'type' => 'yesno' ],
                ],
            ],
            'kbo_board_known' => [
                'label'         => __( 'Het is geweten wie er volgens de Kruispuntbank van Ondernemingen momenteel in het Bestuur zit.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Via https://kbo.economie.fgov.be/nl/search', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Stemt de KBO-samenstelling overeen met de werkelijkheid?', 'eme-vzw-helper' ),          'type' => 'yesno' ],
                    [ 'label' => __( 'Zijn ontslagen bestuurders verwijderd uit de KBO?', 'eme-vzw-helper' ),                 'type' => 'yesno' ],
                ],
            ],
            'ubo_registered' => [
                'label'         => __( "De 'uiteindelijk begunstigden' van de VZW werden geregistreerd in het UBO-register.", 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Verplicht via MyMinFin of Isabel. Jaarlijkse bevestiging vereist.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Wanneer werd het UBO-register voor het laatste bijgewerkt?', 'eme-vzw-helper' ),        'type' => 'date' ],
                    [ 'label' => __( 'Is de jaarlijkse bevestiging al gedaan dit jaar?', 'eme-vzw-helper' ),                  'type' => 'yesno' ],
                ],
            ],
            'statutes_approved_av' => [
                'label'         => __( 'De nieuwe statuten werden goedgekeurd door de Algemene Vergadering.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => 'Zet N.v.t indien niet aangepast',
                'sub_questions' => [
                    [ 'label' => __( 'Waren er voldoende leden aanwezig (quorum voor statutenwijziging: 2/3 van de leden)?', 'eme-vzw-helper' ), 'type' => 'yesno' ],
                    [ 'label' => __( 'Was er een 4/5 meerderheid van stemmen?', 'eme-vzw-helper' ),                                             'type' => 'yesno' ],
                    [ 'label' => __( 'Zijn de notulen getekend?', 'eme-vzw-helper' ),                                                           'type' => 'yesno' ],
                ],
            ],
            'current_year_dates_correct' => [
                'label'         => __( 'Het huidige boekjaar start en eindigt op de correcte data.', 'eme-vzw-helper' ),
                'weight'        => 1,
                'group'         => 'current_year',
                'help'          => '',
                'sub_questions' => [
                    [ 'label' => __( 'Startdatum huidige boekjaar?', 'eme-vzw-helper' ),  'type' => 'date' ],
                    [ 'label' => __( 'Einddatum huidige boekjaar?', 'eme-vzw-helper' ),   'type' => 'date' ],
                ],
            ],
            'prev_year_bookkeeping_closed' => [
                'label'         => __( 'De boekhouding van het vorige boekjaar is afgerond en goedgekeurd door het Bestuur.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => ''
            ],
            'budget_approved' => [
                'label'         => __( 'De begroting van het huidige boekjaar is opgemaakt en goedgekeurd door het Bestuur.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => '',
                'sub_questions' => [
                    [ 'label' => __( 'Is de begroting opgemaakt voor het lopende boekjaar?', 'eme-vzw-helper' ),              'type' => 'yesno' ],
                    [ 'label' => __( 'Is de begroting goedgekeurd door het bestuur?', 'eme-vzw-helper' ),                     'type' => 'yesno' ],
                    [ 'label' => __( 'Is de begroting daarna ook goedgekeurd door de AV?', 'eme-vzw-helper' ),                'type' => 'yesno' ],
                ],
            ],
            'invitation_sent' => [
                'label'         => __( 'De uitnodiging voor de AV met de agenda is verstuurd naar de leden.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Minstens 15 dagen voor de AV (of zoals in statuten bepaald).', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Wanneer werden de uitnodigingen verstuurd?', 'eme-vzw-helper' ),                        'type' => 'date' ],
                    [ 'label' => __( 'Is de vereiste termijn gerespecteerd?', 'eme-vzw-helper' ),                             'type' => 'yesno' ],
                    [ 'label' => __( 'Bevatte de uitnodiging de volledige agenda?', 'eme-vzw-helper' ),                       'type' => 'yesno' ],
                ],
            ],
            'av_held' => [
                'label'         => __( 'De Algemene Vergadering heeft plaatsgevonden.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'De jaarvergadering moet uiterlijk 6 maanden na afsluiting boekjaar plaatsvinden.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Datum van de Algemene Vergadering?', 'eme-vzw-helper' ),                                'type' => 'date' ],
                    [ 'label' => __( 'Was er een quorum (statutair bepaald minimum aanwezig)?', 'eme-vzw-helper' ),           'type' => 'yesno' ],
                    [ 'label' => __( 'Hoeveel leden waren aanwezig of vertegenwoordigd?', 'eme-vzw-helper' ),                 'type' => 'number' ],
                ],
            ],
            'av_quorum_met' => [
                'label'         => __( 'Op de AV waren voldoende leden aanwezig om alle agendapunten af te werken.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => '',
                'sub_questions' => [
                    [ 'label' => __( 'Hoeveel leden waren aanwezig of vertegenwoordigd?', 'eme-vzw-helper' ),                 'type' => 'number' ],
                    [ 'label' => __( 'Was er voor elk agendapunt het vereiste quorum?', 'eme-vzw-helper' ),                   'type' => 'yesno' ],
                ],
            ],
            'av_approved_bookkeeping' => [
                'label'         => __( 'Op de AV werd de boekhouding van het vorige boekjaar goedgekeurd.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => '',
                'sub_questions' => [
                    [ 'label' => __( 'Is dit vermeld in de notulen?', 'eme-vzw-helper' ),                                     'type' => 'yesno' ],
                    [ 'label' => __( 'Is de stemming correct verlopen (gewone meerderheid)?', 'eme-vzw-helper' ),             'type' => 'yesno' ],
                ],
            ],
            'av_decisions' => [
                'label'         => __( 'Welke beslissingen heeft de Algemene Vergadering al dan niet genomen?', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Overzicht van alle AV-beslissingen: bestuur, begroting, kwijting, …', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Kwijting aan de bestuurders goedgekeurd?', 'eme-vzw-helper' ),                          'type' => 'yesno' ],
                    [ 'label' => __( 'Verkiezingen / herverkiezingen?', 'eme-vzw-helper' ),                                   'type' => 'yesno' ],
                    [ 'label' => __( 'Overige beslissingen (omschrijving)?', 'eme-vzw-helper' ),                              'type' => 'text' ],
                ],
            ],
            'av_approved_budget' => [
                'label'         => __( 'Op de AV werd de begroting van het huidige boekjaar goedgekeurd.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => '',
                'sub_questions' => [
                    [ 'label' => __( 'Is dit vermeld in de notulen?', 'eme-vzw-helper' ), 'type' => 'yesno' ],
                ],
            ],
            'financial_statements_deposited' => [
                'label'         => __( "Zijn de 'Staat van Ontvangsten en Uitgaven' en de 'Staat van het Vermogen' neergelegd bij de Griffie?", 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Verplicht voor alle VZW\'s, zelfs micro-VZW\'s (art. 3:51 WVV).', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Datum van neerlegging?', 'eme-vzw-helper' ),                'type' => 'date' ],
                    [ 'label' => __( 'Zijn beide documenten ingediend?', 'eme-vzw-helper' ),      'type' => 'yesno' ],
                ],
            ],
            'prev_bookkeeping_closed_final' => [
                'label'         => __( 'Werd de boekhouding van het vorige boekjaar definitief afgesloten?', 'eme-vzw-helper' ),
                'weight'        => 0,
                'group'         => 'current_year',
                'help'          => '',
                'sub_questions' => [],
            ],
            'form1_2_needed' => [
                'label'         => __( 'De Formulieren 1 en 2 zijn opgemaakt omdat er een bestuurswissel en/of statutenwijziging was.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Zet op "N.v.t." indien niet toepasselijk.', 'eme-vzw-helper' ),
                'sub_questions' => [],
            ],
            'statute_change_bs' => [
                'label'         => __( 'Zijn de bestuurswissels en/of statutenwijzigingen gepubliceerd in het Belgisch Staatsblad.', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Publicatie verplicht binnen 30 dagen na goedkeuring door AV. Zet op "N.v.t." indien niet toepasselijk.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Datum van publicatie?', 'eme-vzw-helper' ), 'type' => 'date' ],
                    [ 'label' => __( 'Is de publicatie binnen de termijn van 30 dagen gebeurd?', 'eme-vzw-helper' ),    'type' => 'yesno' ],
                ],
            ],
            'ubo_update_needed' => [
                'label'         => __( "Het UBO register (Uiteindelijk Begunstigden Onderneming) is aangepast omdat er een bestuurswissel was.", 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Zet op "N.v.t." indien niet toepasselijk.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Is de deadline aangepast in de instellingen?', 'eme-vzw-helper' ), 'type' => 'yesno' ],
                ],
            ],
            'patrimonium_tax' => [
                'label'         => __( "Is de aangifte Patrimoniumtaks op VZW's correct ingediend?", 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Jaarlijkse aangifte vóór 31 maart via MyMinFin.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Is de aangifte tijdig (vóór 31 maart) ingediend?', 'eme-vzw-helper' ),  'type' => 'yesno' ],
                    [ 'label' => __( 'Is de taks betaald?', 'eme-vzw-helper' ),                               'type' => 'yesno' ],
                ],
            ],
            'legal_entity_tax' => [
                'label'         => __( 'Is de aangifte Rechtspersonenbelasting correct ingediend?', 'eme-vzw-helper' ),
                'weight'        => 3,
                'group'         => 'current_year',
                'help'          => __( 'Verplicht voor alle VZW\'s, zelfs micro-VZW\'s. Via Biztax. Deadline: uiterlijk 6 maanden na afsluiting boekjaar, maar meestal maar mogelijk vanaf mei.', 'eme-vzw-helper' ),
                'sub_questions' => [
                    [ 'label' => __( 'Is de aangifte tijdig ingediend?', 'eme-vzw-helper' ),           'type' => 'yesno' ],
                    [ 'label' => __( 'Is de eventuele belasting betaald?', 'eme-vzw-helper' ),         'type' => 'yesno' ],
                    [ 'label' => __( 'Is de deadline aangepast in de instellingen?', 'eme-vzw-helper' ), 'type' => 'yesno' ],
                ],
            ],
        ];
    }

    /* ------------------------------------------------------------------
     * HELPERS
     * ------------------------------------------------------------------ */

    /**
     * Effective weight of a checklist item:
     * base weight + 1 for each sub-question defined.
     */
    public static function effective_weight( array $item ): int {
        $subs = $item['sub_questions'] ?? [];
        return (int) $item['weight'] + count( $subs );
    }

    /* ------------------------------------------------------------------
     * SCORING
     * ------------------------------------------------------------------ */

    /**
     * Calculate compliance score for a given year.
     *
     * @return array{score_pct: float, max_points: int, earned_points: int, by_group: array}
     */
    public static function get_score( int $fiscal_year ): array {
        $definitions = self::get_definitions();
        $answers     = self::get_answers( $fiscal_year );

        $max_points    = 0;
        $earned_points = 0;
        $by_group      = [ 'general' => [ 'max' => 0, 'earned' => 0 ], 'current_year' => [ 'max' => 0, 'earned' => 0 ] ];

        $max_questions = count($definitions);
        $no_answer     = 0;
        foreach ( $definitions as $key => $item ) {
            $weight = self::effective_weight( $item );
            $status = $answers[ $key ]['status'] ?? 'pending';
            $group  = $item['group'];

            $max_points              += $weight;
            $by_group[ $group ]['max'] += $weight;

            if ( $status === 'yes' ) {
                $earned_points             += $weight;
                $by_group[ $group ]['earned'] += $weight;
            }
            // 'na' counts as full points (not applicable = not penalised)
            if ( $status === 'na' ) {
                $earned_points             += $weight;
                $by_group[ $group ]['earned'] += $weight;
            }

            if ( $status === 'pending' ) {
                $no_answer++;
            }
        }

        return [
            'score_pct'     => $max_points > 0 ? round( $earned_points / $max_points * 100, 1 ) : 0,
            'max_points'    => $max_points,
            'max_questions' => $max_questions,
            'no_answer'     => $no_answer,
            'earned_points' => $earned_points,
            'by_group'      => $by_group,
        ];
    }

    /* ------------------------------------------------------------------
     * DATA ACCESS
     * ------------------------------------------------------------------ */

    /**
     * Get all answers for a given year, keyed by check_key.
     *
     * @return array<string, string>  check_key => status
     */
    public static function get_answers( int $fiscal_year ): array {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT check_key, status, sub_answers, notes, checked_at, fiscal_year
             FROM `{$wpdb->prefix}emevzw_checks`
             WHERE fiscal_year IS NULL OR fiscal_year = %d",
            $fiscal_year
        ) );
        $out = [];
        foreach ( $rows as $row ) {
            $existing =& $out[ $row->check_key ];
            if ( ! $existing || $row->fiscal_year !== null ) {
                $existing = [
                    'status'      => $row->status,
                    'sub_answers' => $row->sub_answers ?? '',
                    'notes'       => $row->notes ?? '',
                ];
            }
        }
        return $out;
    }

    /**
     * Save a single check answer.
     *
     * @param  string $check_key
     * @param  string $status       yes|no|na|pending
     * @param  string $sub_answers  Optional free-text answer
     * @param  string $notes
     */
    public static function save_check( int $fiscal_year, string $check_key, string $status, string $sub_answers = '', string $notes = '' ): bool {
        global $wpdb;
        $allowed = [ 'yes', 'no', 'na', 'pending' ];
        if ( ! in_array( $status, $allowed, true ) ) return false;

        $definition = self::get_definitions();
        if ( ! isset( $definition[ $check_key ] ) ) return false;

        $item        = $definition[ $check_key ];
        $is_general  = ( $item['group'] === 'general' );
        $store_year  = $is_general ? null : $fiscal_year;

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM `{$wpdb->prefix}emevzw_checks`
             WHERE fiscal_year " . ( $is_general ? 'IS NULL' : '= %d' ) . " AND check_key = %s",
            ...( $is_general ? [ $check_key ] : [ $fiscal_year, $check_key ] )
        ) );

        $data = [
            'status'      => $status,
            'sub_answers' => eme_sanitize_request( $sub_answers ),
            'notes'       => eme_sanitize_request( $notes ),
            'checked_by'  => get_current_user_id(),
            'checked_at'  => current_time( 'mysql' ),
        ];

        if ( $existing ) {
            $where = [ 'check_key' => $check_key ];
            if ( $is_general ) {
                $where['fiscal_year'] = null;
            } else {
                $where['fiscal_year'] = $fiscal_year;
            }
            $wpdb->update( $wpdb->prefix . 'emevzw_checks', $data, $where );
        } else {
            $insert = array_merge( $data, [
                'fiscal_year' => $store_year,
                'check_key'   => $check_key,
            ] );
            $wpdb->insert( $wpdb->prefix . 'emevzw_checks', $insert );
        }

        do_action( 'eme_vzw_check_saved', $check_key, $status, $fiscal_year );
        return true;
    }

    /* ------------------------------------------------------------------
     * AJAX
     * ------------------------------------------------------------------ */

    public static function ajax_save_check(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $result = self::save_check(
            (int)   ( $_POST['fiscal_year']  ?? gmdate( 'Y' ) ),
            sanitize_key( $_POST['check_key']  ?? '' ),
            sanitize_key( $_POST['status']     ?? 'pending' ),
            eme_sanitize_request( $_POST['sub_answers'] ?? '' ),
            eme_sanitize_request( $_POST['notes']       ?? '' )
        );

        if ( $result ) {
            $score = self::get_score( (int) ( $_POST['fiscal_year'] ?? gmdate( 'Y' ) ) );
            wp_send_json_success( [ 'score' => $score ] );
        } else {
            wp_send_json_error( __( 'Ongeldige check-sleutel of status.', 'eme-vzw-helper' ) );
        }
    }

    public static function ajax_get_score(): void {
        check_ajax_referer( 'eme_vzw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        $score = self::get_score( (int) ( $_POST['fiscal_year'] ?? gmdate( 'Y' ) ) );
        wp_send_json_success( $score );
    }
}
