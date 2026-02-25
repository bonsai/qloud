// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./sw.js')
            .then(reg => console.log('SW registered'))
            .catch(err => console.log('SW failed', err));
    });
}

// State
let quizData = [];
let currentQuestions = [];
let currentIndex = 0;
let score = 0;
let currentMode = 'text_to_image'; // or 'image_to_text'

// Load Data
fetch('./quiz_data.json')
    .then(res => res.json())
    .then(data => {
        quizData = data.filter(item => item.images && item.images.length > 0);
        console.log("Loaded " + quizData.length + " items");
    })
    .catch(err => {
        console.error("Failed to load data", err);
        const appEl = document.getElementById('app');
        if (appEl) {
            appEl.innerHTML += "<p class='error-msg'>Failed to load quiz data. Ensure quiz_data.json exists.</p>";
        }
    });

function startQuiz(mode) {
    if (quizData.length < 4) {
        alert("Not enough data to start quiz (need at least 4 items)");
        return;
    }
    currentMode = mode;
    score = 0;
    currentIndex = 0;
    
    // Generate 10 random questions
    const shuffled = [...quizData].sort(() => 0.5 - Math.random());
    currentQuestions = shuffled.slice(0, 10);
    
    document.getElementById('start-screen').classList.add('hidden');
    document.getElementById('quiz-screen').classList.remove('hidden');
    
    renderQuestion();
}

function renderQuestion() {
    if (currentIndex >= currentQuestions.length) {
        showResult();
        return;
    }

    const item = currentQuestions[currentIndex];
    const qContainer = document.getElementById('question-container');
    const oContainer = document.getElementById('options-container');
    
    document.getElementById('progress').textContent = `Question ${currentIndex + 1} / ${currentQuestions.length}`;

    // Prepare Options (1 Correct + 3 Distractors)
    const distractors = quizData
        .filter(i => i.id !== item.id)
        .sort(() => 0.5 - Math.random())
        .slice(0, 3);
    
    const options = [item, ...distractors].sort(() => 0.5 - Math.random());

    // Render based on mode
    let questionHtml = '';
    let optionsHtml = '';
    
    // Determine effective mode for Mix Mode
    let effectiveMode = currentMode;
    if (currentMode === 'mix') {
        const modes = ['text_to_image', 'image_to_text', 'desc_to_name', 'desc_to_icon'];
        effectiveMode = modes[Math.floor(Math.random() * modes.length)];
    }

    if (effectiveMode === 'text_to_image') {
        // Question: Text Name
        questionHtml = `<div class="question-text">${item.name}</div>
                        <div class="category-text">${item.category}</div>`;
        
        // Options: Images
        optionsHtml = options.map(opt => {
            const img = opt.images.find(img => img.score === 100) || opt.images[0];
            return `<button class="option-btn" onclick="handleAnswer('${opt.id}', this)">
                        <img src="/${img.path}" class="option-img" alt="Option">
                    </button>`;
        }).join('');

    } else if (effectiveMode === 'image_to_text') {
        // Question: Image
        const qImg = item.images.find(img => img.score === 100) || item.images[0];
        questionHtml = `<img src="/${qImg.path}" class="question-img" alt="Question">`;
        
        // Options: Text
        optionsHtml = options.map(opt => {
            return `<button class="option-btn" onclick="handleAnswer('${opt.id}', this)">
                        <span class="option-text">${opt.name}</span>
                    </button>`;
        }).join('');
        
    } else if (effectiveMode === 'desc_to_name') {
        // Question: Description (JA preferred, fallback to EN)
        const desc = item.description.ja || item.description.en;
        questionHtml = `<div class="desc-text">${desc}</div>
                        <div class="instruction-text">Which service is this?</div>`;
        
        // Options: Text
        optionsHtml = options.map(opt => {
            return `<button class="option-btn" onclick="handleAnswer('${opt.id}', this)">
                        <span class="option-text">${opt.name}</span>
                    </button>`;
        }).join('');

    } else if (effectiveMode === 'desc_to_icon') {
        // Question: Description
        const desc = item.description.ja || item.description.en;
        questionHtml = `<div class="desc-text">${desc}</div>
                        <div class="instruction-text">Select the correct icon</div>`;
        
        // Options: Images
        optionsHtml = options.map(opt => {
            const img = opt.images.find(img => img.score === 100) || opt.images[0];
            return `<button class="option-btn" onclick="handleAnswer('${opt.id}', this)">
                        <img src="/${img.path}" class="option-img" alt="Option">
                    </button>`;
        }).join('');
    }

    qContainer.innerHTML = questionHtml;
    oContainer.innerHTML = optionsHtml;
}

function handleAnswer(selectedId, btnElement) {
    // Disable all buttons
    const buttons = document.querySelectorAll('.option-btn');
    buttons.forEach(b => b.disabled = true);

    const correctId = currentQuestions[currentIndex].id;
    const isCorrect = selectedId === correctId;

    if (isCorrect) {
        btnElement.classList.add('correct');
        score++;
    } else {
        btnElement.classList.add('wrong');
    }

    setTimeout(() => {
        currentIndex++;
        renderQuestion();
    }, 1000);
}

function showResult() {
    document.getElementById('quiz-screen').classList.add('hidden');
    const rScreen = document.getElementById('result-screen');
    rScreen.classList.remove('hidden');
    
    document.getElementById('final-score').textContent = score;
    document.getElementById('total-q').textContent = currentQuestions.length;
}

function submitScore() {
    const nameInput = document.getElementById('player-name');
    const name = nameInput.value.trim() || 'Anonymous';
    
    // Disable input
    nameInput.disabled = true;
    document.querySelector('#submit-score-area button').disabled = true;

    const apiUrl = '/api/ranking.php';

    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ name: name, score: score })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            displayRanking(data.ranking);
            document.getElementById('submit-score-area').classList.add('hidden');
        } else {
            alert('Failed to save score');
        }
    })
    .catch(err => {
        console.error('Error submitting score:', err);
        alert('Could not connect to ranking server.');
    });
}

function displayRanking(rankingData) {
    const listDiv = document.getElementById('ranking-list');
    const ul = document.getElementById('ranking-ul');
    ul.innerHTML = '';
    
    rankingData.forEach((entry, index) => {
        const li = document.createElement('li');
        li.textContent = `${index + 1}. ${entry.name}: ${entry.score} pts (${entry.date.split(' ')[0]})`;
        if (entry.name === document.getElementById('player-name').value && entry.score === score) {
            li.classList.add('current-user-score');
        }
        ul.appendChild(li);
    });
    
    listDiv.classList.add('show-block');
}

// Expose functions to global scope
window.startQuiz = startQuiz;
window.handleAnswer = handleAnswer;
window.submitScore = submitScore;
