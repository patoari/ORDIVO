<div class="promo-carousel-container">
    <div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
        <?php if (count($banners) > 1): ?>
        <div class="carousel-indicators">
            <?php foreach ($banners as $index => $banner): ?>
            <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $index ?>" 
                    class="<?= $index === 0 ? 'active' : '' ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="carousel-inner">
            <?php if (empty($banners)): ?>
                <div class="carousel-item active">
                    <div class="promo-banner" style="background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);">
                        <div class="promo-content">
                            <h2>Get 25% off</h2>
                            <p>Min order Tk 250</p>
                            <button class="promo-btn">Get it</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($banners as $index => $banner): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="promo-banner" style="
                        <?php if (!empty($banner['background_image'])): ?>
                            background-image: url('../<?= htmlspecialchars($banner['background_image']) ?>');
                            background-size: cover;
                            background-position: center;
                        <?php endif; ?>
                        background-color: <?= htmlspecialchars($banner['background_color'] ?? '#f97316') ?>;
                    ">
                        <div class="promo-content" style="color: <?= htmlspecialchars($banner['text_color'] ?? '#ffffff') ?>">
                            <h2><?= htmlspecialchars($banner['title']) ?></h2>
                            <?php if (!empty($banner['subtitle'])): ?>
                            <p><?= htmlspecialchars($banner['subtitle']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($banner['button_text'])): ?>
                            <button class="promo-btn" onclick="window.location.href='<?= htmlspecialchars($banner['button_link'] ?? '#') ?>'"><?= htmlspecialchars($banner['button_text']) ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (count($banners) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
        <?php endif; ?>
    </div>
</div>
