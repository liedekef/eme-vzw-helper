<?php
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
defined( 'ABSPATH' ) || exit;

$config = EMEVzw\Config::get();
$year   = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) gmdate( 'Y' );
$score  = EMEVzw\Checklist::get_score( $year );
$board  = EMEVzw\Config::get_active_board_members();
$pubs   = EMEVzw\Publications::get_all();

// Deadline calculations
$today    = new DateTime( current_time( 'Y-m-d' ) );
$deadlines = [];

if ( $config ) {
    if ( $config->board_mandate_end ) {
        $end  = new DateTime( $config->board_mandate_end );
        $diff = $today->diff( $end );
        $deadlines[] = [
            'label'  => __( 'Einde bestuursmandaat', 'eme-vzw-helper' ),
            'date'   => $config->board_mandate_end,
            'days'   => $end >= $today ? $diff->days : -1,
            'warn'   => $end >= $today && $diff->days <= 90,
            'urgent' => $end < $today || $diff->days <= 30,
        ];
    }
    if ( $config->ubo_last_confirmed ) {
        $last  = new DateTime( $config->ubo_last_confirmed );
        $next  = clone $last;
        $next->modify( '+1 year' );
        $diff  = $today->diff( $next );
        $deadlines[] = [
            'label'  => __( 'Jaarlijkse UBO-bevestiging', 'eme-vzw-helper' ),
            'date'   => $next->format( 'Y-m-d' ),
            'days'   => $next >= $today ? $diff->days : -1,
            'warn'   => $next >= $today && $diff->days <= 90,
            'urgent' => $next < $today || $diff->days <= 30,
        ];
    }
    // AV deadline: 6 months after fiscal year end
    $fy_end = new DateTime( $year . '-' . str_pad( $config->fiscal_year_end_mm, 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $config->fiscal_year_end_dd, 2, '0', STR_PAD_LEFT ) );
    $av_deadline = clone $fy_end;
    $av_deadline->modify( '+6 months' );
    if ( $av_deadline > $today ) {
        $diff = $today->diff( $av_deadline );
        $deadlines[] = [
            'label'  => sprintf( // translators: %d is the year of the general assembly.
                __( 'Deadline Algemene Vergadering %d', 'eme-vzw-helper' ), $year+1 ),
            'date'   => $av_deadline->format( 'Y-m-d' ),
            'days'   => $diff->days,
            'warn'   => $diff->days <= 90,
            'urgent' => $diff->days <= 30,
        ];
    }

    // Patrimoniumtaks: vaste deadline 31 maart van het lopende kalenderjaar
    $patrimony_year   = (int) $today->format( 'Y' );
    $patrimony_date   = new DateTime( $patrimony_year . '-03-31' );
    if ( $patrimony_date < $today ) {
        // Deadline dit jaar is voorbij → toon deadline volgend jaar
        $patrimony_date = new DateTime( ( $patrimony_year + 1 ) . '-03-31' );
    }
    $diff = $today->diff( $patrimony_date );
    $deadlines[] = [
        'label'  => __( 'Aangifte patrimoniumtaks VZW (31 maart)', 'eme-vzw-helper' ),
        'date'   => $patrimony_date->format( 'Y-m-d' ),
        'days'   => $patrimony_date >= $today ? $diff->days : -1,
        'warn'   => $patrimony_date >= $today && $diff->days <= 90,
        'urgent' => $patrimony_date < $today || $diff->days <= 30,
        'info'   => __( 'Als uw vereniging niet meer dan 50.000 euro aan belastbare bezittingen heeft, betaalt u geen taks en hoeft u geen aangifte in te dienen. Sommige verenigingen worden door de wet vrijgesteld van de taks.', 'eme-vzw-helper' ),
    ];

    // Rechtspersonenbelasting (RPB): gebaseerd op rpb_last_confirmed, net als UBO.
    // Als de laatste aangifte bekend is, is de volgende deadline 7 maanden na het einde
    // van het eerstvolgende boekjaar ná die bevestiging.
    // Indien niet ingevuld: toon 31 juli van het huidige jaar als standaard deadline.
    if ( $config->rpb_last_confirmed ) {
        $rpb_confirmed = new DateTime( $config->rpb_last_confirmed );
        // Bepaal het boekjaareinde dat volgt op de bevestigingsdatum
        $fy_end_this = new DateTime(
            $rpb_confirmed->format( 'Y' ) . '-'
            . str_pad( $config->fiscal_year_end_mm, 2, '0', STR_PAD_LEFT ) . '-'
            . str_pad( $config->fiscal_year_end_dd, 2, '0', STR_PAD_LEFT )
        );
        // Als de bevestiging ná het boekjaareinde van dat jaar valt, neem volgend jaar
        $next_fy_end  = $rpb_confirmed > $fy_end_this
            ? ( clone $fy_end_this )->modify( '+1 year' )
            : clone $fy_end_this;
        $rpb_year     = (int) $next_fy_end->format( 'Y' );
        $rpb_deadline = ( clone $next_fy_end )->modify( '+7 months' );
    } else {
        // Geen bevestiging ingevuld: standaard deadline = 30 juni dit jaar
        $rpb_year     = (int) $today->format( 'Y' );
        $rpb_deadline = new DateTime( $rpb_year . '-07-31' );
    }
    $diff_rpb = $today->diff( $rpb_deadline );
    $deadlines[] = [
        'label'      => sprintf( // translators: %d is the fiscal year of the return.
            __( 'Aangifte rechtspersonenbelasting boekjaar %d (via Biztax)', 'eme-vzw-helper' ), $rpb_year ),
        'date'       => $rpb_deadline->format( 'Y-m-d' ),
        'days'       => $rpb_deadline >= $today ? $diff_rpb->days : -1,
        'warn'       => $rpb_deadline >= $today && $diff_rpb->days <= 90,
        'urgent'     => $rpb_deadline < $today || $diff_rpb->days <= 30,
        'link'       => 'https://financien.belgium.be/nl/E-services/biztax/',
        'link_label' => __( 'Biztax openen', 'eme-vzw-helper' ),
    ];

    // BTW-deadlines: enkel als de VZW BTW-plichtig is
    if ( ! empty( $config->vat_liable ) ) {
        // Kwartaalaangiften: Q1 t.e.m. Q4 — deadline 20e van de maand na het kwartaal
        $vat_quarters = [
            [ 'q' => 'Q1', 'deadline' => sprintf( '%d-04-20', (int) $today->format( 'Y' ) ) ],
            [ 'q' => 'Q2', 'deadline' => sprintf( '%d-07-20', (int) $today->format( 'Y' ) ) ],
            [ 'q' => 'Q3', 'deadline' => sprintf( '%d-10-20', (int) $today->format( 'Y' ) ) ],
            [ 'q' => 'Q4', 'deadline' => sprintf( '%d-01-20', (int) $today->format( 'Y' ) + 1 ) ],
        ];
        foreach ( $vat_quarters as $vq ) {
            $vat_date = new DateTime( $vq['deadline'] );
            if ( $vat_date >= $today ) {
                $diff_vat = $today->diff( $vat_date );
                $deadlines[] = [
                    'label'  => sprintf( // translators: %s is the quarter (Q1-Q4).
                        __( 'BTW-aangifte %s (kwartaal)', 'eme-vzw-helper' ), $vq['q'] ),
                    'date'   => $vat_date->format( 'Y-m-d' ),
                    'days'   => $diff_vat->days,
                    'warn'   => $diff_vat->days <= 30,
                    'urgent' => $diff_vat->days <= 14,
                ];
                break; // Toon enkel de eerstvolgende BTW-deadline
            }
        }
    }
}

