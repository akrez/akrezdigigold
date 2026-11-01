<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>AI Assistant</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

    <style>
        /* Custom scrollbar */
        .chat-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .chat-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        .chat-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        .chat-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        /* Message styling */
        .message-bubble-user {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.75rem;
        }
        .message-bubble-ai {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 0.75rem;
        }

        .prompt-textarea {
            resize: none;
            min-height: 58px;
            max-height: 180px;
        }

        /* Settings sidebar fixed width on desktop */
        @media (min-width: 768px) {
            .settings-panel {
                width: 340px;
                min-width: 340px;
                max-width: 340px;
            }
        }
    </style>
</head>

<body class="vh-100 overflow-hidden font-monospace">

    <div x-data="aiChatRunner()" class="h-100 d-flex flex-column">

        <!-- Mobile Top Bar (Visible only on mobile screens < md) -->
        <div class="d-md-none flex-shrink-0 d-flex justify-content-between align-items-center px-3 py-2 bg-black border-bottom border-secondary">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-light">AI Assistant</span>
                <span class="badge bg-secondary text-truncate" style="max-width: 140px;" x-text="model || 'No model'"></span>
            </div>
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

        <!-- Main Body Row (Left Chat, Right Settings) -->
        <div class="flex-grow-1 d-flex flex-column flex-md-row overflow-hidden">

            <!-- ========================================== -->
            <!-- LEFT PANEL: Chat History & Bottom Input    -->
            <!-- ========================================== -->
            <div class="flex-grow-1 d-flex flex-column h-100 overflow-hidden order-1">

                <!-- 1. Top Chat History Section (Scrollable) -->
                <div class="flex-grow-1 overflow-auto chat-scroll bg-black p-3"
                     x-ref="chatBody"
                     :dir="direction"
                     :style="{ textAlign: textAlign }">

                    <!-- Empty State -->
                    <template x-if="history.length === 0">
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-secondary py-5 user-select-none">
                            <div class="fs-2 opacity-50 mb-2">💬</div>
                            <div class="fs-5 fw-bold text-light mb-1">AI Assistant Ready</div>
                            <div class="small text-secondary mb-3">Configure settings on the right and start typing your message below.</div>
                            <div class="badge bg-dark border border-secondary text-secondary font-monospace">
                                Press Enter to send, Shift + Enter for new line
                            </div>
                        </div>
                    </template>

                    <!-- Messages List -->
                    <template x-for="(item, index) in history" :key="item.id">
                        <div class="w-100 mb-4">

                            <!-- User Prompt Message -->
                            <div class="d-flex flex-column mb-2 align-items-start">
                                <div class="message-bubble-user p-3 w-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-secondary border-opacity-25">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary">You</span>
                                            <span class="text-secondary small" x-text="item.timestamp"></span>
                                        </div>
                                    </div>
                                    <div class="text-light text-break" style="white-space: pre-wrap;" x-text="item.prompt"></div>
                                </div>
                            </div>

                            <!-- AI Response Message -->
                            <div class="d-flex flex-column align-items-start">
                                <div class="message-bubble-ai p-3 w-100"
                                     :class="{
                                         'border-warning': item.status === 'running',
                                         'border-danger': item.status === 'error',
                                         'border-secondary': item.status === 'success'
                                     }">

                                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-secondary border-opacity-25">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-info text-dark">AI</span>
                                            <span class="text-secondary small" x-text="item.model"></span>
                                        </div>
                                        <div>
                                            <template x-if="item.status === 'running'">
                                                <span class="badge bg-warning text-dark d-flex align-items-center gap-1">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    <span>Generating...</span>
                                                </span>
                                            </template>
                                            <template x-if="item.status === 'success'">
                                                <span class="badge bg-success">Success</span>
                                            </template>
                                            <template x-if="item.status === 'error'">
                                                <span class="badge bg-danger" x-text="item.resultCode !== null ? `exit ${item.resultCode}` : 'failed'"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Running State -->
                                    <template x-if="item.status === 'running'">
                                        <div class="text-secondary py-2 d-flex align-items-center gap-2">
                                            <div class="spinner-grow spinner-grow-sm text-info" role="status"></div>
                                            <span>Processing prompt with model...</span>
                                        </div>
                                    </template>

                                    <!-- Success State -->
                                    <template x-if="item.status === 'success'">
                                        <div>
                                            <template x-if="item.output && item.output.length > 0">
                                                <pre class="mb-0 text-break text-light"
                                                     :style="{ textAlign: textAlign, whiteSpace: 'pre-wrap' }"
                                                     x-text="item.output.join('\n')"></pre>
                                            </template>
                                            <template x-if="!item.output || item.output.length === 0">
                                                <span class="fst-italic text-secondary small">(Completed with exit code 0 and no output)</span>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Error State -->
                                    <template x-if="item.status === 'error'">
                                        <div>
                                            <template x-if="item.output && item.output.length > 0">
                                                <pre class="mb-2 text-break text-danger"
                                                     :style="{ textAlign: textAlign, whiteSpace: 'pre-wrap' }"
                                                     x-text="item.output.join('\n')"></pre>
                                            </template>
                                            <template x-if="item.error">
                                                <div class="alert alert-danger py-2 px-3 mb-0 text-break small" x-text="item.error"></div>
                                            </template>
                                            <template x-if="(!item.output || item.output.length === 0) && !item.error">
                                                <div class="text-danger small fst-italic" x-text="`Command failed with exit code ${item.resultCode}`"></div>
                                            </template>
                                        </div>
                                    </template>

                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- 2. Bottom Pinned Input Bar -->
                <div class="flex-shrink-0 bg-dark border-top border-secondary p-3">
                    <form @submit.prevent="run">
                        <div class="d-flex flex-column gap-2">
                            <div class="input-group">
                                <textarea x-model="prompt"
                                          x-ref="promptInput"
                                          @keydown="handleKeydown($event)"
                                          class="form-control prompt-textarea font-monospace"
                                          rows="2"
                                          placeholder="Type your prompt here... (Enter to send, Shift+Enter for newline)"
                                          :disabled="loading"
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
                                <span>Press <kbd class="bg-secondary text-light">Enter</kbd> to send, <kbd class="bg-secondary text-light">Shift + Enter</kbd> for new line</span>
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
            <!-- Desktop: Fixed width, no scroll           -->
            <!-- Mobile: Full screen collapsible at top    -->
            <!-- ========================================== -->
            <div class="settings-panel bg-dark border-start border-secondary d-flex flex-column order-2"
                 :class="{
                     'd-flex position-fixed top-0 start-0 w-100 h-100 z-3 p-3 overflow-auto': mobileSettingsOpen,
                     'd-none d-md-flex p-3 overflow-hidden': !mobileSettingsOpen
                 }">

                <!-- Settings Header -->
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="text-primary" viewBox="0 0 16 16">
                            <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
                            <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.185 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.185l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.185-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.185l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
                        </svg>
                        <h6 class="mb-0 fw-bold text-light">AI Settings</h6>
                    </div>

                    <!-- Mobile Close / Collapse Button -->
                    <button type="button"
                            class="btn btn-sm btn-outline-light d-md-none"
                            @click="mobileSettingsOpen = false">
                        <span>Done ✕</span>
                    </button>
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
                                       placeholder="App shell secret"
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
                                   placeholder="https://api.openai.com/v1"
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
                                       placeholder="sk-..."
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
                                   placeholder="gpt-4o, gpt-4o-mini..."
                                   :disabled="loading"
                                   autocomplete="off">
                        </div>

                        <!-- View Controls (Direction & Align) -->
                        <div class="mb-3">
                            <label class="form-label small text-secondary mb-1">Text Direction & Align</label>
                            <div class="d-flex gap-2">
                                <!-- Direction -->
                                <div class="btn-group btn-group-sm flex-grow-1" role="group">
                                    <button type="button"
                                            class="btn"
                                            :class="direction === 'ltr' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="direction = 'ltr'">
                                        LTR
                                    </button>
                                    <button type="button"
                                            class="btn"
                                            :class="direction === 'rtl' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="direction = 'rtl'">
                                        RTL
                                    </button>
                                </div>
                                <!-- Alignment -->
                                <div class="btn-group btn-group-sm flex-grow-1" role="group">
                                    <button type="button"
                                            class="btn"
                                            :class="textAlign === 'left' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="textAlign = 'left'">
                                        Left
                                    </button>
                                    <button type="button"
                                            class="btn"
                                            :class="textAlign === 'center' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="textAlign = 'center'">
                                        Center
                                    </button>
                                    <button type="button"
                                            class="btn"
                                            :class="textAlign === 'right' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="textAlign = 'right'">
                                        Right
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Bottom -->
                    <div class="pt-3 border-top border-secondary flex-shrink-0">
                        <button class="btn btn-sm btn-outline-danger w-100"
                                type="button"
                                :disabled="history.length === 0"
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
                secret: localStorage.getItem('ai_secret') || '',
                showSecret: false,
                apiBaseUrl: localStorage.getItem('ai_base_url') || 'https://api.openai.com/v1',
                apiKey: localStorage.getItem('ai_api_key') || '',
                showApiKey: false,
                model: localStorage.getItem('ai_model') || 'gpt-4o',
                direction: localStorage.getItem('ai_dir') || 'ltr',
                textAlign: localStorage.getItem('ai_align') || 'left',
                prompt: '',
                loading: false,
                mobileSettingsOpen: false,
                history: [],

                init() {
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
                    this.history = [];
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

                    const id = Date.now() + Math.random();
                    this.history = [
                        ...this.history,
                        {
                            id: id,
                            prompt: prompt,
                            model: model,
                            timestamp: new Date().toLocaleTimeString(),
                            status: 'running',
                            resultCode: null,
                            output: [],
                            error: null,
                        }
                    ];
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
                        let resultCode = null;
                        let output = [];
                        let errorMsg = null;

                        if (response.ok && data) {
                            resultCode = data.resultCode;
                            output = Array.isArray(data.output) ? data.output : [];
                            isSuccess = (data.isSuccess === true || data.resultCode === 0);
                            if (!isSuccess && output.length === 0) {
                                errorMsg = `AI process exited with code ${data.resultCode}`;
                            }
                        } else {
                            resultCode = (data && data.resultCode !== undefined) ? data.resultCode : response.status;
                            if (data && data.output && data.output.length) {
                                output = Array.isArray(data.output) ? data.output : [data.output];
                            } else if (data && data.errors) {
                                errorMsg = Object.values(data.errors).flat().join('\n');
                            } else if (data && data.message) {
                                errorMsg = data.message;
                            } else if (response.status === 403) {
                                errorMsg = 'Forbidden: Invalid security key or secret not configured in .env';
                            } else if (response.status === 408) {
                                errorMsg = 'Timeout: The AI process took too long to respond.';
                            } else {
                                errorMsg = `HTTP ${response.status}: ${response.statusText || 'Request failed'}`;
                            }
                        }

                        this.history = this.history.map(item => {
                            if (item.id === id) {
                                return {
                                    ...item,
                                    status: isSuccess ? 'success' : 'error',
                                    resultCode: resultCode,
                                    output: output,
                                    error: errorMsg,
                                };
                            }
                            return item;
                        });
                    } catch (error) {
                        this.history = this.history.map(item => {
                            if (item.id === id) {
                                return {
                                    ...item,
                                    status: 'error',
                                    resultCode: -1,
                                    output: [],
                                    error: error.message || 'Network error occurred',
                                };
                            }
                            return item;
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
