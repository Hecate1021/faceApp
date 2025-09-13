<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Face</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0px 6px 15px rgba(0,0,0,0.2);
            width: 800px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .camera-container {
            position: relative;
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }
        video {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        canvas {
            position: absolute;
            top: 0;
            left: 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input, textarea, button {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:disabled {
            background: #aaa;
            cursor: not-allowed;
        }
        .captured {
            margin-top: 15px;
            text-align: center;
        }
        .captured img {
            max-width: 200px;
            border: 3px solid #2563eb;
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Face Enrollment</h2>

    @if(session('success'))
        <p style="color: green; text-align:center;">{{ session('success') }}</p>
    @endif

    <div class="camera-container">
        <video id="video" width="640" height="480" autoplay muted></video>
        <canvas id="overlay" width="640" height="480"></canvas>
    </div>

    <button id="captureBtn" disabled>📸 Capture Face</button>
    <div class="captured" id="capturedContainer"></div>

    <form id="enrollForm" method="POST" action="{{ route('enroll.store') }}">
        @csrf
        <input type="hidden" name="photo" id="photoInput">

        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" required>
        </div>
        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middle_name">
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" required>
        </div>
        <div class="form-group">
            <label>Age</label>
            <input type="number" name="age" required>
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" required></textarea>
        </div>
        <button type="submit" id="submitBtn" disabled>Submit Enrollment</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('overlay');
    const captureBtn = document.getElementById('captureBtn');
    const submitBtn = document.getElementById('submitBtn');
    const capturedContainer = document.getElementById('capturedContainer');
    const photoInput = document.getElementById('photoInput');

    let faceDetected = false;
    let captured = false;

    async function startVideo() {
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
    }

    async function loadModels() {
        await faceapi.nets.ssdMobilenetv1.loadFromUri('/models');
        await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
        await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
    }

    video.addEventListener('play', () => {
        const displaySize = { width: video.width, height: video.height };
        faceapi.matchDimensions(canvas, displaySize);

        setInterval(async () => {
            const detections = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options());
            const resizedDetections = faceapi.resizeResults(detections, displaySize);

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            faceDetected = resizedDetections.length > 0;
            captureBtn.disabled = !faceDetected || captured;

            resizedDetections.forEach(det => {
                const { x, y, width, height } = det.box;
                ctx.strokeStyle = "red";
                ctx.lineWidth = 3;
                ctx.strokeRect(x, y, width, height);
            });
        }, 100);
    });

    captureBtn.addEventListener('click', () => {
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = video.width;
        tempCanvas.height = video.height;
        tempCanvas.getContext('2d').drawImage(video, 0, 0, video.width, video.height);

        const dataUrl = tempCanvas.toDataURL('image/png');
        photoInput.value = dataUrl;

        capturedContainer.innerHTML = `<h4>Captured Photo:</h4><img src="${dataUrl}" />`;
        captured = true;
        submitBtn.disabled = false;
        captureBtn.disabled = true;
    });

    loadModels().then(startVideo);
</script>
</body>
</html>
