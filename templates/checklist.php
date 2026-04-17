<?php
defined( 'ABSPATH' ) || exit;

$year        = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) gmdate( 'Y' );
$definitions = EMEVzw\Checklist::get_definitions();
$answers     = EMEVzw\Checklist::get_answers( $year );
$score       = EMEVzw\Checklist::get_score( $year );

$groups = [
    'general'      => __( 'Algemeen na te kijken', 'eme-vzw-helper' ),
    'current_year' => sprintf( __( 'Dit boekjaar nog na te kijken (%d)', 'eme-vzw-helper' ), $year ),
];

$status_labels = [
    'yes'     => __( 'Ja / OK', 'eme-vzw-helper' ),
    'no'      => __( 'Nee / NOK', 'eme-vzw-helper' ),
    'na'      => __( 'N.v.t.', 'eme-vzw-helper' ),
    'pending' => __( 'Te controleren', 'eme-vzw-helper' ),
];

$pct    = $score['score_pct'];
$radius = 48;
$circ   = 2 * M_PI * $radius;
$offset = $circ * ( 1 - $pct / 100 );
$color  = $pct >= 80 ? '#00a32a' : ( $pct >= 50 ? '#dba617' : '#d63638' );
?>
<div class="wrap vzw-wrap">
<h1>
    <?php esc_html_e( 'Compliance Checklist', 'eme-vzw-helper' ); ?>
    <select id="vzw-fiscal-year" style="margin-left:12px;">
        <?php foreach ( range( (int) gmdate( 'Y' ), (int) gmdate( 'Y' ) - 10 ) as $y ): ?>
        <option value="<?= $y ?>" <?= selected( $y, $year, false ) ?>><?= $y ?></option>
        <?php endforeach; ?>
    </select>
</h1>

<!-- Compact score header -->
<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:14px 20px">
    <div class="vzw-score-ring" style="width:80px;height:80px">
        <svg width="80" height="80" viewBox="0 0 80 80">
            <circle cx="40" cy="40" r="32" fill="none" stroke="#f0f0f1" stroke-width="8"/>
            <circle id="score-ring-progress" cx="40" cy="40" r="32"
                    fill="none"
                    stroke="<?= esc_attr( $color ) ?>"
                    stroke-width="8"
                    stroke-linecap="round"
                    stroke-dasharray="<?= round( 2 * M_PI * 32, 2 ) ?>"
                    stroke-dashoffset="<?= round( 2 * M_PI * 32 * ( 1 - $pct / 100 ), 2 ) ?>"
                    style="transform:rotate(-90deg);transform-origin:center"/>
        </svg>
        <div class="score-text" style="font-size:16px">
            <span id="score-pct-text"><?= number_format( $pct, 1, ',', '' ) ?>%</span>
            <small><?php esc_html_e( 'OK', 'eme-vzw-helper' ); ?></small>
        </div>
    </div>
    <div>
        <strong><span id="earned-points-text"><?= (int) $score['earned_points'] ?></span> / <?= (int) $score['max_points'] ?> <?php esc_html_e( 'punten', 'eme-vzw-helper' ); ?></strong>
        <br>
        <?php
        $todo = 0;
        foreach ( $definitions as $key => $item ) {
            if ( ( $answers[ $key ]['status'] ?? 'pending' ) === 'pending' ) $todo++;
        }
        esc_html_e( 'Aantal openstaande vragen: ', 'eme-vzw-helper' );
        printf( '<span id="no_answer">%d</span>', $todo );
        ?>
    </div>
    <div style="flex:1"></div>
    <div style="font-size:12px;color:#646970;text-align:right">
        <?php esc_html_e( 'Klik op een antwoord om op te slaan. Subvragen worden automatisch meegestuurd.', 'eme-vzw-helper' ); ?>
    </div>
</div>

<!-- Checklist groups -->
<?php foreach ( $groups as $group_key => $group_label ):
    $items_in_group = array_filter( $definitions, fn( $i ) => $i['group'] === $group_key );
    $g_score        = $score['by_group'][ $group_key ];
    $g_pct          = $g_score['max'] > 0 ? round( $g_score['earned'] / $g_score['max'] * 100, 1 ) : 0;
