<?php
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
defined( 'ABSPATH' ) || exit;

$edit_id          = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$edit_m           = $edit_id ? EMEVzw\Config::get_board_member( $edit_id ) : null;
$all_members      = EMEVzw\Config::get_all_board_members();
$members          = array_filter( $all_members, fn( $m ) => (int) $m->is_active === 1 );
$members_inactive = array_filter( $all_members, fn( $m ) => (int) $m->is_active === 0 );

$legal_capacities = EMEVzw\Config::legal_capacities();
$function_titles  = EMEVzw\Config::function_titles();
?>
<div class="wrap vzw-wrap">
<h1><?php esc_html_e( 'Bestuur', 'eme-vzw-helper' ); ?></h1>

<!-- Board members grid -->
<?php if ( $members ): ?>
<div class="vzw-board-grid">
<?php foreach ( $members as $m ):
    $initials = strtoupper( mb_substr( $m->first_name, 0, 1 ) . mb_substr( $m->last_name, 0, 1 ) );
    $mandate_end = EMEVzw\Config::mandate_end_of( $m );
    $mandate  = $mandate_end ? date_i18n( 'd/m/Y', strtotime( $mandate_end ) ) : '—';
    $expired  = $mandate_end && $mandate_end < current_time( 'Y-m-d' );
    $automatic  = empty($m->end_date) && !empty($mandate_end);
?>
<div class="vzw-board-card" style="<?php echo $expired ? 'border-color:#d63638' : ''; ?>">
    <div class="avatar"><?php echo esc_html( $initials ); ?></div>
    <h4><?php echo esc_html( $m->last_name . ' ' . $m->first_name ); ?></h4>
    <span class="role-badge"><?php echo esc_html( EMEVzw\Config::legal_capacity_label($m->legal_capacity) ); ?><?php echo $m->function_title ? ' · ' . esc_html( EMEVzw\Config::function_title_label($m->function_title) ) : ''; ?></span>
    <div class="meta">
        <?php if ( $m->email ): ?>
        <div><?php echo esc_html( $m->email ); ?></div>
        <?php endif; ?>
        <div><?php echo esc_html( trim( $m->street . ' ' . $m->house_number . ', ' . $m->postal_code . ' ' . $m->city ) ); ?></div>
        <?php if ( $m->national_id ): ?>
        <div><?php esc_html_e( 'RR:', 'eme-vzw-helper' ); ?> <?php echo esc_html( $m->national_id ); ?></div>
        <?php endif; ?>
        <?php if ( $m->appointed_date ): ?>
        <div><?php esc_html_e( 'Benoemd:', 'eme-vzw-helper' ); ?> <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $m->appointed_date ) ) ); ?></div>
        <?php endif; ?>
        <div <?php echo $expired ? 'style="color:#d63638;font-weight:600"' : ''; ?>>
            <?php esc_html_e( 'Mandaat t/m:', 'eme-vzw-helper' ); ?> <?php echo esc_html( $mandate ); ?>
            <?php echo $expired ? ' ⚠️' : ''; ?>
            <?php echo $automatic ? esc_html_e( '(automatisch gezet, zie Instellingen)', 'eme-vzw-helper' ) : ''; ?>
        </div>
        <?php if ( $m->no_management_ban ): ?>
        <div style="color:#00a32a">✓ <?php esc_html_e( 'Geen bestuursverbod bevestigd', 'eme-vzw-helper' ); ?></div>
        <?php endif; ?>
    </div>
    <p style="margin:8px 0 0">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=emevzwhelper-board&edit=' . $m->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Bewerk', 'eme-vzw-helper' ); ?></a>
        <button class="button button-small vzw-delete-board" data-id="<?php echo (int) $m->id; ?>"><?php esc_html_e( 'Deactiveer', 'eme-vzw-helper' ); ?></button>
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
    <div class="avatar" style="background:#999"><?php echo esc_html( $initials ); ?></div>
    <h4><?php echo esc_html( $display_name ); ?></h4>
    <?php if ( $m->legal_capacity ): ?>
    <span class="role-badge" style="background:#999"><?php echo esc_html( $m->legal_capacity ); ?><?php echo $m->function_title ? ' · ' . esc_html( $m->function_title ) : ''; ?></span>
    <?php endif; ?>
    <div class="meta">
        <?php if ( $m->end_date ): ?>
        <div><?php esc_html_e( 'Einde mandaat:', 'eme-vzw-helper' ); ?> <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $m->end_date ) ) ); ?></div>
        <?php endif; ?>
    </div>
    <p style="margin:8px 0 0;display:flex;gap:4px;flex-wrap:wrap">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=emevzwhelper-board&edit=' . $m->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Bewerk', 'eme-vzw-helper' ); ?></a>
        <button class="button button-small vzw-reactivate-board" data-id="<?php echo (int) $m->id; ?>"><?php esc_html_e( 'Heractiveer', 'eme-vzw-helper' ); ?></button>
        <button class="button button-small vzw-purge-board" data-id="<?php echo (int) $m->id; ?>" style="color:#d63638;border-color:#d63638"><?php esc_html_e( 'Verwijder', 'eme-vzw-helper' ); ?></button>
    </p>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add / Edit form -->
