<?php
defined( 'ABSPATH' ) || exit;

$edit_id          = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$edit_m           = $edit_id ? EMEVzw\Config::get_board_member( $edit_id ) : null;
$all_members      = EMEVzw\Config::get_all_board_members();
$members          = array_filter( $all_members, fn( $m ) => (int) $m->is_active === 1 );
$members_inactive = array_filter( $all_members, fn( $m ) => (int) $m->is_active === 0 );

$roles = [
    'voorzitter'   => __( 'Voorzitter', 'eme-vzw-helper' ),
    'ondervoorzitter' => __( 'Ondervoorzitter', 'eme-vzw-helper' ),
    'secretaris'   => __( 'Secretaris', 'eme-vzw-helper' ),
    'penningmeester' => __( 'Penningmeester', 'eme-vzw-helper' ),
    'bestuurder'   => __( 'Bestuurder', 'eme-vzw-helper' ),
    'dagelijks bestuurder' => __( 'Dagelijks bestuurder', 'eme-vzw-helper' ),
];
?>
<div class="wrap vzw-wrap">
<h1><?php esc_html_e( 'Bestuur', 'eme-vzw-helper' ); ?></h1>

<!-- Board members grid -->
<?php if ( $members ): ?>
<div class="vzw-board-grid">
<?php foreach ( $members as $m ):
    $initials = strtoupper( mb_substr( $m->first_name, 0, 1 ) . mb_substr( $m->last_name, 0, 1 ) );
    $mandate  = $m->end_date ? date_i18n( 'd/m/Y', strtotime( $m->end_date ) ) : '—';
    $expired  = $m->end_date && strtotime( $m->end_date ) < time();
?>
<div class="vzw-board-card" style="<?= $expired ? 'border-color:#d63638' : '' ?>">
    <div class="avatar"><?= esc_html( $initials ) ?></div>
    <h4><?= esc_html( $m->last_name . ' ' . $m->first_name ) ?></h4>
    <span class="role-badge"><?= esc_html( $m->role ) ?></span>
    <div class="meta">
        <?php if ( $m->email ): ?>
        <div><?= esc_html( $m->email ) ?></div>
        <?php endif; ?>
        <div><?= esc_html( trim( $m->street . ' ' . $m->house_number . ', ' . $m->postal_code . ' ' . $m->city ) ) ?></div>
        <?php if ( $m->national_id ): ?>
        <div><?php esc_html_e( 'RR:', 'eme-vzw-helper' ); ?> <?= esc_html( $m->national_id ) ?></div>
        <?php endif; ?>
        <?php if ( $m->appointed_date ): ?>
        <div><?php esc_html_e( 'Benoemd:', 'eme-vzw-helper' ); ?> <?= esc_html( date_i18n( 'd/m/Y', strtotime( $m->appointed_date ) ) ) ?></div>
        <?php endif; ?>
        <div <?= $expired ? 'style="color:#d63638;font-weight:600"' : '' ?>>
            <?php esc_html_e( 'Mandaat t/m:', 'eme-vzw-helper' ); ?> <?= esc_html( $mandate ) ?>
            <?= $expired ? ' ⚠️' : '' ?>
        </div>
        <?php if ( $m->no_management_ban ): ?>
        <div style="color:#00a32a">✓ <?php esc_html_e( 'Geen bestuursverbod bevestigd', 'eme-vzw-helper' ); ?></div>
        <?php endif; ?>
    </div>
    <p style="margin:8px 0 0">
        <a href="<?= esc_url( admin_url( 'admin.php?page=eme-vzw-helper-board&edit=' . $m->id ) ) ?>" class="button button-small"><?php esc_html_e( 'Bewerk', 'eme-vzw-helper' ); ?></a>
        <button class="button button-small vzw-delete-board" data-id="<?= (int) $m->id ?>"><?php esc_html_e( 'Deactiveer', 'eme-vzw-helper' ); ?></button>
    </p>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ( $members_inactive ): ?>
<h2 style="margin-top:28px"><?php esc_html_e( 'Inactieve bestuurders', 'eme-vzw-helper' ); ?></h2>
<div class="vzw-board-grid">
<?php foreach ( $members_inactive as $m ):
    $initials = strtoupper( mb_substr( $m->first_name, 0, 1 ) . mb_substr( $m->last_name, 0, 1 ) );
    $display_name = $m->is_legal_entity ? esc_html( $m->legal_name ) : esc_html( $m->last_name . ' ' . $m->first_name );
