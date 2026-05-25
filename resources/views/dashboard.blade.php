<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <!-- Modals -->
        <div id="otpModal" class="modal-overlay">
            <div class="modal-card">
                <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--primary-yellow); margin-bottom: 1.5rem;"></i>
                <h3>التحقق من الهوية</h3>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">تم إرسال رمز OTP إلى هاتفك</p>
                <div style="display: flex; gap: 12px; justify-content: center; margin-bottom: 2.5rem;">
                    <input type="text" class="otp-input" maxlength="1">
                    <input type="text" class="otp-input" maxlength="1">
                    <input type="text" class="otp-input" maxlength="1">
                    <input type="text" class="otp-input" maxlength="1">
                </div>
                <button class="btn btn-primary" style="width: 100%;" onclick="verifyOtp()">تأكيد التعديل</button>
                <button class="btn btn-outline" style="width: 100%; margin-top: 10px;" onclick="closeModal('otpModal')">إلغاء</button>
            </div>
        </div>

        <div id="accountModal" class="modal-overlay">
            <div class="modal-card" style="text-align: right; width: 550px;">
                <h3 style="margin-bottom: 10px;">إضافة حساب جديد</h3>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">أدخل بيانات العضو الجديد لإضافته للنظام.</p>
                <div style="margin-bottom: 1.5rem;"><label>الاسم الكامل</label><input type="text" id="accName" class="edit-input" placeholder="مثلاً: د. سمير خالد"></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div><label>القسم</label><input type="text" id="accDept" class="edit-input" placeholder="مثلاً: قسم العلوم"></div>
                    <div><label>نوع الحساب</label><select id="accRole" class="edit-input"><option value="trainer">مدرب</option><option value="student">طالب</option></select></div>
                </div>
                <div style="display: flex; gap: 12px; margin-top: 2rem;">
                    <button class="btn btn-primary" style="flex: 2;" onclick="saveAccount()">إضافة الحساب</button>
                    <button class="btn btn-outline" style="flex: 1;" onclick="closeModal('accountModal')">إلغاء</button>
                </div>
            </div>
        </div>

        <div id="settingsModal" class="modal-overlay">
            <div class="modal-card" style="text-align: right; width: 480px; padding: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <button class="circle-btn" onclick="closeModal('settingsModal')"><i class="fas fa-arrow-right"></i></button>
                    <h3 style="font-weight: 800;">الإعدادات</h3>
                </div>

                <!-- User Card -->
                <div class="card" style="display: flex; align-items: center; gap: 1.2rem; background: #FFFFFF; border: 1px solid #F0F0F0; padding: 1.2rem; border-radius: 20px;">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name) }}&background=FFD200&color=333" class="global-user-avatar" style="width: 60px; height: 60px;">
                    <div>
                        <h4 class="global-user-name" style="font-weight: 800;">{{ $user->full_name }}</h4>
                        <small style="color: var(--text-muted);">رئيس قسم</small>
                    </div>
                </div>

                <!-- Appearance -->
                <div class="settings-section">
                    <small style="color: #AAA; margin-bottom: 1rem; display: block;">المظهر</small>
                    <div class="card" style="padding: 1.5rem; border-radius: 20px; border: 1px solid #F0F0F0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <span style="font-size: 1.2rem; color: #AAA; font-weight: 800;">Tt</span>
                            <div style="flex-grow: 1; margin: 0 1.5rem;">
                                <input type="range" min="14" max="22" value="18" class="yellow-range" oninput="updateFontSize(this.value)">
                                <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.75rem; color: #AAA;">
                                    <span>كبير</span><span id="font-mid-label" style="color: var(--primary-yellow); font-weight: 800;">متوسط</span><span>صغير</span>
                                </div>
                            </div>
                            <span style="font-weight: 700;">حجم الخط</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label class="yellow-switch">
                                <input type="checkbox" onchange="toggleDarkMode(this.checked)">
                                <span class="slider"></span>
                            </label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-weight: 700;">الوضع الداكن</span>
                                <i class="fas fa-moon" style="color: #AAA;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Language -->
                <div class="settings-section" style="margin-top: 1.5rem;">
                    <small style="color: #AAA; margin-bottom: 1rem; display: block;">اللغة</small>
                    <div class="card" style="padding: 1.2rem; border-radius: 20px; border: 1px solid #F0F0F0; display: flex; justify-content: space-between; align-items: center;">
                        <div class="lang-toggle">
                            <span class="active">عربي</span>
                            <span>EN</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-weight: 700;">لغة التطبيق</span>
                            <i class="fas fa-globe" style="color: #AAA;"></i>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="settings-section" style="margin-top: 1.5rem;">
                    <small style="color: #AAA; margin-bottom: 1rem; display: block;">الإشعارات</small>
                    <div class="card" style="padding: 1.5rem; border-radius: 20px; border: 1px solid #F0F0F0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
                            <label class="yellow-switch"><input type="checkbox" checked><span class="slider"></span></label>
                            <div style="display: flex; align-items: center; gap: 10px;"><span style="font-weight: 700;">تفعيل الإشعارات</span><i class="fas fa-bell" style="color: #AAA;"></i></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
                            <label class="yellow-switch"><input type="checkbox" checked><span class="slider"></span></label>
                            <div style="display: flex; align-items: center; gap: 10px;"><span style="font-weight: 700;">الأصوات</span><i class="fas fa-volume-up" style="color: #AAA;"></i></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label class="yellow-switch"><input type="checkbox"><span class="slider"></span></label>
                            <div style="display: flex; align-items: center; gap: 10px;"><span style="font-weight: 700;">الاهتزاز</span><i class="fas fa-vibration" style="color: #AAA;"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">Edu<span>Bridge</span></div>
            <ul class="nav-links">
                <li class="nav-item"><a class="nav-link active" onclick="showTab('home', this)"><i class="fas fa-th-large"></i><span>الرئيسية</span></a></li>
                <li class="nav-item"><a class="nav-link" onclick="showTab('profile', this)"><i class="fas fa-user-circle"></i><span>الملف الشخصي</span></a></li>
                <li class="nav-item"><a class="nav-link" onclick="showTab('messages', this)"><i class="fas fa-comment-dots"></i><span>المراسلة</span></a></li>
                <li class="nav-item"><a class="nav-link" onclick="showTab('notifications', this)"><i class="fas fa-bell"></i><span>الإشعارات</span></a></li>
                <li class="nav-item"><a class="nav-link" onclick="showTab('org', this)"><i class="fas fa-calendar-alt"></i><span>التنظيم</span></a></li>
                <li class="nav-item"><a class="nav-link" onclick="showTab('accounts', this)"><i class="fas fa-users-cog"></i><span>الحسابات</span></a></li>
                <li class="nav-item"><a class="nav-link" onclick="showTab('reports', this)"><i class="fas fa-chart-pie"></i><span>التقارير</span></a></li>
                <li class="nav-item"><a class="nav-link" onclick="showTab('leaves', this)"><i class="fas fa-file-medical"></i><span>الإجازات</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <div class="user-info">
                    <div style="text-align: right;">
                        <h4 style="margin: 0; font-weight: 700;" class="global-user-name">{{ $user->full_name }}</h4>
                        <small style="color: var(--text-muted);">مرحباً بك مجدداً</small>
                    </div>
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name) }}&background=FFD200&color=333" class="global-user-avatar" alt="Avatar">
                </div>
                <button class="settings-btn" onclick="openModal('settingsModal')"><i class="fas fa-cog"></i></button>
            </header>

            <!-- Tabs -->
            <section id="home" class="tab-content active">
                <div class="notice-box">
                    <i class="fas fa-info-circle" style="color: var(--primary-yellow); font-size: 1.5rem;"></i>
                    <div><h4 style="margin-bottom: 5px;">تحديث القوائم</h4><p style="font-size: 0.9rem; color: var(--text-muted);">تم نقل كافة الأقسام إلى القائمة الجانبية لتسهيل الوصول.</p></div>
                </div>
                <h3>آخر الأخبار</h3>
                <div class="news-grid">
                    @foreach($announcements as $news)
                    <div class="card news-card">
                        <div style="background: linear-gradient(135deg, #FFD200 0%, #FFB000 100%); height: 160px; display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem;"><i class="fas fa-bullhorn"></i></div>
                        <div class="news-body">
                            <span class="tag">{{ $news->type == 'general' ? 'إعلان عام' : 'تحديث' }}</span>
                            <h4>{{ $news->title }}</h4>
                            <p style="color: var(--text-muted); font-size: 0.9rem;">{{ $news->content }}</p>
                            <small style="color: #AAA; margin-top: 10px; display: block;">{{ $news->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>

            </section>

            <section id="profile" class="tab-content">
                <div class="card">
                    <div class="profile-header" style="text-align: center;">
                        <div style="position: relative; width: 120px; height: 120px; margin: 0 auto 1.5rem;">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name) }}&background=FFD200&color=333&size=200" alt="Profile" class="global-user-avatar" style="width: 100%; height: 100%;">
                            <div style="position: absolute; bottom: 0; left: 0; background: var(--primary-yellow); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid var(--white); cursor: pointer;"><i class="fas fa-camera" style="font-size: 0.8rem;"></i></div>
                        </div>
                        <h2 class="global-user-name" style="font-weight: 800;">{{ $user->full_name }}</h2>
                        <p style="color: var(--text-muted);">{{ $user->role == 'head' ? 'رئيس القسم الأكاديمي' : $user->role }}</p>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 3rem;">
                        <div class="card" style="margin-bottom: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;"><h4>بيانات الحساب</h4><button class="btn btn-outline" style="padding: 5px 15px; font-size: 0.75rem;" onclick="toggleEdit()">تعديل</button></div>
                            <div style="margin-bottom: 1rem;"><small style="color: var(--text-muted);">الجوال</small><p id="phoneDisplay" style="font-weight: 700;">{{ $user->phone ?? 'غير مسجل' }}</p><input type="text" id="phoneInput" class="edit-input" value="{{ $user->phone }}" style="display: none;"></div>
                            <div style="margin-bottom: 1rem;"><small style="color: var(--text-muted);">البريد</small><p id="emailDisplay" style="font-weight: 700;">{{ $user->email }}</p><input type="email" id="emailInput" class="edit-input" value="{{ $user->email }}" style="display: none;"></div>
                            <button id="saveBtn" class="btn btn-primary" style="width: 100%; display: none;" onclick="startOtpProcess()">حفظ التغييرات</button>
                        </div>
                        <div class="card" style="margin-bottom: 0;"><h4>معلومات القسم</h4><p style="margin-top: 1.5rem;">القسم: {{ $user->department ?? 'غير محدد' }}</p><p>تاريخ الميلاد: {{ $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('d M Y') : 'غير مسجل' }}</p></div>
                    </div>

                </div>
            </section>

            <section id="org" class="tab-content">
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem;"><button class="btn btn-primary sub-tab-btn" onclick="showSubTab('org', 'study-schedule', this)">جدول دراسي</button><button class="btn btn-outline sub-tab-btn" onclick="showSubTab('org', 'exam-schedule', this)">جدول امتحاني</button></div>

                <div id="study-schedule" class="sub-tab-content active">
                    <div id="org-main-menu" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="card" style="cursor: pointer; text-align: center;" onclick="showOrgView('view-schedule-grid')"><i class="fas fa-eye" style="font-size: 2.5rem; color: var(--primary-yellow); margin-bottom: 1rem;"></i><h4>استعراض الجدول</h4></div>
                        <div class="card" style="cursor: pointer; text-align: center;" onclick="showOrgView('create-schedule-grid')"><i class="fas fa-plus-circle" style="font-size: 2.5rem; color: var(--primary-yellow); margin-bottom: 1rem;"></i><h4>بناء جدول جديد</h4></div>
                    </div>
                    <!-- View Schedule (Screenshot Style) -->
                    <div id="view-schedule-grid" class="org-view" style="display: none;">
                        <button class="circle-btn" style="margin-bottom: 1.5rem;" onclick="showOrgView('org-main-menu')"><i class="fas fa-arrow-right"></i></button>
                        <div class="schedule-container">
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th style="background-color: #F9FED7;">اليوم / الشعبة</th>
                                        <th>معلوماتية 1</th><th>معلوماتية 2</th><th>معلوماتية 3</th><th>معلوماتية 4</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];
                                    @endphp
                                    @foreach($days as $day)
                                    <tr>
                                        <td class="day-cell">{{ $day }}</td>
                                        @for($i=1; $i<=4; $i++)
                                        <td>
                                            @php
                                                $sessions = $schedules->get($day)?->where('room_number', "قاعة $i") ?? collect();
                                            @endphp
                                            @forelse($sessions as $session)
                                                <div class="schedule-card {{ $loop->even ? 'blue' : '' }}">
                                                    <div class="course-name">{{ $session->course?->course_name ?? 'مادة غير معروفة' }}</div>
                                                    <div class="course-time">{{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}</div>
                                                    <div class="course-loc">{{ $session->room_number }}</div>
                                                </div>
                                            @empty
                                                <div class="add-slot-icon"><i class="far fa-plus-square"></i></div>
                                            @endforelse
                                        </td>
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>
                    <!-- Create Schedule -->
                    <div id="create-schedule-grid" class="org-view" style="display: none;">
                        <button class="circle-btn" style="margin-bottom: 1.5rem;" onclick="showOrgView('org-main-menu')"><i class="fas fa-arrow-right"></i></button>
                        <div class="schedule-container">
                            <table class="schedule-table">
                                <thead><tr><th style="background-color: #F9FED7;">اليوم / الشعبة</th><th>معلوماتية 1</th><th>معلوماتية 2</th><th>معلوماتية 3</th><th>معلوماتية 4</th></tr></thead>
                                <tbody>
                                    <tr><td class="day-cell">الأحد</td><td><div class="add-slot-icon"><i class="far fa-plus-square"></i></div></td><td><div class="add-slot-icon"><i class="far fa-plus-square"></i></div></td><td><div class="add-slot-icon"><i class="far fa-plus-square"></i></div></td><td><div class="add-slot-icon"><i class="far fa-plus-square"></i></div></td></tr>
                                    <tr><td class="day-cell">الاثنين</td><td><div class="add-slot-icon"><i class="far fa-plus-square"></i></div></td><td><div class="add-slot-icon"><i class="far fa-plus-square"></i></div></td><td><div class="add-slot-icon"><i class="far fa-plus-square"></i></div></td><td><div class="add-slot-icon"><i class="far fa-plus-square"></i></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>


            <section id="accounts" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                    <div style="display: flex; gap: 1rem;"><button class="btn btn-primary sub-tab-btn" onclick="showSubTab('accounts', 'trainers-list', this)">مدرب</button><button class="btn btn-outline sub-tab-btn" onclick="showSubTab('accounts', 'students-list', this)">طالب</button></div>
                    <button class="btn btn-primary" onclick="openModal('accountModal')"><i class="fas fa-plus-circle"></i> إضافة حساب</button>
                </div>
                <div id="trainers-list" class="sub-tab-content active"><div id="trainers-container" class="account-grid"><div class="card account-card"><div style="background: var(--light-yellow); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--primary-yellow);">ع</div><div><h4 style="margin: 0;">د. علي محمد</h4><small style="color: var(--text-muted);">قسم العلوم</small></div></div></div></div>
                <div id="students-list" class="sub-tab-content"><div id="students-container" class="account-grid"><div class="card" style="text-align: center; color: var(--text-muted);">لا يوجد بيانات حالياً</div></div></div>
            </section>

            <!-- Other Tab Placeholders -->
            <section id="messages" class="tab-content"><div class="card" style="text-align: center; padding: 4rem;"><i class="fas fa-comments" style="font-size: 3rem; color: #EEE; margin-bottom: 1rem;"></i><h4>صندوق الوارد فارغ</h4></div></section>
            <section id="notifications" class="tab-content"><h3>الإشعارات</h3><div class="card" style="display: flex; align-items: center; gap: 1rem; margin-top: 1.5rem;"><div style="background: #e6f3ff; color: #007bff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-info-circle"></i></div><div><h5 style="margin: 0;">تنبيه نظام</h5><small>تم تحديث النظام لعام 2026.</small></div></div></section>
            <section id="reports" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <button class="circle-btn" onclick="showTab('home', document.querySelector('.nav-link'))"><i class="fas fa-arrow-right"></i></button>
                    <div style="text-align: right;">
                        <h2 style="font-weight: 800;">طلب التقارير</h2>
                        <p style="color: var(--text-muted);">تقديم طلب تقرير عن أداء طالب</p>
                    </div>
                </div>

                <!-- Student Search Card -->
                <div class="card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                        <h4 style="font-weight: 800;">الطالب المعني</h4>
                        <div style="background: #FFF9C4; color: #FBC02D; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user-tag"></i></div>
                    </div>
                    <div style="position: relative; margin-bottom: 1rem;">
                        <i class="fas fa-search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #BBB;"></i>
                        <input type="text" class="edit-input" placeholder="ابحث باسم الطالب أو الرقم الأكاديمي..." style="padding-right: 45px;">
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; justify-content: flex-end;">
                        <small style="color: #AAA;">مقترحات سريعة:</small>
                        <div class="quick-chip"><i class="fas fa-user"></i> أحمد محمد</div>
                        <div class="quick-chip"><i class="fas fa-user"></i> فيصل العتيبي</div>
                    </div>
                </div>

                <!-- Trainer Selection Card -->
                <div class="card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                        <h4 style="font-weight: 800;">المدرب المسؤول</h4>
                        <div style="background: #E3F2FD; color: #1976D2; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-users"></i></div>
                    </div>
                    <select class="edit-input" style="appearance: none; background: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23999%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22></polyline></svg>') no-repeat left 15px center; background-size: 15px;">
                        <option value="">اختر المدرب من القائمة...</option>
                        <option>د. خالد العتيبي</option>
                        <option>أ. سارة العمر</option>
                    </select>
                </div>

                <!-- Report Details Card -->
                <div class="card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                        <h4 style="font-weight: 800;">تفاصيل التقرير</h4>
                        <div style="background: #F3E5F5; color: #9C27B0; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-clipboard-list"></i></div>
                    </div>
                    <div style="display: flex; gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="report-type-btn active" onclick="selectReport(this)">
                            <div class="type-icon"><i class="fas fa-graduation-cap"></i></div>
                            <p>أداء أكاديمي</p>
                        </div>
                        <div class="report-type-btn" onclick="selectReport(this)">
                            <div class="type-icon"><i class="fas fa-user-check"></i></div>
                            <p>سلوك وحضور</p>
                        </div>
                    </div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 700;">ملاحظات إضافية (اختياري)</label>
                    <textarea class="edit-input" style="height: 120px; resize: none;" placeholder="اكتب أي نقاط محددة ترغب في التركيز عليها..."></textarea>
                    <button class="btn btn-primary" style="width: 100%; margin-top: 2rem; font-size: 1.1rem; padding: 1.2rem;">إرسال طلب التقرير</button>
                </div>
            </section>

            <section id="leaves" class="tab-content"><div class="card" style="text-align: center; padding: 4rem;"><i class="fas fa-file-medical" style="font-size: 3rem; color: #EEE; margin-bottom: 1rem;"></i><h4>لا يوجد طلبات إجازة</h4></div></section>

        </main>
    </div>

    <script>
        function showTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            if(btn) btn.classList.add('active');
        }
        function showSubTab(parentId, subId, btn) {
            const parent = document.getElementById(parentId);
            parent.querySelectorAll('.sub-tab-content').forEach(s => s.classList.remove('active'));
            parent.querySelectorAll('.sub-tab-btn').forEach(b => { b.classList.remove('btn-primary'); b.classList.add('btn-outline'); });
            document.getElementById(subId).classList.add('active');
            btn.classList.add('btn-primary'); btn.classList.remove('btn-outline');
        }
        function showOrgView(viewId) {
            document.querySelectorAll('.org-view').forEach(v => v.style.display = 'none');
            document.getElementById('org-main-menu').style.display = viewId === 'org-main-menu' ? 'grid' : 'none';
            if(viewId !== 'org-main-menu') document.getElementById(viewId).style.display = 'block';
        }
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        function toggleEdit() {
            const isEdit = document.getElementById('phoneInput').style.display === 'block';
            document.getElementById('phoneDisplay').style.display = isEdit ? 'block' : 'none';
            document.getElementById('phoneInput').style.display = isEdit ? 'none' : 'block';
            document.getElementById('emailDisplay').style.display = isEdit ? 'block' : 'none';
            document.getElementById('emailInput').style.display = isEdit ? 'none' : 'block';
            document.getElementById('saveBtn').style.display = isEdit ? 'none' : 'block';
        }
        function startOtpProcess() {
            openModal('otpModal');
            document.querySelectorAll('.otp-input').forEach(i => i.value = '');
            document.querySelectorAll('.otp-input')[0].focus();

            fetch('/dashboard/send-otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) alert(data.msg); 
            });
        }

        // Auto-focus move logic
        document.querySelectorAll('.otp-input').forEach((input, idx) => {
            input.addEventListener('input', (e) => {
                if (e.target.value && idx < 3) {
                    document.querySelectorAll('.otp-input')[idx + 1].focus();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                    document.querySelectorAll('.otp-input')[idx - 1].focus();
                }
            });
        });

        function verifyOtp() {
            const inputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(inputs).map(i => i.value).join('');
            
            const phone = document.getElementById('phoneInput').value;
            const email = document.getElementById('emailInput').value;

            fetch('/dashboard/update-profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ otp, phone, email })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert(data.msg);
                    location.reload();
                } else {
                    alert(data.msg);
                }
            });
        }


        function updateFontSize(val) { document.documentElement.style.fontSize = val + 'px'; document.getElementById('fontSizeLabel').innerText = val + 'px'; }
        function toggleDarkMode(isDark) { isDark ? document.body.classList.add('dark-mode') : document.body.classList.remove('dark-mode'); }
        function saveAccount() {
            const name = document.getElementById('accName').value;
            const role = document.getElementById('accRole').value;
            const container = document.getElementById(role === 'trainer' ? 'trainers-container' : 'students-container');
            if(container.innerText.includes('بيانات')) container.innerHTML = '';
            const card = `<div class="card account-card" style="animation: fadeIn 0.4s ease;"><div style="background: #EEE; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800;">${name.charAt(0)}</div><div><h4 style="margin: 0;">${name}</h4><small>عضو جديد</small></div></div>`;
            container.insertAdjacentHTML('afterbegin', card);
            closeModal('accountModal');
        }
        function selectReport(btn) {
            btn.parentElement.querySelectorAll('.report-type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    </script>
</body>
</html>
