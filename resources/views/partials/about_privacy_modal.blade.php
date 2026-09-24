<!-- Edu-Bridge Ultra-Premium About & Privacy Modal Component -->
<div id="eduBridgeModal" class="edubridge-modal-backdrop" style="display: none;">
    <div class="edubridge-modal-container">
        <!-- Close Button -->
        <button type="button" class="edubridge-close-btn" onclick="closeEduBridgeModal()" title="إغلاق">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Modal Header & Navigation Tabs -->
        <div class="edubridge-modal-header">
            <div class="edubridge-header-brand">
                <div class="edubridge-logo-gem">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <h2 class="edubridge-app-title">Edu-Bridge</h2>
                    </div>
                    <p class="edubridge-app-subtitle">المنظومة الأكاديمية والطلابية الذكية المتكاملة</p>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="edubridge-tabs-nav">
                <button type="button" class="edubridge-tab-btn active" data-tab="tab-about" onclick="switchEduTab('tab-about')">
                    <i class="fa-solid fa-sparkles"></i>
                    <span>حول المنصة</span>
                </button>
                <button type="button" class="edubridge-tab-btn" data-tab="tab-team" onclick="switchEduTab('tab-team')">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>فريق التطوير</span>
                </button>
                <button type="button" class="edubridge-tab-btn" data-tab="tab-privacy" onclick="switchEduTab('tab-privacy')">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>سياسة الخصوصية</span>
                </button>
                <button type="button" class="edubridge-tab-btn" data-tab="tab-contact" onclick="switchEduTab('tab-contact')">
                    <i class="fa-solid fa-headset"></i>
                    <span>تواصل معنا</span>
                </button>
            </div>
        </div>

        <!-- Modal Body Content -->
        <div class="edubridge-modal-body">

            <!-- TAB 1: ABOUT APP -->
            <div id="tab-about" class="edubridge-tab-pane active">
                <div class="edubridge-hero-card">
                    <div class="edubridge-hero-quote">
                        <i class="fa-solid fa-quote-right edubridge-quote-icon"></i>
                        <p>
                            منصة <strong>Edu-Bridge</strong> هي نظام متكامل ومتقدم لإدارة الشؤون الأكاديمية والطلابية والمعاهد، تهدف لتسهيل التواجد، الخدمات الإلكترونية، ومتابعة المحاضرات والنتائج بكل يسر وسلاسة وأمان.
                        </p>
                    </div>
                </div>

                <div class="edubridge-features-grid">
                    <div class="edubridge-feature-item">
                        <div class="edubridge-feat-icon" style="background: rgba(242, 242, 13, 0.12); color: #eab308;">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <h4>إدارة أكاديمية شاملة</h4>
                            <p>تنظيم المقررات، النتائج الامتحانية، الخطط الدراسية، وتقارير المتابعة الدقيقة لكل طالب.</p>
                        </div>
                    </div>

                    <div class="edubridge-feature-item">
                        <div class="edubridge-feat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div>
                            <h4>حضور ذكي بالـ QR وبصمة الوجه</h4>
                            <p>تسجيل حضور إلكتروني فوري ومؤمّن يمنع التلاعب مع دعم كامل للعمل والمزامنة دون إنترنت.</p>
                        </div>
                    </div>

                    <div class="edubridge-feature-item">
                        <div class="edubridge-feat-icon" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div>
                            <h4>تواصل ومحادثات فورية</h4>
                            <p>غرف محادثة مباشرة ومشفرة بين الطلاب والمدرسين والإدارة لتبادل الملفات والاستفسارات.</p>
                        </div>
                    </div>

                    <div class="edubridge-feature-item">
                        <div class="edubridge-feat-icon" style="background: rgba(168, 85, 247, 0.12); color: #a855f7;">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                        <div>
                            <h4>تجربة موحدة (ويب وموبايل)</h4>
                            <p>تطبيق فلاتر فائق السرعة ولوحة تحكم ويب متجاوبة بالكامل لجميع أطراف العملية التعليمية.</p>
                        </div>
                    </div>
                </div>

                <!-- System Highlights Strip -->
                <div class="edubridge-tech-strip">
                    <span class="edubridge-tech-tag"><i class="fa-solid fa-code"></i> Flutter & Laravel</span>
                    <span class="edubridge-tech-tag"><i class="fa-solid fa-database"></i> MySQL Cloud DB</span>
                    <span class="edubridge-tech-tag"><i class="fa-solid fa-lock"></i> AES Encrypted</span>
                    <span class="edubridge-tech-tag"><i class="fa-solid fa-bell"></i> Live Notifications</span>
                </div>
            </div>

            <!-- TAB 2: DEVELOPMENT TEAM -->
            <div id="tab-team" class="edubridge-tab-pane">
                <div class="edubridge-section-intro">
                    <div class="edubridge-badge-label">
                        <i class="fa-solid fa-laptop-code"></i>
                        كفاءات هندسية متميزة
                    </div>
                    <h3>فريق التطوير والبرمجة</h3>
                    <p>فريق العمل الهندسي المتخصص القائم على تصميم وبناء وتطوير كافة مفاصل نظام Edu-Bridge:</p>
                </div>

                <div class="edubridge-team-grid">
                    <!-- Member 1: مجدولين محمود -->
                    <div class="edubridge-member-card">
                        <div class="edubridge-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <span>م</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>مجدولين محمود</h4>
                        </div>
                    </div>

                    <!-- Member 2: محمود غنّام -->
                    <div class="edubridge-member-card">
                        <div class="edubridge-avatar" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <span>م</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>محمود غنّام</h4>
                        </div>
                    </div>

                    <!-- Member 3: إسراء منوّر -->
                    <div class="edubridge-member-card">
                        <div class="edubridge-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <span>إ</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>إسراء منوّر</h4>
                        </div>
                    </div>

                    <!-- Member 4: شهد زريقي -->
                    <div class="edubridge-member-card">
                        <div class="edubridge-avatar" style="background: linear-gradient(135deg, #ec4899, #db2777);">
                            <span>ش</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>شهد زريقي</h4>
                        </div>
                    </div>

                    <!-- Member 5: هبة عيسى -->
                    <div class="edubridge-member-card">
                        <div class="edubridge-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <span>هـ</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>هبة عيسى</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PRIVACY & DATA SECURITY -->
            <div id="tab-privacy" class="edubridge-tab-pane">
                <div class="edubridge-privacy-hero">
                    <div class="edubridge-privacy-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3>سياسة الخصوصية وأمان البيانات</h3>
                        <p class="edubridge-privacy-lead">
                            نحن نلتزم بحماية بياناتك الشخصية والأكاديمية. يتم استخدام البيانات المجمعة حصرياً لأغراض التحقق من الهوية والأداء الأكاديمي داخل المعهد ومتابعة الخدمات الطلابية.
                        </p>
                    </div>
                </div>

                <div class="edubridge-policy-sections">
                    <div class="edubridge-policy-card">
                        <div class="edubridge-policy-card-header">
                            <i class="fa-solid fa-key" style="color: #f59e0b;"></i>
                            <h4>1. سرية وتشفير الحسابات والمعلومات</h4>
                        </div>
                        <p>
                            تخضع جميع كلمات المرور لجلسات تسجيل الدخول لتقنيات التشفير المتقدم (Bcrypt Hashing). لا يمكن لأي كادر تقني أو إداري الاطلاع على كلمة المرور الأصلية لأي مستخدم.
                        </p>
                    </div>

                    <div class="edubridge-policy-card">
                        <div class="edubridge-policy-card-header">
                            <i class="fa-solid fa-user-graduate" style="color: #10b981;"></i>
                            <h4>2. الخصوصية الطلابية والبيانات الأكاديمية</h4>
                        </div>
                        <p>
                            سجلات الحضور، الدرجات الامتحانية، الإنذارات والتقارير المرفوعة هي بيانات محصورة بالجهات المعنية المحددة حصراً (الطالب صاحب الحساب، ولي أمره، وإدارة المعهد ذات الصلاحية).
                        </p>
                    </div>

                    <div class="edubridge-policy-card">
                        <div class="edubridge-policy-card-header">
                            <i class="fa-solid fa-face-smile" style="color: #3b82f6;"></i>
                            <h4>3. أمان التعرف بالوجه وتقنيات الحضور (Biometrics & QR)</h4>
                        </div>
                        <p>
                            معرفات الأجهزة وبيانات التعرف البصري تُعالج بأعلى معايير الحماية ولا يتم نقلها أو استخدامها لأي غرض خارج نطاق تأكيد التواجد الفعلي في قاعة المحاضرة.
                        </p>
                    </div>

                    <div class="edubridge-policy-card">
                        <div class="edubridge-policy-card-header">
                            <i class="fa-solid fa-handshake-slash" style="color: #ef4444;"></i>
                            <h4>4. التزام تام بعدم مشاركة البيانات مع جهات خارجية</h4>
                        </div>
                        <p>
                            تتعهد منظومة Edu-Bridge بعدم تزويد أو بيع أو مشاركة أي جزء من البيانات الشخصية أو الأكاديمية مع أي طرف ثالث أو منصات إعلانية أو خارجية إطلاقاً.
                        </p>
                    </div>
                </div>

                <div class="edubridge-compliance-badge">
                    <i class="fa-solid fa-certificate"></i>
                    <span>متوافق مع أفضل معايير أمن المعلومات والخصوصية الأكاديمية المعاصرة</span>
                </div>
            </div>

            <!-- TAB 4: CONTACT & SUPPORT -->
            <div id="tab-contact" class="edubridge-tab-pane">
                <div class="edubridge-section-intro">
                    <div class="edubridge-badge-label">
                        <i class="fa-solid fa-headset"></i>
                        دعم فني سريع ومباشر
                    </div>
                    <h3>تواصل مع إدارة النظام والمنصة</h3>
                    <p>يسعدنا تلقي استفساراتك واقتراحاتك ومساعدتك على مدار الساعة عبر القنوات الرسمية التالية:</p>
                </div>

                <div class="edubridge-contact-grid">
                    <!-- Email Card -->
                    <div class="edubridge-contact-card">
                        <div class="edubridge-contact-icon-box" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                            <i class="fa-solid fa-envelope-open-text"></i>
                        </div>
                        <div style="flex: 1;">
                            <span class="edubridge-contact-type">البريد الإلكتروني الرسمي</span>
                            <div class="edubridge-contact-value">edubridge2006@gmail.com</div>
                            <p class="edubridge-contact-desc">للمراسلات الرسمية، الدعم الفني، والملاحظات الأكاديمية</p>
                        </div>
                        <div class="edubridge-contact-action">
                            <a href="mailto:edubridge2006@gmail.com" class="edubridge-btn-action primary">
                                <i class="fa-solid fa-paper-plane"></i> إرسال رسالة
                            </a>
                            <button type="button" class="edubridge-btn-action secondary" onclick="copyContactText('edubridge2006@gmail.com', this)">
                                <i class="fa-solid fa-copy"></i> <span>نسخ</span>
                            </button>
                        </div>
                    </div>

                    <!-- WhatsApp / Phone Card -->
                    <div class="edubridge-contact-card">
                        <div class="edubridge-contact-icon-box" style="background: rgba(34, 197, 94, 0.12); color: #22c55e;">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div style="flex: 1;">
                            <span class="edubridge-contact-type">الدعم الفني المباشر (واتساب / هاتف)</span>
                            <div class="edubridge-contact-value" dir="ltr">0959031594</div>
                            <p class="edubridge-contact-desc">للمساعدة الفورية في الحسابات والأجهزة والدورات</p>
                        </div>
                        <div class="edubridge-contact-action">
                            <a href="https://wa.me/963959031594" target="_blank" class="edubridge-btn-action whatsapp">
                                <i class="fa-brands fa-whatsapp"></i> محادثة واتساب
                            </a>
                            <button type="button" class="edubridge-btn-action secondary" onclick="copyContactText('0959031594', this)">
                                <i class="fa-solid fa-copy"></i> <span>نسخ</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="edubridge-location-info">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>الجمهورية العربية السورية — نظام إدارة المؤسسات الأكاديمية</span>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="edubridge-modal-footer">
            <span class="edubridge-footer-copy">© {{ date('Y') }} Edu-Bridge Inc. جميع الحقوق محفوظة</span>
            <button type="button" class="edubridge-btn-close-modal" onclick="closeEduBridgeModal()">
                إغلاق النافذة
            </button>
        </div>
    </div>