?>
<div class="vzw-board-card" style="opacity:0.6;border-style:dashed">
    <div class="avatar" style="background:#999"><?= esc_html( $initials ) ?></div>
    <h4><?= $display_name ?></h4>
    <?php if ( $m->role ): ?>
    <span class="role-badge" style="background:#999"><?= esc_html( $m->role ) ?></span>
    <?php endif; ?>
    <div class="meta">
        <?php if ( $m->end_date ): ?>
        <div><?php esc_html_e( 'Einde mandaat:', 'eme-vzw-helper' ); ?> <?= esc_html( date_i18n( 'd/m/Y', strtotime( $m->end_date ) ) ) ?></div>
        <?php endif; ?>
    </div>
    <p style="margin:8px 0 0;display:flex;gap:4px;flex-wrap:wrap">
        <a href="<?= esc_url( admin_url( 'admin.php?page=eme-vzw-helper-board&edit=' . $m->id ) ) ?>" class="button button-small"><?php esc_html_e( 'Bewerk', 'eme-vzw-helper' ); ?></a>
        <button class="button button-small vzw-reactivate-board" data-id="<?= (int) $m->id ?>"><?php esc_html_e( 'Heractiveer', 'eme-vzw-helper' ); ?></button>
        <button class="button button-small vzw-purge-board" data-id="<?= (int) $m->id ?>" style="color:#d63638;border-color:#d63638"><?php esc_html_e( 'Verwijder', 'eme-vzw-helper' ); ?></button>
    </p>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add / Edit form -->
<div class="vzw-form-box">
<h2><?= $edit_m ? esc_html__( 'Bestuurder bewerken', 'eme-vzw-helper' ) : esc_html__( 'Bestuurder toevoegen', 'eme-vzw-helper' ) ?></h2>
<form id="vzw-board-form">
<input type="hidden" name="id" value="<?= $edit_m ? (int) $edit_m->id : 0 ?>">
<input type="hidden" name="is_active" value="<?= $edit_m ? (int) $edit_m->is_active : 1 ?>">

<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Rechtspersoon?', 'eme-vzw-helper' ); ?></label>
        <label style="display:flex;align-items:center;gap:6px;margin-top:6px">
            <input type="checkbox" id="is_legal_entity" name="is_legal_entity" value="1"
                   <?= checked( $edit_m->is_legal_entity ?? 0, 1 ) ?>>
            <?php esc_html_e( 'Dit is een rechtspersoon (geen natuurlijk persoon)', 'eme-vzw-helper' ); ?>
        </label>
    </div>
</div>

<div id="legal-entity-fields" style="<?= ( $edit_m->is_legal_entity ?? 0 ) ? '' : 'display:none' ?>">
<div class="vzw-row">
    <div class="vzw-field"><label><?php esc_html_e( 'Naam rechtspersoon', 'eme-vzw-helper' ); ?></label><input type="text" name="legal_name" value="<?= esc_attr( $edit_m->legal_name ?? '' ) ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Ondernemingsnummer', 'eme-vzw-helper' ); ?></label><input type="text" name="legal_number" value="<?= esc_attr( $edit_m->legal_number ?? '' ) ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Vaste vertegenwoordiger', 'eme-vzw-helper' ); ?></label><input type="text" name="legal_representative" value="<?= esc_attr( $edit_m->legal_representative ?? '' ) ?>"></div>
</div>
</div>

<div id="natural-person-fields" style="<?= ( $edit_m->is_legal_entity ?? 0 ) ? 'display:none' : '' ?>">
<div class="vzw-row">
    <div class="vzw-field"><label><?php esc_html_e( 'Voornaam', 'eme-vzw-helper' ); ?> *</label><input type="text" name="first_name" required value="<?= esc_attr( $edit_m->first_name ?? '' ) ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Achternaam', 'eme-vzw-helper' ); ?> *</label><input type="text" name="last_name" required value="<?= esc_attr( $edit_m->last_name ?? '' ) ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Rijksregisternummer', 'eme-vzw-helper' ); ?></label><input type="text" name="national_id" maxlength="15" value="<?= esc_attr( $edit_m->national_id ?? '' ) ?>"></div>
</div>
<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Geboorteplaats', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="birth_place" value="<?= esc_attr( $edit_m->birth_place ?? '' ) ?>">
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'Geboortedatum', 'eme-vzw-helper' ); ?></label>
        <input type="date" name="birth_date" value="<?= esc_attr( $edit_m->birth_date ?? '' ) ?>">
    </div>