?>
<div class="vzw-checklist-group">
    <div class="vzw-checklist-group-header">
        <span><?= esc_html( $group_label ) ?></span>
        <span style="font-size:12px;font-weight:400" id="group-<?= esc_attr($group_key) ?>-score"><?= $g_pct ?>% &nbsp;(<?= (int) $g_score['earned'] ?>/<?= (int) $g_score['max'] ?> pt)</span>
    </div>

    <?php foreach ( $items_in_group as $key => $item ):
        $current_status  = $answers[ $key ]['status']      ?? 'pending';
        $saved_note      = $answers[ $key ]['notes']        ?? '';
        // Sub-answers are stored as a JSON array in sub_answers
        $saved_subs_json = $answers[ $key ]['sub_answers']  ?? '';
        $saved_subs      = [];
        if ( $saved_subs_json ) {
            $decoded = json_decode( $saved_subs_json, true );
            if ( is_array( $decoded ) ) $saved_subs = $decoded;
        }
    ?>
    <div class="vzw-check-item status-<?= esc_attr( $current_status ) ?>" data-key="<?= esc_attr( $key ) ?>">
        <div class="vzw-check-label">
            <?= esc_html( $item['label'] ) ?>
            <?php if ( $item['weight'] > 0 ): ?>
            <span class="weight-badge"><?= (int) $item['weight'] ?>pt</span>
            <?php else: ?>
            <span class="weight-badge" title="<?php esc_attr_e( 'Informatief – telt niet mee in score', 'eme-vzw-helper' ); ?>">–</span>
            <?php endif; ?>

            <?php if ( $item['help'] ): ?>
            <div class="help-text"><?= esc_html( $item['help'] ) ?></div>
            <?php endif; ?>

            <?php if ( !empty($item['sub_questions']) ): ?>
            <details>
                <summary><?php printf( esc_html( _n( '%d subvraag', '%d subvragen', count( $item['sub_questions'] ), 'eme-vzw-helper' ) ), count( $item['sub_questions'] ) ); ?>
                </summary>
                <ul id="subs-<?= esc_attr( $key ) ?>" class="vzw-check-subs" style="list-style:none;margin:6px 0 0 0;padding:0">
                    <?php foreach ( $item['sub_questions'] as $sq_idx => $sq ):
                        $sq_label = is_array( $sq ) ? $sq['label'] : $sq;
                        $sq_type  = is_array( $sq ) ? ( $sq['type'] ?? 'text' ) : 'text';
                        $sq_val   = $saved_subs[ $sq_idx ] ?? '';
                    ?>
                    <li style="margin-bottom:6px;display:flex;align-items:center;gap:8px;flex-wrap:wrap" data-subidx="<?= (int) $sq_idx ?>">
                        <span style="font-size:12px;color:#444;flex:1;min-width:200px"><?= esc_html( $sq_label ) ?></span>
                        <?php if ( $sq_type === 'yesno' ): ?>
                            <label style="font-size:12px;display:flex;align-items:center;gap:4px;cursor:pointer">
                                <input type="radio" class="vzw-sub-input" data-subidx="<?= (int) $sq_idx ?>" name="sub_<?= $key . '_' . (int) $sq_idx ?>" value="ja" <?= $sq_val === 'ja' ? 'checked' : '' ?>> Ja
                            </label>
                            <label style="font-size:12px;display:flex;align-items:center;gap:4px;cursor:pointer">
                                <input type="radio" class="vzw-sub-input" data-subidx="<?= (int) $sq_idx ?>" name="sub_<?= $key . '_' . (int) $sq_idx ?>" value="nee" <?= $sq_val === 'nee' ? 'checked' : '' ?>> Nee
                            </label>
                        <?php elseif ( $sq_type === 'date' ): ?>
                            <input type="date" class="vzw-sub-input" data-subidx="<?= (int) $sq_idx ?>" value="<?= esc_attr( $sq_val ) ?>" style="font-size:12px;padding:2px 4px">
                        <?php elseif ( $sq_type === 'number' ): ?>
                            <input type="number" class="vzw-sub-input" data-subidx="<?= (int) $sq_idx ?>" value="<?= esc_attr( $sq_val ) ?>" min="0" style="font-size:12px;padding:2px 4px;width:80px">
                        <?php else: ?>
                            <input type="text" class="vzw-sub-input" data-subidx="<?= (int) $sq_idx ?>" value="<?= esc_attr( $sq_val ) ?>" style="font-size:12px;padding:2px 4px;width:200px">
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>
        </div>

        <div class="vzw-check-actions">
            <input type="text" class="vzw-check-note-input"
                   placeholder="<?php esc_attr_e( 'Notitie…', 'eme-vzw-helper' ); ?>"
                   value="<?= esc_attr( $saved_note ) ?>"
                   style="width:140px">
            <?php foreach ( $status_labels as $status_val => $status_label ): ?>
            <button type="button"
                    class="vzw-status-btn <?= $current_status === $status_val ? 'active-' . esc_attr( $status_val ) : '' ?>"
                    data-status="<?= esc_attr( $status_val ) ?>">
                <?= esc_html( $status_label ) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

</div>
