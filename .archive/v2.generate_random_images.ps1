$baseDir = "c:\Users\dance\zone\icons"
$imgDirName = "img"
$imgDir = Join-Path $baseDir $imgDirName
$extensions = "*.png", "*.jpg", "*.jpeg" # SVG excluded
$outputFile = Join-Path $baseDir "quiz_images.html"
$quizCount = 10 # Number of questions

# Get all image files
Write-Host "Searching for images in $imgDir..."
if (-not (Test-Path $imgDir)) {
    Write-Error "Image directory not found: $imgDir"
    exit
}

$images = Get-ChildItem -Path $imgDir -Include $extensions -Recurse -File

$totalCount = $images.Count
Write-Host "Total images found: $totalCount"

if ($totalCount -eq 0) {
    Write-Host "No images found."
    exit
}

# Select random images for the quiz
$selectionCount = [Math]::Min($totalCount, $quizCount)
$selectedImages = $images | Get-Random -Count $selectionCount

# Prepare JS data
$jsDataArray = @()
foreach ($img in $selectedImages) {
    # Relative path from img directory
    $relativePathFromImg = $img.FullName.Substring($imgDir.Length).TrimStart('\').Replace('\', '/')
    
    # URL Encode path parts to handle spaces and special characters
    $parts = $relativePathFromImg.Split('/')
    $encodedParts = $parts | ForEach-Object { [Uri]::EscapeDataString($_) }
    $safePathFromImg = $encodedParts -join '/'
    
    # Path relative to the HTML file (which is in baseDir)
    $finalPath = "$imgDirName/$safePathFromImg"
    
    $filename = $img.Name
    # Escape quotes for JS
    $safeName = $filename.Replace("'", "\'")
    $jsDataArray += "{ path: '$finalPath', name: '$safeName' }"
}
$jsDataString = $jsDataArray -join ","

# Generate HTML
$htmlContent = @"
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Icon Quiz</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        h1 { margin-bottom: 0.5rem; color: #333; }
        .stats { color: #666; font-size: 0.9rem; margin-bottom: 2rem; }
        
        .quiz-area { display: flex; flex-direction: column; align-items: center; }
        .image-container {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            width: 100%;
            background-color: #fafafa;
            border-radius: 8px;
            border: 1px dashed #ddd;
        }
        img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .answer-section {
            margin-top: 1rem;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .filename {
            font-weight: bold;
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: none;
        }
        .path {
            font-size: 0.8rem;
            color: #7f8c8d;
            word-break: break-all;
            display: none;
        }
        
        .controls {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        button {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
            font-size: 1rem;
        }
        .btn-show { background-color: #3498db; color: white; }
        .btn-show:hover { background-color: #2980b9; }
        .btn-next { background-color: #2ecc71; color: white; }
        .btn-next:hover { background-color: #27ae60; }
        .btn-next:disabled { background-color: #bdc3c7; cursor: not-allowed; }
        
        .progress {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>AWS & Google Cloud Icon Quiz</h1>
        <div class="stats">
            Total images in library: $totalCount<br>
            Quiz Questions: $selectionCount
        </div>

        <div id="quiz-container" class="quiz-area">
            <div class="image-container">
                <img id="quiz-image" src="" alt="Quiz Image">
            </div>
            
            <div class="answer-section">
                <div id="answer-filename" class="filename"></div>
                <div id="answer-path" class="path"></div>
                <div id="placeholder-text" style="color: #999;">???</div>
            </div>

            <div class="controls">
                <button id="btn-show" class="btn-show" onclick="showAnswer()">Show Answer</button>
                <button id="btn-next" class="btn-next" onclick="nextQuestion()">Next</button>
            </div>
            
            <div class="progress" id="progress-display"></div>
        </div>
        
        <div id="result-container" style="display: none;">
            <h2>Quiz Completed!</h2>
            <p>You have reviewed all $selectionCount images.</p>
            <button class="btn-show" onclick="location.reload()">Restart Quiz</button>
        </div>
    </div>

    <script>
        const questions = [$jsDataString];
        let currentIndex = 0;
        let isAnswerShown = false;

        const imgEl = document.getElementById('quiz-image');
        const filenameEl = document.getElementById('answer-filename');
        const pathEl = document.getElementById('answer-path');
        const placeholderEl = document.getElementById('placeholder-text');
        const progressEl = document.getElementById('progress-display');
        const btnShow = document.getElementById('btn-show');
        const btnNext = document.getElementById('btn-next');
        const quizContainer = document.getElementById('quiz-container');
        const resultContainer = document.getElementById('result-container');

        function loadQuestion(index) {
            if (index >= questions.length) {
                showResults();
                return;
            }
            
            const q = questions[index];
            imgEl.src = q.path;
            filenameEl.textContent = q.name;
            pathEl.textContent = decodeURIComponent(q.path);
            
            // Reset state
            isAnswerShown = false;
            filenameEl.style.display = 'none';
            pathEl.style.display = 'none';
            placeholderEl.style.display = 'block';
            btnShow.style.display = 'inline-block';
            btnNext.style.display = 'inline-block';
            btnNext.disabled = false;
            
            updateProgress();
        }

        function showAnswer() {
            filenameEl.style.display = 'block';
            pathEl.style.display = 'block';
            placeholderEl.style.display = 'none';
            btnShow.style.display = 'none';
            isAnswerShown = true;
        }

        function nextQuestion() {
            currentIndex++;
            loadQuestion(currentIndex);
        }
        
        function updateProgress() {
            progressEl.textContent = `Question `$${currentIndex + 1} of `$${questions.length}`;
        }
        
        function showResults() {
            quizContainer.style.display = 'none';
            resultContainer.style.display = 'block';
        }

        // Initialize
        loadQuestion(0);
    </script>
</body>
</html>
"@


$htmlContent | Set-Content -Path $outputFile -Encoding UTF8
Write-Host "Quiz HTML generated at: $outputFile"
