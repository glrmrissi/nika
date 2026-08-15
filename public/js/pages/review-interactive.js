var currentKanji = null;
var currentStage = '';
var reviewed = 0;
var total = 0;
var submitting = false;
var answerSubmitted = false;
var loadingNext = false;

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
  var pct = Math.min(100, Math.round((reviewed / total) * 100));
  document.getElementById('progress-fill').style.width = pct + '%';
}

function showKanji(kanji) {
  submitting = false;
  answerSubmitted = false;
  document.getElementById('kanji-char').textContent = kanji.character;

  var stageEl = document.getElementById('kanji-stage');
  stageEl.textContent = currentStage;
  stageEl.style.display = currentStage ? 'inline-block' : 'none';

  var inputArea = document.getElementById('input-area');
  var resultArea = document.getElementById('result-area');
  var nextBtn = document.getElementById('next-btn');
  var input = document.getElementById('reading-input');
  var checkBtn = document.getElementById('check-btn');

  inputArea.style.display = 'flex';
  resultArea.style.display = 'none';
  nextBtn.style.display = 'none';
  input.disabled = false;
  checkBtn.disabled = false;
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
  if (!currentKanji || submitting || answerSubmitted) return;

  var input = document.getElementById('reading-input');
  var answer = normalizeReading(input.value);
  if (!answer) return;

  var readings = getCorrectReadings(currentKanji);
  var correct = readings.some(function(r) {
    return normalizeReading(r.value) === answer;
  });
  var rating = correct ? 3 : 1;

  submitting = true;
  input.disabled = true;
  document.getElementById('check-btn').disabled = true;

  fetch('/review/submit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ kanji_id: currentKanji.id, rating: rating })
  }).then(function(response) {
    return response.json().then(function(data) {
      if (!response.ok) throw new Error(data.error || 'Could not save review');
      return data;
    });
  }).then(function() {
    submitting = false;
    answerSubmitted = true;
    showResult(correct, readings, rating);
  }).catch(function(error) {
    submitting = false;
    input.disabled = false;
    document.getElementById('check-btn').disabled = false;
    document.getElementById('review-progress').textContent = error.message;
    input.focus();
  });
}

function showResult(correct, readings, rating) {
  var inputArea = document.getElementById('input-area');
  var resultArea = document.getElementById('result-area');
  var resultText = document.getElementById('result-text');
  var resultInterval = document.getElementById('result-interval');
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

  resultInterval.textContent = 'Next review: ' + (currentIntervals[rating] || 'soon');

  var html = '';
  readings.forEach(function(r) {
    var cls = r.type === 'onyomi' ? 'review-readings__item review-readings__item--onyomi' : 'review-readings__item review-readings__item--kunyomi';
    html += '<span class="' + cls + '">' + escapeHtml(r.value) + '</span>';
  });
  readingsList.innerHTML = html;

  reviewed++;
  updateProgress();
  nextBtn.focus();
}

function nextKanji() {
  if (loadingNext || submitting || !answerSubmitted) return;
  fetchNext();
}

function fetchNext() {
  if (loadingNext) return;
  loadingNext = true;

  fetch('/review/next').then(function(response) {
    return response.json().then(function(data) {
      if (!response.ok) throw new Error(data.error || 'Could not load the next card');
      return data;
    });
  }).then(function(data) {
    loadingNext = false;
    if (data.done) {
      showDone();
      return;
    }
    currentKanji = data.kanji;
    currentStage = data.stage || '';
    currentIntervals = data.intervals || {};
    showKanji(currentKanji);
  }).catch(function(error) {
    loadingNext = false;
    document.getElementById('review-progress').textContent = error.message;
  });
}

function showDone() {
  document.getElementById('kanji-char').textContent = 'Done';
  document.getElementById('kanji-stage').style.display = 'none';
  document.getElementById('input-area').style.display = 'none';
  document.getElementById('result-area').style.display = 'none';
  document.getElementById('next-btn').style.display = 'none';
  document.getElementById('review-progress').textContent = reviewed + ' kanji reviewed';
  document.getElementById('progress-fill').style.width = '100%';
}

document.addEventListener('keydown', function(e) {
  if (e.target.tagName !== 'INPUT') return;

  var inputArea = document.getElementById('input-area');
  if (e.key === 'Enter' && inputArea && inputArea.style.display !== 'none') {
    e.preventDefault();
    checkAnswer();
  }
});

document.getElementById('reading-input').addEventListener('input', function() {
  this.value = this.value.replace(/[^ぁ-んァ-ヶー]/g, '');
});
