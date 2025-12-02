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
    
    // Basic Information Form
    const basicForm = document.getElementById('basic-form');
    if (!basicForm) {
        console.error('Basic form not found!');
        return;
    }
    
    // Company name
    const companyNameInput = basicForm.querySelector('input[name="company_name"]');
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
    
    // Branch department
    const branchDeptInput = basicForm.querySelector('input[name="branch_department"]');
    if (branchDeptInput && data.branch_department) {
        branchDeptInput.value = data.branch_department;
        console.log('Set branch_department:', data.branch_department);
    }
    
    // Position
    const positionInput = basicForm.querySelector('input[name="position"]');
    if (positionInput && data.position) {
        positionInput.value = data.position;
        console.log('Set position:', data.position);
    }
    
    // Greetings
    if (data.greetings && Array.isArray(data.greetings) && data.greetings.length > 0) {
        console.log('Displaying greetings:', data.greetings);
        displayGreetings(data.greetings);
    } else {
        console.log('No greetings to display');
    }
    
    // Tech Tools
    if (data.tech_tools && Array.isArray(data.tech_tools) && data.tech_tools.length > 0) {
        console.log('Displaying tech tools:', data.tech_tools);
        displayTechTools(data.tech_tools);
    } else {
        console.log('No tech tools to display');
    }
    
    // Communication Methods
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
        greetingItem.innerHTML = `
            <div class="greeting-header">
                <span class="greeting-number">${index + 1}</span>
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

// Display tech tools
function displayTechTools(techTools) {
    const techToolsList = document.getElementById('tech-tools-list');
    if (!techToolsList) return;
    
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
    
    techToolsList.innerHTML = '';
    
    techTools.forEach(tool => {
        const toolItem = document.createElement('div');
        toolItem.className = 'tech-tool-item';
        toolItem.dataset.id = tool.id;
        toolItem.dataset.toolType = tool.tool_type;
        toolItem.innerHTML = `
            <label class="tech-tool-checkbox">
                <input type="checkbox" ${tool.is_active ? 'checked' : ''} onchange="toggleTechTool(${tool.id}, this.checked)">
                <div class="tool-icon">${toolIcons[tool.tool_type] || '📋'}</div>
                <span>${toolNames[tool.tool_type] || tool.tool_type}</span>
            </label>
        `;
        techToolsList.appendChild(toolItem);
    });
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
    
    commList.innerHTML = '';
    
    methods.forEach(method => {
        const commItem = document.createElement('div');
        commItem.className = 'communication-item';
        commItem.dataset.id = method.id;
        commItem.dataset.methodType = method.method_type;
        
        const isUrlBased = ['instagram', 'facebook', 'twitter', 'youtube', 'tiktok', 'note', 'pinterest', 'threads'].includes(method.method_type);
        const value = isUrlBased ? (method.method_url || '') : (method.method_id || '');
        const placeholder = isUrlBased ? 'https://example.com' : 'IDまたはURL';
        
        commItem.innerHTML = `
            <label class="communication-checkbox">
                <input type="checkbox" ${method.is_active ? 'checked' : ''} onchange="toggleCommunicationMethod(${method.id}, this.checked)">
                <span>${methodNames[method.method_type] || method.method_type}</span>
            </label>
            <div class="comm-details" style="display: ${method.is_active ? 'block' : 'none'};">
                <input type="${isUrlBased ? 'url' : 'text'}" class="form-control comm-value" value="${escapeHtml(value)}" placeholder="${placeholder}">
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
    formData.append('file_type', fieldName === 'company_logo' ? 'logo' : 'photo');
    
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
                preview.innerHTML = `<img src="${result.data.file_path}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
            }
            
            // Update business card data
            if (businessCardData) {
                businessCardData[fieldName] = result.data.file_path;
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
    
    const greetingItem = document.createElement('div');
    greetingItem.className = 'greeting-item';
    greetingItem.innerHTML = `
        <div class="greeting-header">
            <span class="greeting-number">${greetingsList.children.length + 1}</span>
            <button type="button" class="btn-delete" onclick="this.closest('.greeting-item').remove()">削除</button>
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
async function toggleTechTool(id, isActive) {
    if (!businessCardData || !businessCardData.tech_tools) return;
    
    const tool = businessCardData.tech_tools.find(t => t.id === id);
    if (!tool) return;
    
    tool.is_active = isActive ? 1 : 0;
    
    // Save immediately
    await saveTechTools();
}

// Save tech tools
async function saveTechTools() {
    if (!businessCardData || !businessCardData.tech_tools) return;
    
    const techTools = businessCardData.tech_tools
        .filter(tool => tool.is_active)
        .map((tool, index) => ({
            tool_type: tool.tool_type,
            tool_url: tool.tool_url || '',
            display_order: index,
            is_active: 1
        }));
    
    try {
        const response = await fetch('../backend/api/business-card/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ tech_tools: techTools }),
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadBusinessCardData(); // Reload data
        } else {
            alert('保存に失敗しました: ' + result.message);
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
    
    // Show selection dialog (simplified - just add LINE as default)
    const commItem = document.createElement('div');
    commItem.className = 'communication-item';
    commItem.dataset.methodType = 'line';
    commItem.innerHTML = `
        <label class="communication-checkbox">
            <input type="checkbox" checked onchange="toggleCommunicationMethod(null, this.checked)">
            <span>LINE</span>
        </label>
        <div class="comm-details" style="display: block;">
            <input type="text" class="form-control comm-value" placeholder="LINE IDまたはURL">
        </div>
        <button type="button" class="btn-delete" onclick="this.closest('.communication-item').remove()">削除</button>
    `;
    commList.appendChild(commItem);
}

// Save communication methods
async function saveCommunicationMethods() {
    const commItems = document.querySelectorAll('#communication-list .communication-item');
    const methods = [];
    
    commItems.forEach((item, index) => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            const methodType = item.dataset.methodType;
            const valueInput = item.querySelector('.comm-value');
            const value = valueInput ? valueInput.value.trim() : '';
            
            const isUrlBased = ['instagram', 'facebook', 'twitter', 'youtube', 'tiktok', 'note', 'pinterest', 'threads'].includes(methodType);
            
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

