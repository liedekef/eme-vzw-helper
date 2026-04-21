<?php
defined( 'ABSPATH' ) || exit;
$edit_id      = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$pub          = $edit_id ? EMEVzw\Publications::get( $edit_id ) : null;
$board_changes= $edit_id ? EMEVzw\Publications::get_board_changes( $edit_id ) : [];
$all_pubs     = EMEVzw\Publications::get_all();
$all_members  = EMEVzw\Config::get_all_board_members();
$board_members_active   = array_filter( $all_members, fn( $m ) => (int) $m->is_active === 1 );
$board_members_inactive = array_filter( $all_members, fn( $m ) => (int) $m->is_active === 0 );

$status_labels = [
    'draft'     => __( 'Concept', 'eme-vzw-helper' ),
    'submitted' => __( 'Ingediend', 'eme-vzw-helper' ),
    'published' => __( 'Gepubliceerd', 'eme-vzw-helper' ),
];
?>
<div class="wrap vzw-wrap">
    <h1><?php esc_html_e( 'Wettelijke Publicaties', 'eme-vzw-helper' ); ?></h1>
    <p style="color:#646970">
        <?php esc_html_e( 'Maak Formulieren I & II en verklaring op eer bestuursverbod voor neerlegging bij de griffie.', 'eme-vzw-helper' ); ?>
    </p>
    <?php if (!$pub) : ?>
    <a href="<?= esc_url( admin_url( 'admin.php?page=emevzwhelper-publications' ) ) ?>" class="button" style="margin-bottom:16px">
        + <?php esc_html_e( 'Nieuwe publicatie', 'eme-vzw-helper' ); ?>
    </a>
    <?php endif; ?>

    <div class="vzw-form-box">
        <h2><?= $pub ? esc_html__( 'Publicatie bewerken', 'eme-vzw-helper' ) : esc_html__( 'Nieuwe publicatie aanmaken', 'eme-vzw-helper' ) ?></h2>
        <form id="vzw-pub-form">
            <input type="hidden" name="pub_id" value="<?= $pub ? (int) $pub->id : '' ?>">

            <div class="vzw-row">
                <div class="vzw-field">
                    <label><?php esc_html_e( 'Datum Algemene Vergadering', 'eme-vzw-helper' ); ?> *</label>
                    <input type="date" name="av_date" required value="<?= esc_attr( $pub->av_date ?? '' ) ?>">
                </div>
                <div class="vzw-field">
                    <label><?php esc_html_e( 'Locatie AV', 'eme-vzw-helper' ); ?></label>
                    <input type="text" name="av_location" value="<?= esc_attr( $pub->av_location ?? '' ) ?>" placeholder="bv. Zetel vereniging, Gemeentehuis, ...">
                </div>
                <div class="vzw-field">
                    <label><?php esc_html_e( 'Status', 'eme-vzw-helper' ); ?></label>
                    <select name="pub_status">
                        <?php foreach ( $status_labels as $v => $l ): ?>
                        <option value="<?= esc_attr( $v ) ?>" <?= selected( $pub->status ?? 'draft', $v, false ) ?>><?= esc_html( $l ) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="vzw-row">
                <div class="vzw-field">
                    <label><?php esc_html_e( 'Ingediend op', 'eme-vzw-helper' ); ?></label>
                    <input type="date" name="submitted_date" value="<?= esc_attr( $pub->submitted_date ?? '' ) ?>">
                </div>
                <div class="vzw-field">
                    <label><?php esc_html_e( 'Gepubliceerd in BS op', 'eme-vzw-helper' ); ?></label>
                    <input type="date" name="published_date" value="<?= esc_attr( $pub->published_date ?? '' ) ?>">
                </div>
            </div>

            <div class="vzw-row">
                <div class="vzw-field">
                    <label><?php esc_html_e( 'BS betaaldatum', 'eme-vzw-helper' ); ?></label>
                    <input type="date" name="bs_payment_date" value="<?= esc_attr( $pub->bs_payment_date ?? '' ) ?>">
                </div>
                <div class="vzw-field">
                    <label><?php esc_html_e( 'BS betaalbedrag (€)', 'eme-vzw-helper' ); ?></label>
                    <input type="number" name="bs_payment_amount" step="0.01" min="0" value="<?= esc_attr( $pub->bs_payment_amount ?? '' ) ?>">
                </div>
                <div class="vzw-field">
                    <label><?php esc_html_e( 'BS betalingsreferentie', 'eme-vzw-helper' ); ?></label>
                    <input type="text" name="bs_payment_ref" value="<?= esc_attr( $pub->bs_payment_ref ?? '' ) ?>">
                </div>
            </div>

            <div class="vzw-field" style="margin-bottom:12px">
                <label><?php esc_html_e( 'Notities', 'eme-vzw-helper' ); ?></label>
                <textarea name="notes"><?= esc_textarea( $pub->notes ?? '' ) ?></textarea>
            </div>

            <!-- BESTUURSWIJZIGINGEN -->
            <h3 style="margin-top:16px"><?php esc_html_e( 'Bestuurswijzigingen', 'eme-vzw-helper' ); ?></h3>
            <p class="description"><?php esc_html_e( 'B = Benoeming, E = Einde/Ontslag', 'eme-vzw-helper' ); ?></p>
            <div id="vzw-board-changes-list">
                <?php foreach ( $board_changes as $bc ): ?>
                <div class="vzw-board-change-row vzw-row" style="background:#f9f9f9;padding:8px;border-radius:4px;margin-bottom:6px">
                    <div class="vzw-field">
                        <label><?php esc_html_e( 'Bestuurder', 'eme-vzw-helper' ); ?></label>
                        <select class="bc-board-id">
                            <option value="">— Kies —</option>
                            <optgroup label="<?php esc_attr_e( 'Actief', 'eme-vzw-helper' ); ?>">
                            <?php foreach ( $board_members_active as $m ): ?>
                            <option value="<?= (int) $m->id ?>" <?= selected( $m->id, $bc->board_id ?? 0, false ) ?>>
                                <?= esc_html( $m->last_name . ' ' . $m->first_name . ( $m->function_title ? ' (' . $m->function_title . ')' : ( $m->legal_capacity ? ' (' . $m->legal_capacity . ')' : '' ) ) ) ?>
                            </option>
                            <?php endforeach; ?>
                            </optgroup>
                            <?php if ( $board_members_inactive ): ?>
                            <optgroup label="<?php esc_attr_e( 'Inactief', 'eme-vzw-helper' ); ?>">
                            <?php foreach ( $board_members_inactive as $m ): ?>
                            <option value="<?= (int) $m->id ?>" <?= selected( $m->id, $bc->board_id ?? 0, false ) ?>>
                                <?= esc_html( $m->last_name . ' ' . $m->first_name . ( $m->function_title ? ' (' . $m->function_title . ')' : ( $m->legal_capacity ? ' (' . $m->legal_capacity . ')' : '' ) ) ) ?>
                            </option>
                            <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="vzw-field" style="max-width:100px">
                        <label><?php esc_html_e( 'Type', 'eme-vzw-helper' ); ?></label>
                        <select class="bc-change-type">
                            <option value="B" <?= selected( $bc->change_type ?? 'B', 'B', false ) ?>>Benoeming</option>
                            <option value="E" <?= selected( $bc->change_type ?? '', 'E', false ) ?>>Ontslag</option>
                        </select>
                    </div>
                    <div class="vzw-field" style="max-width:160px">
                        <label><?php esc_html_e( 'Datum ingang', 'eme-vzw-helper' ); ?></label>
                        <input type="date" class="bc-effective-date" value="<?= esc_attr( $bc->effective_date ?? '' ) ?>">
                    </div>
                    <div style="padding-top:22px"><button type="button" class="button button-small vzw-remove-row">✕</button></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="vzw-board-change-template" class="vzw-board-change-row vzw-row" style="background:#f9f9f9;padding:8px;border-radius:4px;margin-bottom:6px;display:none">
                <div class="vzw-field">
                    <label><?php esc_html_e( 'Bestuurder', 'eme-vzw-helper' ); ?></label>
                    <select class="bc-board-id">
                        <option value=""><?php esc_html_e( '— Kies —', 'eme-vzw-helper' ); ?></option>
                        <optgroup label="<?php esc_attr_e( 'Actief', 'eme-vzw-helper' ); ?>">
                        <?php foreach ( $board_members_active as $m ): ?>
                        <option value="<?= (int) $m->id ?>"><?= esc_html( $m->last_name . ' ' . $m->first_name . ( $m->function_title ? ' (' . $m->function_title . ')' : ( $m->legal_capacity ? ' (' . $m->legal_capacity . ')' : '' ) ) ) ?></option>
                        <?php endforeach; ?>
                        </optgroup>
                        <?php if ( $board_members_inactive ): ?>
                        <optgroup label="<?php esc_attr_e( 'Inactief', 'eme-vzw-helper' ); ?>">
                        <?php foreach ( $board_members_inactive as $m ): ?>
                        <option value="<?= (int) $m->id ?>"><?= esc_html( $m->last_name . ' ' . $m->first_name . ( $m->function_title ? ' (' . $m->function_title . ')' : ( $m->legal_capacity ? ' (' . $m->legal_capacity . ')' : '' ) ) ) ?></option>
                        <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="vzw-field" style="max-width:100px">
                    <label><?php esc_html_e( 'Type', 'eme-vzw-helper' ); ?></label>
                    <select class="bc-change-type">
                        <option value="B">Benoeming</option><option value="E">Ontslag</option>
                    </select>
                </div>
                <div class="vzw-field" style="max-width:160px">
                    <label><?php esc_html_e( 'Datum ingang', 'eme-vzw-helper' ); ?></label>
                    <input type="date" class="bc-effective-date">
                </div>
                <div style="padding-top:22px"><button type="button" class="button button-small vzw-remove-row">✕</button></div>
            </div>
            <button type="button" id="btn-add-board-change" class="button" style="margin-bottom:16px">+ Bestuurder toevoegen</button>

            <!-- STATUTENWIJZIGINGEN (ENKEL VELD) -->
            <h3 style="margin-top:20px"><?php esc_html_e( 'Statutenwijzigingen', 'eme-vzw-helper' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Vul hier de volledige wettelijke tekst in die in het Staatsblad moet verschijnen (laat leeg indien geen wijziging).', 'eme-vzw-helper' ); ?></p>
            <textarea name="statute_text" rows="8" style="width:100%;font-family:monospace"><?= esc_textarea( $pub->statute_text ?? '' ) ?></textarea>

            <p style="margin-top:16px">
                <button type="submit" class="button button-primary submit-btn"><?php esc_html_e( 'Opslaan', 'eme-vzw-helper' ); ?></button>
                <?php if ( $pub ): ?>
                <a href="<?= esc_url( admin_url( 'admin.php?page=emevzwhelper-publications' ) ) ?>" class="button"><?php esc_html_e( 'Annuleer', 'eme-vzw-helper' ); ?></a>
                <?php endif; ?>
            </p>
        </form>

        <!-- DOC BUTTONS -->
        <div id="vzw-doc-buttons" style="<?= $pub ? '' : 'display:none' ?>;margin-top:16px;padding-top:16px;border-top:1px solid #f0f0f1">
            <h3 style="margin:0 0 10px"><?php esc_html_e( 'Documenten genereren', 'eme-vzw-helper' ); ?></h3>
            <div class="doc-btns" style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="button" class="button button-primary vzw-gen-doc" data-pub-id="<?= $pub ? (int) $pub->id : '' ?>">📄 Maak Formulieren I & II aan</button>
                <button type="button" class="button vzw-gen-bestuursverbod" data-pub-id="<?= $pub ? (int) $pub->id : '' ?>">📋 Verklaring op eer bestuursverbod</button>
                <?php if ($pub): ?>
                <button type="button" class="button vzw-help-btn" data-id="vzw-howto-dialog-<?= (int) $pub->id ?>" style="width:auto;border-radius:4px;padding:0 10px" title="<?php esc_attr_e( 'Hoe neerleggen bij de griffie?', 'eme-vzw-helper' ); ?>">ℹ️ Hoe neerleggen?</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TABEL MET ALLE PUBLICATIES -->
    <?php if ( $all_pubs ): ?>
    <h2 style="margin-top:28px"><?php esc_html_e( 'Alle publicaties', 'eme-vzw-helper' ); ?></h2>
    <table class="vzw-pub-table">
        <thead><tr>
            <th>#</th><th><?php esc_html_e( 'Datum AV', 'eme-vzw-helper' ); ?></th><th><?php esc_html_e( 'Status', 'eme-vzw-helper' ); ?></th><th><?php esc_html_e( 'Acties', 'eme-vzw-helper' ); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ( $all_pubs as $p ): ?>
            <tr>
                <td><?= (int) $p->id ?></td>
                <td><?= esc_html( date_i18n( 'd/m/Y', strtotime( $p->av_date ) ) ) ?></td>
                <td><span class="status-badge status-<?= esc_attr( $p->status ) ?>"><?= esc_html( $status_labels[ $p->status ] ?? $p->status ) ?></span></td>
                <td>
                    <a href="<?= esc_url( admin_url( 'admin.php?page=emevzwhelper-publications&edit=' . $p->id ) ) ?>" class="button button-small">Bewerk</a>
                    <button type="button" class="button button-small vzw-gen-doc" data-pub-id="<?= (int) $p->id ?>">Maak Formulieren I &amp; II aan</button>
                    <button type="button" class="button button-small vzw-gen-bestuursverbod" data-pub-id="<?= (int) $p->id ?>">Verklaring op eer bestuursverbod</button>
                    <button type="button" class="button vzw-help-btn" data-id="vzw-howto-dialog-<?= (int) $p->id ?>" style="width:auto;border-radius:4px;padding:0 10px" title="<?php esc_attr_e( 'Hoe neerleggen bij de griffie?', 'eme-vzw-helper' ); ?>">ℹ️ Hoe neerleggen?</button>
                    <button type="button" class="button button-small vzw-delete-pub" data-id="<?= (int) $p->id ?>">✕</button>
                    <?php include EME_VZW_DIR . 'templates/publication-help.php' ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
