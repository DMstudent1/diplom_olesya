<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Успешная оплата</title>
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
            background-color: #C5E99B; /* Лаймовая шапка */
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
        .details-card {
            background-color: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid #C5E99B;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-label {
            font-weight: 600;
            color: #2d3748;
        }
        .detail-value {
            color: #4a5568;
        }
        .amount {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
        }
        .button {
            display: inline-block;
            background-color: #C5E99B;
            color: #2d3748;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #b3d47e;
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
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
        </div>

        <!-- Основной контент -->
        <div class="content">
            <div class="greeting">
                Здравствуйте, {{ $name }}!
            </div>
            
            <div class="message">
                Сообщаем вам об успешной оплате заказа. В скором времени он будет отправлен.
            </div>

            <!-- Карточка с деталями оплаты -->
            <div class="details-card">
                <div class="detail-row">
                    <span class="detail-label">Сумма оплаты: </span>
                    <span class="detail-value amount"> {{ number_format($amount, 2, ',', ' ') }} ₽</span>
                </div>
            </div>

            <!-- Кнопка для проверки статуса -->
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/orders" class="button">
                    Проверить статус заказа
                </a>
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