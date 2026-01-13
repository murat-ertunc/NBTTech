<?php
http_response_code(404);
$UygulamaAdi = 'NbtProject';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>404 - Sayfa Bulunamadı | <?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/bootstrap.min.css" />
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="/assets/error.css" />
</head>
<body>
    <i class="bi bi-question-circle decoration decoration-1 text-white"></i>
    <i class="bi bi-exclamation-circle decoration decoration-2 text-white"></i>
    
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i class="bi bi-emoji-frown"></i>
            </div>
            <div class="error-code">404</div>
            <h1 class="error-title">Sayfa Bulunamadı</h1>
            <p class="error-desc">
                Aradığınız sayfa mevcut değil veya taşınmış olabilir. 
                Lütfen ana sayfaya dönüp tekrar deneyin.
            </p>
            <a href="/dashboard" class="btn-home">
                <i class="bi bi-house-door"></i>
                Ana Sayfaya Dön
            </a>
            <div class="mt-4">
                <small class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    <?= date('d.m.Y H:i:s') ?>
                </small>
            </div>
        </div>
    </div>
</body>
</html>
