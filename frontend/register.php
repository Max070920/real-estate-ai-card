<?php
/**
 * Registration Page (Multi-step Form)
 * Note: Step 1 (Account Registration) is now in new_register.php
 */
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/includes/functions.php';

startSessionIfNotStarted();

// 認証チェック - 未登録の場合はモーダルを表示
$isLoggedIn = !empty($_SESSION['user_id']);

$userType = $_GET['type'] ?? 'new'; // new, existing, free

// Default greeting messages
$defaultGreetings = [
    [
        'title' => '笑顔が増える「住み替え」を叶えます',
        'content' => '初めての売買で感じる不安や疑問。「あなたに頼んでよかった」と言っていただけるよう、理想の住まい探しと売却を全力で伴走いたします。私は、お客様が描く「10年後の幸せな日常」を第一に考えます。'
    ],
    [
        'title' => '自宅は大きな貯金箱',
        'content' => '「不動産売買は人生最大の投資」という視点に立ち、物件のメリットだけでなく、将来のリスクやデメリットも隠さずお伝えするのが信条です。感情に流されない、確実な資産形成と納得のいく取引をサポートします。'
    ],
    [
        'title' => 'お客様に「情報武装」をご提案',
        'content' => '「この価格は妥当なのだろうか？」「もっとよい物件情報は無いのだろうか？」私は全ての情報をお客様に開示いたしますが、お客様に「情報武装」していただく事で、それをさらに担保いたします。他のエージェントにはない、私独自のサービスをご活用ください。'
    ],
    [
        'title' => 'お客様を「3つの疲労」から解放いたします',
        'content' => '一つ目は、ポータルサイト巡りの「情報収集疲労」。二つ目は、不動産会社への「問い合わせ疲労」、専門知識不足による「判断疲労」です。私がご提供するテックツールで、情報収集は自動化、私が全ての情報を公開しますので多くの不動産会社に問い合わせることも不要、物件情報にAI評価がついているので客観的判断も自動化されます。'
    ],
    [
        'title' => '忙しい子育て世代へ。手間を省くスマート売買',
        'content' => '「売り」と「買い」を同時に進める住み替えは手続きが煩雑になりがちです。忙しいご夫婦に代わり、書類作成から金融機関との折衝、内覧の調整まで私が窓口となってスムーズに進めます。お子様連れでの内覧や打ち合わせも大歓迎です。ご家族の貴重な時間を奪わないよう、迅速かつ丁寧な段取りをお約束します。'
    ]
];

