/**
 * Luxe Marble - Main JavaScript
 * Handles: navbar, gallery, cart AJAX, toast, checkout, OTP, forms, filters, lazy loading
 */

(function () {
  'use strict';

  /* ====================================================
   * Utility: get CSRF token from meta tag
   * ==================================================== */
  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  /* ====================================================
   * 1. Navbar scroll effect
   * ==================================================== */
  function initNavbarScroll() {
    var nav = document.getElementById('mainNav');
    if (!nav) return;

    function onScroll() {
      if (window.scrollY > 50) {
        nav.classList.add('scrolled');
      } else {
        nav.classList.remove('scrolled');
      }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // run once on load
  }

  /* ====================================================
   * 2. Product Gallery: thumbnail click
   * ==================================================== */
  function initProductGallery() {
    var thumbs = document.querySelectorAll('.thumbnail-img');
    var mainImage = document.querySelector('.product-gallery .main-image');
    if (!thumbs.length || !mainImage) return;

    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        var src = this.getAttribute('data-full') || this.getAttribute('src');
        mainImage.src = src;
        // active class
        thumbs.forEach(function (t) { t.classList.remove('active'); });
        this.classList.add('active');
      });
    });
  }

  /* ====================================================
   * 3. Cart AJAX functions
   * ==================================================== */

  /**
   * Resolve the cart API endpoint from a data attribute on <body> or fall back
   * to the default relative path. Set data-cart-api="/api/cart.php" on <body>
   * (or window.LuxeConfig.cartApi) to override for non-root deployments.
   */
  function getCartApiUrl() {
    if (window.LuxeConfig && window.LuxeConfig.cartApi) {
      return window.LuxeConfig.cartApi;
    }
    var body = document.body;
    if (body && body.getAttribute('data-cart-api')) {
      return body.getAttribute('data-cart-api');
    }
    return '/api/cart.php';
  }

  /**
   * POST helper that returns a Promise resolving with parsed JSON.
   * @param {string} action
   * @param {Object} data
   * @returns {Promise}
   */
  function cartRequest(action, data) {
    var formData = new FormData();
    formData.append('action', action);
    formData.append('csrf_token', getCsrfToken());
    Object.keys(data).forEach(function (key) {
      formData.append(key, data[key]);
    });

    return fetch(getCartApiUrl(), {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Network response was not ok: ' + response.status);
      }
      return response.json();
    });
  }

  function addToCart(productId, quantity) {
    return cartRequest('add', { product_id: productId, quantity: quantity || 1 });
  }

  function updateCart(cartId, quantity) {
    return cartRequest('update', { cart_id: cartId, quantity: quantity });
  }

  function removeCart(cartId) {
    return cartRequest('remove', { cart_id: cartId });
  }

  function updateCartBadge(count) {
    var badge = document.getElementById('cartCount');
    if (badge) {
      badge.textContent = count;
    }
  }

  /* ====================================================
   * 4. Cart page: qty controls + remove
   * ==================================================== */
  function initCartPage() {
    var cartTable = document.querySelector('.cart-table');
    if (!cartTable) return;

    // Quantity increase
    cartTable.addEventListener('click', function (e) {
      var btn = e.target.closest('.qty-increase');
      if (btn) {
        var input = btn.closest('.quantity-control').querySelector('.qty-input');
        var cartId = input.getAttribute('data-cart-id');
        var max = parseInt(input.getAttribute('max') || '999', 10);
        var newQty = parseInt(input.value, 10) + 1;
        if (newQty > max) newQty = max;
        input.value = newQty;
        updateCart(cartId, newQty).then(function (res) {
          if (res.success) {
            refreshCartTotals(res);
            updateCartBadge(res.cart_count);
          } else {
            showToast(res.message || 'Error updating cart.', 'error');
          }
        }).catch(function () {
          showToast('Error updating cart.', 'error');
        });
      }
    });

    // Quantity decrease
    cartTable.addEventListener('click', function (e) {
      var btn = e.target.closest('.qty-decrease');
      if (btn) {
        var input = btn.closest('.quantity-control').querySelector('.qty-input');
        var cartId = input.getAttribute('data-cart-id');
        var newQty = parseInt(input.value, 10) - 1;
        if (newQty < 1) newQty = 1;
        input.value = newQty;
        updateCart(cartId, newQty).then(function (res) {
          if (res.success) {
            refreshCartTotals(res);
            updateCartBadge(res.cart_count);
          } else {
            showToast(res.message || 'Error updating cart.', 'error');
          }
        }).catch(function () {
          showToast('Error updating cart.', 'error');
        });
      }
    });

    // Qty input direct change (blur)
    cartTable.addEventListener('change', function (e) {
      var input = e.target.closest('.qty-input');
      if (input) {
        var cartId = input.getAttribute('data-cart-id');
        var min = parseInt(input.getAttribute('min') || '1', 10);
        var max = parseInt(input.getAttribute('max') || '999', 10);
        var newQty = parseInt(input.value, 10);
        if (isNaN(newQty) || newQty < min) newQty = min;
        if (newQty > max) newQty = max;
        input.value = newQty;
        updateCart(cartId, newQty).then(function (res) {
          if (res.success) {
            refreshCartTotals(res);
            updateCartBadge(res.cart_count);
          }
        }).catch(function () {
          showToast('Error updating cart.', 'error');
        });
      }
    });

    // Remove item
    cartTable.addEventListener('click', function (e) {
      var btn = e.target.closest('.remove-cart-btn');
      if (btn) {
        var cartId = btn.getAttribute('data-cart-id');
        var row = btn.closest('tr');
        removeCart(cartId).then(function (res) {
          if (res.success) {
            if (row) {
              row.style.opacity = '0';
              row.style.transition = 'opacity 0.3s ease';
              setTimeout(function () { row.remove(); }, 320);
            }
            updateCartBadge(res.cart_count);
            refreshCartTotals(res);
            showToast('Item removed from cart.', 'success');
            if (res.cart_count === 0) {
              location.reload();
            }
          } else {
            showToast(res.message || 'Error removing item.', 'error');
          }
        }).catch(function () {
          showToast('Error removing item.', 'error');
        });
      }
    });
  }

  function refreshCartTotals(res) {
    if (res.subtotal !== undefined) {
      var el = document.getElementById('cart-subtotal');
      if (el) el.textContent = res.subtotal;
    }
    if (res.total !== undefined) {
      var totalEl = document.getElementById('cart-total');
      if (totalEl) totalEl.textContent = res.total;
    }
  }

  /* ====================================================
   * 5. showToast
   * ==================================================== */
  function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    if (!container) return;

    var icons = {
      success: 'fas fa-check-circle text-success',
      error:   'fas fa-times-circle text-danger',
      warning: 'fas fa-exclamation-triangle text-warning',
      info:    'fas fa-info-circle text-info'
    };
    var iconClass = icons[type] || icons.info;

    var toastId = 'toast-' + Date.now();
    var toastEl = document.createElement('div');
    toastEl.id = toastId;
    toastEl.className = 'toast toast-' + type;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML =
      '<div class="toast-header">' +
        '<i class="' + iconClass + ' me-2"></i>' +
        '<strong class="me-auto">Luxe Marble</strong>' +
        '<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="toast"></button>' +
      '</div>' +
      '<div class="toast-body">' + message + '</div>';

    container.appendChild(toastEl);

    var bsToast = new bootstrap.Toast(toastEl, { delay: 5000, autohide: true });
    bsToast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
      toastEl.remove();
    });
  }

  // Expose globally for PHP-embedded scripts
  window.showToast = showToast;

  /* ====================================================
   * 6. Add to Cart buttons
   * ==================================================== */
  function initAddToCartButtons() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.add-to-cart-btn');
      if (!btn) return;
      e.preventDefault();

      var productId = btn.getAttribute('data-product-id');
      if (!productId) return;

      var qtyInput = document.getElementById('product-qty');
      var qty = qtyInput ? parseInt(qtyInput.value, 10) : 1;
      if (isNaN(qty) || qty < 1) qty = 1;

      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Adding...';

      addToCart(productId, qty).then(function (res) {
        btn.disabled = false;
        btn.innerHTML = btn.getAttribute('data-original-text') || '<i class="fas fa-cart-plus me-1"></i> Add to Cart';
        if (res.success) {
          updateCartBadge(res.cart_count);
          showToast('Added to cart!', 'success');
        } else {
          showToast(res.message || 'Could not add to cart.', 'error');
        }
      }).catch(function () {
        btn.disabled = false;
        btn.innerHTML = btn.getAttribute('data-original-text') || '<i class="fas fa-cart-plus me-1"></i> Add to Cart';
        showToast('Error adding to cart. Please try again.', 'error');
      });
    });

    // Save original text
    document.querySelectorAll('.add-to-cart-btn').forEach(function (btn) {
      btn.setAttribute('data-original-text', btn.innerHTML);
    });
  }

  /* ====================================================
   * 7 & 8. Checkout: payment method toggle + Razorpay
   * ==================================================== */
  function initCheckout() {
    var paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    if (!paymentRadios.length) return;

    var sections = {
      razorpay: document.getElementById('razorpay-section'),
      cod: document.getElementById('cod-section'),
      bank: document.getElementById('bank-section')
    };

    function showPaymentSection(method) {
      Object.keys(sections).forEach(function (key) {
        if (sections[key]) {
          sections[key].style.display = (key === method) ? 'block' : 'none';
        }
      });
      // Toggle selected class on payment options
      document.querySelectorAll('.payment-option').forEach(function (opt) {
        opt.classList.remove('selected');
      });
      var active = document.querySelector('.payment-option[data-method="' + method + '"]');
      if (active) active.classList.add('selected');
    }

    paymentRadios.forEach(function (radio) {
      radio.addEventListener('change', function () {
        showPaymentSection(this.value);
      });
    });

    // Clickable payment option containers
    document.querySelectorAll('.payment-option').forEach(function (opt) {
      opt.addEventListener('click', function () {
        var method = this.getAttribute('data-method');
        var radio = document.querySelector('input[name="payment_method"][value="' + method + '"]');
        if (radio) {
          radio.checked = true;
          showPaymentSection(method);
        }
      });
    });

    // Razorpay form submit
    var razorpayForm = document.getElementById('razorpay-form');
    if (razorpayForm) {
      razorpayForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var options = {
          key:         razorpayForm.getAttribute('data-key'),
          amount:      razorpayForm.getAttribute('data-amount'),
          currency:    razorpayForm.getAttribute('data-currency') || 'INR',
          name:        'Luxe Marble',
          description: 'Order Payment',
          order_id:    razorpayForm.getAttribute('data-order-id'),
          handler:     function (response) {
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_order_id').value   = response.razorpay_order_id;
            document.getElementById('razorpay_signature').value  = response.razorpay_signature;
            razorpayForm.submit();
          },
          prefill: {
            name:  razorpayForm.getAttribute('data-prefill-name')  || '',
            email: razorpayForm.getAttribute('data-prefill-email') || '',
            contact: razorpayForm.getAttribute('data-prefill-phone') || ''
          },
          theme: { color: '#C8A96E' }
        };
        initRazorpay(options);
      });
    }
  }

  function initRazorpay(options) {
    if (typeof Razorpay === 'undefined') {
      showToast('Payment gateway not loaded. Please refresh the page.', 'error');
      return;
    }
    var rzp = new Razorpay(options);
    rzp.on('payment.failed', function (response) {
      showToast('Payment failed: ' + (response.error.description || 'Please try again.'), 'error');
    });
    rzp.open();
  }

  /* ====================================================
   * 9. OTP inputs: auto-advance and backspace
   * ==================================================== */
  function initOtpInputs() {
    var otpInputs = document.querySelectorAll('.otp-input');
    if (!otpInputs.length) return;

    otpInputs.forEach(function (input, index) {
      input.addEventListener('keyup', function (e) {
        var val = this.value;

        // Allow only digits
        this.value = val.replace(/\D/g, '').slice(0, 1);

        if (this.value && index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }

        if (e.key === 'Backspace' && !this.value && index > 0) {
          otpInputs[index - 1].focus();
          otpInputs[index - 1].value = '';
        }
      });

      input.addEventListener('paste', function (e) {
        e.preventDefault();
        var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        pasted.split('').forEach(function (char, i) {
          if (otpInputs[index + i]) {
            otpInputs[index + i].value = char;
          }
        });
        var next = index + pasted.length;
        if (next < otpInputs.length) {
          otpInputs[next].focus();
        } else {
          otpInputs[otpInputs.length - 1].focus();
        }
      });

      input.addEventListener('focus', function () {
        this.select();
      });
    });
  }

  /* ====================================================
   * 10. Product page qty controls
   * ==================================================== */
  function initProductQty() {
    var qtyInput   = document.getElementById('product-qty');
    var btnInc     = document.getElementById('qty-increase');
    var btnDec     = document.getElementById('qty-decrease');
    if (!qtyInput || !btnInc || !btnDec) return;

    var maxStock = parseInt(qtyInput.getAttribute('data-max') || qtyInput.getAttribute('max') || '999', 10);

    btnInc.addEventListener('click', function () {
      var val = parseInt(qtyInput.value, 10);
      if (val < maxStock) {
        qtyInput.value = val + 1;
      } else {
        showToast('Maximum available quantity is ' + maxStock + '.', 'warning');
      }
    });

    btnDec.addEventListener('click', function () {
      var val = parseInt(qtyInput.value, 10);
      if (val > 1) {
        qtyInput.value = val - 1;
      }
    });

    qtyInput.addEventListener('change', function () {
      var val = parseInt(this.value, 10);
      if (isNaN(val) || val < 1) this.value = 1;
      if (val > maxStock) this.value = maxStock;
    });
  }

  /* ====================================================
   * 11. Form validation
   * ==================================================== */
  function initFormValidation() {
    // Password match on register form
    var password = document.getElementById('password');
    var confirmPwd = document.getElementById('confirm_password');
    var matchMsg = document.getElementById('password-match-msg');

    if (password && confirmPwd && matchMsg) {
      function checkMatch() {
        if (confirmPwd.value === '') {
          matchMsg.textContent = '';
          return;
        }
        if (password.value === confirmPwd.value) {
          matchMsg.textContent = '✓ Passwords match';
          matchMsg.style.color = '#28a745';
          confirmPwd.setCustomValidity('');
        } else {
          matchMsg.textContent = '✗ Passwords do not match';
          matchMsg.style.color = '#dc3545';
          confirmPwd.setCustomValidity('Passwords do not match');
        }
      }
      password.addEventListener('input', checkMatch);
      confirmPwd.addEventListener('input', checkMatch);
    }

    // Phone number validation (10 digits)
    var phoneInputs = document.querySelectorAll('input[type="tel"], input[name="phone"]');
    phoneInputs.forEach(function (input) {
      input.addEventListener('blur', function () {
        var digits = this.value.replace(/\D/g, '');
        if (this.value && digits.length !== 10) {
          this.setCustomValidity('Please enter a valid 10-digit phone number.');
          this.classList.add('is-invalid');
        } else {
          this.setCustomValidity('');
          this.classList.remove('is-invalid');
        }
      });
      input.addEventListener('input', function () {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
      });
    });

    // Bootstrap validation on form submit
    var forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
          // Scroll to first invalid field
          var firstInvalid = form.querySelector(':invalid');
          if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
          }
        }
        form.classList.add('was-validated');
      });
    });
  }

  /* ====================================================
   * 12. Search form: prevent empty submit
   * ==================================================== */
  function initSearchForm() {
    var searchForms = document.querySelectorAll('.search-form, form[role="search"]');
    searchForms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        var input = form.querySelector('input[type="search"], input[name="q"], input[name="search"]');
        if (input && input.value.trim() === '') {
          e.preventDefault();
          input.focus();
          input.classList.add('is-invalid');
          setTimeout(function () { input.classList.remove('is-invalid'); }, 2000);
        }
      });
    });
  }

  /* ====================================================
   * 13. Price range filter
   * ==================================================== */
  function initPriceRangeFilter() {
    var rangeInput = document.getElementById('price-range');
    var rangeValue = document.getElementById('price-range-value');
    if (!rangeInput) return;

    function filterProducts(maxPrice) {
      var products = document.querySelectorAll('.product-item[data-price]');
      products.forEach(function (item) {
        var price = parseFloat(item.getAttribute('data-price'));
        item.style.display = price <= maxPrice ? '' : 'none';
      });
    }

    rangeInput.addEventListener('input', function () {
      var val = parseInt(this.value, 10);
      if (rangeValue) {
        rangeValue.textContent = '₹' + val.toLocaleString('en-IN');
      }
      filterProducts(val);
    });

    // Initialize display
    var initVal = parseInt(rangeInput.value, 10);
    if (rangeValue && initVal) {
      rangeValue.textContent = '₹' + initVal.toLocaleString('en-IN');
    }
  }

  /* ====================================================
   * 14. Lazy loading with IntersectionObserver
   * ==================================================== */
  function initLazyLoading() {
    var lazyImages = document.querySelectorAll('.lazy-img');
    if (!lazyImages.length) return;

    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var img = entry.target;
            var src = img.getAttribute('data-src');
            if (src) {
              img.src = src;
              img.removeAttribute('data-src');
            }
            img.classList.add('loaded');
            observer.unobserve(img);
          }
        });
      }, { rootMargin: '100px 0px', threshold: 0.01 });

      lazyImages.forEach(function (img) {
        observer.observe(img);
      });
    } else {
      // Fallback: load all images immediately
      lazyImages.forEach(function (img) {
        var src = img.getAttribute('data-src');
        if (src) img.src = src;
        img.classList.add('loaded');
      });
    }
  }

  /* ====================================================
   * 15. Smooth scroll for anchor links
   * ==================================================== */
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        var targetId = this.getAttribute('href');
        if (targetId === '#') return;
        var target = document.querySelector(targetId);
        if (target) {
          e.preventDefault();
          var navHeight = document.getElementById('mainNav') ? document.getElementById('mainNav').offsetHeight : 0;
          var top = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;
          window.scrollTo({ top: top, behavior: 'smooth' });
        }
      });
    });
  }

  /* ====================================================
   * 16. Sticky header: add class when scrolled
   * (already handled by initNavbarScroll, this tracks offset)
   * ==================================================== */
  function initStickyHeader() {
    var nav = document.getElementById('mainNav');
    if (!nav) return;
    // Expose header height as CSS variable for offset calculations
    function setNavHeight() {
      document.documentElement.style.setProperty('--nav-height', nav.offsetHeight + 'px');
    }
    setNavHeight();
    window.addEventListener('resize', setNavHeight, { passive: true });
  }

  /* ====================================================
   * 17. Mobile menu: close on nav link click
   * ==================================================== */
  function initMobileMenu() {
    var navbarCollapse = document.getElementById('navbarNav');
    if (!navbarCollapse) return;

    var navLinks = navbarCollapse.querySelectorAll('.nav-link:not(.dropdown-toggle)');
    navLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 992) {
          var bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
          if (bsCollapse) {
            bsCollapse.hide();
          } else {
            navbarCollapse.classList.remove('show');
          }
        }
      });
    });
  }

  /* ====================================================
   * DOMContentLoaded: initialise everything
   * ==================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    initNavbarScroll();
    initProductGallery();
    initCartPage();
    initAddToCartButtons();
    initCheckout();
    initOtpInputs();
    initProductQty();
    initFormValidation();
    initSearchForm();
    initPriceRangeFilter();
    initLazyLoading();
    initSmoothScroll();
    initStickyHeader();
    initMobileMenu();
  });

})();
