<?php
// 設定
$baseDir = __DIR__;
$jsonFile = $baseDir . DIRECTORY_SEPARATOR . 'img.tree.json';
$quizCount = 10; // 出題数

// JSONファイルから画像リストを読み込み
$images = [];
if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $images = json_decode($jsonContent, true);
    if ($images === null) {
        $images = []; // JSONパースエラー時は空配列
    }
}

$totalCount = count($images);
$selectedImages = [];

if ($totalCount > 0) {
    // ランダムに画像を選択
    $count = min($totalCount, $quizCount);
    // array_rand はランダムなキーを返す
    $randomKeys = array_rand($images, $count);
    
    // 1つだけ選択された場合は配列ではなく単一のキーが返るので配列化
    if ($count === 1) {
        $randomKeys = [$randomKeys];
    }
    
    // 選択された画像の情報を整形
    foreach ($randomKeys as $key) {
        $imgData = $images[$key];
        $rawPath = $imgData['path']; // 例: img/Folder Name/File.png
        $name = $imgData['name'];
        
        // URLエンコード処理
        // img/Folder Name/File.png -> img/Folder%20Name/File.png
        $parts = explode('/', $rawPath);
        $encodedParts = array_map('rawurlencode', $parts);
        $safePath = implode('/', $encodedParts);
        
        $selectedImages[] = [
            'path' => $safePath,
            'name' => $name
        ];
    }
}

