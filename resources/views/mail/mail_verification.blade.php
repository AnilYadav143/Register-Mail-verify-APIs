<!DOCTYPE html>
<html>
<head>
    <title>DG</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #007bff;
            margin-bottom: 20px;
        }
        p {
            margin-bottom: 10px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to DG</h1>
        <p>Hello <strong style="color: #007bff;">{{ $data['business_name'] ?? '' }}</strong>,</p>
        <p>Please click the following link to verify your email:</p>
        <p><a href="{{ url('mail-verification/'. $data['token']) }}" class="button">Verify your email</a></p>
        <p>Thank you</p>
    </div>
</body>
</html>
