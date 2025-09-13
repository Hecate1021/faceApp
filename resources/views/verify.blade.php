<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Face</title>
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
            width: 900px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .camera-container {
            position: relative;
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
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
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Face Verification</h2>

    <div class="camera-container">
        <video id="video" width="720" height="560" autoplay muted></video>
        <canvas id="overlay" width="720" height="560"></canvas>
    </div>

    <form>
        <div class="form-group">
            <label>First Name</label>
            <input type="text" id="first_name" readonly>
        </div>
        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" id="middle_name" readonly>
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" id="last_name" readonly>
        </div>
        <div class="form-group">
            <label>Age</label>
            <input type="text" id="age" readonly>
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea id="address" readonly></textarea>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('overlay');
    const ctx = canvas.getContext('2d');

    let labeledFaceDescriptors;
    let faceMatcher;

    async function startVideo() {
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
    }

    async function loadModels() {
        await faceapi.nets.ssdMobilenetv1.loadFromUri('/models');
        await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
        await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
    }

    async function loadEnrolledFaces() {
        const res = await fetch('/api/enrolled-faces');
        const faces = await res.json();

        const labeledDescriptors = [];
        for (let face of faces) {
            const img = await faceapi.fetchImage(`/storage/${face.photo_path}`);
            const detection = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();
            if (!detection) continue;
            labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(
                `${face.id}|${face.first_name}|${face.middle_name}|${face.last_name}`,
                [detection.descriptor]
            ));
        }

        return labeledDescriptors;
    }

    video.addEventListener('play', () => {
        const displaySize = { width: video.width, height: video.height };
        faceapi.matchDimensions(canvas, displaySize);

        setInterval(async () => {
            const detections = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options())
                .withFaceLandmarks()
                .withFaceDescriptors();

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const resizedDetections = faceapi.resizeResults(detections, displaySize);

            if (resizedDetections.length > 0 && faceMatcher) {
                resizedDetections.forEach(detection => {
                    const bestMatch = faceMatcher.findBestMatch(detection.descriptor);
                    const { x, y, width, height } = detection.detection.box;

                    ctx.strokeStyle = "red";
                    ctx.lineWidth = 3;
                    ctx.strokeRect(x, y, width, height);

                    ctx.fillStyle = "red";
                    ctx.font = "16px Arial";
                    ctx.fillText(bestMatch.toString(), x, y - 10);

                    if (!bestMatch.label.includes("unknown")) {
                        let [id, first_name, middle_name, last_name, age, address] = bestMatch.label.split("|");
                        document.getElementById('first_name').value = first_name;
                        document.getElementById('middle_name').value = middle_name;
                        document.getElementById('last_name').value = last_name;
                        document.getElementById('age').value = age;
                        document.getElementById('address').value = address;
                    }
                });
            }
        }, 200);
    });

    async function init() {
        await loadModels();
        const descriptors = await loadEnrolledFaces();
        faceMatcher = new faceapi.FaceMatcher(descriptors, 0.6);
        startVideo();
    }

    init();
</script>
</body>
</html>