// JavaScriptに渡すためにJSON形式に変換
$jsDataString = json_encode($selectedImages);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Icon Quiz (PHP)</title>
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
        
        #start-screen {
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
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
            flex-wrap: wrap;
            justify-content: center;
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
        
        /* 正解・不正解ボタン */
        .btn-correct { background-color: #2ecc71; color: white; display: none; }
        .btn-correct:hover { background-color: #27ae60; }
        .btn-wrong { background-color: #e74c3c; color: white; display: none; }
        .btn-wrong:hover { background-color: #c0392b; }

        .btn-next { background-color: #95a5a6; color: white; display: none; } /* Nextボタンは基本的に使わず、判定後に自動遷移または判定ボタンで兼ねる方針へ変更も可能だが、今回は判定後にNextを表示する形にする */
        
        .progress {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #888;
        }
        
        /* 結果画面用 */
        .result-details {
            text-align: left;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .input-group {
            margin-bottom: 1rem;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .input-group input {
            width: 100%;
            padding: 0.5rem;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        #qrcode {
            margin: 1rem auto;
            display: flex;
            justify-content: center;
        }
    </style>
    <!-- QRコード生成ライブラリ -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <script src="app.js"></script>
</head>
<body>
    <div class="container">
        <h1>AWS & Google Cloud Icon Quiz</h1>
        <div class="stats">
            Total images in library: <?php echo $totalCount; ?><br>
            Quiz Questions: <?php echo count($selectedImages); ?>
        </div>

        <div id="start-screen">
            <p>Enter your name to start the quiz!</p>
            <div class="input-group" style="width: 100%; max-width: 300px;">
                <input type="text" id="user-name-input" placeholder="Your Name" value="Guest">
            </div>
            <button class="btn-show" onclick="startQuiz()">Start Quiz</button>
        </div>

        <div id="quiz-container" class="quiz-area" style="display: none;">
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
                <button id="btn-correct" class="btn-correct" onclick="recordResult(true)">✅ Correct</button>
                <button id="btn-wrong" class="btn-wrong" onclick="recordResult(false)">❌ Incorrect</button>
            </div>
            
            <div class="progress" id="progress-display"></div>
        </div>
        
        <div id="result-container" style="display: none;">
            <h2>Quiz Completed!</h2>
            
            <div class="input-group">
                <label for="username">Enter your name to generate result:</label>
                <input type="text" id="username" placeholder="Your Name" value="Guest">
                <button class="btn-show" style="margin-top:0.5rem; width:100%;" onclick="generateResult()">Generate Result & QR</button>
            </div>

            <div id="final-result" style="display:none;">
                <div class="result-details">
                    <p><strong>Date:</strong> <span id="res-date"></span></p>
                    <p><strong>Score:</strong> <span id="res-score"></span> / 100</p>
                    <p><strong>Deviation (Est.):</strong> <span id="res-deviation"></span></p>
                    <p><strong>Name:</strong> <span id="res-name"></span></p>
                </div>
                
                <div id="qrcode"></div>
                <p style="font-size:0.8rem; color:#666;">Scan to save or download below</p>
                
                <button class="btn-show" onclick="downloadJson()">Download kekka.json</button>
                <button class="btn-next" onclick="location.reload()" style="display:inline-block; background-color:#95a5a6; margin-top:1rem;">Restart Quiz</button>
            </div>
        </div>
    </div>

    <script>
        // PHPから出力されたJSONデータをJavaScriptの変数に代入
        const questions = <?php echo $jsDataString; ?>;
        let currentIndex = 0;
        let scoreCount = 0;
        let resultData = null;

        const imgEl = document.getElementById('quiz-image');
        const filenameEl = document.getElementById('answer-filename');
        const pathEl = document.getElementById('answer-path');
        const placeholderEl = document.getElementById('placeholder-text');
        const progressEl = document.getElementById('progress-display');
        
        const btnShow = document.getElementById('btn-show');
        const btnCorrect = document.getElementById('btn-correct');
        const btnWrong = document.getElementById('btn-wrong');
        
        const quizContainer = document.getElementById('quiz-container');
        const resultContainer = document.getElementById('result-container');
        const finalResultDiv = document.getElementById('final-result');
        const startScreen = document.getElementById('start-screen');
        const userNameInput = document.getElementById('user-name-input');
        
        // 結果画面の入力欄（IDを変更して区別）
        const resultUsernameInput = document.getElementById('username'); 

        function startQuiz() {
            const name = userNameInput.value.trim() || 'Guest';
            // 結果画面の入力欄に反映
            resultUsernameInput.value = name;
            
            startScreen.style.display = 'none';
            quizContainer.style.display = 'flex';
            
            if (questions && questions.length > 0) {
                loadQuestion(0);
            } else {
                quizContainer.innerHTML = '<p>No images found in the img directory.</p>';
            }
        }

        function loadQuestion(index) {
            if (index >= questions.length) {
                showInputScreen();
                return;
            }
            
            const q = questions[index];
            imgEl.src = q.path;
            filenameEl.textContent = q.name;
            pathEl.textContent = decodeURIComponent(q.path);
            
            // Reset state
            filenameEl.style.display = 'none';
            pathEl.style.display = 'none';
            placeholderEl.style.display = 'block';
            
            btnShow.style.display = 'inline-block';
            btnCorrect.style.display = 'none';
            btnWrong.style.display = 'none';
            
            updateProgress();
        }

        function showAnswer() {
            filenameEl.style.display = 'block';
            pathEl.style.display = 'block';
            placeholderEl.style.display = 'none';
            
            btnShow.style.display = 'none';
            btnCorrect.style.display = 'inline-block';
            btnWrong.style.display = 'inline-block';
        }

        function recordResult(isCorrect) {
            if (isCorrect) {
                scoreCount++;
            }
            currentIndex++;
            loadQuestion(currentIndex);
        }
        
        function updateProgress() {
            progressEl.textContent = "Question " + (currentIndex + 1) + " of " + questions.length;
        }
        
        function showInputScreen() {
            quizContainer.style.display = 'none';
            resultContainer.style.display = 'block';
        }

        function generateResult() {
            const username = resultUsernameInput.value || 'Guest';
            const now = new Date();
            const dateStr = now.getFullYear() + "-" + 
                            String(now.getMonth()+1).padStart(2, '0') + "-" + 
                            String(now.getDate()).padStart(2, '0') + " " + 
                            String(now.getHours()).padStart(2, '0') + ":" + 
                            String(now.getMinutes()).padStart(2, '0');
            
            // Calculate Score (10 points per question)
            const totalQuestions = questions.length;
            const score = Math.round((scoreCount / totalQuestions) * 100);
            
            // Calculate Deviation (Pseudo)
            // Mean = 70, SD = 15
            const mean = 70;
            const sd = 15;
            let deviation = 50 + ((score - mean) / sd) * 10;
            deviation = Math.round(deviation * 10) / 10; // Round to 1 decimal

            resultData = {
                date: dateStr,
                score: score,
                deviation: deviation,
                username: username
            };

            // Display Results
            document.getElementById('res-date').textContent = resultData.date;
            document.getElementById('res-score').textContent = resultData.score;
            document.getElementById('res-deviation').textContent = resultData.deviation;
            document.getElementById('res-name').textContent = resultData.username;

            // Generate QR Code
            const qrcodeDiv = document.getElementById('qrcode');
            qrcodeDiv.innerHTML = ""; // clear previous
            new QRCode(qrcodeDiv, {
                text: JSON.stringify(resultData),
                width: 128,
                height: 128
            });

            finalResultDiv.style.display = 'block';
        }

        function downloadJson() {
            if (!resultData) return;
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(resultData, null, 2));
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "kekka.json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        }

        // Initialize
        // startQuiz() is called by button click
        // if (questions && questions.length > 0) {
        //     loadQuestion(0);
        // } else {
        //     quizContainer.innerHTML = '<p>No images found in the img directory.</p>';
        // }
    </script>
</body>
</html>
