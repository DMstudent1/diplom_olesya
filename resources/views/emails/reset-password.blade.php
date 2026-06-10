<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #C5E99B;
            /* Лаймовая шапка */
            padding: 40px 20px;
            text-align: center;
        }

        .logo {
            max-width: 180px;
            height: auto;
        }

        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            line-height: 1.6;
            color: #4a5568;
            margin-bottom: 30px;
        }

        .warning-card {
            background-color: #fef5e7;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid #f59e0b;
        }

        .warning-text {
            color: #92400e;
            font-size: 14px;
            line-height: 1.5;
        }

        .reset-button {
            display: inline-block;
            background-color: #C5E99B;
            color: #2d3748;
            text-decoration: none;
            padding: 14px 35px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            transition: background-color 0.3s;
        }

        .reset-button:hover {
            background-color: #b3d47e;
        }

        .expiry-text {
            font-size: 12px;
            color: #6c757d;
            text-align: center;
            margin-top: 20px;
        }

        .link-fallback {
            font-size: 14px;
            color: #4a5568;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            word-break: break-all;
        }

        .footer {
            padding: 20px 30px;
            background-color: #f9fafb;
            text-align: center;
            font-size: 12px;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
        }

        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }

            .reset-button {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Лаймовая шапка с логотипом -->
        <div class="header">
        </div>

        <!-- Основной контент -->
        <div class="content">
            <div class="greeting">
                Здравствуйте, {{ $name }}!
            </div>

            <div class="message">
                Вы получили это письмо, потому что мы получили запрос на восстановление пароля для вашей учётной записи.
            </div>

            <!-- Карточка с предупреждением -->
            <div class="warning-card">
                <div class="warning-text">
                    <strong>Внимание:</strong> Если вы не запрашивали восстановление пароля, просто проигнорируйте это
                    письмо. Ваш пароль останется без изменений.
                </div>
            </div>

            <!-- Кнопка для сброса пароля -->
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">
                    Сбросить пароль
                </a>
            </div>

            <!-- Текст о сроке действия ссылки -->
            <div class="expiry-text">
                Ссылка действительна в течение 60 минут.<br>
                Если кнопка не работает, скопируйте ссылку ниже в адресную строку браузера.
            </div>

            <!-- Fallback ссылка на случай, если кнопка не работает -->
            <div class="link-fallback">
                <strong>Или перейдите по ссылке:</strong><br>
                <a href="{{ $resetUrl }}" style="color: #C5E99B; text-decoration: none;">{{ $resetUrl }}</a>
            </div>
        </div>

        <!-- Футер -->
        <div class="footer">
            <p>С уважением от команды Leafstory</p>
            <p>Это письмо было отправлено автоматически, пожалуйста, не отвечайте на него.</p>
        </div>
    </div>
</body>

</html>