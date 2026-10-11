@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Edu-Bridge Ultra-Premium About & Privacy Modal Component -->
<div id="eduBridgeModal" class="edubridge-modal-backdrop" style="display: none;" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="edubridge-modal-container">
        <!-- Close Button -->
        <button type="button" class="edubridge-close-btn" onclick="closeEduBridgeModal()" title="{{ __('messages.about_btn_close_modal') }}">
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
                        <h2 class="edubridge-app-title">{{ __('messages.about_modal_title') }}</h2>
                    </div>
                    <p class="edubridge-app-subtitle">{{ __('messages.about_modal_subtitle') }}</p>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="edubridge-tabs-nav">
                <button type="button" class="edubridge-tab-btn active" data-tab="tab-about" onclick="switchEduTab('tab-about')">
                    <i class="fa-solid fa-sparkles"></i>
                    <span>{{ __('messages.about_tab_about') }}</span>
                </button>
                <button type="button" class="edubridge-tab-btn" data-tab="tab-team" onclick="switchEduTab('tab-team')">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>{{ __('messages.about_tab_team') }}</span>
                </button>
                <button type="button" class="edubridge-tab-btn" data-tab="tab-privacy" onclick="switchEduTab('tab-privacy')">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>{{ __('messages.about_tab_privacy') }}</span>
                </button>
                <button type="button" class="edubridge-tab-btn" data-tab="tab-contact" onclick="switchEduTab('tab-contact')">
                    <i class="fa-solid fa-headset"></i>
                    <span>{{ __('messages.about_tab_contact') }}</span>
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
                            {!! __('messages.about_hero_desc', ['app' => '<strong>Edu-Bridge</strong>']) !!}
                        </p>
                    </div>
                </div>

                <div class="edubridge-features-grid">
                    <div class="edubridge-feature-item">
                        <div class="edubridge-feat-icon" style="background: rgba(242, 242, 13, 0.15); color: #eab308;">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <h4>{{ __('messages.about_feat_academic_title') }}</h4>
                            <p>{{ __('messages.about_feat_academic_desc') }}</p>
                        </div>
                    </div>

                    <div class="edubridge-feature-item">
                        <div class="edubridge-feat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div>
                            <h4>{{ __('messages.about_feat_attendance_title') }}</h4>
                            <p>{{ __('messages.about_feat_attendance_desc') }}</p>
                        </div>
                    </div>

                    <div class="edubridge-feature-item">
                        <div class="edubridge-feat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div>
                            <h4>{{ __('messages.about_feat_comm_title') }}</h4>
                            <p>{{ __('messages.about_feat_comm_desc') }}</p>
                        </div>
                    </div>

                    <div class="edubridge-feature-item">
                        <div class="edubridge-feat-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                        <div>
                            <h4>{{ __('messages.about_feat_experience_title') }}</h4>
                            <p>{{ __('messages.about_feat_experience_desc') }}</p>
                        </div>
                    </div>
                </div>

                <!-- System Highlights Strip -->
                <div class="edubridge-tech-strip">
                    <span class="edubridge-tech-tag"><i class="fa-solid fa-code"></i> Flutter &amp; Laravel</span>
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
                        {{ __('messages.about_team_badge') }}
                    </div>
                    <h3>{{ __('messages.about_team_title') }}</h3>
                    <p>{{ __('messages.about_team_desc') }}</p>
                </div>

                <!-- 5 Interactive Team Cards -->
                <div class="edubridge-team-grid">
                    <!-- Member 1: Majdouleen Mahmoud -->
                    <div class="edubridge-member-card active-member" 
                         data-member="1" 
                         data-name="{{ __('messages.team_member_1_name') }}"
                         data-role="{{ __('messages.team_member_1_role') }}"
                         data-desc="{{ __('messages.team_member_1_desc') }}"
                         data-icon="fa-solid fa-server"
                         data-initials="MM"
                         data-color="#f59e0b"
                         data-tags="Laravel, System Architecture, REST APIs Integration, Security & Auth"
                         onclick="selectTeamMember(this)">
                        <div class="edubridge-member-icon-badge" style="background: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.3); color: #fbbf24;">
                            <i class="fa-solid fa-server"></i>
                            <span class="edubridge-initials-sub">MM</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>{{ __('messages.team_member_1_name') }}</h4>
                            <p class="edubridge-member-role">{{ __('messages.team_member_1_role') }}</p>
                        </div>
                        <div class="edubridge-active-pointer"></div>
                    </div>

                    <!-- Member 2: Mahmoud Ghannam -->
                    <div class="edubridge-member-card" 
                         data-member="2" 
                         data-name="{{ __('messages.team_member_2_name') }}"
                         data-role="{{ __('messages.team_member_2_role') }}"
                         data-desc="{{ __('messages.team_member_2_desc') }}"
                         data-icon="fa-solid fa-mobile-screen-button"
                         data-initials="MG"
                         data-color="#0ea5e9"
                         data-tags="Flutter, Dart, Mobile Architecture, QR & Biometrics, Fluid UX"
                         onclick="selectTeamMember(this)">
                        <div class="edubridge-member-icon-badge" style="background: rgba(14, 165, 233, 0.15); border-color: rgba(14, 165, 233, 0.3); color: #38bdf8;">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                            <span class="edubridge-initials-sub">MG</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>{{ __('messages.team_member_2_name') }}</h4>
                            <p class="edubridge-member-role">{{ __('messages.team_member_2_role') }}</p>
                        </div>
                        <div class="edubridge-active-pointer"></div>
                    </div>

                    <!-- Member 3: Israa Munawwar -->
                    <div class="edubridge-member-card" 
                         data-member="3" 
                         data-name="{{ __('messages.team_member_3_name') }}"
                         data-role="{{ __('messages.team_member_3_role') }}"
                         data-desc="{{ __('messages.team_member_3_desc') }}"
                         data-icon="fa-solid fa-code"
                         data-initials="EM"
                         data-color="#a855f7"
                         data-tags="PHP & Laravel, RESTful APIs, MySQL Database, Backend Logic"
                         onclick="selectTeamMember(this)">
                        <div class="edubridge-member-icon-badge" style="background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.3); color: #c084fc;">
                            <i class="fa-solid fa-code"></i>
                            <span class="edubridge-initials-sub">EM</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>{{ __('messages.team_member_3_name') }}</h4>
                            <p class="edubridge-member-role">{{ __('messages.team_member_3_role') }}</p>
                        </div>
                        <div class="edubridge-active-pointer"></div>
                    </div>

                    <!-- Member 4: Hiba Issa -->
                    <div class="edubridge-member-card" 
                         data-member="4" 
                         data-name="{{ __('messages.team_member_4_name') }}"
                         data-role="{{ __('messages.team_member_4_role') }}"
                         data-desc="{{ __('messages.team_member_4_desc') }}"
                         data-icon="fa-solid fa-palette"
                         data-initials="HA"
                         data-color="#10b981"
                         data-tags="Admin Web UI, Blade Templating, Responsive Layouts, JavaScript UX"
                         onclick="selectTeamMember(this)">
                        <div class="edubridge-member-icon-badge" style="background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: #34d399;">
                            <i class="fa-solid fa-palette"></i>
                            <span class="edubridge-initials-sub">HA</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>{{ __('messages.team_member_4_name') }}</h4>
                            <p class="edubridge-member-role">{{ __('messages.team_member_4_role') }}</p>
                        </div>
                        <div class="edubridge-active-pointer"></div>
                    </div>

                    <!-- Member 5: Shahd Zureki -->
                    <div class="edubridge-member-card" 
                         data-member="5" 
                         data-name="{{ __('messages.team_member_5_name') }}"
                         data-role="{{ __('messages.team_member_5_role') }}"
                         data-desc="{{ __('messages.team_member_5_desc') }}"
                         data-icon="fa-solid fa-database"
                         data-initials="SZ"
                         data-color="#eab308"
                         data-tags="Database Modeling, SQL Optimization, Relational Integrity, UI Design"
                         onclick="selectTeamMember(this)">
                        <div class="edubridge-member-icon-badge" style="background: rgba(234, 179, 8, 0.15); border-color: rgba(234, 179, 8, 0.3); color: #facc15;">
                            <i class="fa-solid fa-database"></i>
                            <span class="edubridge-initials-sub">SZ</span>
                        </div>
                        <div class="edubridge-member-info">
                            <h4>{{ __('messages.team_member_5_name') }}</h4>
                            <p class="edubridge-member-role">{{ __('messages.team_member_5_role') }}</p>
                        </div>
                        <div class="edubridge-active-pointer"></div>
                    </div>
                </div>

                <!-- Interactive Spotlight Detail Card -->
                <div class="edubridge-member-spotlight" id="memberSpotlight">
                    <div class="edubridge-spotlight-left">
                        <div class="edubridge-spotlight-avatar" id="spotlightAvatar" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24;">
                            <i class="fa-solid fa-server" id="spotlightIcon"></i>
                        </div>
                        <div>
                            <div class="edubridge-spotlight-header">
                                <h4 id="spotlightName">{{ __('messages.team_member_1_name') }}</h4>
                                <span class="edubridge-spotlight-badge" id="spotlightBadge">{{ __('messages.team_member_1_role') }}</span>
                            </div>
                            <p class="edubridge-spotlight-desc" id="spotlightDesc">
                                {{ __('messages.team_member_1_desc') }}
                            </p>
                        </div>
                    </div>
                    <div class="edubridge-spotlight-tags" id="spotlightTags">
                        <span class="edubridge-mini-tag"><i class="fa-solid fa-check-circle"></i> Laravel</span>
                        <span class="edubridge-mini-tag"><i class="fa-solid fa-check-circle"></i> System Architecture</span>
                        <span class="edubridge-mini-tag"><i class="fa-solid fa-check-circle"></i> REST APIs Integration</span>
                        <span class="edubridge-mini-tag"><i class="fa-solid fa-check-circle"></i> Security &amp; Auth</span>
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
                        <h3>{{ __('messages.about_privacy_title') }}</h3>
                        <p class="edubridge-privacy-lead">
                            {{ __('messages.about_privacy_lead') }}
                        </p>
                    </div>
                </div>

                <div class="edubridge-policy-sections">
                    <div class="edubridge-policy-card">
                        <div class="edubridge-policy-card-header">
                            <i class="fa-solid fa-key" style="color: #f59e0b;"></i>
                            <h4>{{ __('messages.about_privacy_sec1_title') }}</h4>
                        </div>
                        <p>
                            {{ __('messages.about_privacy_sec1_desc') }}
                        </p>
                    </div>

                    <div class="edubridge-policy-card">
                        <div class="edubridge-policy-card-header">
                            <i class="fa-solid fa-user-graduate" style="color: #10b981;"></i>
                            <h4>{{ __('messages.about_privacy_sec2_title') }}</h4>
                        </div>
                        <p>
                            {{ __('messages.about_privacy_sec2_desc') }}
                        </p>
                    </div>

                    <div class="edubridge-policy-card">
                        <div class="edubridge-policy-card-header">
                            <i class="fa-solid fa-face-smile" style="color: #3b82f6;"></i>
                            <h4>{{ __('messages.about_privacy_sec3_title') }}</h4>
                        </div>
                        <p>
                            {{ __('messages.about_privacy_sec3_desc') }}
                        </p>
                    </div>

                    <div class="edubridge-policy-card">
                        <div class="edubridge-policy-card-header">
                            <i class="fa-solid fa-handshake-slash" style="color: #ef4444;"></i>
                            <h4>{{ __('messages.about_privacy_sec4_title') }}</h4>
                        </div>
                        <p>
                            {{ __('messages.about_privacy_sec4_desc') }}
                        </p>
                    </div>
                </div>

                <div class="edubridge-compliance-badge">
                    <i class="fa-solid fa-certificate"></i>
                    <span>{{ __('messages.about_privacy_badge') }}</span>
                </div>
            </div>

            <!-- TAB 4: CONTACT & SUPPORT -->
            <div id="tab-contact" class="edubridge-tab-pane">
                <div class="edubridge-section-intro">
                    <div class="edubridge-badge-label">
                        <i class="fa-solid fa-headset"></i>
                        {{ __('messages.about_contact_badge') }}
                    </div>
                    <h3>{{ __('messages.about_contact_title') }}</h3>
                    <p>{{ __('messages.about_contact_desc') }}</p>
                </div>

                <div class="edubridge-contact-grid">
                    <!-- Email Card -->
                    <div class="edubridge-contact-card">
                        <div class="edubridge-contact-icon-box" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                            <i class="fa-solid fa-envelope-open-text"></i>
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <span class="edubridge-contact-type">{{ __('messages.about_contact_email_title') }}</span>
                            <div class="edubridge-contact-value">edubridge2006@gmail.com</div>
                            <p class="edubridge-contact-desc">{{ __('messages.about_contact_email_desc') }}</p>
                        </div>
                        <div class="edubridge-contact-action">
                            <a href="mailto:edubridge2006@gmail.com" class="edubridge-btn-action primary">
                                <i class="fa-solid fa-paper-plane"></i> {{ __('messages.about_contact_send_email') }}
                            </a>
                            <button type="button" class="edubridge-btn-action secondary" onclick="copyContactText('edubridge2006@gmail.com', this)">
                                <i class="fa-solid fa-copy"></i> <span>{{ __('messages.about_contact_copy') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- WhatsApp / Phone Card -->
                    <div class="edubridge-contact-card">
                        <div class="edubridge-contact-icon-box" style="background: rgba(34, 197, 94, 0.12); color: #22c55e;">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <span class="edubridge-contact-type">{{ __('messages.about_contact_whatsapp_title') }}</span>
                            <div class="edubridge-contact-value" dir="ltr">0959031594</div>
                            <p class="edubridge-contact-desc">{{ __('messages.about_contact_whatsapp_desc') }}</p>
                        </div>
                        <div class="edubridge-contact-action">
                            <a href="https://wa.me/963959031594" target="_blank" class="edubridge-btn-action whatsapp">
                                <i class="fa-brands fa-whatsapp"></i> {{ __('messages.about_contact_chat_whatsapp') }}
                            </a>
                            <button type="button" class="edubridge-btn-action secondary" onclick="copyContactText('0959031594', this)">
                                <i class="fa-solid fa-copy"></i> <span>{{ __('messages.about_contact_copy') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="edubridge-location-info">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>{{ __('messages.about_contact_location') }}</span>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="edubridge-modal-footer">
            <span class="edubridge-footer-copy">© {{ date('Y') }} Edu-Bridge Inc. {{ __('messages.about_footer_rights') }}</span>
            <button type="button" class="edubridge-btn-close-modal" onclick="closeEduBridgeModal()">
                {{ __('messages.about_btn_close_modal') }}
            </button>
        </div>
    </div>
</div>

<!-- Dedicated Ultra-Modern Theme-Responsive Styles -->
<style>
    :root {
        --eb-modal-bg: #ffffff;
        --eb-modal-text: #0f172a;
        --eb-modal-text-muted: #64748b;
        --eb-modal-border: #e2e8f0;
        --eb-modal-card-bg: #f8fafc;
        --eb-modal-card-border: #e2e8f0;
        --eb-modal-card-hover: #f1f5f9;
        --eb-modal-tab-bg: #f1f5f9;
        --eb-modal-tab-border: #e2e8f0;
        --eb-modal-tab-text: #475569;
        --eb-modal-tab-hover: #e2e8f0;
        --eb-modal-header-bg: linear-gradient(180deg, rgba(234, 179, 8, 0.08) 0%, transparent 100%);
        --eb-modal-footer-bg: #f8fafc;
        --eb-modal-close-bg: #f1f5f9;
        --eb-modal-close-border: #e2e8f0;
        --eb-modal-close-text: #64748b;
        --eb-modal-btn-sec-bg: #f1f5f9;
        --eb-modal-btn-sec-border: #cbd5e1;
        --eb-modal-btn-sec-text: #334155;
        --eb-modal-tag-bg: #f1f5f9;
        --eb-modal-tag-border: #e2e8f0;
        --eb-modal-tag-text: #475569;
        --eb-modal-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.3), 0 0 35px -10px rgba(234, 179, 8, 0.2);
    }

    html.dark, body.dark, body.dark-mode, .dark {
        --eb-modal-bg: #141416;
        --eb-modal-text: #f4f4f5;
        --eb-modal-text-muted: #a1a1aa;
        --eb-modal-border: rgba(255, 255, 255, 0.1);
        --eb-modal-card-bg: rgba(255, 255, 255, 0.03);
        --eb-modal-card-border: rgba(255, 255, 255, 0.08);
        --eb-modal-card-hover: rgba(255, 255, 255, 0.06);
        --eb-modal-tab-bg: rgba(255, 255, 255, 0.04);
        --eb-modal-tab-border: rgba(255, 255, 255, 0.06);
        --eb-modal-tab-text: #a1a1aa;
        --eb-modal-tab-hover: rgba(255, 255, 255, 0.08);
        --eb-modal-header-bg: linear-gradient(180deg, rgba(255, 204, 0, 0.04) 0%, transparent 100%);
        --eb-modal-footer-bg: rgba(0, 0, 0, 0.25);
        --eb-modal-close-bg: rgba(255, 255, 255, 0.06);
        --eb-modal-close-border: rgba(255, 255, 255, 0.1);
        --eb-modal-close-text: #a1a1aa;
        --eb-modal-btn-sec-bg: rgba(255, 255, 255, 0.08);
        --eb-modal-btn-sec-border: rgba(255, 255, 255, 0.12);
        --eb-modal-btn-sec-text: #e4e4e7;
        --eb-modal-tag-bg: rgba(255, 255, 255, 0.04);
        --eb-modal-tag-border: rgba(255, 255, 255, 0.08);
        --eb-modal-tag-text: #d4d4d8;
        --eb-modal-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.8), 0 0 50px -10px rgba(255, 204, 0, 0.15);
    }

    .edubridge-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 999999;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        font-family: inherit;
        animation: edubridgeFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .edubridge-modal-container {
        background: var(--eb-modal-bg);
        color: var(--eb-modal-text);
        border: 1px solid var(--eb-modal-border);
        border-radius: 28px;
        width: 100%;
        max-width: 820px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: var(--eb-modal-shadow);
        position: relative;
        overflow: hidden;
        animation: edubridgeZoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    /* Directional Close Button */
    [dir="rtl"] .edubridge-close-btn {
        left: 1.25rem;
        right: auto;
    }
    [dir="ltr"] .edubridge-close-btn {
        right: 1.25rem;
        left: auto;
    }

    .edubridge-close-btn {
        position: absolute;
        top: 1.25rem;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--eb-modal-close-bg);
        border: 1px solid var(--eb-modal-close-border);
        color: var(--eb-modal-close-text);
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
        border-bottom: 1px solid var(--eb-modal-border);
        background: var(--eb-modal-header-bg);
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
        color: var(--eb-modal-text);
    }

    .edubridge-app-subtitle {
        margin: 0.25rem 0 0;
        font-size: 0.9rem;
        color: var(--eb-modal-text-muted);
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
        background: var(--eb-modal-tab-bg);
        border: 1px solid var(--eb-modal-tab-border);
        color: var(--eb-modal-tab-text);
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
        background: var(--eb-modal-tab-hover);
        color: var(--eb-modal-text);
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
        background: rgba(148, 163, 184, 0.3);
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
        background: var(--eb-modal-card-bg);
        border: 1px solid var(--eb-modal-card-border);
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
        color: var(--eb-modal-text);
    }
    
    [dir="rtl"] .edubridge-quote-icon {
        left: 0;
        right: auto;
    }
    [dir="ltr"] .edubridge-quote-icon {
        right: 0;
        left: auto;
        transform: scaleX(-1);
    }
    .edubridge-quote-icon {
        position: absolute;
        top: -10px;
        font-size: 2.2rem;
        color: rgba(234, 179, 8, 0.15);
    }

    /* Features Grid */
    .edubridge-features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .edubridge-feature-item {
        background: var(--eb-modal-card-bg);
        border: 1px solid var(--eb-modal-card-border);
        border-radius: 18px;
        padding: 1.25rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        transition: transform 0.2s, background 0.2s, border-color 0.2s;
    }
    .edubridge-feature-item:hover {
        transform: translateY(-2px);
        background: var(--eb-modal-card-hover);
        border-color: rgba(234, 179, 8, 0.3);
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
        color: var(--eb-modal-text);
    }
    .edubridge-feature-item p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--eb-modal-text-muted);
        line-height: 1.5;
    }

    /* Tech Strip */
    .edubridge-tech-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: center;
        padding-top: 0.75rem;
        border-top: 1px solid var(--eb-modal-border);
    }
    .edubridge-tech-tag {
        background: var(--eb-modal-tag-bg);
        border: 1px solid var(--eb-modal-tag-border);
        border-radius: 20px;
        padding: 0.3rem 0.85rem;
        font-size: 0.78rem;
        color: var(--eb-modal-tag-text);
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 600;
    }
    .edubridge-tech-tag i {
        color: #eab308;
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
        background: rgba(234, 179, 8, 0.12);
        color: #ca8a04;
        border-radius: 20px;
        padding: 0.25rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    html.dark .edubridge-badge-label, body.dark .edubridge-badge-label, body.dark-mode .edubridge-badge-label, .dark .edubridge-badge-label {
        color: #facc15;
    }
    .edubridge-section-intro h3 {
        margin: 0.25rem 0 0.5rem;
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--eb-modal-text);
    }
    .edubridge-section-intro p {
        margin: 0 auto;
        max-width: 520px;
        font-size: 0.88rem;
        color: var(--eb-modal-text-muted);
    }

    /* Team Grid */
    .edubridge-team-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }

    .edubridge-member-card {
        background: var(--eb-modal-card-bg);
        border: 1px solid var(--eb-modal-card-border);
        border-radius: 20px;
        padding: 1.1rem 0.65rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.65rem;
        cursor: pointer;
        position: relative;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        user-select: none;
    }
    .edubridge-member-card:hover {
        transform: translateY(-4px);
        background: var(--eb-modal-card-hover);
        border-color: rgba(234, 179, 8, 0.5);
        box-shadow: 0 12px 25px -8px rgba(0, 0, 0, 0.25);
    }
    .edubridge-member-card.active-member {
        border: 2px solid #eab308 !important;
        background: rgba(234, 179, 8, 0.08);
        box-shadow: 0 0 25px rgba(234, 179, 8, 0.3), 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        transform: translateY(-4px);
    }
    .edubridge-member-card.active-member .edubridge-active-pointer {
        display: block;
    }
    .edubridge-active-pointer {
        display: none;
        position: absolute;
        bottom: -9px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-top: 8px solid #eab308;
    }
    .edubridge-member-icon-badge {
        width: 58px;
        height: 58px;
        border-radius: 18px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        border: 1px solid;
        flex-shrink: 0;
        gap: 2px;
        transition: transform 0.3s;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }
    .edubridge-member-card:hover .edubridge-member-icon-badge,
    .edubridge-member-card.active-member .edubridge-member-icon-badge {
        transform: scale(1.08);
    }
    .edubridge-initials-sub {
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        opacity: 0.85;
    }
    .edubridge-member-info h4 {
        margin: 0;
        font-size: 0.92rem;
        font-weight: 800;
        color: var(--eb-modal-text);
        letter-spacing: -0.2px;
        line-height: 1.3;
    }
    .edubridge-member-role {
        margin: 0.25rem 0 0;
        font-size: 0.72rem;
        color: var(--eb-modal-text-muted);
        line-height: 1.4;
        font-weight: 600;
    }

    /* Spotlight Showcase */
    .edubridge-member-spotlight {
        background: var(--eb-modal-card-bg);
        border: 1px solid var(--eb-modal-card-border);
        border-radius: 20px;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        flex-wrap: wrap;
        transition: all 0.3s ease;
        animation: edubridgeSpotlightIn 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .edubridge-spotlight-left {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1;
        min-width: 260px;
    }
    .edubridge-spotlight-avatar {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }
    .edubridge-spotlight-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 0.3rem;
    }
    .edubridge-spotlight-header h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--eb-modal-text);
    }
    .edubridge-spotlight-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        background: rgba(234, 179, 8, 0.12);
        color: #ca8a04;
        border: 1px solid rgba(234, 179, 8, 0.25);
    }
    html.dark .edubridge-spotlight-badge, body.dark .edubridge-spotlight-badge, body.dark-mode .edubridge-spotlight-badge, .dark .edubridge-spotlight-badge {
        color: #facc15;
    }
    .edubridge-spotlight-desc {
        margin: 0;
        font-size: 0.84rem;
        color: var(--eb-modal-text-muted);
        line-height: 1.5;
    }
    .edubridge-spotlight-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        max-width: 320px;
    }
    .edubridge-mini-tag {
        background: var(--eb-modal-tab-bg);
        border: 1px solid var(--eb-modal-tab-border);
        border-radius: 12px;
        padding: 0.25rem 0.6rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--eb-modal-text);
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: transform 0.2s;
    }
    .edubridge-mini-tag i {
        color: #10b981;
        font-size: 0.7rem;
    }
    @keyframes edubridgeSpotlightIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
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
        color: var(--eb-modal-text);
    }
    .edubridge-privacy-lead {
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.6;
        color: var(--eb-modal-text-muted);
    }
    html.dark .edubridge-privacy-lead, body.dark .edubridge-privacy-lead, body.dark-mode .edubridge-privacy-lead, .dark .edubridge-privacy-lead {
        color: #d1fae5;
    }

    .edubridge-policy-sections {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .edubridge-policy-card {
        background: var(--eb-modal-card-bg);
        border: 1px solid var(--eb-modal-card-border);
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        transition: background 0.2s;
    }
    .edubridge-policy-card:hover {
        background: var(--eb-modal-card-hover);
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
        color: var(--eb-modal-text);
    }
    .edubridge-policy-card p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--eb-modal-text-muted);
        line-height: 1.6;
    }

    .edubridge-compliance-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        background: var(--eb-modal-card-bg);
        border: 1px dashed var(--eb-modal-card-border);
        border-radius: 14px;
        padding: 0.75rem;
        font-size: 0.82rem;
        color: var(--eb-modal-text-muted);
        font-weight: 600;
        text-align: center;
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
        background: var(--eb-modal-card-bg);
        border: 1px solid var(--eb-modal-card-border);
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
        color: var(--eb-modal-text-muted);
        display: block;
        margin-bottom: 0.2rem;
    }
    .edubridge-contact-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--eb-modal-text);
        font-family: monospace, system-ui;
    }
    .edubridge-contact-desc {
        margin: 0.25rem 0 0;
        font-size: 0.82rem;
        color: var(--eb-modal-text-muted);
    }

    [dir="rtl"] .edubridge-contact-action {
        margin-right: auto;
        margin-left: 0;
    }
    [dir="ltr"] .edubridge-contact-action {
        margin-left: auto;
        margin-right: 0;
    }

    .edubridge-contact-action {
        display: flex;
        gap: 0.5rem;
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
        background: var(--eb-modal-btn-sec-bg);
        color: var(--eb-modal-btn-sec-text);
        border: 1px solid var(--eb-modal-btn-sec-border);
    }
    .edubridge-btn-action.secondary:hover {
        background: var(--eb-modal-tab-hover);
        color: var(--eb-modal-text);
    }

    .edubridge-location-info {
        text-align: center;
        font-size: 0.85rem;
        color: var(--eb-modal-text-muted);
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
        border-top: 1px solid var(--eb-modal-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--eb-modal-footer-bg);
    }
    .edubridge-footer-copy {
        font-size: 0.8rem;
        color: var(--eb-modal-text-muted);
    }
    .edubridge-btn-close-modal {
        background: var(--eb-modal-btn-sec-bg);
        color: var(--eb-modal-btn-sec-text);
        border: 1px solid var(--eb-modal-btn-sec-border);
        padding: 0.5rem 1.25rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    .edubridge-btn-close-modal:hover {
        background: var(--eb-modal-tab-hover);
        color: var(--eb-modal-text);
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

    /* Responsive Styles */
    @media (max-width: 640px) {
        .edubridge-modal-backdrop {
            padding: 0.75rem;
        }
        .edubridge-modal-container {
            max-height: 94vh;
            border-radius: 20px;
        }
        .edubridge-modal-header {
            padding: 1.25rem 1rem 0.75rem;
        }
        .edubridge-header-brand {
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .edubridge-logo-gem {
            width: 46px;
            height: 46px;
            font-size: 1.35rem;
            border-radius: 14px;
        }
        .edubridge-app-title {
            font-size: 1.25rem;
        }
        .edubridge-app-subtitle {
            font-size: 0.78rem;
        }
        .edubridge-close-btn {
            top: 0.85rem;
            width: 32px;
            height: 32px;
            font-size: 0.95rem;
        }
        [dir="rtl"] .edubridge-close-btn {
            left: 0.85rem;
            right: auto;
        }
        [dir="ltr"] .edubridge-close-btn {
            right: 0.85rem;
            left: auto;
        }
        .edubridge-tabs-nav {
            gap: 0.35rem;
            -webkit-overflow-scrolling: touch;
        }
        .edubridge-tab-btn {
            padding: 0.5rem 0.85rem;
            font-size: 0.8rem;
            border-radius: 10px;
        }
        .edubridge-modal-body {
            padding: 1rem;
        }
        .edubridge-features-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        .edubridge-team-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.65rem;
        }
        .edubridge-feature-item,
        .edubridge-member-card,
        .edubridge-contact-card,
        .edubridge-hero-card {
            padding: 0.85rem;
            border-radius: 16px;
        }
        .edubridge-member-spotlight {
            padding: 1rem;
            flex-direction: column;
            align-items: flex-start;
        }
        .edubridge-spotlight-tags {
            max-width: 100%;
        }
        .edubridge-privacy-hero {
            flex-direction: column;
            text-align: center;
            padding: 1.25rem 1rem;
        }
        [dir="rtl"] .edubridge-contact-action,
        [dir="ltr"] .edubridge-contact-action {
            margin: 0.75rem 0 0 0;
            width: 100%;
        }
        .edubridge-btn-action {
            flex: 1;
            justify-content: center;
            padding: 0.6rem 0.85rem;
            font-size: 0.82rem;
        }
        .edubridge-modal-footer {
            padding: 0.85rem 1rem;
            flex-direction: column-reverse;
            gap: 0.65rem;
            text-align: center;
        }
        .edubridge-btn-close-modal {
            width: 100%;
            padding: 0.6rem 1rem;
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

    function selectTeamMember(cardElement) {
        document.querySelectorAll('.edubridge-member-card').forEach(c => c.classList.remove('active-member'));
        cardElement.classList.add('active-member');

        const name = cardElement.getAttribute('data-name');
        const role = cardElement.getAttribute('data-role');
        const desc = cardElement.getAttribute('data-desc');
        const icon = cardElement.getAttribute('data-icon');
        const color = cardElement.getAttribute('data-color');
        const tagsStr = cardElement.getAttribute('data-tags');

        const spotlightName = document.getElementById('spotlightName');
        const spotlightBadge = document.getElementById('spotlightBadge');
        const spotlightDesc = document.getElementById('spotlightDesc');
        const spotlightIcon = document.getElementById('spotlightIcon');
        const spotlightAvatar = document.getElementById('spotlightAvatar');
        const spotlightTags = document.getElementById('spotlightTags');

        if (spotlightName) spotlightName.textContent = name;
        if (spotlightBadge) spotlightBadge.textContent = role;
        if (spotlightDesc) spotlightDesc.textContent = desc;
        if (spotlightIcon) spotlightIcon.className = icon;
        if (spotlightAvatar) {
            spotlightAvatar.style.background = color + '25';
            spotlightAvatar.style.color = color;
        }

        if (spotlightTags && tagsStr) {
            const tags = tagsStr.split(',').map(t => t.trim());
            spotlightTags.innerHTML = tags.map(t => `<span class="edubridge-mini-tag"><i class="fa-solid fa-check-circle"></i> ${t}</span>`).join('');
        }
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
        const copiedText = "{{ __('messages.about_contact_copied') }}";
        if (span) span.innerText = copiedText;
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
