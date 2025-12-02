/**
 * Edit Business Card JavaScript
 */

// Make businessCardData globally accessible
let businessCardData = null;
window.businessCardData = businessCardData;

// Load business card data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadBusinessCardData();
    
    // Navigation handling
    setupNavigation();
    
    // File upload preview handlers
    setupFileUploads();
});

// Load business card data from API
async function loadBusinessCardData() {
    const previewContent = document.getElementById('preview-content');
    if (previewContent) {
        previewContent.innerHTML = '<p>データを読み込み中...</p>';
    }
    
    try {
        const response = await fetch('../backend/api/business-card/get.php', {
            method: 'GET',
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.success && result.data) {
            businessCardData = result.data;
            window.businessCardData = businessCardData; // Make it globally accessible
            console.log('Business Card Data:', businessCardData);
            populateForms(businessCardData);
            updatePreview(businessCardData);
        } else {
            console.error('Failed to load business card data:', result);
            const errorMsg = result.message || 'データの読み込みに失敗しました';
            if (previewContent) {
                previewContent.innerHTML = `<p style="color: red;">${errorMsg}</p>`;
            }
            alert('データの読み込みに失敗しました: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error loading business card data:', error);
        const errorMsg = error.message || 'エラーが発生しました';
        if (previewContent) {
            previewContent.innerHTML = `<p style="color: red;">${errorMsg}</p>`;
        }
        alert('エラーが発生しました: ' + errorMsg);
    }
}

// Populate forms with loaded data
function populateForms(data) {
    console.log('Populating forms with data:', data);
    
    // Step 1: Header & Greeting Form
    const headerGreetingForm = document.getElementById('header-greeting-form');
    if (headerGreetingForm) {
        // Company name
        const companyNameInput = headerGreetingForm.querySelector('input[name="company_name"]');
        if (companyNameInput && data.company_name) {
            companyNameInput.value = data.company_name;
            console.log('Set company_name:', data.company_name);
        }
        
        // Logo
        if (data.company_logo) {
            const logoPreview = document.querySelector('[data-upload-id="company_logo"] .upload-preview');
            if (logoPreview) {
                const logoPath = data.company_logo.startsWith('http') ? data.company_logo : '../' + data.company_logo;
                logoPreview.innerHTML = `<img src="${logoPath}" alt="ロゴ" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
                console.log('Set company_logo:', logoPath);
            }
        }
        
        // Profile Photo
        if (data.profile_photo) {
            const photoPreview = document.querySelector('[data-upload-id="profile_photo"] .upload-preview');
            if (photoPreview) {
                const photoPath = data.profile_photo.startsWith('http') ? data.profile_photo : '../' + data.profile_photo;
                photoPreview.innerHTML = `<img src="${photoPath}" alt="プロフィール写真" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
                console.log('Set profile_photo:', photoPath);
            }
        }
        
        // Greetings
        if (data.greetings && Array.isArray(data.greetings) && data.greetings.length > 0) {
            console.log('Displaying greetings:', data.greetings);
            displayGreetings(data.greetings);
        } else {
            console.log('No greetings to display');
        }
    }
    
    // Step 2: Company Profile Form
    const companyProfileForm = document.getElementById('company-profile-form');
    if (companyProfileForm) {
        if (data.real_estate_license_prefecture) {
            const prefectureSelect = companyProfileForm.querySelector('select[name="real_estate_license_prefecture"]');
            if (prefectureSelect) prefectureSelect.value = data.real_estate_license_prefecture;
        }
        if (data.real_estate_license_renewal_number) {
            const renewalSelect = companyProfileForm.querySelector('select[name="real_estate_license_renewal_number"]');
            if (renewalSelect) renewalSelect.value = data.real_estate_license_renewal_number;
        }
        if (data.real_estate_license_registration_number) {
            const registrationInput = companyProfileForm.querySelector('input[name="real_estate_license_registration_number"]');
            if (registrationInput) registrationInput.value = data.real_estate_license_registration_number;
        }
        if (data.company_name) {
            const companyNameInput = companyProfileForm.querySelector('input[name="company_name_profile"]');
            if (companyNameInput) companyNameInput.value = data.company_name;
        }
        if (data.company_postal_code) {
            const postalCodeInput = companyProfileForm.querySelector('input[name="company_postal_code"]');
            if (postalCodeInput) postalCodeInput.value = data.company_postal_code;
        }
        if (data.company_address) {
            const addressInput = companyProfileForm.querySelector('input[name="company_address"]');
            if (addressInput) addressInput.value = data.company_address;
        }
        if (data.company_phone) {
            const phoneInput = companyProfileForm.querySelector('input[name="company_phone"]');
            if (phoneInput) phoneInput.value = data.company_phone;
        }
        if (data.company_website) {
            const websiteInput = companyProfileForm.querySelector('input[name="company_website"]');
            if (websiteInput) websiteInput.value = data.company_website;
        }
    }
    
    // Step 3: Personal Information Form
    const personalInfoForm = document.getElementById('personal-info-form');
    if (personalInfoForm) {
        if (data.branch_department) {
            const branchDeptInput = personalInfoForm.querySelector('input[name="branch_department"]');
            if (branchDeptInput) branchDeptInput.value = data.branch_department;
        }
        if (data.position) {
            const positionInput = personalInfoForm.querySelector('input[name="position"]');
            if (positionInput) positionInput.value = data.position;
        }
        
        // Name (split into last_name and first_name)
        if (data.name) {
            const lastNameInput = document.getElementById('edit_last_name');
            const firstNameInput = document.getElementById('edit_first_name');
            
            if (lastNameInput && firstNameInput) {
                const nameParts = data.name.trim().split(/\s+/);
                if (nameParts.length >= 2) {
                    lastNameInput.value = nameParts[0];
                    firstNameInput.value = nameParts.slice(1).join(' ');
                } else {
                    lastNameInput.value = data.name;
                    firstNameInput.value = '';
                }
                console.log('Set name:', data.name, '->', lastNameInput.value, firstNameInput.value);
            }
        }
        
        // Name Romaji (split into last_name_romaji and first_name_romaji)
        if (data.name_romaji) {
            const lastNameRomajiInput = document.getElementById('edit_last_name_romaji');
            const firstNameRomajiInput = document.getElementById('edit_first_name_romaji');
            
            if (lastNameRomajiInput && firstNameRomajiInput) {
                const romajiParts = data.name_romaji.trim().split(/\s+/);
                if (romajiParts.length >= 2) {
                    lastNameRomajiInput.value = romajiParts[0];
                    firstNameRomajiInput.value = romajiParts.slice(1).join(' ');
                } else {
                    lastNameRomajiInput.value = data.name_romaji;
                    firstNameRomajiInput.value = '';
                }
                console.log('Set name_romaji:', data.name_romaji);
            }
        }
        
        if (data.mobile_phone) {
            const mobilePhoneInput = personalInfoForm.querySelector('input[name="mobile_phone"]');
            if (mobilePhoneInput) mobilePhoneInput.value = data.mobile_phone;
        }
        if (data.birth_date) {
            const birthDateInput = personalInfoForm.querySelector('input[name="birth_date"]');
            if (birthDateInput) birthDateInput.value = data.birth_date;
        }
        if (data.current_residence) {
            const residenceInput = personalInfoForm.querySelector('input[name="current_residence"]');
            if (residenceInput) residenceInput.value = data.current_residence;
        }
        if (data.hometown) {
            const hometownInput = personalInfoForm.querySelector('input[name="hometown"]');
            if (hometownInput) hometownInput.value = data.hometown;
        }
        if (data.alma_mater) {
            const almaMaterInput = personalInfoForm.querySelector('input[name="alma_mater"]');
            if (almaMaterInput) almaMaterInput.value = data.alma_mater;
        }
        
        // Qualifications
        if (data.qualifications) {
            const qualifications = data.qualifications.split('、');
            if (qualifications.includes('宅地建物取引士')) {
                const takkenCheckbox = personalInfoForm.querySelector('input[name="qualification_takken"]');
                if (takkenCheckbox) takkenCheckbox.checked = true;
            }
            if (qualifications.includes('建築士')) {
                const kenchikushiCheckbox = personalInfoForm.querySelector('input[name="qualification_kenchikushi"]');
                if (kenchikushiCheckbox) kenchikushiCheckbox.checked = true;
            }
            // Other qualifications
            const otherQuals = qualifications.filter(q => q !== '宅地建物取引士' && q !== '建築士').join('、');
            if (otherQuals) {
                const otherQualsTextarea = personalInfoForm.querySelector('textarea[name="qualifications_other"]');
                if (otherQualsTextarea) otherQualsTextarea.value = otherQuals;
            }
        }
        
        if (data.hobbies) {
            const hobbiesTextarea = personalInfoForm.querySelector('textarea[name="hobbies"]');
            if (hobbiesTextarea) hobbiesTextarea.value = data.hobbies;
        }
        
        // Free input
        if (data.free_input) {
            try {
                const freeInputData = JSON.parse(data.free_input);
                if (freeInputData.text) {
                    const freeTextTextarea = personalInfoForm.querySelector('textarea[name="free_input_text"]');
                    if (freeTextTextarea) freeTextTextarea.value = freeInputData.text;
                }
                if (freeInputData.image_link) {
                    const freeImageLinkInput = personalInfoForm.querySelector('input[name="free_image_link"]');
                    if (freeImageLinkInput) freeImageLinkInput.value = freeInputData.image_link;
                }
                if (freeInputData.image) {
                    const freeImagePreview = document.querySelector('#free-image-upload .upload-preview');
                    if (freeImagePreview) {
                        const imagePath = freeInputData.image.startsWith('http') ? freeInputData.image : '../' + freeInputData.image;
                        freeImagePreview.innerHTML = `<img src="${imagePath}" alt="フリー画像" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
                    }
                }
            } catch (e) {
                console.error('Error parsing free_input:', e);
            }
        }
    }
    
    // Step 4: Tech Tools
    if (data.tech_tools && Array.isArray(data.tech_tools) && data.tech_tools.length > 0) {
        console.log('Displaying tech tools:', data.tech_tools);
        displayTechTools(data.tech_tools);
    } else {
        console.log('No tech tools to display');
    }
    
    // Step 5: Communication Methods
    if (data.communication_methods && Array.isArray(data.communication_methods) && data.communication_methods.length > 0) {
        console.log('Displaying communication methods:', data.communication_methods);
        displayCommunicationMethods(data.communication_methods);
    } else {
        console.log('No communication methods to display');
    }
    
    console.log('Form population complete');
}

// Display greetings
function displayGreetings(greetings) {
    const greetingsList = document.getElementById('greetings-list');
    if (!greetingsList) return;
    
    greetingsList.innerHTML = '';
    
    greetings.forEach((greeting, index) => {
        const greetingItem = document.createElement('div');
        greetingItem.className = 'greeting-item';
        greetingItem.dataset.id = greeting.id;
        greetingItem.dataset.order = index;
        greetingItem.innerHTML = `
            <div class="greeting-header">
                <span class="greeting-number">${index + 1}</span>
                <div class="greeting-actions">
                    <button type="button" class="btn-move-up" onclick="moveGreeting(${index}, 'up')" ${index === 0 ? 'disabled' : ''}>↑</button>
                    <button type="button" class="btn-move-down" onclick="moveGreeting(${index}, 'down')" ${index === greetings.length - 1 ? 'disabled' : ''}>↓</button>
                </div>
                <button type="button" class="btn-delete" onclick="deleteGreeting(${greeting.id})">削除</button>
            </div>
            <div class="form-group">
                <label>タイトル</label>
                <input type="text" class="form-control greeting-title" value="${escapeHtml(greeting.title || '')}" placeholder="タイトル">
            </div>
            <div class="form-group">
                <label>本文</label>
                <textarea class="form-control greeting-content" rows="4" placeholder="本文">${escapeHtml(greeting.content || '')}</textarea>
            </div>
        `;
        greetingsList.appendChild(greetingItem);
    });
}

// Move greeting up/down
function moveGreeting(index, direction) {
    const container = document.getElementById('greetings-list');
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
    const items = document.querySelectorAll('#greetings-list .greeting-item');
    items.forEach((item, index) => {
        item.querySelector('.greeting-number').textContent = index + 1;
        item.setAttribute('data-order', index);
    });
}

function updateGreetingButtons() {
    const items = document.querySelectorAll('#greetings-list .greeting-item');
    items.forEach((item, index) => {
        const upBtn = item.querySelector('.btn-move-up');
        const downBtn = item.querySelector('.btn-move-down');
        if (upBtn) {
            upBtn.disabled = index === 0;
            upBtn.setAttribute('onclick', `moveGreeting(${index}, 'up')`);
        }
        if (downBtn) {
            downBtn.disabled = index === items.length - 1;
            downBtn.setAttribute('onclick', `moveGreeting(${index}, 'down')`);
        }
    });
}

// Display tech tools
function displayTechTools(techTools) {
    const techToolsList = document.getElementById('tech-tools-list');
    if (!techToolsList) {
        console.error('Tech tools list element not found');
        return;
    }
    
    const toolNames = {
        'mdb': '全国マンションデータベース',
        'rlp': '物件提案ロボ',
        'llp': '土地情報ロボ',
        'ai': 'AIマンション査定',
        'slp': 'セルフィン',
        'olp': 'オーナーコネクト'
    };
    
    const toolIcons = {
        'mdb': '🏢',
        'rlp': '🤖',
        'llp': '🏞️',
        'ai': '📊',
        'slp': '🔍',
        'olp': '💼'
    };
    
    // All available tools
    const allTools = ['mdb', 'rlp', 'llp', 'ai', 'slp', 'olp'];
    
    techToolsList.innerHTML = '';
    
    // Display all tools, marking which ones are selected
    allTools.forEach((toolType, index) => {
        const existingTool = techTools.find(t => t.tool_type === toolType);
        const isActive = existingTool ? (existingTool.is_active === 1 || existingTool.is_active === true) : false;
        
        const toolItem = document.createElement('div');
        toolItem.className = 'tech-tool-item';
        toolItem.style.cssText = 'margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;';
        if (existingTool) {
            toolItem.dataset.id = existingTool.id;
        }
        toolItem.dataset.toolType = toolType;
        toolItem.innerHTML = `
            <label class="tech-tool-checkbox" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" ${isActive ? 'checked' : ''} onchange="toggleTechTool('${toolType}', this.checked)">
                <div class="tool-icon" style="font-size: 24px;">${toolIcons[toolType] || '📋'}</div>
                <div>
                    <div style="font-weight: bold;">${toolNames[toolType] || toolType}</div>
                    ${existingTool && existingTool.tool_url ? `<div style="font-size: 0.85rem; color: #666;">${existingTool.tool_url}</div>` : ''}
                </div>
            </label>
        `;
        techToolsList.appendChild(toolItem);
    });
    
    console.log('Tech tools displayed:', techTools);
}

// Display communication methods
function displayCommunicationMethods(methods) {
    const commList = document.getElementById('communication-list');
    if (!commList) return;
    
    const methodNames = {
        'line': 'LINE',
        'messenger': 'Messenger',
        'whatsapp': 'WhatsApp',
        'plus_message': '+メッセージ',
        'chatwork': 'Chatwork',
        'andpad': 'Andpad',
        'instagram': 'Instagram',
        'facebook': 'Facebook',
        'twitter': 'X (Twitter)',
        'youtube': 'YouTube',
        'tiktok': 'TikTok',
        'note': 'note',
        'pinterest': 'Pinterest',
        'threads': 'Threads'
    };
    
    const methodIcons = {
        'line': '<img src="./assets/images/icons/line.png" alt="LINE" class="comm-icon-img">',
        'messenger': '<img src="./assets/images/icons/messenger.png" alt="Messenger" class="comm-icon-img">',
        'whatsapp': '<img src="./assets/images/icons/whatsapp.png" alt="WhatsApp" class="comm-icon-img">',
        'plus_message': '<img src="./assets/images/icons/message.png" alt="+メッセージ" class="comm-icon-img">',
        'chatwork': '<img src="./assets/images/icons/chatwork.png" alt="Chatwork" class="comm-icon-img">',
        'andpad': '<img src="./assets/images/icons/andpad.png" alt="Andpad" class="comm-icon-img">',
        'instagram': '<img src="./assets/images/icons/instagram.png" alt="Instagram" class="comm-icon-img">',
        'facebook': '<img src="./assets/images/icons/facebook.png" alt="Facebook" class="comm-icon-img">',
        'twitter': '<img src="./assets/images/icons/twitter.png" alt="X (Twitter)" class="comm-icon-img">',
        'youtube': '<img src="./assets/images/icons/youtube.png" alt="YouTube" class="comm-icon-img">',
        'tiktok': '<img src="./assets/images/icons/tiktok.png" alt="TikTok" class="comm-icon-img">',
        'note': '<img src="./assets/images/icons/note.png" alt="note" class="comm-icon-img">',
        'pinterest': '<img src="./assets/images/icons/pinterest.png" alt="Pinterest" class="comm-icon-img">',
        'threads': '<img src="./assets/images/icons/threads.png" alt="Threads" class="comm-icon-img">'
    };
    
    commList.innerHTML = '';
    
    methods.forEach(method => {
        const commItem = document.createElement('div');
        commItem.className = 'communication-item';
        commItem.dataset.id = method.id;
        commItem.dataset.methodType = method.method_type;
        
        const isUrlBased = ['instagram', 'facebook', 'twitter', 'youtube', 'tiktok', 'note', 'pinterest', 'threads'].includes(method.method_type);
        const value = isUrlBased ? (method.method_url || '') : (method.method_id || '');
        const placeholder = isUrlBased ? 
            `https://${method.method_type === 'twitter' ? 'x.com' : method.method_type === 'note' ? 'note.com' : method.method_type === 'threads' ? 'threads.net' : method.method_type + '.com'}/...` : 
            `${methodNames[method.method_type] || method.method_type} IDまたはURL`;
        
        commItem.innerHTML = `
            <label class="communication-checkbox">
                <input type="checkbox" ${method.is_active ? 'checked' : ''} onchange="toggleCommunicationMethod(${method.id}, this.checked)">
                <div class="comm-icon">${methodIcons[method.method_type] || '<img src="./assets/images/icons/message.png" alt="+メッセージ" class="comm-icon-img">'}</div>
                <span>${methodNames[method.method_type] || method.method_type}</span>
            </label>
            <div class="comm-details" style="display: ${method.is_active ? 'block' : 'none'};">
                <input type="${isUrlBased ? 'url' : 'text'}" class="form-control comm-value" value="${escapeHtml(value)}" placeholder="${placeholder}" ${isUrlBased ? 'pattern="https?://.+"' : ''}>
                ${isUrlBased ? '<small style="color: #666; display: block; margin-top: 4px;">有効なURLを入力してください（https://で始まる必要があります）</small>' : ''}
            </div>
            <button type="button" class="btn-delete" onclick="deleteCommunicationMethod(${method.id})">削除</button>
        `;
        commList.appendChild(commItem);
    });
}

// Setup navigation
function setupNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.edit-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            
            // Update active nav item
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Show target section
            sections.forEach(section => {
                section.classList.remove('active');
                if (section.id === targetId + '-section') {
                    section.classList.add('active');
                }
            });
        });
    });
    
    // Map navigation IDs to section IDs
    const navMap = {
        'header-greeting': 'header-greeting-section',
        'company-profile': 'company-profile-section',
        'personal-info': 'personal-info-section',
        'tech-tools': 'tech-tools-section',
        'communication': 'communication-section'
    };
}

