<!DOCTYPE html>
<html>
<head>
    <title>Cache Test</title>
    <style>
        body { 
            background: linear-gradient(135deg, #f8e8ff 0%, #e8d5ff 50%, #d4b5ff 100%);
            font-family: Arial, sans-serif;
            padding: 50px;
            text-align: center;
        }
        .test-box {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="test-box">
        <h1>🍦 Cache Test</h1>
        <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
        <p><strong>Random Number:</strong> <?= rand(1000, 9999) ?></p>
        <p>If you see different numbers when refreshing, the server is working!</p>
        <a href="index.php" style="color: #2d1b69; font-weight: bold;">← Back to Ice Cream Shop</a>
    </div>
</body>
</html>