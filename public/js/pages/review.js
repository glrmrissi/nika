var currentKanji = null;
var reviewed = 0;
var total = 0;

function startReview() {
  document.getElementById('review-area').style.display = 'flex';
  document.getElementById('review-area').style.flexDirection = 'column';
  document.getElementById('review-area').style.alignItems = 'center';
  fetchNext();
}

function showKanji(kanji) {
  document.getElementById('kanji-char').textContent = kanji.character;
  document.getElementById('kanji-onyomi').textContent = '\u97F3: ' + kanji.onyomi;
  document.getElementById('kanji-kunyomi').textContent = '\u8A13: ' + kanji.kunyomi;
  document.getElementById('kanji-meanings').textContent = kanji.meanings;
  var meta = kanji.jlptLevel;
  if (kanji.strokeCount) meta += ' \u00B7 ' + kanji.strokeCount + ' tra\u00E7os';
  document.getElementById('kanji-level').textContent = meta;

  document.getElementById('kanji-info').classList.remove('review-card__info--visible');
  document.getElementById('quality-buttons').style.display = 'none';
  document.getElementById('show-answer-btn').style.display = 'block';
  document.getElementById('review-progress').textContent = 'Revisado: ' + reviewed + ' / ' + total;
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
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ kanji_id: currentKanji.id, quality: quality })
  }).then(function (r) { return r.json(); }).then(function () {
    reviewed++;
    fetchNext();
  });
}

function showDone() {
  document.getElementById('kanji-char').textContent = '\uD83C\uDF89';
  document.getElementById('kanji-info').classList.remove('review-card__info--visible');
  document.getElementById('quality-buttons').style.display = 'none';
  document.getElementById('show-answer-btn').style.display = 'none';
  document.getElementById('review-progress').textContent = 'Revis\u00E3o completa! ' + reviewed + ' kanji revisados.';
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
