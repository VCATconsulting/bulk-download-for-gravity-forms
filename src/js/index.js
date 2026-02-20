import { __ } from '@wordpress/i18n';

document.addEventListener( 'DOMContentLoaded', function () {
	// wp_localize_script()
	const cfg = window.bdfgf_bulk_delete || {};

	const confirmMessage =
		cfg.confirmMessage || __( 'Are you sure?', 'bulk-download-for-gravity-forms' );

	const bulkDeleteValue = 'bdfgf_bulk_delete';
	const bulkDownloadValue = 'gf_bulk_download';

	// AJAX config (von wp_localize_script setzen!)
	const ajaxUrl = cfg.ajaxUrl || window.ajaxurl || '';
	const nonce = cfg.nonce || '';

	// Optional: Texte (können Sie auch aus PHP lokalisieren)
	const msgNoDownloadables =
		cfg.msgNoDownloadables ||
		__( 'No downloadable files exist for the selected entries.', 'bulk-download-for-gravity-forms' );

	const msgNoDeletables =
		cfg.msgNoDeletables ||
		__( 'No deletable files exist for the selected entries.', 'bulk-download-for-gravity-forms' );

	const msgValidationFailed =
		cfg.msgValidationFailed ||
		__( 'Could not validate the selected entries.', 'bulk-download-for-gravity-forms' );

	document.addEventListener( 'click', function ( e ) {
		const link = e.target.closest( 'a.bdfgf-confirm-delete-link' );
		if ( ! link ) {
			return;
		}

		if ( ! window.confirm( confirmMessage ) ) {
			e.preventDefault();
			e.stopPropagation();
		}
	} );

	function getSelectedBulkAction( form ) {
		if ( ! form ) {return '';}

		const selTop = form.querySelector( 'select[name="action"]' );
		const selBottom = form.querySelector( 'select[name="action2"]' );

		const topVal = selTop ? selTop.value : '';
		const bottomVal = selBottom ? selBottom.value : '';

		if ( topVal && topVal !== '-1' ) {return topVal;}
		if ( bottomVal && bottomVal !== '-1' ) {return bottomVal;}

		return '';
	}

	function getSelectedEntryIds( form ) {
		const checked = form
			? form.querySelectorAll( 'input[name="entry[]"]:checked' )
			: [];

		return Array.from( checked )
			.map( ( el ) => String( el.value || '' ).trim() )
			.filter( Boolean );
	}

	async function validateBulkAction( { formId, action, entryIds } ) {
		if ( ! ajaxUrl ) {
			throw new Error( 'Missing ajaxUrl' );
		}

		const body = new URLSearchParams();
		body.set( 'action', 'bdfgf_validate_bulk_action' );
		if ( nonce ) {body.set( 'nonce', nonce );}
		body.set( 'form_id', formId );
		body.set( 'bulk_action', action );
		entryIds.forEach( ( id ) => body.append( 'entry_ids[]', id ) );

		const res = await fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} );

		const json = await res.json();
		if ( ! json || ! json.success || ! json.data ) {
			throw new Error( 'Invalid response' );
		}

		return json.data; // { total, readable, missing, action }
	}

	let isSubmitting = false;

		async function onApplyClick( e ) {
		if ( isSubmitting ) {
			return;
		}

		const btn = e.currentTarget;
		const form = btn.closest( 'form' );
		const selected = getSelectedBulkAction( form );

		// Nur unsere Aktionen
		if ( selected !== bulkDeleteValue && selected !== bulkDownloadValue ) {
			return;
		}

		const entryIds = getSelectedEntryIds( form );
		if ( ! entryIds.length ) {
			return; // WP/GF Standardmeldung
		}

		// Bulk Delete: Confirm (wie bisher)
		if ( selected === bulkDeleteValue ) {
			if ( ! window.confirm( confirmMessage ) ) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
		}

		// Precheck per AJAX
		e.preventDefault();
		e.stopPropagation();

		const formId = cfg.formId;
		if ( ! formId ) {
			window.alert( msgValidationFailed );
			return;
		}

		try {
			const data = await validateBulkAction( {
				formId,
				action: selected,
				entryIds,
			} );

			// data.readable == "downloadbar/löschbar" (serverseitig als readable gezählt)
			if ( Number( data.readable ) <= 0 ) {
				window.alert(
					selected === bulkDownloadValue ? msgNoDownloadables : msgNoDeletables
				);
				return;
			}

			// Optional: teilweise fehlend -> confirm
			// if ( Number( data.missing ) > 0 ) {
			// 	const ok = window.confirm( `${data.readable} available, ${data.missing} missing. Continue?` );
			// 	if ( ! ok ) return;
			// }

			// Submit jetzt wirklich
			isSubmitting = true;
			form.submit();
		} catch ( err ) {
			window.alert( msgValidationFailed );
		} finally {
			isSubmitting = false;
		}
	}

	const btnTop = document.getElementById( 'doaction' );
	const btnBottom = document.getElementById( 'doaction2' );

	if ( btnTop ) {btnTop.addEventListener( 'click', onApplyClick );}
	if ( btnBottom ) {btnBottom.addEventListener( 'click', onApplyClick );}
} );
