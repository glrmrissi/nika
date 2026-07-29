document.addEventListener('DOMContentLoaded', function () {
  var html = document.documentElement;
  var themeToggle = document.getElementById('theme-toggle');
  var themeIcon = document.getElementById('theme-toggle-icon');

  var savedTheme = localStorage.getItem('theme');
  var theme = savedTheme || 'dark';

  html.setAttribute('data-theme', theme);
  updateThemeUI(theme);

  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var current = html.getAttribute('data-theme');
      var next = current === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem('theme', next);
      updateThemeUI(next);
    });
  }

  var avatar = document.querySelector('.topbar__avatar');
  if (avatar) {
    avatar.addEventListener('click', function (e) {
      var menu = this.closest('.topbar__user-menu');
      var dropdown = menu.querySelector('.topbar__dropdown');
      var isOpen = dropdown.classList.contains('topbar__dropdown--open');
      document.querySelectorAll('.topbar__dropdown--open').forEach(function (d) {
        d.classList.remove('topbar__dropdown--open');
      });
      if (!isOpen) {
        dropdown.classList.add('topbar__dropdown--open');
      }
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.topbar__user-menu')) {
        document.querySelectorAll('.topbar__dropdown--open').forEach(function (d) {
          d.classList.remove('topbar__dropdown--open');
        });
      }
    });
  }

  document.querySelectorAll('[data-modal]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var msg = btn.getAttribute('data-modal-message') || 'Are you sure?';
      var confirmText = btn.getAttribute('data-modal-confirm') || 'Confirm';
      var cancelText = btn.getAttribute('data-modal-cancel') || 'Cancel';
      var action = btn.getAttribute('data-modal-action') || btn.getAttribute('href') || '';

      var overlay = document.createElement('div');
      overlay.className = 'modal-overlay';

      overlay.innerHTML = '<div class="modal">'
        + '<div class="modal__title">' + (btn.getAttribute('data-modal-title') || 'Confirm') + '</div>'
        + '<div class="modal__text">' + msg + '</div>'
        + '<div class="modal__actions">'
        + '<button class="btn btn--secondary btn--sm" data-modal-cancel>' + cancelText + ' (N)</button>'
        + '<button class="btn btn--primary btn--sm" data-modal-confirm>' + confirmText + ' (Y)</button>'
        + '</div>'
        + '</div>';

      document.body.appendChild(overlay);
      requestAnimationFrame(function () {
        overlay.classList.add('modal-overlay--open');
      });

      function closeModal() {
        overlay.classList.remove('modal-overlay--open');
        setTimeout(function () { overlay.remove(); }, 150);
      }

      function handleKeydown(ev) {
        if (ev.key === 'y' || ev.key === 'Y') {
          document.removeEventListener('keydown', handleKeydown);
          confirmAction();
        } else if (ev.key === 'n' || ev.key === 'N' || ev.key === 'Escape') {
          document.removeEventListener('keydown', handleKeydown);
          closeModal();
        }
      }
      document.addEventListener('keydown', handleKeydown);

      overlay.querySelector('[data-modal-cancel]').addEventListener('click', function () {
        document.removeEventListener('keydown', handleKeydown);
        closeModal();
      });

      overlay.addEventListener('click', function (ev) {
        if (ev.target === overlay) {
          document.removeEventListener('keydown', handleKeydown);
          closeModal();
        }
      });

      function confirmAction() {
        overlay.classList.remove('modal-overlay--open');
        setTimeout(function () { overlay.remove(); }, 150);
        if (btn.tagName === 'BUTTON' && btn.form) {
          btn.form.submit();
        } else if (action) {
          var form = document.createElement('form');
          form.method = 'POST';
          form.action = action;
          var csrf = document.createElement('input');
          csrf.type = 'hidden';
          csrf.name = '_token';
          csrf.value = btn.getAttribute('data-modal-token') || '';
          form.appendChild(csrf);
          document.body.appendChild(form);
          form.submit();
        }
      }

      overlay.querySelector('[data-modal-confirm]').addEventListener('click', function () {
        document.removeEventListener('keydown', handleKeydown);
        confirmAction();
      });
    });
  });

  function updateThemeUI(t) {
    if (!themeIcon) return;
    themeIcon.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
  }

  var sidebarToggle = document.getElementById('sidebar-toggle');
  var sidebar = document.getElementById('sidebar');
  var sidebarOverlay = document.getElementById('sidebar-overlay');
  var sidebarClose = document.getElementById('sidebar-close');

  function openSidebar() {
    if (sidebar) sidebar.classList.add('sidebar--open');
    if (sidebarOverlay) sidebarOverlay.classList.add('sidebar-overlay--open');
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('sidebar--open');
    if (sidebarOverlay) sidebarOverlay.classList.remove('sidebar-overlay--open');
  }

  if (sidebarToggle) sidebarToggle.addEventListener('click', openSidebar);
  if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
  if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

  var scrollBtn = document.getElementById('scroll-top');
  if (scrollBtn) {
    var fill = scrollBtn.querySelector('.scroll-top__ring-fill');
    var circumference = 160;
    var ticking = false;

    function updateScrollRing() {
      var scrollTop = window.scrollY || document.documentElement.scrollTop;
      var docHeight = document.documentElement.scrollHeight - window.innerHeight;

      if (docHeight <= 0) {
        scrollBtn.classList.remove('scroll-top--visible');
        return;
      }

      if (scrollTop > 80) {
        scrollBtn.classList.add('scroll-top--visible');
      } else {
        scrollBtn.classList.remove('scroll-top--visible');
      }

      var progress = Math.min(scrollTop / docHeight, 1);
      var offset = circumference - (progress * circumference);
      fill.style.strokeDashoffset = offset;
      ticking = false;
    }

    function onScroll() {
      if (!ticking) {
        requestAnimationFrame(function () {
          updateScrollRing();
        });
        ticking = true;
      }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    updateScrollRing();

    scrollBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
});