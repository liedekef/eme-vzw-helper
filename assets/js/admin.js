/* eme-vzw-helper/assets/js/admin.js */
/* global emeVzw */
(function() {
    'use strict';

    const { nonce, ajaxUrl, i18n } = emeVzw;

    /* ════════════════════════════════════════════════════════════════
     * HELP DIALOGS
     * ════════════════════════════════════════════════════════════════ */
    document.querySelectorAll('dialog.vzw-help-dialog').forEach(function(dlg) {
        dlg.addEventListener('click', function(e) {
            if (e.target === dlg) dlg.close();
        });
    });
    document.querySelectorAll('.vzw-help-btn').forEach(function(e) {
        e.addEventListener('click', function(e) {
            const targetId = e.target.dataset.id;
            const helpDialog = document.getElementById(targetId);
            helpDialog.showModal();
            helpDialog.focus();
            helpDialog.scrollTop = 0;
        });
    });
    /**
     * Bevestigingsdialog (vervangt window.confirm). Geeft een Promise<boolean>.
     * Esc, klik naast de dialog of "Annuleren" => false.
     */
    function vzwConfirm(message, { title = '', confirmLabel = 'OK', danger = false } = {}) {
        return new Promise(function(resolve) {
            const dlg = document.createElement('dialog');
            dlg.classList.add('vzw-help-dialog', 'vzw-confirm-dialog');
            if (title) {
                const h = document.createElement('h4');
                h.textContent = title;
                dlg.appendChild(h);
            }
            const p = document.createElement('p');
            p.textContent = message;
            dlg.appendChild(p);

            const footer = document.createElement('div');
            footer.className = 'dialog-footer';
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'vzw-btn-cancel';
            cancel.textContent = i18n.cancel;
            const ok = document.createElement('button');
            ok.type = 'button';
            ok.textContent = confirmLabel;
            if (danger) ok.classList.add('vzw-btn-danger');
            footer.append(cancel, ok);
            dlg.appendChild(footer);

            let result = false;
            ok.addEventListener('click', () => { result = true; dlg.close(); });
            cancel.addEventListener('click', () => dlg.close());
            dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
            dlg.addEventListener('close', () => { dlg.remove(); resolve(result); });

            document.body.appendChild(dlg);
            dlg.showModal();
            cancel.focus(); // veilige default bij destructieve acties
        });
    }

    /**
     * Recursieve helper: zet complexe JS-objecten om naar FormData met bracket-keys.
     * Bijv: { board_changes: [{ id: 1 }] } wordt board_changes[0][id]=1
     */
    function buildFormData(data, formData = new FormData(), parentKey = '') {
        for (const key in data) {
            if (!data.hasOwnProperty(key)) continue;
            const value = data[key];
            const formKey = parentKey ? `${parentKey}[${key}]` : key;

            // Recursief voor objecten/arrays (behalve File instances)
            if (typeof value === 'object' && value !== null && !(value instanceof File)) {
                buildFormData(value, formData, formKey);
            } else {
                formData.append(formKey, value);
            }
        }
        return formData;
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('nl-BE', {
            style: 'currency', currency: i18n.currency, minimumFractionDigits: 2,
        }).format(amount);
    }
    function parseBE(val) {
        return parseFloat(val.replace(/\./g, '').replace(',', '.')) || 0;
    }

    /**
     * Generieke AJAX functie
     * Gebruikt automatisch FormData met recursieve keys voor nested data.
     */
    function ajax(action, data, onSuccess, onError) {
        // Voeg action en nonce toe aan de data
        const payload = { action, nonce, ...data };
        
        // Bouw de FormData met geneste keys
        const formData = buildFormData(payload);

        // Fetch verstuurt FormData als 'multipart/form-data' automatisch
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(r => {
            if (r.success) {
                onSuccess && onSuccess(r.data);
            } else {
                (onError || alert)(r.data || 'Er is een fout opgetreden.');
            }
        })
        .catch(() => (onError || alert)('Verbindingsfout met server.'));
    }

    // Helper: find all matching elements inside container
    function findAll(container, selector) {
        return Array.from(container.querySelectorAll(selector));
    }

    /* ════════════════════════════════════════════════════════════════
     * CHECKLIST
     * ════════════════════════════════════════════════════════════════ */

    /* ════════════════════════════════════════════════════════════════
     * CHECKLIST — hover opens details, required on status-yes
     * ════════════════════════════════════════════════════════════════ */

    /**
     * Open/close the <details> element inside a check-item on mouseenter,
     * but only if the item has sub-questions (data-has-subs="1").
     */
    /*
    document.addEventListener('mouseover', function(e) {
        if (!e.target || typeof e.target.closest !== 'function') return;
        const $item = e.target.closest('.vzw-check-item[data-has-subs="1"]');
        if (!$item) return;

        // Only trigger when entering the main vzw-check-item from outside
        const $currentTarget = e.currentTarget;
        const $relatedTarget = e.relatedTarget;
        if ($relatedTarget && $item.contains($relatedTarget)) return;

        const $details = $item.querySelector('details');
        if ($details) $details.open = true;
    }, true);

    document.addEventListener('mouseout', function(e) {
        if (!e.target || typeof e.target.closest !== 'function') return;
        const $item = e.target.closest('.vzw-check-item[data-has-subs="1"]');
        if (!$item) return;

        // Only close when leaving the main vzw-check-item to outside
        const $relatedTarget = e.relatedTarget;
        if ($relatedTarget && $item.contains($relatedTarget)) return;

        const $details = $item.querySelector('details');
        if ($details) $details.open = false;
    }, true);
    */

    // Status button click → save via AJAX, update UI score
    function handleStatusBtn(btn) {
        const $item = btn.closest('.vzw-check-item');
        const checkKey = $item.dataset.key;
        const status = btn.dataset.status;
        const year = document.getElementById('vzw-fiscal-year')?.value || new Date().getFullYear();
        const note = $item.querySelector('.vzw-check-note-input')?.value || '';
        const subs = collectSubAnswers($item);

        // Optimistic UI update
        $item.classList.remove('status-yes', 'status-no', 'status-na', 'status-pending');
        $item.classList.add(`status-${status}`);
        findAll($item, '.vzw-status-btn').forEach(b => {
            b.classList.remove('active-yes', 'active-no', 'active-na', 'active-pending');
        });
        btn.classList.add(`active-${status}`);
        syncSubRequired($item);

        ajax('eme_vzw_save_check', {
            fiscal_year: year,
            check_key: checkKey,
            status: status,
            sub_answers: JSON.stringify(subs),
            notes: note,
        }, function(data) {
            if (data && data.score) updateScoreUI(data.score);
        });
    }

    // Collect sub-question answers from a check-item
    function collectSubAnswers($item) {
        const subs = {};
        findAll($item, '.vzw-sub-input').forEach(function(el) {
            const idx = el.dataset.subidx;
            if (el.type === 'radio') {
                if (el.checked) subs[idx] = el.value;
            } else {
                subs[idx] = el.value;
            }
        });
        return subs;
    }

    // Note input change → auto-save after 800ms debounce
    let noteTimer;
    document.addEventListener('input', function(e) {
        if (!e.target.matches('.vzw-check-note-input, .vzw-sub-input')) return;

        clearTimeout(noteTimer);
        const $item = e.target.closest('.vzw-check-item');
        const checkKey = $item.dataset.key;
        const activeBtn = $item.querySelector('.vzw-status-btn.active-yes, .vzw-status-btn.active-no, .vzw-status-btn.active-na, .vzw-status-btn.active-pending');
        const status = activeBtn?.dataset?.status || 'pending';
        const year = document.getElementById('vzw-fiscal-year')?.value || new Date().getFullYear();

        noteTimer = setTimeout(() => {
            const note = $item.querySelector('.vzw-check-note-input')?.value;
            const subs = collectSubAnswers($item);
            ajax('eme_vzw_save_check', {
                fiscal_year: year,
                check_key: checkKey,
                status: status,
                sub_answers: JSON.stringify(subs),
                notes: note,
            });
        }, 800);
    });

    // Also listen to change event for radio buttons
    document.addEventListener('change', function(e) {
        if (!e.target.matches('.vzw-check-note-input, .vzw-sub-input')) return;
        // Trigger same handler as input
        const event = new Event('input', { bubbles: true });
        e.target.dispatchEvent(event);
    });

    function updateScoreUI(score) {
        // Ring
        const pct = score.score_pct;
        const radius = 32;
        const circ = 2 * Math.PI * radius;
        const offset = circ * (1 - pct / 100);
        const $circle = document.getElementById('score-ring-progress');
        if ($circle) {
            $circle.setAttribute('stroke-dashoffset', offset.toFixed(1));
            $circle.setAttribute('stroke', pct >= 80 ? '#00a32a' : pct >= 50 ? '#dba617' : '#d63638');
        }
        const earnedPointsText = document.getElementById('earned-points-text');
        if (earnedPointsText) earnedPointsText.textContent = score.earned_points;
        const pctText = document.getElementById('score-pct-text');
        if (pctText) pctText.textContent = pct.toFixed(1) + '%';
        const no_answer = document.getElementById('no_answer');
        if (no_answer) no_answer.textContent = score.no_answer;

        // Group scores
        const g = score.by_group;
        if (g.general) {
            const gPct = g.general.max > 0 ? (g.general.earned / g.general.max * 100).toFixed(1) : 0;
            const generalScore = document.getElementById('group-general-score');
            if (generalScore) generalScore.textContent = gPct + '% (' + g.general.earned + '/' + g.general.max + ' pt)';
        }
        if (g.current_year) {
            const yPct = g.current_year.max > 0 ? (g.current_year.earned / g.current_year.max * 100).toFixed(1) : 0;
            const yearScore = document.getElementById('group-current_year-score');
            if (yearScore) yearScore.textContent = yPct + '% (' + g.current_year.earned + '/' + g.current_year.max + ' pt)';
        }
    }

    // Year selector
    document.getElementById('vzw-fiscal-year')?.addEventListener('change', function() {
        window.location = `${window.location.pathname}?page=emevzwhelper-checklist&year=${this.value}`;
    });

    /* ════════════════════════════════════════════════════════════════
     * PUBLICATIONS
     * ════════════════════════════════════════════════════════════════ */

    // Save publication form
    const pubForm = document.getElementById('vzw-pub-form');
    if (pubForm) {
        pubForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const $form = this;
            const $btn = $form.querySelector('.submit-btn');
            const originalText = $btn.textContent;
            $btn.textContent = i18n.saving;
            $btn.disabled = true;

            const data = buildPublicationData($form);
            ajax('eme_vzw_save_publication', data, function(r) {
                $btn.textContent = i18n.saved;
                $btn.disabled = false;
                const pubIdField = $form.querySelector('[name="pub_id"]');
                if (!pubIdField.value) {
                    // New publication — update hidden field and show document buttons
                    pubIdField.value = r.id;
                    const docButtons = document.getElementById('vzw-doc-buttons');
                    if (docButtons) {
                        docButtons.dataset.pubId = r.id;
                        docButtons.style.display = 'block';
                    }
                }
                setTimeout(() => {
                    $btn.textContent = i18n.save;
                    $btn.disabled = false;
                }, 1800);
            }, function(msg) {
                $btn.textContent = i18n.save;
                $btn.disabled = false;
                alert(msg);
            });
        });
    }

    function buildPublicationData($form) {
        return {
            id: $form.querySelector('[name="pub_id"]')?.value || 0,
            av_date: $form.querySelector('[name="av_date"]')?.value,
            av_location: $form.querySelector('[name="av_location"]')?.value || '',
            submitted_date: $form.querySelector('[name="submitted_date"]')?.value,
            published_date: $form.querySelector('[name="published_date"]')?.value,
            bs_payment_ref: $form.querySelector('[name="bs_payment_ref"]')?.value,
            bs_payment_date: $form.querySelector('[name="bs_payment_date"]')?.value,
            bs_payment_amount: $form.querySelector('[name="bs_payment_amount"]')?.value,
            status: $form.querySelector('[name="pub_status"]')?.value,
            notes: $form.querySelector('[name="notes"]')?.value,
            statute_text: $form.querySelector('[name="statute_text"]')?.value || '',
            board_changes: collectBoardChanges(),
        };
    }

    function collectBoardChanges() {
        const changes = [];
        document.querySelectorAll('.vzw-board-change-row').forEach(function(row) {
            const bid = row.querySelector('.bc-board-id')?.value;
            const type = row.querySelector('.bc-change-type')?.value;
            const date = row.querySelector('.bc-effective-date')?.value;
            if (bid) changes.push({ board_id: bid, change_type: type, effective_date: date });
        });
        return changes;
    }

    // Add board change row
    function handleAddBoardChange() {
        const $tmpl = document.getElementById('vzw-board-change-template');
        if (!$tmpl) return;
        const clone = $tmpl.cloneNode(true);
        clone.removeAttribute('id');
        clone.style.display = 'flex';
        document.getElementById('vzw-board-changes-list')?.appendChild(clone);
    }

    // Remove row
    function handleRemoveRow(btn) {
        const row = btn.closest('.vzw-board-change-row, .vzw-statute-change-row');
        if (row) row.remove();
    }

    // Generate & open document in new window (print to PDF)
    function handleGenDoc(btn) {
        const pubId = btn.dataset.pubId;
        if (!pubId) return;
        const originalText = btn.textContent;
        btn.textContent = i18n.pleasewait;
        btn.disabled = true;

        const url = `${ajaxUrl}?action=eme_vzw_download_pdf&nonce=${nonce}&pub_id=${pubId}`;
        fetch(url)
        .then(response => response.blob())
        .then(blob => {
            const blobUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = blobUrl;
            const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            link.download = `Formulieren-${date}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(blobUrl);
            btn.disabled = false;
            btn.textContent = originalText;
        })
        .catch(error => {
            console.error('Download failed:', error);
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    // Generate Verklaring Bestuursverbod PDF
    function handleGenBestuursverbod(btn) {
        const pubId = btn.dataset.pubId;
        if (!pubId) return;
        const originalText = btn.textContent;
        btn.textContent = i18n.pleasewait;
        btn.disabled = true;

        const url = `${ajaxUrl}?action=eme_vzw_download_bestuursverbod&nonce=${nonce}&pub_id=${pubId}`;
        fetch(url)
        .then(response => response.blob())
        .then(blob => {
            const blobUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = blobUrl;
            const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            link.download = `Bestuursverbod-${date}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(blobUrl);
            btn.disabled = false;
            btn.textContent = originalText;
        })
        .catch(error => {
            console.error('Download failed:', error);
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    // Howto dialog
    function handleVzwHowtoSend(btn) {
        const pubId = btn.dataset.pubId || 0;
        const url = `${ajaxUrl}?action=eme_vzw_howto_send&nonce=${nonce}&pub_id=${pubId}`;
        ajax('eme_vzw_howto_send', { pub_id: pubId, nonce: nonce }, function(data) {
            // Create dialog element
            const dialog = document.createElement('dialog');
            dialog.id = "dialog-vzw-howto-send";
            dialog.classList.add("vzw-help-dialog", "vzw-howto-dialog");
            dialog.autofocus = true;

            // Add content from response
            dialog.innerHTML = `
            <div>
                ${data}
                <div class="dialog-footer"><button type="button" commandfor="dialog-vzw-howto-send" command="close">Sluiten</button></div>
            </div>
            `;

            // Add to body and show
            document.body.appendChild(dialog);
            dialog.showModal(); // Opens as modal dialog
            dialog.focus();
            dialog.scrollTop = 0;

            // Optional: Close dialog when clicking backdrop (for modal)
            dialog.addEventListener('click', (e) => {
                if (e.target === dialog) {
                    dialog.close();
                }
            });

            // Optional: Remove from DOM when closed
            dialog.addEventListener('close', () => {
                dialog.remove();
            });
        });
    }

    // Delete publication
    function handleDeletePub(btn) {
        if (!confirm(i18n.confirm_delete_pub)) return;
        const $row = btn.closest('tr');
        ajax('eme_vzw_delete_publication', { id: btn.dataset.id }, function() {
            if ($row) {
                $row.style.transition = 'opacity 0.3s';
                $row.style.opacity = '0';
                setTimeout(() => $row.remove(), 300);
            }
        });
    }

    // Delete board member
    function handleDeleteBoard(btn) {
        if (!confirm(i18n.confirm_delete)) return;
        const $card = btn.closest('.vzw-board-card');
        ajax('eme_vzw_delete_board_member', { id: btn.dataset.id }, function() {
            if ($card) {
                $card.style.transition = 'opacity 0.3s';
                $card.style.opacity = '0';
                setTimeout(() => location.reload(), 350);
            }
        });
    }

    // Reactivate board member
    function handleReactivateBoard(btn) {
        if (!confirm(i18n.confirm_reactivate)) return;
        ajax('eme_vzw_save_board_member', { id: btn.dataset.id, is_active: 1 }, function() {
            location.reload();
        });
    }

    // Purge board member
    async function handlePurgeBoard(btn) {
        const $card = btn.closest('.vzw-board-card');
        const name = $card?.querySelector('h4')?.textContent || '';
        if (!await vzwConfirm(i18n.confirm_purge, { title: name, confirmLabel: i18n.purge, danger: true })) return;
        ajax('eme_vzw_purge_board_member', { id: btn.dataset.id }, function() {
            if ($card) {
                $card.style.transition = 'opacity 0.3s';
                $card.style.opacity = '0';
                setTimeout(() => $card.remove(), 300);
            }
        });
    }

    /* ── Single delegated click handler ──────────────────────────── */
    const clickHandlers = {
        'vzw-status-btn':       handleStatusBtn,
        'btn-add-board-change': handleAddBoardChange,
        'vzw-remove-row':       handleRemoveRow,
        'vzw-gen-doc':          handleGenDoc,
        'vzw-howto-send':       handleVzwHowtoSend,
        'vzw-gen-bestuursverbod': handleGenBestuursverbod,
        'vzw-delete-pub':       handleDeletePub,
        'vzw-delete-board':     handleDeleteBoard,
        'vzw-reactivate-board': handleReactivateBoard,
        'vzw-purge-board':      handlePurgeBoard,
    };
    document.addEventListener('click', function(e) {
        for (const [cls, handler] of Object.entries(clickHandlers)) {
            const btn = e.target.closest(`#${cls}, .${cls}`);
            if (btn) { handler(btn, e); return; }
        }
    });

    /* ════════════════════════════════════════════════════════════════
     * BOARD / CONFIG FORMS
     * ════════════════════════════════════════════════════════════════ */

    const boardForm = document.getElementById('vzw-board-form');
    if (boardForm) {
        boardForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const $btn = this.querySelector('.submit-btn');
            $btn.textContent = i18n.saving;
            $btn.disabled = true;
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => { data[key] = value; });
            ajax('eme_vzw_save_board_member', data, () => location.reload(), function(msg) {
                $btn.textContent = i18n.save;
                $btn.disabled = false;
                alert(msg);
            });
        });
    }
    const configForm = document.getElementById('vzw-config-form');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const $btn = this.querySelector('.submit-btn');
            $btn.textContent = i18n.saving;
            $btn.disabled = true;
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => { data[key] = value; });
            ajax('eme_vzw_save_config', data, function() {
                $btn.textContent = i18n.saved;
                $btn.disabled = false;
                setTimeout(() => {
                    location.reload();
                }, 500);
            }, function(msg) {
                $btn.textContent = i18n.save;
                $btn.disabled = false;
                alert(msg);
            });
        });
    }

    // Legal entity toggle
    const legalEntityCheckbox = document.getElementById('is_legal_entity');
    if (legalEntityCheckbox) {
        legalEntityCheckbox.addEventListener('change', function() {
            const legalFields = document.getElementById('legal-entity-fields');
            const naturalFields = document.getElementById('natural-person-fields');
            if (legalFields) legalFields.style.display = this.checked ? 'block' : 'none';
            if (naturalFields) naturalFields.style.display = this.checked ? 'none' : 'block';
        });
    }

    function initSnapSelectRemote(selector, options = {}) {
        // Convert selector to elements array
        const elements = typeof selector === 'string'
            ? Array.from(document.querySelectorAll(selector))
            : [selector];

        if (!elements.length) return [];

        return Array.from(elements).map(el => {
            // Guard: skip if already initialised
            if (el.snapselectInstance) return el.snapselectInstance;

            if (!el.id) {
                el.id = 'snap-select-' + Math.random().toString(36).slice(2, 11);
            }

            const pagesize = options.pagesize || 30;
            // Build the ajax.url function, incorporating any extra data
            const config = {
                placeholder:    options.placeholder    || undefined,
                defaultText:    options.defaultText    || 'Select',
                closeOnSelect:  !el.multiple,
                showClearButton:     options.showClearButton !== undefined ? options.showClearButton : undefined,
                onItemAdd:      typeof options.onItemAdd    === 'function' ? options.onItemAdd    : undefined,
                onItemDelete:   typeof options.onItemDelete === 'function' ? options.onItemDelete : undefined,
                ajax: {
                    // Allow caller to pass a custom url function (e.g. for state→country cascade)
                    url: options.url || ajaxUrl,
                    cache: options.cache !== undefined ? options.cache : undefined,
                    pagesize: pagesize,
                    data: options.data || {},
                    processResults: function(data, search, page) {
                        // EME backend returns { TotalRecordCount, Records:[{id,text},...] }
                        // or a plain array
                        const records = Array.isArray(data)
                            ? data
                            : (data.Records || []);
                        let hasMore;
                        if (data.hasMore !== undefined) {
                            hasMore = data.hasMore;
                        } else {
                            const total   = data.TotalRecordCount !== undefined
                                ? data.TotalRecordCount
                                : records.length;
                            hasMore = total > page * pagesize;
                            // the next if covers the case where all rows are returned in 1 go, not respecting paging
                            if (data.TotalRecordCount !== undefined && records.length >= data.TotalRecordCount)
                                hasMore = false;
                        }
                        return { results: records, hasMore };
                    },
                }
            };
            const instance = new SnapSelectClass(el, config);

            // Expose a .snapselect property on the element
            el.snapselectInstance = instance;

            return instance;
        });
    }
    initSnapSelectRemote('select.emevzw_snapselect_searchemeperson', {
        placeholder: 'Zoek een bestaande persoon uit Events Made Easy',
        showClearButton: true,
        data: function(search, page) {
            return {
                action: 'eme_vzw_snapselect_searchemeperson',
                eme_admin_nonce: i18n.emeadminnonce,
            };
        },
        onItemAdd: function(value, text) {
            // first hide all personal info, then show informational info on the selected person
            // Read extra fields stored on the <option> by SnapSelect
            const opt = this.querySelector(`option[value="${value}"]`);
            const lastname   = document.querySelector('input[name=last_name]');
            const firstname  = document.querySelector('input[name=first_name]');
            const email      = document.querySelector('input[name=email]');
            const phone      = document.querySelector('input[name=phone]');
            const birthdate  = document.querySelector('input[name=birth_date]');
            const birthplace = document.querySelector('input[name=birth_place]');
            const address1   = document.querySelector('input[name=street]');
            const address2   = document.querySelector('input[name=house_number]');
            const zip        = document.querySelector('input[name=postal_code]');
            const city       = document.querySelector('input[name=city]');
            if (lastname)   { lastname.value   = opt?.dataset.lastname   || '';}
            if (firstname)  { firstname.value  = opt?.dataset.firstname  || '';}
            if (email)      { email.value      = opt?.dataset.email      || '';}
            if (phone)      { phone.value      = opt?.dataset.phone      || '';}
            if (birthdate)  { birthdate.value  = opt?.dataset.birthdate  || '';}
            if (birthplace) { birthplace.value = opt?.dataset.birthplace || '';}
            if (address1)   { address1.value   = opt?.dataset.address1   || '';}
            if (address2)   { address2.value   = opt?.dataset.address2   || '';}
            if (zip)        { zip.value        = opt?.dataset.zip        || '';}
            if (city)       { city.value       = opt?.dataset.city       || '';}
        }
    });

})();
