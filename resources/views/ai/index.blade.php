<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>AI Assistant</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-Variable-font-face.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Vazirmatn', system-ui, -apple-system, sans-serif;
        }

        /* Custom scrollbar */
        .chat-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .chat-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        .chat-scroll::-webkit-scrollbar-thumb {
            background: rgba(125, 125, 125, 0.3);
            border-radius: 4px;
        }
        .chat-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(125, 125, 125, 0.5);
        }

        /* Prompt Textarea */
        .prompt-textarea {
            resize: none;
            min-height: 58px;
            max-height: 180px;
        }

        /* Distinct, high-contrast, beautiful neutral cards */
        .chat-card-user,
        .chat-card-ai {
            background-color: #1e222b !important;
            border: 1px solid #374151 !important;
            border-radius: 0.625rem !important;
        }
        [data-bs-theme="light"] .chat-card-user,
        [data-bs-theme="light"] .chat-card-ai {
            background-color: #f1f5f9 !important;
            border: 1px solid #cbd5e1 !important;
        }

        .chat-card-user .card-footer,
        .chat-card-ai .card-footer {
            background-color: rgba(0, 0, 0, 0.18) !important;
            border-top: 1px solid #374151 !important;
        }
        [data-bs-theme="light"] .chat-card-user .card-footer,
        [data-bs-theme="light"] .chat-card-ai .card-footer {
            background-color: rgba(0, 0, 0, 0.03) !important;
            border-top: 1px solid #cbd5e1 !important;
        }

        /* Desktop Sidebar & Cards Sizing */
        @media (min-width: 768px) {
            .settings-panel {
                width: 340px !important;
                min-width: 340px !important;
                max-width: 340px !important;
                position: static !important;
                display: flex !important;
            }

            .chat-card-user,
            .chat-card-ai {
                max-width: 82% !important;
                margin: 0 !important;
            }
        }

        /* Mobile styles: full width cards without margins */
        @media (max-width: 767.98px) {
            .chat-card-user,
            .chat-card-ai {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
            }

            .settings-panel.mobile-open {
                display: flex !important;
                position: fixed !important;
                top: 0;
                left: 0;
                width: 100% !important;
                height: 100% !important;
                z-index: 1050;
            }

            .settings-panel:not(.mobile-open) {
                display: none !important;
            }
        }

        .chat-message-content {
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.7;
        }
    </style>
</head>

