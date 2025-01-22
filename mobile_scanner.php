<?php
session_start();
require_once 'config.php';

// Ensure the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Receipt Scanner</title>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@2.1.1/dist/tesseract.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .scanner-container {
            text-align: center;
        }
        video {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        #canvas {
            display: none;
        }
        .spinner {
            display: none;
        }
    </style>
</head>
<body>
    <h1>📷 Mobile Receipt Scanner</h1>
    <div class="scanner-container">
        <video id="camera" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
        <button id="capture" class="btn btn-primary mt-3">Capture Receipt</button>
        <div class="spinner mt-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Processing receipt...</p>
        </div>
    </div>
    <div id="result" class="mt-4"></div>
    <script> 
    document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('camera');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('capture');
    const resultDiv = document.getElementById('result');
    const spinner = document.querySelector('.spinner');

    // Access the device camera
    async function startCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
        } catch (error) {
            alert('Camera access denied or unavailable.');
        }
    }

    // Capture and process the image
    captureBtn.addEventListener('click', async () => {
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Show loading spinner
        spinner.style.display = 'block';

        // Use Tesseract.js for OCR
        Tesseract.recognize(canvas.toDataURL(), 'eng', {
            tessedit_char_whitelist: '0123456789.RM',
            preserve_interword_spaces: 1
        }).then(({ data: { text } }) => {
            spinner.style.display = 'none';
            const match = text.match(/RM(\d+(\.\d{1,2})?)/i);
            if (match) {
                const amount = match[0];
                resultDiv.innerHTML = `<p><strong>Extracted Amount:</strong> ${amount}</p>`;
                saveToDatabase(amount);
            } else {
                resultDiv.innerHTML = '<p>No amount found. Please try again.</p>';
            }
        }).catch(err => {
            spinner.style.display = 'none';
            alert('Error processing receipt: ' + err.message);
        });
    });

    // Save the scanned amount to the database
    async function saveToDatabase(amount) {
        try {
            const response = await fetch('save_receipt.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount })
            });
            const result = await response.json();
            if (result.success) {
                alert('Receipt saved successfully!');
            } else {
                alert('Failed to save receipt.');
            }
        } catch (error) {
            console.error('Error saving receipt:', error);
        }
    }

    startCamera();
});
</script>
</body>
</html>
