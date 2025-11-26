/**
 * Registration Form JavaScript
 */

let currentStep = 1;
let formData = {};

// Step navigation
function goToStep(step) {
    if (step < 1 || step > 7) return;
    
    // Hide all steps
    document.querySelectorAll('.register-step').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show target step
    document.getElementById(`step-${step}`).classList.add('active');
    
    // Update step indicator
    document.querySelectorAll('.step').forEach((el, index) => {
        if (index + 1 <= step) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
    
    currentStep = step;
}

// Move greeting up/down
function moveGreeting(index, direction) {
    const container = document.getElementById('greetings-container');
    const items = Array.from(container.querySelectorAll('.greeting-item'));
    
    if (direction === 'up' && index > 0) {
        const currentItem = items[index];
        const prevItem = items[index - 1];
        container.insertBefore(currentItem, prevItem);
        updateGreetingNumbers();
        updateGreetingButtons();
    } else if (direction === 'down' && index < items.length - 1) {
        const currentItem = items[index];
        const nextItem = items[index + 1];
        container.insertBefore(nextItem, currentItem);
        updateGreetingNumbers();
        updateGreetingButtons();
    }
}

function updateGreetingNumbers() {
    const items = document.querySelectorAll('.greeting-item');
    items.forEach((item, index) => {
        item.querySelector('.greeting-number').textContent = index + 1;
        item.setAttribute('data-order', index);
    });
}

function updateGreetingButtons() {
    const items = document.querySelectorAll('.greeting-item');
    items.forEach((item, index) => {
        const upBtn = item.querySelector('.btn-move-up');
        const downBtn = item.querySelector('.btn-move-down');
        upBtn.disabled = index === 0;
        downBtn.disabled = index === items.length - 1;
        upBtn.setAttribute('onclick', `moveGreeting(${index}, 'up')`);
        downBtn.setAttribute('onclick', `moveGreeting(${index}, 'down')`);
    });
}

// Postal code lookup
document.getElementById('lookup-address')?.addEventListener('click', async () => {
    const postalCode = document.getElementById('company_postal_code').value.replace(/-/g, '');
    
    if (!postalCode || postalCode.length !== 7) {
        alert('7桁の郵便番号を入力してください');
        return;
    }
    
    try {
        const response = await fetch(`../backend/api/utils/postal-code-lookup.php?postal_code=${postalCode}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('company_address').value = result.data.address;
        } else {
            alert(result.message || '住所の取得に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
});

// License lookup
document.getElementById('lookup-license')?.addEventListener('click', async () => {
    const prefecture = document.getElementById('license_prefecture').value;
    const renewal = document.getElementById('license_renewal').value;
    const registration = document.getElementById('license_registration').value;
    
    if (!prefecture || !renewal || !registration) {
        alert('都道府県、更新番号、登録番号をすべて入力してください');
        return;
    }
    
    try {
        const response = await fetch('../backend/api/utils/license-lookup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                prefecture: prefecture,
                renewal: renewal,
                registration: registration
            })
        });
        const result = await response.json();
        
        if (result.success) {
            if (result.data.company_name) {
                document.querySelector('input[name="company_name_profile"]').value = result.data.company_name;
            }
            if (result.data.address) {
                document.getElementById('company_address').value = result.data.address;
            }
        } else {
            alert(result.message || '会社情報の取得に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
});

// Communication checkbox handlers
document.querySelectorAll('.communication-checkbox input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const item = this.closest('.communication-item');
        const details = item.querySelector('.comm-details');
        if (this.checked) {
            details.style.display = 'block';
        } else {
            details.style.display = 'none';
        }
    });
});

// Step 1: Account Registration
document.getElementById('register-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formDataObj = new FormData(e.target);
    const data = Object.fromEntries(formDataObj);
    
    try {
        const response = await fetch('../backend/api/auth/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            formData = { ...formData, ...data, user_id: result.data.user_id };
            sessionStorage.setItem('registerData', JSON.stringify(formData));
            goToStep(2);
        } else {
            alert(result.message || '登録に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
});

// Step 2: Header & Greeting
document.getElementById('header-greeting-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formDataObj = new FormData(e.target);
    const data = Object.fromEntries(formDataObj);
    
    // Handle logo upload
    const logoFile = document.getElementById('company_logo').files[0];
    if (logoFile) {
        const uploadData = new FormData();
        uploadData.append('file', logoFile);
        uploadData.append('file_type', 'logo');
        
        try {
            const uploadResponse = await fetch('../backend/api/business-card/upload.php', {
                method: 'POST',
                body: uploadData,
                credentials: 'include'
            });
            
            const uploadResult = await uploadResponse.json();
            if (uploadResult.success) {
                data.company_logo = uploadResult.data.file_path;
            }
        } catch (error) {
            console.error('Logo upload error:', error);
        }
    }
    
    // Handle profile photo upload
    const photoFile = document.getElementById('profile_photo_header').files[0];
    if (photoFile) {
        const uploadData = new FormData();
        uploadData.append('file', photoFile);
        uploadData.append('file_type', 'photo');
        
        try {
            const uploadResponse = await fetch('../backend/api/business-card/upload.php', {
                method: 'POST',
                body: uploadData,
                credentials: 'include'
            });
            
            const uploadResult = await uploadResponse.json();
            if (uploadResult.success) {
                data.profile_photo = uploadResult.data.file_path;
            }
        } catch (error) {
            console.error('Photo upload error:', error);
        }
    }
    
    // Handle greetings - get order from DOM
    const greetingItems = document.querySelectorAll('.greeting-item');
    const greetings = [];
    greetingItems.forEach((item, index) => {
        const title = item.querySelector('input[name="greeting_title[]"]').value;
        const content = item.querySelector('textarea[name="greeting_content[]"]').value;
        if (title || content) {
            greetings.push({
                title: title,
                content: content,
                display_order: index
            });
        }
    });
    
    data.greetings = greetings;
    
    formData = { ...formData, ...data };
    sessionStorage.setItem('registerData', JSON.stringify(formData));
    
    // Update business card
    try {
        const response = await fetch('../backend/api/business-card/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            goToStep(3);
        } else {
            alert('更新に失敗しました: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
});

// Step 3: Company Profile
document.getElementById('company-profile-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formDataObj = new FormData(e.target);
    const data = Object.fromEntries(formDataObj);
    
    // Merge company_name from profile step
    if (data.company_name_profile) {
        data.company_name = data.company_name_profile;
        delete data.company_name_profile;
    }
    
    formData = { ...formData, ...data };
    sessionStorage.setItem('registerData', JSON.stringify(formData));
    
    // Update business card
    try {
        const response = await fetch('../backend/api/business-card/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            goToStep(4);
        } else {
            alert('更新に失敗しました: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
});

// Step 4: Personal Information
document.getElementById('personal-info-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formDataObj = new FormData(e.target);
    const data = Object.fromEntries(formDataObj);
    
    // Handle qualifications checkboxes
    const qualifications = [];
    if (formDataObj.get('qualification_takken')) {
        qualifications.push('宅地建物取引士');
    }
    if (formDataObj.get('qualification_kenchikushi')) {
        qualifications.push('建築士');
    }
    if (data.qualifications_other) {
        qualifications.push(data.qualifications_other);
    }
    data.qualifications = qualifications.join('、');
    
    // Remove individual qualification fields
    delete data.qualification_takken;
    delete data.qualification_kenchikushi;
    delete data.qualifications_other;
    
    // Combine free input fields
    let freeInputData = {
        text: data.free_input_text || '',
        image_link: data.free_image_link || ''
    };
    
    // Handle free image upload
    const freeImageFile = document.getElementById('free_image').files[0];
    if (freeImageFile) {
        const uploadData = new FormData();
        uploadData.append('file', freeImageFile);
        uploadData.append('file_type', 'free');
        
        try {
            const uploadResponse = await fetch('../backend/api/business-card/upload.php', {
                method: 'POST',
                body: uploadData,
                credentials: 'include'
            });
            
            const uploadResult = await uploadResponse.json();
            if (uploadResult.success) {
                const fullPath = uploadResult.data.file_path;
                const relativePath = fullPath.split('/php/')[1];
                freeInputData.image = relativePath;
            }
        } catch (error) {
            console.error('Upload error:', error);
        }
    }
    
    // Store free input as JSON
    data.free_input = JSON.stringify(freeInputData);
    delete data.free_input_text;
    delete data.free_image_link;
    
    formData = { ...formData, ...data };
    sessionStorage.setItem('registerData', JSON.stringify(formData));
    
    // Update business card
    try {
        const response = await fetch('../backend/api/business-card/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            goToStep(5);
        } else {
            alert('更新に失敗しました: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
});

// Step 5: Tech Tools Selection
document.getElementById('tech-tools-form')?.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const selectedTools = Array.from(document.querySelectorAll('input[name="tech_tools[]"]:checked'))
        .map(cb => cb.value);
    
    if (selectedTools.length < 2) {
        alert('最低2つ以上のテックツールを選択してください');
        return;
    }
    
    formData.tech_tools = selectedTools;
    sessionStorage.setItem('registerData', JSON.stringify(formData));
    
    // Generate tech tool URLs
    generateTechToolUrls(selectedTools);
    
    goToStep(6);
});

// Generate Tech Tool URLs
async function generateTechToolUrls(selectedTools) {
    try {
        const response = await fetch('../backend/api/tech-tools/generate-urls.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ selected_tools: selectedTools }),
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            formData.tech_tool_urls = result.data.tech_tools;
            sessionStorage.setItem('registerData', JSON.stringify(formData));
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Step 6: Communication Functions
document.getElementById('communication-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formDataObj = new FormData(e.target);
    const communicationMethods = [];
    let displayOrder = 0;
    
    // Message apps
    const messageApps = [
        { key: 'comm_line', type: 'line', idField: 'comm_line_id' },
        { key: 'comm_messenger', type: 'messenger', idField: 'comm_messenger_id' },
        { key: 'comm_whatsapp', type: 'whatsapp', idField: 'comm_whatsapp_id' },
        { key: 'comm_plus_message', type: 'plus_message', idField: 'comm_plus_message_id' },
        { key: 'comm_chatwork', type: 'chatwork', idField: 'comm_chatwork_id' },
        { key: 'comm_andpad', type: 'andpad', idField: 'comm_andpad_id' }
    ];
    
    messageApps.forEach(app => {
        if (formDataObj.get(app.key)) {
            const id = formDataObj.get(app.idField) || '';
            communicationMethods.push({
                method_type: app.type,
                method_name: app.type,
                method_url: id.startsWith('http') ? id : '',
                method_id: id.startsWith('http') ? '' : id,
                display_order: displayOrder++
            });
        }
    });
    
    // SNS
    const snsApps = [
        { key: 'comm_instagram', type: 'instagram', urlField: 'comm_instagram_url' },
        { key: 'comm_facebook', type: 'facebook', urlField: 'comm_facebook_url' },
        { key: 'comm_twitter', type: 'twitter', urlField: 'comm_twitter_url' },
        { key: 'comm_youtube', type: 'youtube', urlField: 'comm_youtube_url' },
        { key: 'comm_tiktok', type: 'tiktok', urlField: 'comm_tiktok_url' },
        { key: 'comm_note', type: 'note', urlField: 'comm_note_url' },
        { key: 'comm_pinterest', type: 'pinterest', urlField: 'comm_pinterest_url' },
        { key: 'comm_threads', type: 'threads', urlField: 'comm_threads_url' }
    ];
    
    snsApps.forEach(app => {
        if (formDataObj.get(app.key)) {
            const url = formDataObj.get(app.urlField) || '';
            communicationMethods.push({
                method_type: app.type,
                method_name: app.type,
                method_url: url,
                method_id: '',
                display_order: displayOrder++
            });
        }
    });
    
    const data = {
        communication_methods: communicationMethods
    };
    
    formData = { ...formData, ...data };
    sessionStorage.setItem('registerData', JSON.stringify(formData));
    
    // Update business card
    try {
        const response = await fetch('../backend/api/business-card/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
            goToStep(7);
            loadPreview();
        } else {
            alert('更新に失敗しました: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
});

// Step 7: Preview & Payment
document.getElementById('submit-payment')?.addEventListener('click', async () => {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
    
    if (!paymentMethod) {
        alert('支払方法を選択してください');
        return;
    }
    
    // Create payment intent
    try {
        const response = await fetch('../backend/api/payment/create-intent.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                payment_method: paymentMethod,
                payment_type: formData.user_type
            }),
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (paymentMethod === 'credit_card') {
                window.location.href = 'payment-success.php';
            } else {
                window.location.href = 'payment-bank-transfer.php';
            }
        } else {
            alert(result.message || '決済処理に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
});

// Load preview
function loadPreview() {
    const previewArea = document.getElementById('preview-area');
    if (previewArea) {
        previewArea.innerHTML = '<p>プレビューを読み込み中...</p>';
        // TODO: Implement actual preview
    }
}

// Photo upload previews
document.getElementById('profile_photo_header')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            const preview = e.target.closest('.upload-area').querySelector('.upload-preview');
            if (preview) {
                preview.innerHTML = `<img src="${event.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
            }
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('company_logo')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            const preview = e.target.closest('.upload-area').querySelector('.upload-preview');
            if (preview) {
                preview.innerHTML = `<img src="${event.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
            }
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('free_image')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            const preview = e.target.closest('.upload-area').querySelector('.upload-preview');
            if (preview) {
                preview.innerHTML = `<img src="${event.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
            }
        };
        reader.readAsDataURL(file);
    }
});

// Initialize from session storage
window.addEventListener('DOMContentLoaded', () => {
    const savedData = sessionStorage.getItem('registerData');
    if (savedData) {
        formData = JSON.parse(savedData);
    }
    
    // Initialize greeting buttons
    updateGreetingButtons();
});
