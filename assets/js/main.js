/**
 * JavaScript chung cho toàn bộ website
 */

// Hàm format số tiền VNĐ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

// Hàm validate form
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            input.style.borderColor = '#ddd';
        }
    });
    
    return isValid;
}

// Xác nhận trước khi xóa
function confirmDelete(message) {
    return confirm(message || 'Bạn có chắc muốn xóa?');
}

// Hiển thị/ẩn modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Đóng modal khi click bên ngoài
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
});

// Auto-hide alerts sau 5 giây
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Xử lý upload ảnh - preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Tính toán ngày thuê và tổng tiền
function calculateRentalDays(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (start && end && end > start) {
        const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        return days;
    }
    
    return 0;
}

// Loading spinner
function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loading-spinner';
    loading.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">
            <div style="background: white; padding: 2rem; border-radius: 10px;">
                <p>Đang xử lý...</p>
            </div>
        </div>
    `;
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.getElementById('loading-spinner');
    if (loading) {
        loading.remove();
    }
}

// Export functions
window.carRental = {
    formatCurrency,
    validateForm,
    confirmDelete,
    showModal,
    hideModal,
    previewImage,
    calculateRentalDays,
    showLoading,
    hideLoading
};