</div>

<!-- Dedicated Ultra-Modern Styles -->
<style>
    .edubridge-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 999999;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        direction: rtl;
        font-family: 'Cairo', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        animation: edubridgeFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .edubridge-modal-container {
        background: #141416;
        color: #f4f4f5;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 28px;
        width: 100%;
        max-width: 820px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.8), 0 0 50px -10px rgba(255, 204, 0, 0.15);
        position: relative;
        overflow: hidden;
        animation: edubridgeZoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .edubridge-close-btn {
        position: absolute;
        top: 1.25rem;
        left: 1.25rem;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #a1a1aa;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        z-index: 10;
    }
    .edubridge-close-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.4);
        transform: rotate(90deg);
    }

    .edubridge-modal-header {
        padding: 1.75rem 2rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(180deg, rgba(255, 204, 0, 0.04) 0%, transparent 100%);
    }

    .edubridge-header-brand {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .edubridge-logo-gem {
        width: 58px;
        height: 58px;
        background: linear-gradient(135deg, #ffcc00 0%, #f59e0b 100%);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: #121212;
        box-shadow: 0 10px 25px -5px rgba(255, 204, 0, 0.4);
        flex-shrink: 0;
    }

    .edubridge-app-title {
        font-size: 1.6rem;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin: 0;
        color: #ffffff;
    }

    .edubridge-version-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(255, 204, 0, 0.12);
        border: 1px solid rgba(255, 204, 0, 0.3);
        color: #facc15;
        font-weight: 700;
        font-size: 0.8rem;
        padding: 0.2rem 0.75rem;
        border-radius: 20px;
    }

    .edubridge-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #22c55e;
        box-shadow: 0 0 8px #22c55e;
        display: inline-block;
    }

    .edubridge-app-subtitle {
        margin: 0.25rem 0 0;
        font-size: 0.9rem;
        color: #a1a1aa;
    }

    /* Tabs Bar */
    .edubridge-tabs-nav {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
    }
    .edubridge-tabs-nav::-webkit-scrollbar {
        display: none;
    }

    .edubridge-tab-btn {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        color: #a1a1aa;
        padding: 0.65rem 1.25rem;
        border-radius: 14px;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
        transition: all 0.25s ease;
    }
    .edubridge-tab-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }
    .edubridge-tab-btn.active {
        background: #ffcc00;
        color: #121212;
        border-color: #ffcc00;
        box-shadow: 0 4px 15px rgba(255, 204, 0, 0.3);
    }

    /* Body */
    .edubridge-modal-body {
        padding: 1.75rem 2rem;
        overflow-y: auto;
        flex: 1;
    }
    .edubridge-modal-body::-webkit-scrollbar {
        width: 6px;
    }
    .edubridge-modal-body::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
    }

    .edubridge-tab-pane {
        display: none;
        animation: edubridgeTabFade 0.25s ease;
    }
    .edubridge-tab-pane.active {
        display: block;
    }

    /* Hero Quote */
    .edubridge-hero-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        position: relative;
    }
    .edubridge-hero-quote {
        position: relative;
    }
    .edubridge-hero-quote p {
        margin: 0;
        font-size: 1.02rem;
        line-height: 1.75;
        color: #e4e4e7;
    }
    .edubridge-quote-icon {
        position: absolute;
        top: -10px;
        left: 0;
        font-size: 2.2rem;
        color: rgba(255, 204, 0, 0.15);
    }

    /* Features Grid */
    .edubridge-features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .edubridge-feature-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 18px;
        padding: 1.25rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        transition: transform 0.2s, background 0.2s;
    }
    .edubridge-feature-item:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.12);
    }
    .edubridge-feat-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .edubridge-feature-item h4 {
        margin: 0 0 0.35rem;
        font-size: 0.95rem;
        font-weight: 800;
        color: #ffffff;
    }
    .edubridge-feature-item p {
        margin: 0;
        font-size: 0.85rem;
        color: #a1a1aa;
        line-height: 1.5;
    }

    /* Tech Strip */
    .edubridge-tech-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: center;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }
    .edubridge-tech-tag {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 0.3rem 0.85rem;
        font-size: 0.78rem;
        color: #d4d4d8;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 600;
    }
    .edubridge-tech-tag i {
        color: #ffcc00;
    }

    /* Section intro */
    .edubridge-section-intro {
        text-align: center;
        margin-bottom: 1.75rem;
    }
    .edubridge-badge-label {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(255, 204, 0, 0.1);
        color: #facc15;
        border-radius: 20px;
        padding: 0.25rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .edubridge-section-intro h3 {
        margin: 0.25rem 0 0.5rem;
        font-size: 1.35rem;
        font-weight: 800;
        color: #ffffff;
    }
    .edubridge-section-intro p {
        margin: 0 auto;
        max-width: 520px;
        font-size: 0.88rem;
        color: #a1a1aa;
    }

    /* Team Grid */
    .edubridge-team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 1rem;
    }

    .edubridge-member-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.25s ease;
    }
    .edubridge-member-card:hover {
        transform: translateY(-3px);
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 204, 0, 0.3);
        box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.5);
    }
    .edubridge-avatar {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 1.4rem;
        color: #ffffff;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        flex-shrink: 0;
    }
    .edubridge-member-info h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.2px;
    }
    .edubridge-role {
        display: block;
        font-size: 0.78rem;
        color: #eab308;
        font-weight: 600;
        margin-top: 0.2rem;
    }
    .edubridge-skills {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        margin-top: 0.4rem;
    }
    .edubridge-skills span {
        background: rgba(255, 255, 255, 0.06);
        border-radius: 6px;
        padding: 0.15rem 0.4rem;
        font-size: 0.68rem;
        color: #a1a1aa;
    }

    /* Privacy Tab */
    .edubridge-privacy-hero {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.04) 100%);
        border: 1px solid rgba(16, 185, 129, 0.25);
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        gap: 1.25rem;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .edubridge-privacy-icon {
        width: 60px;
        height: 60px;
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        flex-shrink: 0;
    }
    .edubridge-privacy-hero h3 {
        margin: 0 0 0.4rem;
        font-size: 1.15rem;
        font-weight: 800;
        color: #ffffff;
    }
    .edubridge-privacy-lead {
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.6;
        color: #d1fae5;
    }

    .edubridge-policy-sections {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .edubridge-policy-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        transition: background 0.2s;
    }
    .edubridge-policy-card:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    .edubridge-policy-card-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }
    .edubridge-policy-card-header h4 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 800;
        color: #ffffff;
    }
    .edubridge-policy-card p {
        margin: 0;
        font-size: 0.85rem;
        color: #a1a1aa;
        line-height: 1.6;
    }

    .edubridge-compliance-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px dashed rgba(255, 255, 255, 0.12);
        border-radius: 14px;
        padding: 0.75rem;
        font-size: 0.82rem;
        color: #d4d4d8;
        font-weight: 600;
    }
    .edubridge-compliance-badge i {
        color: #22c55e;
    }

    /* Contact Tab */
    .edubridge-contact-grid {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .edubridge-contact-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex-wrap: wrap;
    }
    .edubridge-contact-icon-box {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.7rem;
        flex-shrink: 0;
    }
    .edubridge-contact-type {
        font-size: 0.8rem;
        color: #a1a1aa;
        display: block;
        margin-bottom: 0.2rem;
    }
    .edubridge-contact-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: #ffffff;
        font-family: monospace, system-ui;
    }
    .edubridge-contact-desc {
        margin: 0.25rem 0 0;
        font-size: 0.82rem;
        color: #71717a;
    }
    .edubridge-contact-action {
        display: flex;
        gap: 0.5rem;
        margin-right: auto;
    }
    .edubridge-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.65rem 1.15rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    .edubridge-btn-action.primary {
        background: #ef4444;
        color: #ffffff;
    }
    .edubridge-btn-action.primary:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }
    .edubridge-btn-action.whatsapp {
        background: #22c55e;
        color: #ffffff;
    }
    .edubridge-btn-action.whatsapp:hover {
        background: #16a34a;
        transform: translateY(-1px);
    }
    .edubridge-btn-action.secondary {
        background: rgba(255, 255, 255, 0.08);
        color: #e4e4e7;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    .edubridge-btn-action.secondary:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .edubridge-location-info {
        text-align: center;
        font-size: 0.85rem;
        color: #71717a;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .edubridge-location-info i {
        color: #eab308;
    }

    /* Modal Footer */
    .edubridge-modal-footer {
        padding: 1.25rem 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(0, 0, 0, 0.2);
    }
    .edubridge-footer-copy {
        font-size: 0.8rem;
        color: #71717a;
    }
    .edubridge-btn-close-modal {
        background: rgba(255, 255, 255, 0.08);
        color: #d4d4d8;
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.5rem 1.25rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    .edubridge-btn-close-modal:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }

    /* Keyframe Animations */
    @keyframes edubridgeFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes edubridgeZoomIn {
        from { opacity: 0; transform: scale(0.94) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes edubridgeTabFade {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 640px) {
        .edubridge-modal-header {
            padding: 1.25rem 1.25rem 0.75rem;
        }
        .edubridge-modal-body {
            padding: 1.25rem;
        }
        .edubridge-contact-action {
            margin-right: 0;
            width: 100%;
            margin-top: 0.5rem;
        }
        .edubridge-btn-action {
            flex: 1;
            justify-content: center;
        }
    }
</style>

<!-- Dedicated Script Logic -->
<script>
    function openEduBridgeModal(targetTab = 'tab-about') {
        const modal = document.getElementById('eduBridgeModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            switchEduTab(targetTab);
        }
    }

    function closeEduBridgeModal() {
        const modal = document.getElementById('eduBridgeModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function switchEduTab(tabId) {
        // Update Buttons
        const buttons = document.querySelectorAll('.edubridge-tab-btn');
        buttons.forEach(btn => {
            if (btn.getAttribute('data-tab') === tabId) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update Panes
        const panes = document.querySelectorAll('.edubridge-tab-pane');
        panes.forEach(pane => {
            if (pane.id === tabId) {
                pane.classList.add('active');
            } else {
                pane.classList.remove('active');
            }
        });
    }

    function copyContactText(text, btnElement) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showCopySuccess(btnElement);
            }).catch(() => {
                fallbackCopyText(text, btnElement);
            });
        } else {
            fallbackCopyText(text, btnElement);
        }
    }

    function fallbackCopyText(text, btnElement) {
        const temp = document.createElement('input');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showCopySuccess(btnElement);
    }

    function showCopySuccess(btnElement) {
        const span = btnElement.querySelector('span');
        const originalText = span ? span.innerText : '';
        if (span) span.innerText = 'تم النسخ!';
        btnElement.style.background = 'rgba(34, 197, 94, 0.25)';
        btnElement.style.color = '#22c55e';
        btnElement.style.borderColor = '#22c55e';

        setTimeout(() => {
            if (span) span.innerText = originalText;
            btnElement.style.background = '';
            btnElement.style.color = '';
            btnElement.style.borderColor = '';
        }, 2000);
    }

    // Close on backdrop click & ESC key
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('eduBridgeModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeEduBridgeModal();
            });
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeEduBridgeModal();
        });
    });
</script>
