<?php include 'includes/header.php'; ?>

<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4">اكتشف جمال الصناعات اليدوية</h1>
                <p class="lead">منتجات فريدة مصنوعة بحب وإتقان من قبل حرفيين موهوبين</p>
                <a href="products.php" class="btn btn-primary btn-lg">تصفح المنتجات</a>
                <a href="courses.php" class="btn btn-outline-primary btn-lg">تعلم حرفة جديدة</a>
            </div>
            <div class="col-lg-6">
                <img src="assets/images/hero-image.jpg" alt="Handicrafts" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">استكشف الحرف اليدوية</h2>
        <div class="row">
            <?php 
            $crafts = get_all_crafts();
            foreach ($crafts as $craft): 
            ?>
            <div class="col-md-4 mb-4">
                <div class="card craft-card h-100">
                    <img src="<?= $craft['image'] ?>" class="card-img-top" alt="<?= $craft['craft_name'] ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= $craft['craft_name'] ?></h5>
                        <p class="card-text"><?= substr($craft['description'], 0, 100) ?>...</p>
                        <a href="products.php?craft=<?= $craft['craft_id'] ?>" class="btn btn-outline-primary">تصفح المنتجات</a>
                        <a href="courses.php?craft=<?= $craft['craft_id'] ?>" class="btn btn-outline-secondary">تعلم الحرفة</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">أحدث المنتجات</h2>
        <div class="row">
            <?php 
            $latest_products = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 6")->fetchAll();
            foreach ($latest_products as $product): 
                $images = json_decode($product['images'], true);
            ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card product-card h-100">
                    <img src="<?= $images[0] ?>" class="card-img-top" alt="<?= $product['product_name'] ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= $product['product_name'] ?></h5>
                        <p class="card-text"><?= substr($product['description'], 0, 100) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price"><?= number_format($product['price'], 2) ?> ر.س</span>
                            <a href="product-details.php?id=<?= $product['product_id'] ?>" class="btn btn-sm btn-primary">التفاصيل</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-primary">عرض جميع المنتجات</a>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <h2>هل لديك فكرة لمنتج يدوي؟</h2>
                <p>يمكنك طلب صنع منتج يدوي خاص بك حسب مواصفاتك وتصميمك</p>
                <a href="custom-request.php" class="btn btn-primary">اطلب منتج مخصص</a>
            </div>
            <div class="col-lg-6">
                <h2>زيارة معرضنا</h2>
                <p>احجز موعد لزيارة معرضنا ومقابلة الحرفيين ورؤية منتجاتهم عن قرب</p>
                <a href="exhibition.php" class="btn btn-primary">حجز موعد</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>