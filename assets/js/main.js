// تفعيل أدوات Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل الأدوات المنبثقة
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })
    
    // تفعيل الأدوات التلميحية
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
    // تغيير الصورة الرئيسية عند النقر على الصور المصغرة
    document.querySelectorAll('.thumbnail').forEach(function(thumb) {
        thumb.addEventListener('click', function() {
            const mainImage = document.querySelector('.main-image');
            mainImage.src = this.src;
        });
    });
    
    // إضافة منتج إلى السلة
    document.querySelectorAll('.add-to-cart').forEach(function(button) {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantity = document.querySelector(`#quantity-${productId}`) ? document.querySelector(`#quantity-${productId}`).value : 1;
            
            fetch('add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    updateCartCount(data.cart_count);
                    showAlert('تمت إضافة المنتج إلى السلة بنجاح', 'success');
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('حدث خطأ أثناء إضافة المنتج إلى السلة', 'danger');
            });
        });
    });
    
    // تحديث عدد العناصر في السلة
    function updateCartCount(count) {
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = count;
        });
    }
    
    // عرض رسالة تنبيه
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(alertDiv);
            }, 150);
        }, 3000);
    }
    
    // تحميل عدد العناصر في السلة عند تحميل الصفحة
    fetch('get-cart-count.php')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                updateCartCount(data.count);
            }
        });
    
    // فلترة المنتجات حسب الحرفة
    const craftFilter = document.getElementById('craft-filter');
    if(craftFilter) {
        craftFilter.addEventListener('change', function() {
            window.location.href = `products.php?craft=${this.value}`;
        });
    }
    
    // فلترة المنتجات حسب السعر
    const priceFilter = document.getElementById('price-filter');
    if(priceFilter) {
        priceFilter.addEventListener('change', function() {
            window.location.href = `products.php?price=${this.value}`;
        });
    }
    
    // البحث عن المنتجات
    const searchForm = document.getElementById('search-form');
    if(searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = document.getElementById('search-query').value;
            window.location.href = `products.php?search=${encodeURIComponent(query)}`;
        });
    }
});