/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/index.js"
/*!*************************!*\
  !*** ./src/js/index.js ***!
  \*************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);

document.addEventListener('DOMContentLoaded', function () {
  // wp_localize_script()
  const cfg = window.bdfgf_bulk_delete || {};
  const confirmMessage = cfg.confirmMessage || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Are you sure?', 'bulk-download-for-gravity-forms');
  const bulkDeleteValue = 'bdfgf_bulk_delete';
  const bulkDownloadValue = 'gf_bulk_download';

  // AJAX config (von wp_localize_script setzen!)
  const ajaxUrl = cfg.ajaxUrl || window.ajaxurl || '';
  const nonce = cfg.nonce || '';

  // Optional: Texte (können Sie auch aus PHP lokalisieren)
  const msgNoDownloadables = cfg.msgNoDownloadables || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No downloadable files exist for the selected entries.', 'bulk-download-for-gravity-forms');
  const msgNoDeletables = cfg.msgNoDeletables || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No deletable files exist for the selected entries.', 'bulk-download-for-gravity-forms');
  const msgValidationFailed = cfg.msgValidationFailed || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Could not validate the selected entries.', 'bulk-download-for-gravity-forms');
  document.addEventListener('click', function (e) {
    const link = e.target.closest('a.bdfgf-confirm-delete-link');
    if (!link) {
      return;
    }
    if (!window.confirm(confirmMessage)) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
  function getSelectedBulkAction(form) {
    if (!form) {
      return '';
    }
    const selTop = form.querySelector('select[name="action"]');
    const selBottom = form.querySelector('select[name="action2"]');
    const topVal = selTop ? selTop.value : '';
    const bottomVal = selBottom ? selBottom.value : '';
    if (topVal && topVal !== '-1') {
      return topVal;
    }
    if (bottomVal && bottomVal !== '-1') {
      return bottomVal;
    }
    return '';
  }
  function getSelectedEntryIds(form) {
    const checked = form ? form.querySelectorAll('input[name="entry[]"]:checked') : [];
    return Array.from(checked).map(el => String(el.value || '').trim()).filter(Boolean);
  }
  async function validateBulkAction({
    formId,
    action,
    entryIds
  }) {
    if (!ajaxUrl) {
      throw new Error('Missing ajaxUrl');
    }
    const body = new URLSearchParams();
    body.set('action', 'bdfgf_validate_bulk_action');
    if (nonce) {
      body.set('nonce', nonce);
    }
    body.set('form_id', formId);
    body.set('bulk_action', action);
    entryIds.forEach(id => body.append('entry_ids[]', id));
    const res = await fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: body.toString()
    });
    const json = await res.json();
    if (!json || !json.success || !json.data) {
      throw new Error('Invalid response');
    }
    return json.data; // { total, readable, missing, action }
  }
  let isSubmitting = false;
  async function onApplyClick(e) {
    if (isSubmitting) {
      return;
    }
    const btn = e.currentTarget;
    const form = btn.closest('form');
    const selected = getSelectedBulkAction(form);

    // Nur unsere Aktionen
    if (selected !== bulkDeleteValue && selected !== bulkDownloadValue) {
      return;
    }
    const entryIds = getSelectedEntryIds(form);
    if (!entryIds.length) {
      return; // WP/GF Standardmeldung
    }

    // Bulk Delete: Confirm (wie bisher)
    if (selected === bulkDeleteValue) {
      if (!window.confirm(confirmMessage)) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }
    }

    // Precheck per AJAX
    e.preventDefault();
    e.stopPropagation();
    const formId = cfg.formId;
    if (!formId) {
      window.alert(msgValidationFailed);
      return;
    }
    try {
      const data = await validateBulkAction({
        formId,
        action: selected,
        entryIds
      });

      // data.readable == "downloadbar/löschbar" (serverseitig als readable gezählt)
      if (Number(data.readable) <= 0) {
        window.alert(selected === bulkDownloadValue ? msgNoDownloadables : msgNoDeletables);
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
    } catch (err) {
      window.alert(msgValidationFailed);
    } finally {
      isSubmitting = false;
    }
  }
  const btnTop = document.getElementById('doaction');
  const btnBottom = document.getElementById('doaction2');
  if (btnTop) {
    btnTop.addEventListener('click', onApplyClick);
  }
  if (btnBottom) {
    btnBottom.addEventListener('click', onApplyClick);
  }
});

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _js_index__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./js/index */ "./src/js/index.js");

})();

/******/ })()
;
//# sourceMappingURL=bdfgf-admin.js.map