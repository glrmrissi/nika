var currentKanji = null;
var reviewed = 0;
var total = 0;

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
  var pct = Math.round((reviewed / total) * 100);
  document.getElementById('progress-fill').style.width = pct + '%';
}

function showKanji(kanji) {
  document.getElementById('kanji-char').textContent = kanji.character;
  document.getElementById('kanji-onyomi').textContent = '\u97F3: ' + kanji.onyomi;
  document.getElementById('kanji-kunyomi').textContent = '\u8A13: ' + kanji.kunyomi;
  document.getElementById('kanji-meanings').textContent = kanji.meanings;
  var meta = kanji.jlptLevel;
  if (kanji.strokeCount) meta += ' \u00B7 ' + kanji.strokeCount + ' strokes';
  document.getElementById('kanji-level').textContent = meta;

  document.getElementById('kanji-info').classList.remove('review-card__info--visible');
  document.getElementById('quality-buttons').style.display = 'none';
  document.getElementById('show-answer-btn').style.display = 'block';
  document.getElementById('show-answer-btn').focus();
  document.getElementById('review-progress').textContent = reviewed + ' / ' + total;
  updateProgress();
}

function showAnswer() {
  document.getElementById('kanji-info').classList.add('review-card__info--visible');
  document.getElementById('quality-buttons').style.display = 'flex';
  document.getElementById('quality-buttons').style.flexDirection = 'column';
  document.getElementById('quality-buttons').style.alignItems = 'center';
  document.getElementById('show-answer-btn').style.display = 'none';
}

function submitReview(quality) {
  if (!currentKanji) return;

  fetch('/review/submit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ kanji_id: currentKanji.id, quality: quality })
  }).then(function (r) { return r.json(); }).then(function () {
    reviewed++;
    fetchNext();
  });
}

function showDone() {
  document.getElementById('kanji-char').innerHTML = '<img src="/assets/anime-happy.png" alt="done" class="review-done-img">';
  document.getElementById('kanji-info').classList.remove('review-card__info--visible');
  document.getElementById('quality-buttons').style.display = 'none';
  document.getElementById('show-answer-btn').style.display = 'none';
  document.getElementById('review-progress').textContent = reviewed + ' kanji reviewed';
  document.getElementById('progress-fill').style.width = '100%';
}

function fetchNext() {
  var params = new URLSearchParams();

  fetch('/review/next?' + params.toString()).then(function (r) { return r.json(); }).then(function (data) {
    if (data.done) {
      showDone();
      return;
    }
    currentKanji = data.kanji;
    showKanji(currentKanji);
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
    var map = { '1': 0, '2': 1, '3': 2, '4': 3, '5': 4, '6': 5 };
    if (map[e.key] !== undefined) {
      e.preventDefault();
      submitReview(map[e.key]);
    }
  }
});
