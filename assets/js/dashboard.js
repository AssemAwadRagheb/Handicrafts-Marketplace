// تفعيل أدوات Bootstrap في لوحة التحكم
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
    
    // تفعيل الجداول القابلة للفرز
    const sortableTables = document.querySelectorAll('.sortable-table');
    sortableTables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const sortField = header.dataset.sort;
                const isAsc = header.classList.contains('asc');
                
                // إزالة كل علامات الترتيب
                headers.forEach(h => {
                    h.classList.remove('asc', 'desc');
                });
                
                // إضافة علامة الترتيب الجديدة
                header.classList.add(isAsc ? 'desc' : 'asc');
                
                // ترتيب الجدول
                sortTable(table, sortField, isAsc);
            });
        });
    });
    
    // دالة ترتيب الجدول
    function sortTable(table, sortField, isAsc) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aValue = a.querySelector(`td[data-${sortField}]`).getAttribute(`data-${sortField}`);
            const bValue = b.querySelector(`td[data-${sortField}]`).getAttribute(`data-${sortField}`);
            
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return isAsc ? Number(aValue) - Number(bValue) : Number(bValue) - Number(aValue);
            } else {
                return isAsc ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
            }
        });
        
        // إزالة الصفوف الحالية
        rows.forEach(row => tbody.removeChild(row));
        
        // إضافة الصفوف المرتبة
        rows.forEach(row => tbody.appendChild(row));
    }
    
    // تحميل البيانات الإحصائية للرسم البياني
    if (document.getElementById('salesChart')) {
        fetch('get-sales-data.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderSalesChart(data.sales_data);
                }
            });
    }
    
    // دالة عرض الرسم البياني للمبيعات
    function renderSalesChart(salesData) {
        const ctx = document.getElementById('salesChart').getContext('2d');
        const labels = salesData.map(item => item.month);
        const data = salesData.map(item => item.total);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'إجمالي المبيعات',
                    data: data,
                    backgroundColor: 'rgba(108, 99, 255, 0.2)',
                    borderColor: 'rgba(108, 99, 255, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true
                    },
                    tooltip: {
                        rtl: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // عرض معاينة الصورة قبل الرفع
    document.querySelectorAll('.image-preview-input').forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    preview.src = this.result;
                    preview.style.display = 'block';
                });
                
                reader.readAsDataURL(file);
            }
        });
    });
    
    // حذف العناصر مع التأكيد
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('هل أنت متأكد من أنك تريد الحذف؟ لا يمكن التراجع عن هذا الإجراء.')) {
                window.location.href = this.href;
            }
        });
    });
    
    // تغيير حالة العناصر
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const itemId = this.dataset.id;
            const itemType = this.dataset.type;
            const newStatus = this.checked ? 'active' : 'inactive';
            
            fetch('update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${itemId}&type=${itemType}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    this.checked = !this.checked;
                    alert('حدث خطأ أثناء تحديث الحالة');
                }
            });
        });
    });
});