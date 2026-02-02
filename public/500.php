<?php






if (http_response_code() === 200) {
    http_response_code(500);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Sunucu Hatası</title>
    <link href="/assets/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/error.css" rel="stylesheet">
</head>
<body class="error-page">
    <div class="container">
        <div class="error-container text-center">
            <div class="error-code">500</div>
            <h1 class="error-title">Sunucu Hatası</h1>
            <p class="error-message">
                Bir şeyler yanlış gitti. Lütfen daha sonra tekrar deneyin.
            </p>
            <div class="error-actions">
                <a href="/" class="btn btn-primary">
                    <i class="bi bi-house-door me-1"></i> Ana Sayfa
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Geri Dön
                </button>
            </div>
            <div class="error-details mt-4">
                <small class="text-muted">
                    Hata ID: <?= substr(md5(microtime()), 0, 8) ?>
                </small>
            </div>
        </div>
    </div>
</body>
</html>
