<?php
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
            'label'  => sprintf( __( 'Deadline Algemene Vergadering %d', 'eme-vzw-helper' ), $year+1 ),
            'date'   => $av_deadline->format( 'Y-m-d' ),
            'days'   => $diff->days,
            'urgent' => $diff->days <= 30,
        ];
    }
}

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
            <circle cx="60" cy="60" r="<?= $radius ?>"
                    fill="none" stroke="#f0f0f1" stroke-width="10"/>
            <circle id="score-ring-progress" cx="60" cy="60" r="<?= $radius ?>"
                    fill="none"
                    stroke="<?= esc_attr( $color ) ?>"
                    stroke-width="10"
                    stroke-linecap="round"
                    stroke-dasharray="<?= round( $circ, 2 ) ?>"
                    stroke-dashoffset="<?= round( $offset, 2 ) ?>"/>
        </svg>
        <div class="score-text">
            <span id="score-pct-text"><?= number_format( $pct, 1, ',', '' ) ?>%</span>
            <small><?php esc_html_e( 'compliant', 'eme-vzw-helper' ); ?></small>
        </div>
    </div>
    <div class="vzw-summary-info">
        <h2><?= esc_html( $config->vzw_name ) ?> <small style="font-size:13px;font-weight:400;color:#646970"><?= esc_html( $config->vzw_number ) ?></small></h2>
        <p><?= esc_html( trim( $config->street . ' ' . $config->house_number . ', ' . $config->postal_code . ' ' . $config->city ) ) ?></p>
        <div class="vzw-group-scores">
            <div class="vzw-group-score">
                <strong id="group-general-score"><?= $g_gen['max'] > 0 ? number_format( $g_gen['earned'] / $g_gen['max'] * 100, 1, ',', '' ) : '–' ?>%</strong>
                <?php esc_html_e( 'Algemeen', 'eme-vzw-helper' ); ?>
            </div>
            <div class="vzw-group-score">
                <strong id="group-year-score"><?= $g_yr['max'] > 0 ? number_format( $g_yr['earned'] / $g_yr['max'] * 100, 1, ',', '' ) : '–' ?>%</strong>
                <?php printf( esc_html__( 'Boekjaar %d', 'eme-vzw-helper' ), $year ); ?>
            </div>
            <div class="vzw-group-score">
                <strong><?= count( $board ) ?></strong>
                <?php esc_html_e( 'Actieve bestuurders', 'eme-vzw-helper' ); ?>
            </div>
            <div class="vzw-group-score">
                <strong><?= count( $pubs ) ?></strong>
                <?php esc_html_e( 'Publicaties', 'eme-vzw-helper' ); ?>
            </div>
        </div>
    </div>
    <div style="flex-shrink:0">
        <select id="vzw-fiscal-year">
            <?php foreach ( range( (int) gmdate( 'Y' ), (int) gmdate( 'Y' ) - 10 ) as $y ): ?>
            <option value="<?= $y ?>" <?= selected( $y, $year, false ) ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Quick-action buttons -->
<p>
    <a href="<?= esc_url( admin_url( 'admin.php?page=emevzwhelper-checklist&year=' . $year ) ) ?>" class="button button-primary">
        <?php esc_html_e( 'Naar checklist', 'eme-vzw-helper' ); ?>
    </a>
    <a href="<?= esc_url( admin_url( 'admin.php?page=emevzwhelper-publications' ) ) ?>" class="button">
        <?php esc_html_e( 'Publicaties / Formulieren', 'eme-vzw-helper' ); ?>
    </a>
    <a href="<?= esc_url( admin_url( 'admin.php?page=emevzwhelper-board' ) ) ?>" class="button">
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
    $cls   = $dl['urgent'] ? 'vzw-deadline-urgent' : 'vzw-deadline-warn';
    $label = $dl['days'] < 0
        ? '<strong>' . esc_html__( 'Verlopen!', 'eme-vzw-helper' ) . '</strong>'
        : sprintf( esc_html__( 'nog %d dagen', 'eme-vzw-helper' ), $dl['days'] );
?>
<li class="<?= esc_attr( $cls ) ?>">
    <span class="vzw-deadline-icon"><?= $icon ?></span>
    <span style="flex:1"><strong><?= esc_html( $dl['label'] ) ?></strong></span>
    <span><?= esc_html( date_i18n( 'd/m/Y', strtotime( $dl['date'] ) ) ) ?></span>
    <span><?= $label ?></span>
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
$status_labels= [ 'draft' => __( 'Concept', 'eme-vzw-helper' ), 'submitted' => __( 'Ingediend', 'eme-vzw-helper' ), 'published' => __( 'Gepubliceerd', 'eme-vzw-helper' ) ];
foreach ( array_slice( $pubs, 0, 5 ) as $pub ):
?>
<tr>
    <td><?= esc_html( date_i18n( 'd/m/Y', strtotime( $pub->av_date ) ) ) ?></td>
    <td><span class="status-badge status-<?= esc_attr( $pub->status ) ?>"><?= esc_html( $status_labels[ $pub->status ] ?? $pub->status ) ?></span></td>
    <td><a href="<?= esc_url( admin_url( 'admin.php?page=emevzwhelper-publications&edit=' . $pub->id ) ) ?>" class="button button-small"><?php esc_html_e( 'Bekijk', 'eme-vzw-helper' ); ?></a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php endif; ?>
</div>