// Sorteer deadlines op datum (vroegste eerst)
usort( $deadlines, fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );

$pct    = $score['score_pct'];
$radius = 48;
$circ   = 2 * M_PI * $radius;
$offset = $circ * ( 1 - $pct / 100 );
$color  = $pct >= 80 ? '#00a32a' : ( $pct >= 50 ? '#dba617' : '#d63638' );
$g_gen  = $score['by_group']['general'];
$g_yr   = $score['by_group']['current_year'];
?>
<div class="wrap vzw-wrap">
<h1><?php esc_html_e( 'EME VZW Helper – Dashboard', 'eme-vzw-helper' ); ?></h1>

<?php if ( ! $config ): ?>
<div class="notice notice-warning">
    <p><?php printf(
        // translators: %s is the link to the VZW settings page.
        esc_html__( 'Stel eerst de VZW-gegevens in via %s.', 'eme-vzw-helper' ),
        '<a href="' . esc_url( admin_url( 'admin.php?page=emevzwhelper-config' ) ) . '">'
            . esc_html__( 'Instellingen VZW', 'eme-vzw-helper' ) . '</a>'
    ); ?></p>
</div>
<?php else: ?>

<!-- Compliance score summary -->
<div class="vzw-summary">
    <div class="vzw-score-ring">
        <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="<?php echo esc_attr( $radius ); ?>"
                    fill="none" stroke="#f0f0f1" stroke-width="10"/>
            <circle id="score-ring-progress" cx="60" cy="60" r="<?php echo esc_attr( $radius ); ?>"
                    fill="none"
                    stroke="<?php echo esc_attr( $color ); ?>"
                    stroke-width="10"
                    stroke-linecap="round"
                    stroke-dasharray="<?php echo esc_attr( round( $circ, 2 ) ); ?>"
                    stroke-dashoffset="<?php echo esc_attr( round( $offset, 2 ) ); ?>"/>
        </svg>
        <div class="score-text">
            <span id="score-pct-text"><?php echo number_format( $pct, 1, ',', '' ); ?>%</span>
            <small><?php esc_html_e( 'compliant', 'eme-vzw-helper' ); ?></small>
        </div>
    </div>
    <div class="vzw-summary-info">
        <h2><?php echo esc_html( $config->vzw_name ); ?> <small style="font-size:13px;font-weight:400;color:#646970"><?php echo esc_html( $config->vzw_number ); ?></small></h2>
        <p><?php echo esc_html( trim( $config->street . ' ' . $config->house_number . ', ' . $config->postal_code . ' ' . $config->city ) ); ?></p>
        <div class="vzw-group-scores">
            <div class="vzw-group-score">
                <strong id="group-general-score"><?php echo $g_gen['max'] > 0 ? number_format( $g_gen['earned'] / $g_gen['max'] * 100, 1, ',', '' ) : '–'; ?>%</strong>
                <?php esc_html_e( 'Algemeen', 'eme-vzw-helper' ); ?>
            </div>
            <div class="vzw-group-score">
                <strong id="group-year-score"><?php echo $g_yr['max'] > 0 ? number_format( $g_yr['earned'] / $g_yr['max'] * 100, 1, ',', '' ) : '–'; ?>%</strong>
                <?php echo esc_html( sprintf( esc_html__( 'Boekjaar %d', 'eme-vzw-helper' ), $year ) ); // translators: %d is the fiscal year ?>
            </div>
            <div class="vzw-group-score">
                <strong><?php echo count( $board ); ?></strong>
                <?php esc_html_e( 'Actieve bestuurders', 'eme-vzw-helper' ); ?>
            </div>
            <div class="vzw-group-score">
                <strong><?php echo count( $pubs ); ?></strong>
                <?php esc_html_e( 'Publicaties', 'eme-vzw-helper' ); ?>
            </div>
        </div>
    </div>
    <div style="flex-shrink:0">
        <select id="vzw-fiscal-year">
            <?php foreach ( range( (int) gmdate( 'Y' ), (int) gmdate( 'Y' ) - 10 ) as $y ): ?>
            <option value="<?php echo esc_attr( $y ); ?>" <?php echo selected( $y, $year, false ); ?>><?php echo esc_html( $y ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Quick-action buttons -->
<p>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=emevzwhelper-checklist&year=' . $year ) ); ?>" class="button button-primary">
        <?php esc_html_e( 'Naar checklist', 'eme-vzw-helper' ); ?>
    </a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=emevzwhelper-publications' ) ); ?>" class="button">
        <?php esc_html_e( 'Publicaties / Formulieren', 'eme-vzw-helper' ); ?>
    </a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=emevzwhelper-board' ) ); ?>" class="button">
        <?php esc_html_e( 'Bestuur beheren', 'eme-vzw-helper' ); ?>
    </a>
</p>

<!-- Deadlines -->
<?php if ( $deadlines ): ?>
<div class="vzw-form-box" style="margin-top:20px">
<h2><?php esc_html_e( 'Aankomende deadlines', 'eme-vzw-helper' ); ?></h2>
<p><?php esc_html_e( 'Deadline aanpassen? Ga naar Instellingen.', 'eme-vzw-helper' ); ?></p>
<ul class="vzw-deadline-list">
<?php foreach ( $deadlines as $dl ):
    $icon  = $dl['urgent'] ? '⚠️' : '📅';
    $cls   = $dl['urgent'] ? 'vzw-deadline-urgent' : ( $dl['warn'] ? 'vzw-deadline-warn' : 'vzw-deadline-ok' );
    $label = $dl['days'] < 0
        ? '<strong>' . esc_html__( 'Verlopen!', 'eme-vzw-helper' ) . '</strong>'
        : sprintf( // translators: %d is the number of days left until the deadline.
            esc_html__( 'nog %d dagen', 'eme-vzw-helper' ), $dl['days'] );
?>
<li class="<?php echo esc_attr( $cls ); ?>" style="flex-wrap:wrap;row-gap:4px">
    <span class="vzw-deadline-icon"><?php echo esc_html( $icon ); ?></span>
    <span style="flex:1">
        <strong><?php echo esc_html( $dl['label'] ); ?></strong>
        <?php if ( ! empty( $dl['link'] ) ): ?>
            &nbsp;<a href="<?php echo esc_url( $dl['link'] ); ?>" target="_blank" class="button button-small" style="font-size:11px;padding:0 6px;height:20px;line-height:20px"><?php echo esc_html( $dl['link_label'] ?? $dl['link'] ); ?></a>
        <?php endif; ?>
    </span>
    <span><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $dl['date'] ) ) ); ?></span>
    <span><?php echo esc_html( $label ); ?></span>
    <?php if ( ! empty( $dl['info'] ) ): ?>
    <span style="flex-basis:100%;padding-left:28px;font-size:12px;color:#646970;font-style:italic"><?php echo esc_html( $dl['info'] ); ?></span>
    <?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<!-- Recent publications -->