// Japanese prefectures
$prefectures = [
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
    '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
    '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
    '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
    '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
    '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
    '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アカウント作成 - 不動産AI名刺</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <a href="index.php" class="logo-link">
                <img src="assets/images/logo.png" alt="不動産AI名刺">
            </a>
        </div>

        <div class="register-content" <?php if (!$isLoggedIn): ?>style="display: none;"<?php endif; ?>>
            <div class="register-steps">
                <div class="step-indicator">
                    <div class="step active" data-step="1">
                        <div class="step-circle">1</div>
                        <div class="step-label">ヘッダー・挨拶</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-circle">2</div>
                        <div class="step-label">会社プロフィール</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-circle">3</div>
                        <div class="step-label">個人情報</div>
                    </div>
                    <div class="step" data-step="4">
                        <div class="step-circle">4</div>
                        <div class="step-label">テックツール</div>
                    </div>
                    <div class="step" data-step="5">
                        <div class="step-circle">5</div>
                        <div class="step-label">コミュニケーション</div>
                    </div>
                    <div class="step" data-step="6">
                        <div class="step-circle">6</div>
                        <div class="step-label">決済</div>
                    </div>
                </div>
                <button type="button" id="preview-btn" class="btn-preview">プレビュー</button>
            </div>

            <!-- Preview Container -->
            <div id="preview-container" class="preview-container" style="display: none;">
                <div class="preview-header">
                    <button type="button" id="close-preview-btn" class="btn-close-preview">編集に戻る</button>
                </div>
                <div id="preview-content" class="preview-content"></div>
            </div>

            <!-- Step 1: Header & Greeting -->
            <div id="step-1" class="register-step active">
                <h1>ヘッダー・挨拶部</h1>
                <p class="step-description">会社情報とご挨拶文を入力してください</p>

                <form id="header-greeting-form" class="register-form">
                    <div class="form-group">
                        <label>会社名 <span class="required">*</span></label>
                        <input type="text" name="company_name" class="form-control" required>
                    </div>

                    <div class="form-section">
                        <h3>ロゴマーク</h3>
                        <div class="upload-area" id="logo-upload">
                            <input type="file" id="company_logo" name="company_logo" accept="image/*" style="display: none;">
                            <div class="upload-preview"></div>
                            <button type="button" class="btn-outline" onclick="document.getElementById('company_logo').click()">
                                ロゴをアップロード
                            </button>
                            <small>自動でリサイズされます</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>顔写真</h3>
                        <div class="upload-area" id="photo-upload-header">
                            <input type="file" id="profile_photo_header" name="profile_photo" accept="image/*" style="display: none;">
                            <div class="upload-preview"></div>
                            <button type="button" class="btn-outline" onclick="document.getElementById('profile_photo_header').click()">
                                写真をアップロード
                            </button>
                            <small>自動でリサイズされます</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>ご挨拶 <span class="required">*</span></h3>
                        <p class="section-note">挨拶文の順序を上下のボタンで変更できます。デフォルトの文章もそのまま使用できます。</p>
                        <div id="greetings-container">
                            <?php foreach ($defaultGreetings as $index => $greeting): ?>
                            <div class="greeting-item" data-order="<?php echo $index; ?>">
                                <div class="greeting-header">
                                    <span class="greeting-number"><?php echo $index + 1; ?></span>
                                    <div class="greeting-actions">
                                        <button type="button" class="btn-move-up" onclick="moveGreeting(<?php echo $index; ?>, 'up')" <?php echo $index === 0 ? 'disabled' : ''; ?>>↑</button>
                                        <button type="button" class="btn-move-down" onclick="moveGreeting(<?php echo $index; ?>, 'down')" <?php echo $index === 4 ? 'disabled' : ''; ?>>↓</button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>タイトル</label>
                                    <input type="text" name="greeting_title[]" class="form-control" value="<?php echo htmlspecialchars($greeting['title']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>本文</label>
                                    <textarea name="greeting_content[]" class="form-control" rows="4" required><?php echo htmlspecialchars($greeting['content']); ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">次へ</button>
                    </div>
                </form>
            </div>

            <!-- Step 2: Company Profile -->
            <div id="step-2" class="register-step">
                <h1>会社プロフィール部</h1>
                <p class="step-description">会社情報を入力してください</p>

                <form id="company-profile-form" class="register-form">
                    <div class="form-section">
                        <h3>宅建業者番号</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>都道府県</label>
                                <select name="real_estate_license_prefecture" id="license_prefecture" class="form-control">
                                    <option value="">選択してください</option>
                                    <?php foreach ($prefectures as $pref): ?>
                                    <option value="<?php echo htmlspecialchars($pref); ?>"><?php echo htmlspecialchars($pref); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>更新番号</label>
                                <select name="real_estate_license_renewal_number" id="license_renewal" class="form-control">
                                    <option value="">選択してください</option>
                                    <?php for ($i = 1; $i <= 20; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>登録番号</label>
                                <input type="text" name="real_estate_license_registration_number" id="license_registration" class="form-control" placeholder="例：12345">
                                <button type="button" class="btn-outline" id="lookup-license" style="margin-top: 0.5rem;">住所を自動入力</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>会社名 <span class="required">*</span></label>
                        <input type="text" name="company_name_profile" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>郵便番号 <span class="required">*</span></label>
                        <input type="text" name="company_postal_code" id="company_postal_code" class="form-control" placeholder="例：123-4567" required>
                        <button type="button" class="btn-outline" id="lookup-address" style="margin-top: 0.5rem;">住所を自動入力</button>
                    </div>

                    <div class="form-group">
                        <label>住所 <span class="required">*</span></label>
                        <input type="text" name="company_address" id="company_address" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>会社電話番号</label>
                        <input type="tel" name="company_phone" class="form-control" placeholder="例：03-1234-5678">
                    </div>

                    <div class="form-group">
                        <label>会社HP URL</label>
                        <input type="url" name="company_website" class="form-control" placeholder="https://example.com">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="goToStep(1)">戻る</button>
                        <button type="submit" class="btn-primary">次へ</button>
                    </div>
                </form>
            </div>

            <!-- Step 3: Personal Information -->
            <div id="step-3" class="register-step">
                <h1>個人情報</h1>
                <p class="step-description">あなたの個人情報を入力してください</p>

                <form id="personal-info-form" class="register-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>部署</label>
                            <input type="text" name="branch_department" class="form-control" value="営業部">
                        </div>
                        <div class="form-group">
                            <label>役職</label>
                            <input type="text" name="position" class="form-control" value="営業課長">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>姓 <span class="required">*</span></label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required placeholder="例：山田">
                        </div>
                        <div class="form-group">
                            <label>名 <span class="required">*</span></label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required placeholder="例：太郎">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>ローマ字姓</label>
                            <input type="text" name="last_name_romaji" id="last_name_romaji" class="form-control" placeholder="例：Yamada">
                        </div>
                        <div class="form-group">
                            <label>ローマ字名</label>
                            <input type="text" name="first_name_romaji" id="first_name_romaji" class="form-control" placeholder="例：Taro">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>電話番号 <span class="required">*</span></label>
                        <input type="tel" name="mobile_phone" class="form-control" required value="090-1234-5678">
                    </div>

                    <div class="form-group">
                        <label>生年月日</label>
                        <input type="date" name="birth_date" class="form-control">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>現在の居住地</label>
                            <input type="text" name="current_residence" class="form-control" placeholder="例：東京都渋谷区">
                        </div>
                        <div class="form-group">
                            <label>出身地</label>
                            <input type="text" name="hometown" class="form-control" placeholder="例：大阪府大阪市">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>出身校</label>
                        <input type="text" name="alma_mater" class="form-control" placeholder="例：○○大学 経済学部">
                    </div>

                    <div class="form-section">
                        <h3>資格</h3>
                        <div class="qualifications-section">
                            <div class="form-group">
                                <label>主な資格（選択）</label>
                                <div class="checkbox-list">
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="qualification_takken" value="1">
                                        <span>宅地建物取引士</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="qualification_kenchikushi" value="1">
                                        <span>建築士</span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>その他の資格（自由入力）</label>
                                <textarea name="qualifications_other" class="form-control" rows="2" placeholder="その他の資格を入力してください"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>趣味</label>
                        <textarea name="hobbies" class="form-control" rows="2" placeholder="趣味や興味があることを入力してください"></textarea>
                    </div>

                    <div class="form-section">
                        <h3>フリー入力欄</h3>
                        <p class="section-note">自由にアピールポイントや追加情報を入力できます。YouTubeのリンクなども貼り付けられます。</p>
                        <div class="form-group">
                            <label>テキスト</label>
                            <textarea name="free_input_text" class="form-control" rows="4" placeholder="自由に入力してください。&#10;例：YouTubeリンク: https://www.youtube.com/watch?v=xxxxx"></textarea>
                        </div>
                        <div class="form-group">
                            <label>画像・バナー（リンク付き画像）</label>
                            <div class="upload-area" id="free-image-upload">
                                <input type="file" id="free_image" name="free_image" accept="image/*" style="display: none;">
                                <div class="upload-preview"></div>
                                <button type="button" class="btn-outline" onclick="document.getElementById('free_image').click()">
                                    画像をアップロード
                                </button>
                            </div>
                            <div class="form-group" style="margin-top: 0.5rem;">
                                <label>画像のリンク先URL（任意）</label>
                                <input type="url" name="free_image_link" class="form-control" placeholder="https://example.com">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="goToStep(2)">戻る</button>
                        <button type="submit" class="btn-primary">次へ</button>
                    </div>
                </form>
            </div>

            <!-- Step 4: Tech Tools -->
            <div id="step-4" class="register-step">
                <h1>テックツール選択</h1>
                <p class="step-description">表示させるテックツールを選択してください（最低2つ以上）</p>

                <form id="tech-tools-form" class="register-form">
                    <div class="tech-tools-grid">
                        <div class="tech-tool-card">
                            <input type="checkbox" id="tool-mdb" name="tech_tools[]" value="mdb">
                            <label for="tool-mdb">
                                <div class="tool-icon">🏢</div>
                                <h4>全国マンションデータベース</h4>
                                <p>全国の分譲マンションの95％以上を網羅</p>
                            </label>
                        </div>

                        <div class="tech-tool-card">
                            <input type="checkbox" id="tool-rlp" name="tech_tools[]" value="rlp">
                            <label for="tool-rlp">
                                <div class="tool-icon">🤖</div>
                                <h4>物件提案ロボ</h4>
                                <p>希望条件に合致した物件情報を自動配信</p>
                            </label>
                        </div>

                        <div class="tech-tool-card">
                            <input type="checkbox" id="tool-llp" name="tech_tools[]" value="llp">
                            <label for="tool-llp">
                                <div class="tool-icon">🏞️</div>
                                <h4>土地情報ロボ</h4>
                                <p>希望条件に合致した土地情報を自動配信</p>
                            </label>
                        </div>

                        <div class="tech-tool-card">
                            <input type="checkbox" id="tool-ai" name="tech_tools[]" value="ai">
                            <label for="tool-ai">
                                <div class="tool-icon">📊</div>
                                <h4>AIマンション査定</h4>
                                <p>個人情報不要でマンションの査定を実施</p>
                            </label>
                        </div>

                        <div class="tech-tool-card">
                            <input type="checkbox" id="tool-slp" name="tech_tools[]" value="slp">
                            <label for="tool-slp">
                                <div class="tool-icon">🔍</div>
                                <h4>セルフィン</h4>
                                <p>物件の良し悪しを自動判定するツール</p>
                            </label>
                        </div>

                        <div class="tech-tool-card">
                            <input type="checkbox" id="tool-olp" name="tech_tools[]" value="olp">
                            <label for="tool-olp">
                                <div class="tool-icon">💼</div>
                                <h4>オーナーコネクト</h4>
                                <p>マンション所有者向けの資産ウォッチツール</p>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="goToStep(3)">戻る</button>
                        <button type="submit" class="btn-primary">次へ</button>
                    </div>
                </form>
            </div>

            <!-- Step 5: Communication Functions -->
            <div id="step-5" class="register-step">
                <h1>コミュニケーション機能部</h1>
                <p class="step-description">メッセージアプリやSNSの連携を設定してください</p>

                <form id="communication-form" class="register-form">
                    <div class="form-section">
                        <h3>メッセージアプリ部</h3>
                        <p class="section-note">一番簡単につながる方法を教えてください。ここが重要になります。</p>
                        
                        <div class="communication-grid">
                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_line" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/line.png" alt="LINE">
                                    </div>
                                    <span>LINE</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="text" name="comm_line_id" class="form-control" placeholder="LINE IDまたはURL">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_messenger" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/messenger.png" alt="Messenger">
                                    </div>
                                    <span>Messenger</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="text" name="comm_messenger_id" class="form-control" placeholder="Messenger IDまたはURL">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_whatsapp" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/whatsapp.png" alt="WhatsApp">
                                    </div>
                                    <span>WhatsApp</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="text" name="comm_whatsapp_id" class="form-control" placeholder="WhatsApp IDまたはURL">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_plus_message" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/message.png" alt="+メッセージ">
                                    </div>
                                    <span>+メッセージ</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="text" name="comm_plus_message_id" class="form-control" placeholder="+メッセージ IDまたはURL">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_chatwork" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/chatwork.png" alt="Chatwork">
                                    </div>
                                    <span>Chatwork</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="text" name="comm_chatwork_id" class="form-control" placeholder="Chatwork IDまたはURL">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_andpad" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/andpad.png" alt="Andpad">
                                    </div>
                                    <span>Andpad</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="text" name="comm_andpad_id" class="form-control" placeholder="Andpad IDまたはURL">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>SNS部</h3>
                        <p class="section-note">SNSのリンク先を入力できます。</p>
                        
                        <div class="communication-grid">
                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_instagram" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/instagram.png" alt="Instagram">
                                    </div>
                                    <span>Instagram</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="url" name="comm_instagram_url" class="form-control" placeholder="https://instagram.com/...">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_facebook" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/facebook.png" alt="Facebook">
                                    </div>
                                    <span>Facebook</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="url" name="comm_facebook_url" class="form-control" placeholder="https://facebook.com/...">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_twitter" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/twitter.png" alt="X (Twitter)">
                                    </div>
                                    <span>X (Twitter)</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="url" name="comm_twitter_url" class="form-control" placeholder="https://x.com/...">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_youtube" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/youtube.png" alt="YouTube">
                                    </div>
                                    <span>YouTube</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="url" name="comm_youtube_url" class="form-control" placeholder="https://youtube.com/...">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_tiktok" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/tiktok.png" alt="TikTok">
                                    </div>
                                    <span>TikTok</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="url" name="comm_tiktok_url" class="form-control" placeholder="https://tiktok.com/...">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_note" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/note.png" alt="note">
                                    </div>
                                    <span>note</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="url" name="comm_note_url" class="form-control" placeholder="https://note.com/...">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_pinterest" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/pinterest.png" alt="Pinterest">
                                    </div>
                                    <span>Pinterest</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="url" name="comm_pinterest_url" class="form-control" placeholder="https://pinterest.com/...">
                                </div>
                            </div>

                            <div class="communication-item">
                                <label class="communication-checkbox">
                                    <input type="checkbox" name="comm_threads" value="1">
                                    <div class="comm-icon">
                                        <img src="assets/images/icons/threads.png" alt="Threads">
                                    </div>
                                    <span>Threads</span>
                                </label>
                                <div class="comm-details" style="display: none;">
                                    <input type="url" name="comm_threads_url" class="form-control" placeholder="https://threads.net/...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="goToStep(4)">戻る</button>
                        <button type="submit" class="btn-primary">次へ</button>
                    </div>
                </form>
            </div>

            <!-- Step 6: Preview & Payment -->
            <div id="step-6" class="register-step">
                <h1>決済</h1>
                <!-- <p class="step-description">入力内容を確認してください</p> -->

                <!-- <div id="preview-area" class="preview-area"> -->
                    <!-- Preview will be loaded here -->
                <!-- </div> -->

                <div class="payment-section">
                    <h3>お支払方法</h3>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="credit_card" checked>
                            <span>クレジットカード決済</span>
                        </label>
                        <?php if ($userType !== 'free'): ?>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer">
                            <span>お振込み</span>
                        </label>
                        <?php endif; ?>
                    </div>

                    <div class="payment-amount">
                        <?php if ($userType === 'new'): ?>
                        <p>初期費用: ¥30,000（税別）</p>
                        <p>月額費用: ¥500（税別）</p>
                        <?php elseif ($userType === 'existing'): ?>
                        <p>初期費用: ¥20,000（税別）</p>
                        <?php else: ?>
                        <p>無料</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="goToStep(5)">戻る</button>
                    <button type="button" id="submit-payment" class="btn-primary">この内容で進める</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Required Modal -->
    <?php if (!$isLoggedIn): ?>
    <div id="registration-modal" class="registration-modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-body">
                <p>まずはご登録ください。</p>
            </div>
            <div class="modal-footer">
                <button type="button" id="modal-confirm-btn" class="btn-primary">確認する</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="assets/js/register.js"></script>
    <script>
        // Modal functionality
        document.getElementById('modal-confirm-btn')?.addEventListener('click', function() {
            window.location.href = 'login.php';
        });
        
        // 漢字からローマ字への自動変換機能
        // 簡易版：よく使われる名前の変換テーブルを使用
        document.addEventListener('DOMContentLoaded', function() {
            const lastNameInput = document.getElementById('last_name');
            const firstNameInput = document.getElementById('first_name');
            const lastNameRomajiInput = document.getElementById('last_name_romaji');
            const firstNameRomajiInput = document.getElementById('first_name_romaji');
            
            // 簡易的な変換テーブル（よく使われる名前の例）
            const nameConversionMap = {
                '山田': 'Yamada', '田中': 'Tanaka', '佐藤': 'Sato', '鈴木': 'Suzuki',
                '高橋': 'Takahashi', '伊藤': 'Ito', '渡辺': 'Watanabe', '中村': 'Nakamura',
                '小林': 'Kobayashi', '加藤': 'Kato', '吉田': 'Yoshida', '山本': 'Yamamoto',
                '松本': 'Matsumoto', '井上': 'Inoue', '木村': 'Kimura', '林': 'Hayashi',
                '斎藤': 'Saito', '清水': 'Shimizu', '山崎': 'Yamazaki', '中島': 'Nakajima',
                '前田': 'Maeda', '藤田': 'Fujita', '後藤': 'Goto', '近藤': 'Kondo',
                '太郎': 'Taro', '次郎': 'Jiro', '三郎': 'Saburo', '花子': 'Hanako',
                '一郎': 'Ichiro', '二郎': 'Jiro', '三郎': 'Saburo', '美咲': 'Misaki',
                'さくら': 'Sakura', 'あかり': 'Akari', 'ひなた': 'Hinata', 'みお': 'Mio'
            };
            
            // 漢字からローマ字への簡易変換関数
            function convertToRomaji(japanese) {
                if (!japanese) return '';
                
                // 変換テーブルに存在する場合はそれを使用
                if (nameConversionMap[japanese]) {
                    return nameConversionMap[japanese];
                }
                
                // ひらがな・カタカナの場合はそのまま返す（後で変換可能）
                // 漢字の場合は空文字を返す（ユーザーが手動で入力する必要がある）
                return '';
            }
            
            // 姓の入力時にローマ字姓を自動入力
            if (lastNameInput && lastNameRomajiInput) {
                let lastNameTimeout;
                lastNameInput.addEventListener('input', function() {
                    clearTimeout(lastNameTimeout);
                    const value = this.value.trim();
                    
                    // ローマ字姓が空の場合のみ自動入力
                    if (!lastNameRomajiInput.value.trim() && value) {
                        lastNameTimeout = setTimeout(function() {
                            const romaji = convertToRomaji(value);
                            if (romaji) {
                                lastNameRomajiInput.value = romaji;
                            }
                        }, 500); // 500ms後に変換を試みる
                    }
                });
            }
            
            // 名の入力時にローマ字名を自動入力
            if (firstNameInput && firstNameRomajiInput) {
                let firstNameTimeout;
                firstNameInput.addEventListener('input', function() {
                    clearTimeout(firstNameTimeout);
                    const value = this.value.trim();
                    
                    // ローマ字名が空の場合のみ自動入力
                    if (!firstNameRomajiInput.value.trim() && value) {
                        firstNameTimeout = setTimeout(function() {
                            const romaji = convertToRomaji(value);
                            if (romaji) {
                                firstNameRomajiInput.value = romaji;
                            }
                        }, 500); // 500ms後に変換を試みる
                    }
                });
            }
        });
    </script>
    <style>
        .registration-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }
        .registration-modal .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 0;
            border: none;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .registration-modal .modal-body {
            padding: 2.5rem;
            text-align: center;
            font-size: 1.2rem;
            color: #333;
            line-height: 1.6;
        }
        .registration-modal .modal-footer {
            padding: 0 2.5rem 2.5rem;
            text-align: center;
        }
        .registration-modal .modal-footer .btn-primary {
            min-width: 150px;
            padding: 0.75rem 2rem;
        }
    </style>
</body>
</html>