<div class="vzw-form-box">
<h2><?php echo $edit_m ? esc_html__( 'Bestuurder bewerken', 'eme-vzw-helper' ) : esc_html__( 'Bestuurder toevoegen', 'eme-vzw-helper' ); ?></h2>
<form id="vzw-board-form">
<input type="hidden" name="id" value="<?php echo $edit_m ? (int) $edit_m->id : 0; ?>">
<input type="hidden" name="is_active" value="<?php echo $edit_m ? (int) $edit_m->is_active : 1; ?>">

<?php if (!$edit_m): ?>
<select id='choose_eme_person' name='choose_eme_person'
    data-placeholder="<?php esc_attr_e( 'Select a person', 'eme-vzw-helper' ); ?>"
    class="emevzw_snapselect_searchemeperson">
</select>
<?php endif;?>
<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Rechtspersoon?', 'eme-vzw-helper' ); ?></label>
        <label style="display:flex;align-items:center;gap:6px;margin-top:6px">
            <input type="checkbox" id="is_legal_entity" name="is_legal_entity" value="1"
                   <?php echo checked( $edit_m->is_legal_entity ?? 0, 1 ); ?>>
            <?php esc_html_e( 'Dit is een rechtspersoon (geen natuurlijk persoon)', 'eme-vzw-helper' ); ?>
        </label>
    </div>
</div>

<div id="legal-entity-fields" style="<?php echo ( $edit_m->is_legal_entity ?? 0 ) ? '' : 'display:none'; ?>">
<div class="vzw-row">
    <div class="vzw-field"><label><?php esc_html_e( 'Naam rechtspersoon', 'eme-vzw-helper' ); ?></label><input type="text" name="legal_name" value="<?php echo esc_attr( $edit_m->legal_name ?? '' ); ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Ondernemingsnummer', 'eme-vzw-helper' ); ?></label><input type="text" name="legal_number" value="<?php echo esc_attr( $edit_m->legal_number ?? '' ); ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Vaste vertegenwoordiger', 'eme-vzw-helper' ); ?></label><input type="text" name="legal_representative" value="<?php echo esc_attr( $edit_m->legal_representative ?? '' ); ?>"></div>
</div>
</div>

<div id="natural-person-fields" style="<?php echo ( $edit_m->is_legal_entity ?? 0 ) ? 'display:none' : ''; ?>">
<div class="vzw-row">
    <div class="vzw-field"><label><?php esc_html_e( 'Voornaam', 'eme-vzw-helper' ); ?> *</label><input type="text" name="first_name" required value="<?php echo esc_attr( $edit_m->first_name ?? '' ); ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Achternaam', 'eme-vzw-helper' ); ?> *</label><input type="text" name="last_name" required value="<?php echo esc_attr( $edit_m->last_name ?? '' ); ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Rijksregisternummer', 'eme-vzw-helper' ); ?></label><input type="text" name="national_id" maxlength="15" value="<?php echo esc_attr( $edit_m->national_id ?? '' ); ?>"></div>
</div>
<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Geboorteplaats', 'eme-vzw-helper' ); ?></label>
        <input type="text" name="birth_place" value="<?php echo esc_attr( $edit_m->birth_place ?? '' ); ?>">
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'Geboortedatum', 'eme-vzw-helper' ); ?></label>
        <input type="date" name="birth_date" value="<?php echo esc_attr( $edit_m->birth_date ?? '' ); ?>">
    </div>
</div>
</div>