// Setup file uploads
function setupFileUploads() {
    // Logo upload
    const logoInput = document.getElementById('company_logo');
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            handleFileUpload(e, 'company_logo');
        });
    }
    
    // Profile photo upload
    const photoInput = document.getElementById('profile_photo');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            handleFileUpload(e, 'profile_photo');
        });
    }
    
    // Free image upload
    const freeImageInput = document.getElementById('free_image');
    if (freeImageInput) {
        freeImageInput.addEventListener('change', function(e) {
            handleFileUpload(e, 'free_image');
        });
    }
}

// Handle file upload
async function handleFileUpload(event, fieldName) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        alert('画像ファイルを選択してください');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    
    // Determine file type
    let fileType = 'photo';
    if (fieldName === 'company_logo') {
        fileType = 'logo';
    } else if (fieldName === 'free_image') {
        fileType = 'free';
    }
    formData.append('file_type', fileType);
    
    try {
        const response = await fetch('../backend/api/business-card/upload.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show preview
            const preview = event.target.closest('.upload-area').querySelector('.upload-preview');
            if (preview) {
                const imagePath = result.data.file_path.startsWith('http') ? result.data.file_path : '../' + result.data.file_path;
                preview.innerHTML = `<img src="${imagePath}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
            }
            
            // Update business card data
            if (businessCardData) {
                if (fieldName === 'free_image') {
                    // For free image, update the free_input JSON
                    let freeInputData = {};
                    try {
                        if (businessCardData.free_input) {
                            freeInputData = JSON.parse(businessCardData.free_input);
                        }
                    } catch (e) {
                        console.error('Error parsing free_input:', e);
                    }
                    const fullPath = result.data.file_path;
                    const relativePath = fullPath.split('/php/')[1] || fullPath;
                    freeInputData.image = relativePath;
                    businessCardData.free_input = JSON.stringify(freeInputData);
                } else {
                    businessCardData[fieldName] = result.data.file_path;
                }
                window.businessCardData = businessCardData; // Sync with global
            }
            
            // Update preview
            if (businessCardData) {
                updatePreview(businessCardData);
            }
        } else {
            alert('アップロードに失敗しました: ' + result.message);
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('エラーが発生しました');
    }
}

// Add greeting
function addGreeting() {
    const greetingsList = document.getElementById('greetings-list');
    if (!greetingsList) return;
    
    const index = greetingsList.children.length;
    const greetingItem = document.createElement('div');
    greetingItem.className = 'greeting-item';
    greetingItem.dataset.order = index;
    greetingItem.innerHTML = `
        <div class="greeting-header">
            <span class="greeting-number">${index + 1}</span>
            <div class="greeting-actions">
                <button type="button" class="btn-move-up" onclick="moveGreeting(${index}, 'up')" ${index === 0 ? 'disabled' : ''}>↑</button>
                <button type="button" class="btn-move-down" onclick="moveGreeting(${index}, 'down')">↓</button>
            </div>
            <button type="button" class="btn-delete" onclick="this.closest('.greeting-item').remove(); updateGreetingNumbers(); updateGreetingButtons();">削除</button>
        </div>
        <div class="form-group">
            <label>タイトル</label>
            <input type="text" class="form-control greeting-title" placeholder="タイトル">
        </div>
        <div class="form-group">
            <label>本文</label>
            <textarea class="form-control greeting-content" rows="4" placeholder="本文"></textarea>
        </div>
    `;
    greetingsList.appendChild(greetingItem);
    updateGreetingButtons();
}

// Save greetings
async function saveGreetings() {
    const greetingItems = document.querySelectorAll('#greetings-list .greeting-item');
    const greetings = [];
    
    greetingItems.forEach((item, index) => {
        const title = item.querySelector('.greeting-title').value.trim();
        const content = item.querySelector('.greeting-content').value.trim();
        
        if (title || content) {
            greetings.push({
                title: title,
                content: content,
                display_order: index
            });
        }
    });
    
    try {
        const response = await fetch('../backend/api/business-card/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ greetings: greetings }),
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('保存しました');
            loadBusinessCardData(); // Reload data
        } else {
            alert('保存に失敗しました: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
}

// Delete greeting
function deleteGreeting(id) {
    if (!confirm('この挨拶文を削除しますか？')) return;
    
    // Remove from DOM
    const item = document.querySelector(`.greeting-item[data-id="${id}"]`);
    if (item) {
        item.remove();
    }
    
    // Save changes
    saveGreetings();
}

// Toggle tech tool
async function toggleTechTool(toolTypeOrId, isActive) {
    console.log('Toggle tech tool:', toolTypeOrId, isActive);
    
    // Save immediately
    await saveTechTools();
}

// Save tech tools
async function saveTechTools() {
    const techToolsList = document.getElementById('tech-tools-list');
    if (!techToolsList) {
        console.error('Tech tools list not found');
        return;
    }
    
    const toolItems = techToolsList.querySelectorAll('.tech-tool-item');
    const selectedToolTypes = [];
    
    toolItems.forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            selectedToolTypes.push(item.dataset.toolType);
        }
    });
    
    if (selectedToolTypes.length < 2) {
        alert('最低2つ以上のテックツールを選択してください');
        return;
    }
    
    try {
        // Step 1: Generate URLs for selected tools
        const urlResponse = await fetch('../backend/api/tech-tools/generate-urls.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ selected_tools: selectedToolTypes }),
            credentials: 'include'
        });
        
        const urlResult = await urlResponse.json();
        if (!urlResult.success) {
            alert('URL生成に失敗しました: ' + urlResult.message);
            return;
        }
        
        // Step 2: Format tech tools for database
        const techTools = urlResult.data.tech_tools.map((tool, index) => ({
            tool_type: tool.tool_type,
            tool_url: tool.tool_url,
            display_order: index,
            is_active: 1
        }));
        
        console.log('Saving tech tools:', techTools);
        
        // Step 3: Save to database
        const saveResponse = await fetch('../backend/api/business-card/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ tech_tools: techTools }),
            credentials: 'include'
        });
        
        const saveResult = await saveResponse.json();
        
        if (saveResult.success) {
            alert('保存しました');
            loadBusinessCardData(); // Reload data
        } else {
            alert('保存に失敗しました: ' + saveResult.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
}

// Toggle communication method
function toggleCommunicationMethod(id, isActive) {
    const item = document.querySelector(`.communication-item[data-id="${id}"]`);
    if (item) {
        const details = item.querySelector('.comm-details');
        details.style.display = isActive ? 'block' : 'none';
    }
}

// Delete communication method
async function deleteCommunicationMethod(id) {
    if (!confirm('このコミュニケーション方法を削除しますか？')) return;
    
    // Remove from DOM
    const item = document.querySelector(`.communication-item[data-id="${id}"]`);
    if (item) {
        item.remove();
    }
    
    // Save changes
    await saveCommunicationMethods();
}

// Add communication method
function addCommunicationMethod() {
    const commList = document.getElementById('communication-list');
    if (!commList) return;
    
    const methodTypes = [
        { type: 'line', name: 'LINE' },
        { type: 'messenger', name: 'Messenger' },
        { type: 'whatsapp', name: 'WhatsApp' },
        { type: 'plus_message', name: '+メッセージ' },
        { type: 'chatwork', name: 'Chatwork' },
        { type: 'andpad', name: 'Andpad' },
        { type: 'instagram', name: 'Instagram' },
        { type: 'facebook', name: 'Facebook' },
        { type: 'twitter', name: 'X (Twitter)' },
        { type: 'youtube', name: 'YouTube' },
        { type: 'tiktok', name: 'TikTok' },
        { type: 'note', name: 'note' },
        { type: 'pinterest', name: 'Pinterest' },
        { type: 'threads', name: 'Threads' }
    ];
    
    // Icon mapping (same as displayCommunicationMethods)
    const methodIcons = {
        'line': '<img src="./assets/images/icons/line.png" alt="LINE" class="comm-icon-img">',
        'messenger': '<img src="./assets/images/icons/messenger.png" alt="Messenger" class="comm-icon-img">',
        'whatsapp': '<img src="./assets/images/icons/whatsapp.png" alt="WhatsApp" class="comm-icon-img">',
        'plus_message': '<img src="./assets/images/icons/message.png" alt="+メッセージ" class="comm-icon-img">',
        'chatwork': '<img src="./assets/images/icons/chatwork.png" alt="Chatwork" class="comm-icon-img">',
        'andpad': '<img src="./assets/images/icons/andpad.png" alt="Andpad" class="comm-icon-img">',
        'instagram': '<img src="./assets/images/icons/instagram.png" alt="Instagram" class="comm-icon-img">',
        'facebook': '<img src="./assets/images/icons/facebook.png" alt="Facebook" class="comm-icon-img">',
        'twitter': '<img src="./assets/images/icons/twitter.png" alt="X (Twitter)" class="comm-icon-img">',
        'youtube': '<img src="./assets/images/icons/youtube.png" alt="YouTube" class="comm-icon-img">',
        'tiktok': '<img src="./assets/images/icons/tiktok.png" alt="TikTok" class="comm-icon-img">',
        'note': '<img src="./assets/images/icons/note.png" alt="note" class="comm-icon-img">',
        'pinterest': '<img src="./assets/images/icons/pinterest.png" alt="Pinterest" class="comm-icon-img">',
        'threads': '<img src="./assets/images/icons/threads.png" alt="Threads" class="comm-icon-img">'
    };
    
    // Get already added method types
    const existingItems = commList.querySelectorAll('.communication-item');
    const existingTypes = Array.from(existingItems).map(item => item.dataset.methodType);
    
    // Find the next method type that hasn't been added yet
    let nextMethod = null;
    for (const method of methodTypes) {
        if (!existingTypes.includes(method.type)) {
            nextMethod = method;
            break;
        }
    }
    
    // If all methods are already added, show message
    if (!nextMethod) {
        alert('すべてのコミュニケーション方法が追加済みです');
        return;
    }
    
    // Determine if URL-based
    const isUrlBased = ['instagram', 'facebook', 'twitter', 'youtube', 'tiktok', 'note', 'pinterest', 'threads'].includes(nextMethod.type);
    const inputType = isUrlBased ? 'url' : 'text';
    const placeholder = isUrlBased ? `https://${nextMethod.type === 'twitter' ? 'x.com' : nextMethod.type === 'note' ? 'note.com' : nextMethod.type === 'threads' ? 'threads.net' : nextMethod.type + '.com'}/...` : `${nextMethod.name} IDまたはURL`;
    
    // Get icon for this method
    const icon = methodIcons[nextMethod.type] || '<img src="./assets/images/icons/message.png" alt="+メッセージ" class="comm-icon-img">';
    
    // Create communication item
    const commItem = document.createElement('div');
    commItem.className = 'communication-item';
    commItem.dataset.methodType = nextMethod.type;
    commItem.innerHTML = `
        <label class="communication-checkbox">
            <input type="checkbox" checked onchange="toggleCommunicationMethod(null, this.checked)">
            <div class="comm-icon">${icon}</div>
            <span>${nextMethod.name}</span>
        </label>
        <div class="comm-details" style="display: block;">
            <input type="${inputType}" class="form-control comm-value" placeholder="${placeholder}" ${isUrlBased ? 'pattern="https?://.+"' : ''}>
            ${isUrlBased ? '<small style="color: #666; display: block; margin-top: 4px;">有効なURLを入力してください（https://で始まる必要があります）</small>' : ''}
        </div>
        <button type="button" class="btn-delete" onclick="this.closest('.communication-item').remove()">削除</button>
    `;
    commList.appendChild(commItem);
}

// Validate URL
function isValidUrl(url) {
    if (!url) return false;
    try {
        const urlObj = new URL(url);
        return urlObj.protocol === 'http:' || urlObj.protocol === 'https:';
    } catch (e) {
        return false;
    }
}

// Save communication methods
async function saveCommunicationMethods() {
    const commItems = document.querySelectorAll('#communication-list .communication-item');
    const methods = [];
    const errors = [];
    
    commItems.forEach((item, index) => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            const methodType = item.dataset.methodType;
            const valueInput = item.querySelector('.comm-value');
            const value = valueInput ? valueInput.value.trim() : '';
            
            const isUrlBased = ['instagram', 'facebook', 'twitter', 'youtube', 'tiktok', 'note', 'pinterest', 'threads'].includes(methodType);
            
            // Validation for URL-based methods
            if (isUrlBased && value) {
                if (!isValidUrl(value)) {
                    const methodNames = {
                        'instagram': 'Instagram',
                        'facebook': 'Facebook',
                        'twitter': 'X (Twitter)',
                        'youtube': 'YouTube',
                        'tiktok': 'TikTok',
                        'note': 'note',
                        'pinterest': 'Pinterest',
                        'threads': 'Threads'
                    };
                    errors.push(`${methodNames[methodType] || methodType}のURLが無効です。https://で始まる有効なURLを入力してください。`);
                    // Highlight the invalid input
                    valueInput.style.borderColor = '#dc3545';
                    valueInput.addEventListener('input', function() {
                        if (isValidUrl(this.value.trim())) {
                            this.style.borderColor = '';
                        }
                    });
                    return; // Skip this item if validation fails
                } else {
                    // Reset border color if valid
                    valueInput.style.borderColor = '';
                }
            }
            
            // Validation for non-URL methods (should have a value if checked)
            if (!isUrlBased && !value) {
                const methodNames = {
                    'line': 'LINE',
                    'messenger': 'Messenger',
                    'whatsapp': 'WhatsApp',
                    'plus_message': '+メッセージ',
                    'chatwork': 'Chatwork',
                    'andpad': 'Andpad'
                };
                errors.push(`${methodNames[methodType] || methodType}のIDまたはURLを入力してください。`);
                valueInput.style.borderColor = '#dc3545';
                valueInput.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.style.borderColor = '';
                    }
                });
                return; // Skip this item if validation fails
            } else if (!isUrlBased && value) {
                valueInput.style.borderColor = '';
            }
            
            methods.push({
                method_type: methodType,
                method_name: methodType,
                method_url: isUrlBased ? value : '',
                method_id: isUrlBased ? '' : value,
                is_active: 1,
                display_order: index
            });
        }
    });
    
    // Show validation errors if any
    if (errors.length > 0) {
        alert('入力内容に誤りがあります:\n' + errors.join('\n'));
        return;
    }
    
    // If no methods selected, show warning
    if (methods.length === 0) {
        alert('少なくとも1つのコミュニケーション方法を選択してください');
        return;
    }
    
    try {
        const response = await fetch('../backend/api/business-card/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ communication_methods: methods }),
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('保存しました');
            loadBusinessCardData(); // Reload data
        } else {
            alert('保存に失敗しました: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('エラーが発生しました');
    }
}

// Update preview
function updatePreview(data) {
    const previewContent = document.getElementById('preview-content');
    if (!previewContent) {
        console.error('Preview content element not found');
        return;
    }
    
    console.log('Updating preview with data:', data);
    
    // Simple preview HTML generation
    let html = '<div class="preview-card" style="padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fff;">';
    
    // Header
    html += '<div style="text-align: center; margin-bottom: 20px;">';
    if (data.company_logo) {
        const logoPath = data.company_logo.startsWith('http') ? data.company_logo : '../' + data.company_logo;
        html += `<div style="margin-bottom: 10px;"><img src="${logoPath}" alt="ロゴ" style="max-width: 150px; max-height: 150px;"></div>`;
    }
    if (data.company_name) {
        html += `<h1 style="font-size: 1.5rem; margin: 10px 0;">${escapeHtml(data.company_name)}</h1>`;
    }
    html += '</div>';
    
    // Profile
    html += '<div style="display: flex; gap: 20px; margin-bottom: 20px;">';
    if (data.profile_photo) {
        const photoPath = data.profile_photo.startsWith('http') ? data.profile_photo : '../' + data.profile_photo;
        html += `<div><img src="${photoPath}" alt="プロフィール写真" style="max-width: 100px; max-height: 100px; border-radius: 50%;"></div>`;
    }
    html += '<div>';
    if (data.name) {
        html += `<h2 style="font-size: 1.2rem; margin: 0 0 10px 0;">${escapeHtml(data.name)}</h2>`;
    }
    if (data.position) {
        html += `<p style="margin: 5px 0; color: #666;">${escapeHtml(data.position)}</p>`;
    }
    if (data.branch_department) {
        html += `<p style="margin: 5px 0; color: #666;">${escapeHtml(data.branch_department)}</p>`;
    }
    html += '</div>';
    html += '</div>';
    
    // Additional info
    if (data.company_address || data.company_phone || data.mobile_phone) {
        html += '<div style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">';
        if (data.company_address) {
            html += `<p style="margin: 5px 0;"><strong>住所:</strong> ${escapeHtml(data.company_address)}</p>`;
        }
        if (data.company_phone) {
            html += `<p style="margin: 5px 0;"><strong>電話:</strong> ${escapeHtml(data.company_phone)}</p>`;
        }
        if (data.mobile_phone) {
            html += `<p style="margin: 5px 0;"><strong>携帯:</strong> ${escapeHtml(data.mobile_phone)}</p>`;
        }
        html += '</div>';
    }
    
    html += '</div>';
    
    previewContent.innerHTML = html;
    console.log('Preview updated');
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

