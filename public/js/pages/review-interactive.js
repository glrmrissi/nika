var currentKanji = null;
var reviewed = 0;
var total = 0;

function escapeHtml(str) {
  if (!str) return '';
  var div = document.createElement('div');
  div.appendChild(document.createTextNode(str));
  return div.innerHTML;
}

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

  var inputArea = document.getElementById('input-area');
  var resultArea = document.getElementById('result-area');
  var nextBtn = document.getElementById('next-btn');
  var input = document.getElementById('reading-input');

  inputArea.style.display = 'flex';
  resultArea.style.display = 'none';
  nextBtn.style.display = 'none';

  input.value = '';
  input.focus();

  document.getElementById('review-progress').textContent = reviewed + ' / ' + total;
  updateProgress();
}

function normalizeReading(str) {
  return str.replace(/\./g, '').replace(/[\u30A1-\u30F6]/g, function(ch) {
    return String.fromCharCode(ch.charCodeAt(0) - 0x60);
  }).trim();
}

function getCorrectReadings(kanji) {
  var readings = [];
  if (kanji.onyomi) {
    kanji.onyomi.split(',').forEach(function(r) {
      var trimmed = r.trim();
      if (trimmed) readings.push({ value: trimmed, type: 'onyomi' });
    });
  }
  if (kanji.kunyomi) {
    kanji.kunyomi.split(',').forEach(function(r) {
      var trimmed = r.trim();
      if (trimmed) readings.push({ value: trimmed, type: 'kunyomi' });
    });
  }
  return readings;
}

function checkAnswer() {
  if (!currentKanji) return;

  var input = document.getElementById('reading-input');
  var answer = normalizeReading(input.value);
  if (!answer) return;

  var readings = getCorrectReadings(currentKanji);
  var correct = readings.some(function(r) {
    return normalizeReading(r.value) === answer;
  });

  var quality = correct ? 4 : 1;

  fetch('/review/submit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ kanji_id: currentKanji.id, quality: quality })
  }).then(function() {
    showResult(correct, readings);
  });
}

function showResult(correct, readings) {
  var inputArea = document.getElementById('input-area');
  var resultArea = document.getElementById('result-area');
  var resultText = document.getElementById('result-text');
  var readingsList = document.getElementById('readings-list');
  var nextBtn = document.getElementById('next-btn');

  inputArea.style.display = 'none';
  resultArea.style.display = 'block';
  nextBtn.style.display = 'block';

  if (correct) {
    resultText.textContent = 'Correct!';
    resultText.className = 'review-result__text review-result__text--correct';
  } else {
    resultText.textContent = 'Wrong';
    resultText.className = 'review-result__text review-result__text--wrong';
  }

  var html = '';
  readings.forEach(function(r) {
    var cls = r.type === 'onyomi' ? 'review-readings__item review-readings__item--onyomi' : 'review-readings__item review-readings__item--kunyomi';
    html += '<span class="' + cls + '">' + escapeHtml(r.value) + '</span>';
  });
  readingsList.innerHTML = html;

  reviewed++;
  updateProgress();
}

function nextKanji() {
  fetchNext();
}

function fetchNext() {
  fetch('/review/next').then(function(r) { return r.json(); }).then(function(data) {
    if (data.done) {
      showDone();
      return;
    }
    currentKanji = data.kanji;
    showKanji(currentKanji);
  });
}

function showDone() {
  document.getElementById('kanji-char').innerHTML = '<img src="/assets/anime-happy.png" alt="done" class="review-done-img">';
  document.getElementById('input-area').style.display = 'none';
  document.getElementById('result-area').style.display = 'none';
  document.getElementById('next-btn').style.display = 'none';
  document.getElementById('review-progress').textContent = reviewed + ' kanji reviewed';
  document.getElementById('progress-fill').style.width = '100%';
}

document.addEventListener('keydown', function(e) {
  if (e.target.tagName !== 'INPUT') return;

  var inputArea = document.getElementById('input-area');
  var resultArea = document.getElementById('result-area');

  if (e.key === 'Enter') {
    e.preventDefault();
    if (inputArea && inputArea.style.display !== 'none') {
      checkAnswer();
    } else if (resultArea && resultArea.style.display !== 'none') {
      nextKanji();
    }
  }
});

document.getElementById('reading-input').addEventListener('input', function() {
  this.value = this.value.replace(/[^ぁ-んァ-ヶー]/g, '');
});
