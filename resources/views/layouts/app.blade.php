<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFUH DMV System</title>

    <style>
        .role-item {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 8px;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 15px;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="password"],
        input[type="date"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-sizing: border-box;
        }

        input[type="checkbox"],
        input[type="radio"] {
            width: auto;
            padding: 0;
            margin: 0;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }

        .mb-3 {
            margin-bottom: 16px;
        }

        button {
            background: #2563eb;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>NFUH DMV System</h2>
        </div>

        @if(session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>