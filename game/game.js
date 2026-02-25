const rainContainer = document.getElementById('rain-container');
const scoreEl = document.getElementById('score');
const timerEl = document.getElementById('timer');
const startBtn = document.getElementById('start-btn');

let score = 0;
let timeLeft = 30;
let gameActive = false;
let spawnInterval;

// AWS SVG Icons (Using paths relative to project root, but we need to consider deployment)
// For now, using common service icons found in the codebase
const iconPaths = [
    'img/Asset-Package_07312025.49d3aab7f9e6131e51ade8f7c6c8b961ee7d3bb1/Architecture-Service-Icons_07312025/Arch_Compute/64/Arch_Amazon-EC2_64.svg',
    'img/Asset-Package_07312025.49d3aab7f9e6131e51ade8f7c6c8b961ee7d3bb1/Architecture-Service-Icons_07312025/Arch_Compute/64/Arch_AWS-Lambda_64.svg',
    'img/Asset-Package_07312025.49d3aab7f9e6131e51ade8f7c6c8b961ee7d3bb1/Architecture-Service-Icons_07312025/Arch_Database/64/Arch_Amazon-RDS_64.svg',
    'img/Asset-Package_07312025.49d3aab7f9e6131e51ade8f7c6c8b961ee7d3bb1/Architecture-Service-Icons_07312025/Arch_Storage/64/Arch_Amazon-EFS_64.svg',
    'img/Asset-Package_07312025.49d3aab7f9e6131e51ade8f7c6c8b961ee7d3bb1/Architecture-Service-Icons_07312025/Arch_Analytics/64/Arch_AWS-Glue_64.svg'
];

function startGame() {
    score = 0;
    timeLeft = 30;
    gameActive = true;
    scoreEl.textContent = score;
    timerEl.textContent = timeLeft;
    startBtn.style.display = 'none';
    rainContainer.innerHTML = '';

    spawnInterval = setInterval(spawnIcon, 600);
    
    const timerInterval = setInterval(() => {
        timeLeft--;
        timerEl.textContent = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            clearInterval(spawnInterval);
            endGame();
        }
    }, 1000);
}

function spawnIcon() {
    if (!gameActive) return;

    const icon = document.createElement('img');
    const randomPath = iconPaths[Math.floor(Math.random() * iconPaths.length)];
    
    // Adjust path for deployment if necessary
    // On Vercel, the root is where vercel.json is
    icon.src = '../' + randomPath; 
    icon.className = 'falling-icon';
    
    const startX = Math.random() * (window.innerWidth - 64);
    icon.style.left = startX + 'px';
    icon.style.top = '-80px';
    
    const duration = 2 + Math.random() * 3; // 2-5 seconds
    icon.style.transition = `top ${duration}s linear`;
    
    rainContainer.appendChild(icon);

    // Trigger falling animation
    setTimeout(() => {
        icon.style.top = window.innerHeight + 'px';
    }, 50);

    icon.onclick = () => {
        if (!icon.classList.contains('caught')) {
            score++;
            scoreEl.textContent = score;
            icon.classList.add('caught');
            setTimeout(() => icon.remove(), 300);
        }
    };

    // Remove icon after animation
    setTimeout(() => {
        if (icon.parentNode) {
            icon.remove();
        }
    }, duration * 1000);
}

function endGame() {
    gameActive = false;
    startBtn.style.display = 'block';
    startBtn.textContent = `Game Over! Score: ${score}. Try Again?`;
}

startBtn.onclick = startGame;
