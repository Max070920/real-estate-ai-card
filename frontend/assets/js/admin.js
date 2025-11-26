/**
 * Admin Dashboard JavaScript
 */

// Payment confirmation with modal
async function confirmPayment(businessCardId) {
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal-overlay active';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>入金確認</h3>
            <p>入金を確認し、QRコードを発行しますか？</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-yes" onclick="processPayment(${businessCardId})">はい</button>
                <button class="modal-btn modal-btn-no" onclick="closeModal()">いいえ</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Store business card ID for processing
    window.currentBusinessCardId = businessCardId;
}

function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        modal.remove();
    }
}

async function processPayment(businessCardId) {
    closeModal();
    
    try {
        const response = await fetch('../../backend/api/admin/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                business_card_id: businessCardId,
                action: 'confirm_payment'
            }),
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('入金を確認し、QRコードを発行しました');
            location.reload();
        } else {
            alert(result.message || '処理に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
}

// Payment checkbox change
document.querySelectorAll('.payment-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            const businessCardId = this.dataset.bcId;
            confirmPayment(businessCardId);
        }
    });
});

// Open checkbox change
document.querySelectorAll('.open-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const businessCardId = this.dataset.bcId;
        const isOpen = this.checked ? 1 : 0;
        
        // Update published status
        // API呼び出し実装が必要
        console.log('Update published status:', businessCardId, isOpen);
    });
});

// Table sorting
function sortTable(column) {
    const url = new URL(window.location);
    const currentSort = url.searchParams.get('sort');
    const currentOrder = url.searchParams.get('order');
    
    let newOrder = 'ASC';
    if (currentSort === column && currentOrder === 'ASC') {
        newOrder = 'DESC';
    }
    
    url.searchParams.set('sort', column);
    url.searchParams.set('order', newOrder);
    window.location = url.toString();
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Add click handlers to sortable headers
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            sortTable(sortField);
        });
    });
    
    // Update sort indicators based on current sort
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort');
    const currentOrder = urlParams.get('order');
    
    if (currentSort) {
        document.querySelectorAll('.sortable').forEach(header => {
            if (header.dataset.sort === currentSort) {
                header.classList.remove('sort-asc', 'sort-desc');
                header.classList.add(currentOrder === 'ASC' ? 'sort-asc' : 'sort-desc');
            } else {
                header.classList.remove('sort-asc', 'sort-desc');
            }
        });
    }
    
    // Close modal on overlay click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeModal();
        }
    });
    
    console.log('Admin dashboard loaded');
});