<?php if ( $pubs ): ?>
<h2 style="margin-top:24px"><?php esc_html_e( 'Recente publicaties', 'eme-vzw-helper' ); ?></h2>
<table class="vzw-pub-table">
<thead><tr>
    <th><?php esc_html_e( 'Datum AV', 'eme-vzw-helper' ); ?></th>
    <th><?php esc_html_e( 'Status', 'eme-vzw-helper' ); ?></th>
    <th></th>
</tr></thead>
<tbody>
<?php
$status_labels= [ 'draft' => __( 'Concept', 'eme-vzw-helper' ), 'submitted' => __( 'Neergelegd', 'eme-vzw-helper' ), 'published' => __( 'Gepubliceerd', 'eme-vzw-helper' ) ];
foreach ( array_slice( $pubs, 0, 5 ) as $pub ):
?>
<tr>
    <td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $pub->av_date ) ) ); ?></td>
    <td><span class="status-badge status-<?php echo esc_attr( $pub->status ); ?>"><?php echo esc_html( $status_labels[ $pub->status ] ?? $pub->status ); ?></span></td>
    <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=emevzwhelper-publications&edit=' . $pub->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Bekijk', 'eme-vzw-helper' ); ?></a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php endif; ?>

<div style="margin-top:20px">
<?php require(EME_VZW_DIR . 'templates/usefull_links.php'); ?>
</div>
