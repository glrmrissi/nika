var currentKanji = null;
var currentIntervals = {};
var currentStage = '';
var reviewed = 0;
var total = 0;
var submitting = false;

function startReview() {
  document.getElementById('start-btn').style.display = 'none';
  document.getElementById('review-area').style.display = 'flex';
  document.getElementById('review-area').style.flexDirection = 'column';
  document.getElementById('review-area').style.alignItems = 'center';
  document.getElementById('progress-bar').style.display = 'block';
  fetchNext();
}

function updateProgress() {
  if (total === 0) return;
  var pct = Math.min(100, Math.round((reviewed / total) * 100));
  document.getElementById('progress-fill').style.width = pct + '%';
}

function showKanji(kanji) {
  submitting = false;
  document.getElementById('kanji-char').textContent = kanji.character;
  document.getElementById('kanji-onyomi').textContent = '\u97F3: ' + kanji.onyomi;
  document.getElementById('kanji-kunyomi').textContent = '\u8A13: ' + kanji.kunyomi;
  document.getElementById('kanji-meanings').textContent = kanji.meanings;
  var meta = kanji.jlptLevel;
  if (kanji.strokeCount) meta += ' \u00B7 ' + kanji.strokeCount + ' strokes';
  document.getElementById('kanji-level').textContent = meta;

  var stageEl = document.getElementById('kanji-stage');
  stageEl.textContent = currentStage;
  stageEl.style.display = currentStage ? 'inline-block' : 'none';

  var labels = { 1: 'Rate Again', 2: 'Rate Hard', 3: 'Rate Good', 4: 'Rate Easy' };
  document.querySelectorAll('.review-rate').forEach(function (button) {
    var rating = button.getAttribute('data-rating');
    var interval = currentIntervals[rating] || '';
    button.disabled = false;
    button.setAttribute('aria-label', labels[rating] + (interval ? ', next review in ' + interval : ''));
    var intervalEl = button.querySelector('.review-rate__iv');
    if (intervalEl) intervalEl.textContent = interval ? '\u00B7 ' + interval : '';
  });

  document.getElementById('kanji-info').classList.remove('review-card__info--visible');
  document.getElementById('quality-buttons').style.display = 'none';
  document.getElementById('show-answer-btn').style.display = 'block';
  document.getElementById('show-answer-btn').focus();
  document.getElementById('review-progress').textContent = reviewed + ' reviewed';
  updateProgress();
}

function showAnswer() {
  if (submitting) return;
  document.getElementById('kanji-info').classList.add('review-card__info--visible');
  document.getElementById('quality-buttons').style.display = 'flex';
  document.getElementById('quality-buttons').style.flexDirection = 'column';
  document.getElementById('quality-buttons').style.alignItems = 'center';
  document.getElementById('show-answer-btn').style.display = 'none';
}

function submitReview(rating) {
  if (!currentKanji || submitting) return;

  submitting = true;
  document.querySelectorAll('.review-rate').forEach(function (button) { button.disabled = true; });

  fetch('/review/submit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ kanji_id: currentKanji.id, rating: rating })
  }).then(function (response) {
    return response.json().then(function (data) {
      if (!response.ok) throw new Error(data.error || 'Could not save review');
      return data;
    });
  }).then(function () {
    reviewed++;
    fetchNext();
  }).catch(function (error) {
    submitting = false;
    document.getElementById('review-progress').textContent = error.message;
    document.querySelectorAll('.review-rate').forEach(function (button) { button.disabled = false; });
  });
}

function showDone() {
  document.getElementById('kanji-char').innerHTML = '<img src="/assets/anime-happy.png" alt="Review complete" width="120" height="120" class="review-done-img">';
  document.getElementById('kanji-stage').style.display = 'none';
  document.getElementById('kanji-info').classList.remove('review-card__info--visible');
  document.getElementById('quality-buttons').style.display = 'none';
  document.getElementById('show-answer-btn').style.display = 'none';
  document.getElementById('review-progress').textContent = reviewed + ' kanji reviewed';
  document.getElementById('progress-fill').style.width = '100%';
}

function fetchNext() {
  fetch('/review/next').then(function (response) {
    return response.json().then(function (data) {
      if (!response.ok) throw new Error(data.error || 'Could not load the next card');
      return data;
    });
  }).then(function (data) {
    if (data.done) {
      showDone();
      return;
    }
    currentKanji = data.kanji;
    currentIntervals = data.intervals || {};
    currentStage = data.stage || '';
    showKanji(currentKanji);
  }).catch(function (error) {
    submitting = false;
    document.getElementById('review-progress').textContent = error.message;
  });
}

document.addEventListener('keydown', function (e) {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

  var showBtn = document.getElementById('show-answer-btn');
  var qualBtns = document.getElementById('quality-buttons');

  if (e.key === ' ' || e.key === 'Enter') {
    if (showBtn && showBtn.style.display !== 'none') {
      e.preventDefault();
      showAnswer();
    }
  }

  if (qualBtns && qualBtns.style.display !== 'none') {
    var map = { '1': 1, '2': 2, '3': 3, '4': 4 };
    if (map[e.key] !== undefined) {
      e.preventDefault();
      submitReview(map[e.key]);
    }
  }
});
