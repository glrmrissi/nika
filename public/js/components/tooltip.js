(function() {
  var tooltip = null;
  var showTimeout = null;
  var hideTimeout = null;
  var currentTarget = null;

  var TOOLTIP_CLASS = 'c-tooltip';
  var DELAY_SHOW = 50;
  var DELAY_HIDE = 100;
  var OFFSET = 8;

  function create() {
    if (tooltip) return tooltip;
    tooltip = document.createElement('div');
    tooltip.className = TOOLTIP_CLASS;
    tooltip.setAttribute('role', 'tooltip');
    document.body.appendChild(tooltip);
    return tooltip;
  }

  function position(el, tip) {
    var er = el.getBoundingClientRect();
    var tr = tip.getBoundingClientRect();
    var top = er.top - tr.height - OFFSET;
    var left = er.left + (er.width / 2) - (tr.width / 2);

    if (top < 4) {
      top = er.bottom + OFFSET;
      tip.classList.add('c-tooltip--bottom');
    } else {
      tip.classList.remove('c-tooltip--bottom');
    }

    if (left < 4) left = 4;
    if (left + tr.width > window.innerWidth - 4) {
      left = window.innerWidth - tr.width - 4;
    }

    tip.style.top = top + 'px';
    tip.style.left = left + 'px';
  }

  function show(el, text) {
    if (hideTimeout) { clearTimeout(hideTimeout); hideTimeout = null; }
    if (showTimeout) { clearTimeout(showTimeout); showTimeout = null; }

    if (currentTarget === el && tooltip) {
      tooltip.classList.add('c-tooltip--visible');
      position(el, tooltip);
      return;
    }

    currentTarget = el;
    showTimeout = setTimeout(function() {
      var tip = create();
      tip.textContent = text;
      tip.classList.add('c-tooltip--visible');
      position(el, tip);
      showTimeout = null;
    }, DELAY_SHOW);
  }

  function hide(el) {
    if (showTimeout) { clearTimeout(showTimeout); showTimeout = null; }
    if (currentTarget !== el) return;

    hideTimeout = setTimeout(function() {
      if (tooltip) {
        tooltip.classList.remove('c-tooltip--visible');
      }
      currentTarget = null;
      hideTimeout = null;
    }, DELAY_HIDE);
  }

  document.addEventListener('mouseenter', function(e) {
    var target = e.target.closest('[data-tooltip]');
    if (!target) return;
    show(target, target.getAttribute('data-tooltip'));
  }, true);

  document.addEventListener('mouseleave', function(e) {
    var target = e.target.closest('[data-tooltip]');
    if (!target) return;
    hide(target);
  }, true);

  document.addEventListener('focusin', function(e) {
    var target = e.target.closest('[data-tooltip]');
    if (!target) return;
    show(target, target.getAttribute('data-tooltip'));
  });

  document.addEventListener('focusout', function(e) {
    var target = e.target.closest('[data-tooltip]');
    if (!target) return;
    hide(target);
  });
})();