<div class="vzw-row">
    <div class="vzw-field" style="flex:2"><label><?php esc_html_e( 'Straat', 'eme-vzw-helper' ); ?></label><input type="text" name="street" value="<?php echo esc_attr( $edit_m->street ?? '' ); ?>"></div>
    <div class="vzw-field" style="max-width:80px"><label><?php esc_html_e( 'Nr.', 'eme-vzw-helper' ); ?></label><input type="text" name="house_number" value="<?php echo esc_attr( $edit_m->house_number ?? '' ); ?>"></div>
    <div class="vzw-field" style="max-width:80px"><label><?php esc_html_e( 'Bus', 'eme-vzw-helper' ); ?></label><input type="text" name="house_bus" value="<?php echo esc_attr( $edit_m->house_bus ?? '' ); ?>"></div>
    <div class="vzw-field" style="max-width:90px"><label><?php esc_html_e( 'Postcode', 'eme-vzw-helper' ); ?></label><input type="text" name="postal_code" value="<?php echo esc_attr( $edit_m->postal_code ?? '' ); ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Gemeente', 'eme-vzw-helper' ); ?></label><input type="text" name="city" value="<?php echo esc_attr( $edit_m->city ?? '' ); ?>"></div>
</div>

<div class="vzw-row">
    <div class="vzw-field">
        <label><?php esc_html_e( 'Wettelijke hoedanigheid', 'eme-vzw-helper' ); ?> *</label>
        <select name="legal_capacity" required>
            <?php foreach ( $legal_capacities as $v => $l ): ?>
            <option value="<?php echo esc_attr( $v ); ?>" <?php echo selected( $edit_m->legal_capacity ?? 'bestuurder', $v, false ); ?>><?php echo esc_html( $l ); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="description"><?php esc_html_e( 'Officiële benaming voor wettelijke documenten (Formulier I/II, verklaring bestuursverbod).', 'eme-vzw-helper' ); ?></span>
    </div>
    <div class="vzw-field">
        <label><?php esc_html_e( 'Interne functie', 'eme-vzw-helper' ); ?></label>
        <select name="function_title">
            <?php foreach ( $function_titles as $v => $l ): ?>
            <option value="<?php echo esc_attr( $v ); ?>" <?php echo selected( $edit_m->function_title ?? '', $v, false ); ?>><?php echo esc_html( $l ); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="description"><?php esc_html_e( 'Optioneel — bepaalt wie als voorzitter/secretaris wordt gebruikt bij vertegenwoordiging.', 'eme-vzw-helper' ); ?></span>
    </div>
    <div class="vzw-field"><label><?php esc_html_e( 'E-mail', 'eme-vzw-helper' ); ?></label><input type="email" name="email" value="<?php echo esc_attr( $edit_m->email ?? '' ); ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Telefoon', 'eme-vzw-helper' ); ?></label><input type="text" name="phone" value="<?php echo esc_attr( $edit_m->phone ?? '' ); ?>"></div>
</div>

<div class="vzw-row">
    <div class="vzw-field"><label><?php esc_html_e( 'Benoemd op', 'eme-vzw-helper' ); ?></label><input type="date" name="appointed_date" value="<?php echo esc_attr( $edit_m->appointed_date ?? '' ); ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Datum AV benoeming', 'eme-vzw-helper' ); ?></label><input type="date" name="appointment_av_date" value="<?php echo esc_attr( $edit_m->appointment_av_date ?? '' ); ?>"></div>
    <div class="vzw-field"><label><?php esc_html_e( 'Einde mandaat', 'eme-vzw-helper' ); ?></label><input type="date" name="end_date" value="<?php echo esc_attr( $edit_m->end_date ?? '' ); ?>"><span class="description"><?php esc_html_e( 'Leeg laten = einddatum uit de instellingen.', 'eme-vzw-helper' ); ?></span></div>
</div>

<div class="vzw-row">
    <div class="vzw-field">
        <label style="display:flex;align-items:center;gap:6px">
            <input type="checkbox" name="no_management_ban" value="1" <?php echo checked( $edit_m->no_management_ban ?? 0, 1 ); ?>>
            <?php esc_html_e( 'Geen bestuursverbod gecontroleerd en bevestigd (wet 4 mei 2023)', 'eme-vzw-helper' ); ?>
        </label>
        <span class="description"><?php _e( 'Verplicht bij elke (her)benoeming – controleer via <a target="_blank" href="https://justban.just.fgov.be/">Centraal register van bestuursverboden</a>.', 'eme-vzw-helper' ); ?></span>
    </div>
</div>

<p>
    <button type="submit" class="button button-primary submit-btn"><?php esc_html_e( 'Opslaan', 'eme-vzw-helper' ); ?></button>
    <?php if ( $edit_m ): ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=emevzwhelper-board' ) ); ?>" class="button"><?php esc_html_e( 'Annuleren', 'eme-vzw-helper' ); ?></a><?php endif; ?>
</p>
</form>
</div>
</div>