<body class="vh-100 overflow-hidden font-monospace">

    <div x-data="aiChatRunner()" class="h-100 d-flex flex-column">

        <!-- Mobile Top Bar (Visible only on mobile screens < md) -->
        <div class="d-md-none flex-shrink-0 d-flex justify-content-between align-items-center px-3 py-2 bg-body-tertiary border-bottom border-secondary-subtle">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold">AI Assistant</span>
                <span class="small text-secondary text-truncate" style="max-width: 130px;" x-text="model || 'No model'"></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Theme Toggle Button -->
                <button type="button"
                        class="btn btn-sm btn-outline-secondary px-2"
                        @click="toggleTheme"
                        :title="theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                    <span x-show="theme === 'dark'">☀️</span>
                    <span x-show="theme === 'light'">🌙</span>
                </button>
                <!-- Mobile Settings Toggle -->
                <button type="button"
                        class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                        @click="mobileSettingsOpen = true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
                        <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.185 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.185l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.185-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.185l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
                    </svg>
                    <span>Settings</span>
                </button>
            </div>
        </div>

        <!-- Main Body Row (Enforced LTR container so Left is Chat, Right is Settings) -->
        <div class="flex-grow-1 d-flex flex-column flex-md-row overflow-hidden" dir="ltr">

            <!-- ========================================== -->
            <!-- LEFT PANEL: Chat History & Bottom Input    -->
            <!-- ========================================== -->
            <div class="flex-grow-1 d-flex flex-column h-100 overflow-hidden order-1">

                <!-- 1. Top Chat History Section (Scrollable, with active direction) -->
                <div class="flex-grow-1 overflow-auto chat-scroll p-3 bg-body"
                     x-ref="chatBody"
                     :dir="direction"
                     :style="{ textAlign: textAlign, direction: direction }">

                    <!-- Empty State -->
                    <template x-if="messages.length === 0">
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-secondary py-5 user-select-none">
                            <div class="fs-2 opacity-50 mb-2">💬</div>
                            <div class="fs-5 fw-bold mb-1">AI Assistant Ready</div>
                            <div class="small text-secondary mb-3">Configure settings on the right and start typing your message below.</div>
                            <div class="small text-secondary border border-secondary-subtle rounded px-3 py-1">
                                Press Enter to send, Shift + Enter for new line
                            </div>
                        </div>
                    </template>

                    <!-- Messages List from unified array -->
                    <template x-for="(msg, index) in messages" :key="msg.id">
                        <!-- Wrapper dynamically respects LTR/RTL so User is always on right, AI always on left -->
                        <div class="w-100 d-flex mb-3"
                             :class="{
                                 'justify-content-start': (msg.type === 'request' && direction === 'rtl') || (msg.type === 'response' && direction === 'ltr'),
                                 'justify-content-end': (msg.type === 'request' && direction === 'ltr') || (msg.type === 'response' && direction === 'rtl')
                             }">

                            <!-- Request Message Card (User) -->
                            <template x-if="msg.type === 'request'">
                                <div class="card chat-card-user shadow-sm">
                                    <div class="card-body p-3"
                                         :dir="direction"
                                         :style="{ textAlign: textAlign, direction: direction }">
                                        <div class="chat-message-content"
                                             :style="{ textAlign: textAlign, direction: direction }"
                                             x-text="msg.text"></div>
                                    </div>
                                    <div class="card-footer py-1 px-3 d-flex justify-content-end text-secondary small"
                                         x-text="msg.timestamp">
                                    </div>
                                </div>
                            </template>

                            <!-- Response Message Card (AI) -->
                            <template x-if="msg.type === 'response'">
                                <div class="card chat-card-ai shadow-sm">

                                    <!-- Response Body Content -->
                                    <div class="card-body p-3"
                                         :dir="direction"
                                         :style="{ textAlign: textAlign, direction: direction }">
                                        <div class="chat-message-content"
                                             :class="{ 'text-danger': msg.status === 'error' }"
                                             :style="{ textAlign: textAlign, direction: direction }"
                                             x-text="msg.text"></div>
                                    </div>

                                    <!-- Response Footer: Model Name and Timestamp -->
                                    <div class="card-footer py-1 px-3 d-flex justify-content-between align-items-center text-secondary small">
                                        <span x-text="msg.model"></span>
                                        <span x-text="msg.timestamp"></span>
                                    </div>

                                </div>
                            </template>

                        </div>
                    </template>
                </div>

                <!-- 2. Bottom Pinned Input Bar -->
                <div class="flex-shrink-0 bg-body-tertiary border-top border-secondary-subtle p-3">
                    <form @submit.prevent="run">
                        <div class="d-flex flex-column gap-2">
                            <div class="input-group">
                                <textarea x-model="prompt"
                                          x-ref="promptInput"
                                          @keydown="handleKeydown($event)"
                                          class="form-control prompt-textarea font-monospace"
                                          rows="2"
                                          placeholder=""
                                          :disabled="loading"
                                          :dir="direction"
                                          :style="{ textAlign: textAlign, direction: direction }"
                                          autofocus></textarea>
                                <button type="submit"
                                        class="btn btn-primary d-flex flex-column align-items-center justify-content-center px-4"
                                        :disabled="loading || !isReadyToSubmit">
                                    <template x-if="loading">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                            <span>Sending</span>
                                        </div>
                                    </template>
                                    <template x-if="!loading">
                                        <div class="d-flex align-items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576zm6.787-8.201L1.591 6.602l4.339 2.76z"/>
                                            </svg>
                                            <span class="fw-bold">Send</span>
                                        </div>
                                    </template>
                                </button>
                            </div>

                            <div class="d-flex justify-content-between align-items-center small text-secondary px-1">
                                <span>Press <kbd class="bg-secondary-subtle text-secondary-emphasis">Enter</kbd> to send, <kbd class="bg-secondary-subtle text-secondary-emphasis">Shift + Enter</kbd> for newline</span>
                                <template x-if="!isConfigValid">
                                    <span class="text-warning">⚠️ Please complete settings in the right panel</span>
                                </template>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- RIGHT PANEL: Settings Menu                 -->
            <!-- Desktop: Fixed width, right side, no scroll-->
            <!-- Mobile: Full screen collapsible at top    -->
            <!-- ========================================== -->
            <div class="settings-panel bg-body-tertiary border-start border-secondary-subtle d-flex flex-column order-2 p-3 overflow-hidden"
                 :class="{ 'mobile-open': mobileSettingsOpen }">

                <!-- Settings Header -->
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary-subtle flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="text-primary" viewBox="0 0 16 16">
                            <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
                            <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.185 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.185l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.185-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.185l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
                        </svg>
                        <h6 class="mb-0 fw-bold">AI Settings</h6>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Theme Toggle Button -->
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                @click="toggleTheme"
                                :title="theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                            <span x-show="theme === 'dark'">☀️</span>
                            <span x-show="theme === 'light'">🌙</span>
                            <span class="small" x-text="theme === 'dark' ? 'Light' : 'Dark'"></span>
                        </button>

                        <!-- Mobile Close / Collapse Button -->
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary d-md-none"
                                @click="mobileSettingsOpen = false">
                            <span>Done ✕</span>
                        </button>
                    </div>
                </div>

                <!-- Settings Form Fields (No scroll on desktop) -->
                <div class="flex-grow-1 d-flex flex-column gap-3 justify-content-between">
                    <div>
                        <!-- Secret Key -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary mb-1">Secret Key <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input :type="showSecret ? 'text' : 'password'"
                                       x-model="secret"
                                       class="form-control"
                                       placeholder=""
                                       :disabled="loading"
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary"
                                        type="button"
                                        @click="showSecret = !showSecret"
                                        x-text="showSecret ? 'Hide' : 'Show'">
                                </button>
                            </div>
                        </div>

                        <!-- API Base URL -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary mb-1">API Base URL <span class="text-danger">*</span></label>
                            <input type="url"
                                   x-model="apiBaseUrl"
                                   class="form-control form-control-sm"
                                   placeholder=""
                                   :disabled="loading"
                                   autocomplete="off">
                        </div>

                        <!-- API Key -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary mb-1">API Key <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input :type="showApiKey ? 'text' : 'password'"
                                       x-model="apiKey"
                                       class="form-control"
                                       placeholder=""
                                       :disabled="loading"
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary"
                                        type="button"
                                        @click="showApiKey = !showApiKey"
                                        x-text="showApiKey ? 'Hide' : 'Show'">
                                </button>
                            </div>
                        </div>

                        <!-- Model Name -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary mb-1">Model Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   x-model="model"
                                   class="form-control form-control-sm"
                                   placeholder=""
                                   :disabled="loading"
                                   autocomplete="off">
                        </div>

                        <!-- View Controls (Direction & Align) -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary mb-1">Conversation Direction & Align</label>
                            <div class="d-flex gap-2">
                                <!-- Direction -->
                                <div class="btn-group btn-group-sm flex-grow-1" role="group">
                                    <button type="button"
                                            class="btn"
                                            :class="direction === 'rtl' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="setDirection('rtl')">
                                        RTL
                                    </button>
                                    <button type="button"
                                            class="btn"
                                            :class="direction === 'ltr' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="setDirection('ltr')">
                                        LTR
                                    </button>
                                </div>
                                <!-- Alignment -->
                                <div class="btn-group btn-group-sm flex-grow-1" role="group">
                                    <button type="button"
                                            class="btn"
                                            :class="textAlign === 'left' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="setTextAlign('left')">
                                        Left
                                    </button>
                                    <button type="button"
                                            class="btn"
                                            :class="textAlign === 'center' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="setTextAlign('center')">
                                        Center
                                    </button>
                                    <button type="button"
                                            class="btn"
                                            :class="textAlign === 'right' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="setTextAlign('right')">
                                        Right
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Bottom -->
                    <div class="pt-3 border-top border-secondary-subtle flex-shrink-0">
                        <button class="btn btn-sm btn-outline-danger w-100"
                                type="button"
                                :disabled="messages.length === 0"
                                @click="clearHistory">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                            </svg>
                            <span>Clear Conversation</span>
                        </button>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <script>
        const pageConfig = {
            storeUrl: @json(route('ai.store')),
            csrfToken: @json(csrf_token()),
        };

        function aiChatRunner() {
            return {
                theme: localStorage.getItem('ai_theme') || 'dark',
                secret: localStorage.getItem('ai_secret') || '',
                showSecret: false,
                apiBaseUrl: localStorage.getItem('ai_base_url') || '',
                apiKey: localStorage.getItem('ai_api_key') || '',
                showApiKey: false,
                model: localStorage.getItem('ai_model') || '',
                direction: localStorage.getItem('ai_dir') || 'rtl',
                textAlign: localStorage.getItem('ai_align') || 'right',
                prompt: '',
                loading: false,
                mobileSettingsOpen: false,
                messages: [],

                init() {
                    // Set initial theme
                    document.documentElement.setAttribute('data-bs-theme', this.theme);

                    this.$watch('theme', (val) => {
                        try { localStorage.setItem('ai_theme', val); } catch (e) {}
                        document.documentElement.setAttribute('data-bs-theme', val);
                    });
                    this.$watch('secret', (val) => {
                        try { localStorage.setItem('ai_secret', val); } catch (e) {}
                    });
                    this.$watch('apiBaseUrl', (val) => {
                        try { localStorage.setItem('ai_base_url', val); } catch (e) {}
                    });
                    this.$watch('apiKey', (val) => {
                        try { localStorage.setItem('ai_api_key', val); } catch (e) {}
                    });
                    this.$watch('model', (val) => {
                        try { localStorage.setItem('ai_model', val); } catch (e) {}
                    });
                    this.$watch('direction', (val) => {
                        try { localStorage.setItem('ai_dir', val); } catch (e) {}
                    });
                    this.$watch('textAlign', (val) => {
                        try { localStorage.setItem('ai_align', val); } catch (e) {}
                    });

                    // Auto close mobile settings when resized to desktop
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 768) {
                            this.mobileSettingsOpen = false;
                        }
                    });
                },

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                },

                setDirection(dir) {
                    this.direction = dir;
                },

                setTextAlign(align) {
                    this.textAlign = align;
                },

                get isConfigValid() {
                    return !!(this.secret.trim() && this.apiBaseUrl.trim() && this.apiKey.trim() && this.model.trim());
                },

                get isReadyToSubmit() {
                    return this.isConfigValid && !!this.prompt.trim();
                },

                handleKeydown(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.run();
                    }
                },

                scrollToBottom() {
                    const body = this.$refs.chatBody;
                    if (body) {
                        body.scrollTop = body.scrollHeight;
                    }
                },

                focusInput() {
                    if (this.$refs.promptInput) {
                        this.$refs.promptInput.focus();
                    }
                },

                clearHistory() {
                    this.messages = [];
                    this.focusInput();
                },

                async run() {
                    const secret = this.secret.trim();
                    const apiBaseUrl = this.apiBaseUrl.trim();
                    const apiKey = this.apiKey.trim();
                    const model = this.model.trim();
                    const prompt = this.prompt.trim();

                    if (!secret || !apiBaseUrl || !apiKey || !model || !prompt || this.loading) {
                        return;
                    }

                    this.prompt = '';

                    // 1. Add request message to unified array immediately
                    this.messages.push({
                        id: Date.now() + Math.random(),
                        type: 'request',
                        text: prompt,
                        timestamp: new Date().toLocaleTimeString(),
                    });

                    this.loading = true;

                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });

                    try {
                        const response = await fetch(pageConfig.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': pageConfig.csrfToken,
                            },
                            body: JSON.stringify({
                                secret: secret,
                                api_base_url: apiBaseUrl,
                                api_key: apiKey,
                                model: model,
                                prompt: prompt,
                            }),
                        });

                        let data = null;
                        try {
                            data = await response.json();
                        } catch (e) {}

                        let isSuccess = false;
                        let output = [];

                        if (data && typeof data.isSuccess === 'boolean') {
                            isSuccess = data.isSuccess;
                            output = Array.isArray(data.output) ? data.output : (data.output ? [data.output] : []);
                        } else if (response.ok && data) {
                            isSuccess = true;
                            output = Array.isArray(data.output) ? data.output : [];
                        } else if (data && data.errors) {
                            output = Object.values(data.errors).flat();
                        } else if (data && data.message) {
                            output = [data.message];
                        } else if (response.status === 403) {
                            output = ['Forbidden: Invalid secret'];
                        } else {
                            output = [`HTTP ${response.status}: ${response.statusText || 'Request failed'}`];
                        }

                        // 2. Add response message to unified array when returned
                        const responseText = output.length > 0
                            ? output.join('\n')
                            : (isSuccess ? '(Empty response)' : 'An error occurred during execution');

                        this.messages.push({
                            id: Date.now() + Math.random(),
                            type: 'response',
                            model: model,
                            text: responseText,
                            status: isSuccess ? 'success' : 'error',
                            timestamp: new Date().toLocaleTimeString(),
                        });

                    } catch (error) {
                        this.messages.push({
                            id: Date.now() + Math.random(),
                            type: 'response',
                            model: model,
                            text: error.message || 'Network error occurred',
                            status: 'error',
                            timestamp: new Date().toLocaleTimeString(),
                        });
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            this.focusInput();
                        });
                    }
                },
            };
        }
    </script>

</body>

</html>
