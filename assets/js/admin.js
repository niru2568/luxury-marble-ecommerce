/**
 * Luxe Marble - Admin JavaScript
 * Handles: slug generation, image preview, delete confirm, char counter,
 *          price validation, sidebar nav active, unsaved changes warning,
 *          image sort drag-and-drop.
 */

(function () {
  'use strict';

  /* ====================================================
   * 1. Slug auto-generation
   * ==================================================== */
  function slugify(str) {
    return str
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')   // remove non-alphanumeric (except hyphens/underscores)
      .replace(/[\s_]+/g, '-')    // spaces/underscores → hyphens
      .replace(/-{2,}/g, '-')     // collapse multiple hyphens
      .replace(/^-+|-+$/g, '');   // trim leading/trailing hyphens
  }

  function initSlugGeneration() {
    var titleInput = document.getElementById('product_name') || document.getElementById('title') || document.getElementById('category_name');
    var slugInput  = document.getElementById('slug');
    if (!titleInput || !slugInput) return;

    var slugManuallyEdited = slugInput.value.trim() !== '';

    titleInput.addEventListener('input', function () {
      if (!slugManuallyEdited) {
        slugInput.value = slugify(this.value);
      }
    });

    slugInput.addEventListener('input', function () {
      // Mark as manually edited once user touches the slug field
      slugManuallyEdited = this.value.trim() !== '';
      // Auto-format on blur
    });

    slugInput.addEventListener('blur', function () {
      this.value = slugify(this.value);
    });

    // Regenerate button
    var regenBtn = document.getElementById('regen-slug');
    if (regenBtn) {
      regenBtn.addEventListener('click', function (e) {
        e.preventDefault();
        slugInput.value = slugify(titleInput.value);
        slugManuallyEdited = false;
      });
    }
  }

  /* ====================================================
   * 2. Single image preview
   * ==================================================== */
  function initSingleImagePreview() {
    var fileInputs = document.querySelectorAll('.single-img-input');
    fileInputs.forEach(function (input) {
      var previewId  = input.getAttribute('data-preview');
      var previewImg = previewId
        ? document.getElementById(previewId)
        : input.closest('.img-preview-wrapper') && input.closest('.img-preview-wrapper').querySelector('img');

      if (!previewImg) return;

      input.addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
          alert('Please select a valid image file.');
          this.value = '';
          return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
          previewImg.src = e.target.result;
          previewImg.style.display = 'block';
        };
        reader.readAsDataURL(file);
      });
    });
  }

  /* ====================================================
   * 3. Multiple image preview grid
   * ==================================================== */
  function initMultiImagePreview() {
    var multiInputs = document.querySelectorAll('.multi-img-input');
    multiInputs.forEach(function (input) {
      var gridId   = input.getAttribute('data-preview-grid');
      var grid     = gridId ? document.getElementById(gridId) : null;
      if (!grid) return;

      input.addEventListener('change', function () {
        grid.innerHTML = '';
        var files = Array.from(this.files);
        files.forEach(function (file) {
          if (!file.type.startsWith('image/')) return;
          var reader = new FileReader();
          reader.onload = function (e) {
            var wrapper = document.createElement('div');
            wrapper.className = 'preview-thumb-wrapper';
            var img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'preview-thumb';
            img.alt = file.name;
            wrapper.appendChild(img);
            grid.appendChild(wrapper);
          };
          reader.readAsDataURL(file);
        });
      });
    });
  }

  /* ====================================================
   * 4. Delete confirmation
   * ==================================================== */
  function initDeleteConfirmation() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.delete-btn[data-confirm], form.delete-form .delete-btn');
      if (!btn) return;

      var message = btn.getAttribute('data-confirm') || 'Are you sure you want to delete this item? This action cannot be undone.';
      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }

      // If inside a form, submit it
      var form = btn.closest('form.delete-form');
      if (form) {
        e.preventDefault();
        form.submit();
      }
    });
  }

  /* ====================================================
   * 5. Character counter for meta_description
   * ==================================================== */
  function initCharCounter() {
    var textareas = document.querySelectorAll('[data-char-limit]');
    textareas.forEach(function (el) {
      var limit      = parseInt(el.getAttribute('data-char-limit'), 10) || 160;
      var counterId  = el.getAttribute('data-counter-id');
      var counterEl  = counterId
        ? document.getElementById(counterId)
        : (function () {
            var span = document.createElement('div');
            span.className = 'char-counter';
            el.parentNode.insertBefore(span, el.nextSibling);
            return span;
          })();

      function updateCounter() {
        var remaining = limit - el.value.length;
        counterEl.textContent = el.value.length + ' / ' + limit + ' characters';
        counterEl.className = 'char-counter';
        if (remaining < 20) counterEl.classList.add('near-limit');
        if (remaining < 0)  counterEl.classList.add('over-limit');
      }

      el.addEventListener('input', updateCounter);
      updateCounter(); // initialize
    });
  }

  /* ====================================================
   * 6. Sale price validation
   * ==================================================== */
  function initPriceValidation() {
    var regularPriceInput = document.getElementById('regular_price') || document.getElementById('price');
    var salePriceInput    = document.getElementById('sale_price');
    if (!regularPriceInput || !salePriceInput) return;

    var errorMsg = document.getElementById('sale-price-error');
    if (!errorMsg) {
      errorMsg = document.createElement('div');
      errorMsg.id = 'sale-price-error';
      errorMsg.className = 'invalid-feedback d-block';
      errorMsg.style.display = 'none';
      salePriceInput.parentNode.appendChild(errorMsg);
    }

    function validatePrices() {
      var regular = parseFloat(regularPriceInput.value);
      var sale    = parseFloat(salePriceInput.value);

      var saleRaw = salePriceInput.value.trim();
      if (saleRaw !== '' && !isNaN(sale) && !isNaN(regular)) {
        if (sale >= regular) {
          salePriceInput.classList.add('is-invalid');
          errorMsg.textContent = 'Sale price must be less than the regular price (₹' + regular + ').';
          errorMsg.style.display = 'block';
          salePriceInput.setCustomValidity('Sale price must be less than the regular price.');
          return false;
        }
        if (sale < 0) {
          salePriceInput.classList.add('is-invalid');
          errorMsg.textContent = 'Sale price cannot be negative.';
          errorMsg.style.display = 'block';
          salePriceInput.setCustomValidity('Sale price cannot be negative.');
          return false;
        }
      }
      salePriceInput.classList.remove('is-invalid');
      salePriceInput.classList.add('is-valid');
      errorMsg.style.display = 'none';
      salePriceInput.setCustomValidity('');
      return true;
    }

    salePriceInput.addEventListener('blur', validatePrices);
    regularPriceInput.addEventListener('blur', validatePrices);
    salePriceInput.addEventListener('input', function () {
      salePriceInput.classList.remove('is-invalid', 'is-valid');
      salePriceInput.setCustomValidity('');
      errorMsg.style.display = 'none';
    });

    // Prevent form submit if invalid
    var form = salePriceInput.closest('form');
    if (form) {
      form.addEventListener('submit', function (e) {
        if (!validatePrices()) {
          e.preventDefault();
          salePriceInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
          salePriceInput.focus();
        }
      });
    }
  }

  /* ====================================================
   * 7. Status badge color on order status change
   * ==================================================== */
  function initOrderStatusUpdate() {
    var statusSelect = document.getElementById('order_status');
    var statusBadge  = document.getElementById('order-status-badge');
    if (!statusSelect || !statusBadge) return;

    var statusClasses = {
      pending:    'status-pending',
      processing: 'status-processing',
      shipped:    'status-shipped',
      delivered:  'status-delivered',
      cancelled:  'status-cancelled'
    };

    statusSelect.addEventListener('change', function () {
      var val = this.value;
      statusBadge.className = 'status-badge ' + (statusClasses[val] || '');
      statusBadge.textContent = val.charAt(0).toUpperCase() + val.slice(1);
    });
  }

  /* ====================================================
   * 8. Sidebar nav: mark current page as active
   * ==================================================== */
  function initSidebarActiveLink() {
    var links = document.querySelectorAll('.sidebar-nav-link');
    var current = window.location.pathname;

    links.forEach(function (link) {
      var href = link.getAttribute('href');
      if (!href) return;

      // Normalize: strip query string and trailing slash for comparison
      var linkPath = href.split('?')[0].replace(/\/$/, '');
      var currPath = current.split('?')[0].replace(/\/$/, '');

      if (linkPath && (currPath === linkPath || currPath.endsWith(linkPath))) {
        link.classList.add('active');
      }
    });
  }

  /* ====================================================
   * 9. Unsaved changes warning on unload
   * ==================================================== */
  function initUnsavedChangesWarning() {
    var forms = document.querySelectorAll('form.admin-form, form.needs-warning');
    forms.forEach(function (form) {
      var isDirty = false;

      var inputs = form.querySelectorAll('input, select, textarea');
      inputs.forEach(function (input) {
        input.addEventListener('change', function () { isDirty = true; });
        input.addEventListener('input',  function () { isDirty = true; });
      });

      form.addEventListener('submit', function () { isDirty = false; });

      // Also disable warning for cancel/back links inside the form
      form.querySelectorAll('[data-no-warn]').forEach(function (el) {
        el.addEventListener('click', function () { isDirty = false; });
      });

      window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
          var msg = 'You have unsaved changes. Are you sure you want to leave?';
          e.returnValue = msg;
          return msg;
        }
      });
    });
  }

  /* ====================================================
   * 10. Image sort order drag-and-drop
   * ==================================================== */
  function initImageSortDrag() {
    var lists = document.querySelectorAll('.sortable-img-list');
    lists.forEach(function (list) {
      var dragging = null;

      list.addEventListener('dragstart', function (e) {
        dragging = e.target.closest('.sortable-img-item');
        if (dragging) {
          dragging.classList.add('dragging');
          e.dataTransfer.effectAllowed = 'move';
        }
      });

      list.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('.sortable-img-item');
        if (target && target !== dragging) {
          var rect   = target.getBoundingClientRect();
          var midX   = rect.left + rect.width / 2;
          var before = e.clientX < midX;
          if (before) {
            list.insertBefore(dragging, target);
          } else {
            list.insertBefore(dragging, target.nextSibling);
          }
        }
      });

      list.addEventListener('dragend', function () {
        if (dragging) {
          dragging.classList.remove('dragging');
          dragging = null;
        }
        updateSortOrderValues(list);
      });

      // Make items draggable
      list.querySelectorAll('.sortable-img-item').forEach(function (item) {
        item.setAttribute('draggable', 'true');
      });
    });
  }

  function updateSortOrderValues(list) {
    var items = list.querySelectorAll('.sortable-img-item');
    items.forEach(function (item, index) {
      var badge = item.querySelector('.sort-order-badge');
      if (badge) badge.textContent = index + 1;
      var orderInput = item.querySelector('input[name*="sort_order"]');
      if (orderInput) orderInput.value = index + 1;
    });
  }

  /* ====================================================
   * DOMContentLoaded
   * ==================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    initSlugGeneration();
    initSingleImagePreview();
    initMultiImagePreview();
    initDeleteConfirmation();
    initCharCounter();
    initPriceValidation();
    initOrderStatusUpdate();
    initSidebarActiveLink();
    initUnsavedChangesWarning();
    initImageSortDrag();
  });

})();