</div>
</div>

<div class="vzw-row">
    <div class="vzw-field" style="flex:2"><label><?php esc_html_e( 'Straat', 'eme-vzw-helper' ); ?></label><input type="text" name="street" value="<?= esc_attr( $edit_m->street ?? '' ) ?>"></div>
    <div class="vzw-field" style="max-width:80px"><label><?php esc_html_e( 'Nr.', 'eme-vzw-helper' ); ?></label><input type="text" name="house_number" value="<?= esc_attr( $edit_m->house_number ?? '' ) ?>"></div>
    <div class="vzw-field" style="max-width:80px"><label><?php esc_html_e( 'Bus', 'eme-vzw-helper' ); ?></label><input type="text" name="house_bus" value="<?= esc_attr( $edit_m->house_bus ?? '' ) ?>"></div>
    <div class="vzw-field" style="max-width:90px"><label><?php esc_html_e( 'Postcode', 'eme-vzw-helper' ); ?></label><input type="text" name="postal_code" value="<?= esc_attr( $edit_m->postal_code ?? '' ) ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Gemeente', 'eme-vzw-helper' ); ?></label><input type="text" name="city" value="<?= esc_attr( $edit_m->city ?? '' ) ?>"></div>
</div>

<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Hoedanigheid', 'eme-vzw-helper' ); ?></label>
        <select name="role">
            <option value="" <?= selected( $edit_m->role ?? 'bestuurder', '', false ) ?>><?php esc_html_e( '— Geen functie —', 'eme-vzw-helper' ); ?></option>
            <?php foreach ( $roles as $v => $l ): ?>
            <option value="<?= esc_attr( $v ) ?>" <?= selected( $edit_m->role ?? 'bestuurder', $v, false ) ?>><?= esc_html( $l ) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="vzw-field"><label><?php esc_html_e( 'E-mail', 'eme-vzw-helper' ); ?></label><input type="email" name="email" value="<?= esc_attr( $edit_m->email ?? '' ) ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Telefoon', 'eme-vzw-helper' ); ?></label><input type="text" name="phone" value="<?= esc_attr( $edit_m->phone ?? '' ) ?>"></div>
</div>

<div class="vzw-row">
    <div class="vzw-field"><label><?php esc_html_e( 'Benoemd op', 'eme-vzw-helper' ); ?></label><input type="date" name="appointed_date" value="<?= esc_attr( $edit_m->appointed_date ?? '' ) ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Datum AV benoeming', 'eme-vzw-helper' ); ?></label><input type="date" name="appointment_av_date" value="<?= esc_attr( $edit_m->appointment_av_date ?? '' ) ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Einde mandaat', 'eme-vzw-helper' ); ?></label><input type="date" name="end_date" value="<?= esc_attr( $edit_m->end_date ?? '' ) ?>"></div>
    <div class="vzw-field" style="max-width:100px"><label><?php esc_html_e( 'Mandaatduur (j)', 'eme-vzw-helper' ); ?></label><input type="number" name="mandate_duration" min="0" value="<?= (int) ( $edit_m->mandate_duration ?? 0 ) ?>"></div>
</div>

<div class="vzw-row">
    <div class="vzw-field">
        <label style="display:flex;align-items:center;gap:6px">
            <input type="checkbox" name="no_management_ban" value="1" <?= checked( $edit_m->no_management_ban ?? 0, 1 ) ?>>
            <?php esc_html_e( 'Geen bestuursverbod gecontroleerd en bevestigd (wet 4 mei 2023)', 'eme-vzw-helper' ); ?>
        </label>
        <span class="description"><?php esc_html_e( 'Verplicht bij elke (her)benoeming – controleer via Centraal register van bestuursverboden.', 'eme-vzw-helper' ); ?></span>
    </div>
</div>

<p>
    <button type="submit" class="button button-primary submit-btn"><?php esc_html_e( 'Opslaan', 'eme-vzw-helper' ); ?></button>
    <?php if ( $edit_m ): ?><a href="<?= esc_url( admin_url( 'admin.php?page=eme-vzw-helper-board' ) ) ?>" class="button"><?php esc_html_e( 'Annuleren', 'eme-vzw-helper' ); ?></a><?php endif; ?>
</p>
</form>
</div>
</div>
