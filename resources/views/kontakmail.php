<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kontak</title>
    <style>
        body {
            margin-top: 28px;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: justify;
        }

        p {
            font-size: 20px;
            color: #333;
        }

        a.email-button {
            margin-top: 20px;
            text-decoration: none;
            background-color: #c9b961;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        a.email-button:hover {
            background-color: #bdac53;
        }
    </style>
</head>
<body>
    <div class="container">
        <p><?php echo $data['pesan']?></p>
        <a href="mailto:<?php echo $data['email']?>" class="email-button">Balas</a>
    </div>
</body>
</html>
