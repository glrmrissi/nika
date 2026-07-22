document.addEventListener('DOMContentLoaded', function () {
  var sidebar = document.getElementById('sidebar');
  var toggleBtn = document.getElementById('sidebar-toggle');
  var toggleIcon = document.getElementById('sidebar-toggle-icon');

  if (!sidebar || !toggleBtn) return;

  var defaultCollapsed = document.body.classList.contains('review-page');
  var stored = localStorage.getItem('sidebarCollapsed');
  var isCollapsed = stored !== null ? (stored === 'true') : defaultCollapsed;

  if (isCollapsed) {
    sidebar.classList.add('sidebar--collapsed');
    document.body.dataset.sb = '1';
    if (toggleIcon) {
      toggleIcon.className = 'bi bi-chevron-right';
    }
  }

  toggleBtn.addEventListener('click', function () {
    sidebar.classList.toggle('sidebar--collapsed');
    var nowCollapsed = sidebar.classList.contains('sidebar--collapsed');

    if (toggleIcon) {
      toggleIcon.className = nowCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
    }

    if (nowCollapsed) {
      document.body.dataset.sb = '1';
    } else {
      delete document.body.dataset.sb;
    }

    localStorage.setItem('sidebarCollapsed', nowCollapsed);
  });

  var html = document.documentElement;
  var themeToggle = document.getElementById('theme-toggle');
  var themeIcon = document.getElementById('theme-toggle-icon');
  var themeLabel = document.getElementById('theme-toggle-label');

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

  function updateThemeUI(t) {
    if (!themeIcon || !themeLabel) return;
    if (t === 'dark') {
      themeIcon.className = 'bi bi-sun';
      themeLabel.textContent = 'Modo Claro';
    } else {
      themeIcon.className = 'bi bi-moon-stars';
      themeLabel.textContent = 'Modo Escuro';
    }
  }
});
