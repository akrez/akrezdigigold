<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Shell</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
</head>

<body class="vh-100 overflow-hidden">

    <div x-data="commandRunner()" class="container-fluid h-100 d-flex flex-column p-3">

        <!-- 1. TOP PART: Input Group (Secret + RTL/LTR + Justify Text) -->
        <div class="mb-3 flex-shrink-0">
            <div class="input-group">
                <span class="input-group-text font-monospace">Secret</span>
                <input :type="showSecret ? 'text' : 'password'"
                       x-model="secret"
                       class="form-control font-monospace"
                       placeholder="Security key"
                       :disabled="loading"
                       autocomplete="off"
                       autofocus>
                <button class="btn btn-outline-secondary font-monospace"
                        type="button"
                        @click="showSecret = !showSecret"
                        x-text="showSecret ? 'Hide' : 'Show'">
                </button>

                <span class="input-group-text font-monospace">Dir</span>
                <select x-model="direction" class="form-select font-monospace" style="max-width: 95px;">
                    <option value="ltr">LTR</option>
                    <option value="rtl">RTL</option>
                </select>

                <div class="input-group-text">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="justifyToggle" x-model="isJustified">
                        <label class="form-check-label font-monospace small user-select-none" for="justifyToggle">Justify Text</label>
                    </div>
                </div>

                <button class="btn btn-outline-danger font-monospace" type="button" @click="clearHistory">
                    Clear
                </button>
            </div>
        </div>

        <!-- 2. MIDDLE PART: Shell Container with Command History & Responses -->
        <div class="card flex-grow-1 overflow-hidden mb-3 border-secondary">
            <!-- Terminal Header -->
            <div class="card-header bg-body-tertiary d-flex align-items-center justify-content-between py-2 border-secondary font-monospace small user-select-none flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-danger p-1"></span>
                    <span class="badge rounded-pill bg-warning p-1"></span>
                    <span class="badge rounded-pill bg-success p-1"></span>
                    <span class="ms-1 text-secondary">shell@terminal</span>
                </div>
                <div class="d-flex align-items-center gap-3 text-secondary">
                    <span x-show="loading" class="text-warning">executing...</span>
                    <span x-text="`${history.length} item${history.length === 1 ? '' : 's'}`"></span>
                </div>
            </div>

            <!-- Terminal Output History -->
            <div class="card-body overflow-auto p-3 font-monospace"
                 x-ref="terminalBody"
                 :dir="direction"
                 :style="isJustified ? 'text-align: justify;' : ''">

                <!-- Empty State -->
                <template x-if="history.length === 0">
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-secondary py-5 user-select-none">
                        <div class="fs-4 opacity-50 mb-2">&gt;_</div>
                        <div class="small">Terminal ready. Enter secret above and type a command below.</div>
                    </div>
                </template>

                <!-- History Records Loop -->
                <template x-for="(item, index) in history" :key="item.id">
                    <div class="card mb-3"
                         :class="{
                             'border-secondary': item.status === 'running',
                             'border-success': item.status === 'success',
                             'border-danger': item.status === 'error'
                         }">
                        <!-- Command Div -->
                        <div class="card-header d-flex align-items-center justify-content-between py-2 font-monospace"
                             :class="{
                                 'bg-body-secondary text-body': item.status === 'running',
                                 'bg-success-subtle text-success-emphasis': item.status === 'success',
                                 'bg-danger-subtle text-danger-emphasis': item.status === 'error'
                             }">
                            <div class="d-flex align-items-center gap-2 text-break flex-grow-1 me-2">
                                <span class="fw-bold"
                                      :class="{
                                          'text-secondary': item.status === 'running',
                                          'text-success': item.status === 'success',
                                          'text-danger': item.status === 'error'
                                      }">$</span>
                                <span class="fw-semibold" x-text="item.command"></span>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-shrink-0 small font-monospace">
                                <span class="text-secondary" x-text="item.timestamp"></span>
                                <template x-if="item.status === 'running'">
                                    <span class="badge bg-warning text-dark">running</span>
                                </template>
                                <template x-if="item.status === 'success'">
                                    <span class="badge bg-success">exit 0</span>
                                </template>
                                <template x-if="item.status === 'error'">
                                    <span class="badge bg-danger" x-text="item.resultCode !== null ? `exit ${item.resultCode}` : 'failed'"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Response Div -->
                        <div class="card-body p-3 font-monospace"
                             :class="{
                                 'bg-body-tertiary text-body-secondary': item.status === 'running',
                                 'bg-body-tertiary text-success': item.status === 'success',
                                 'bg-body-tertiary text-danger': item.status === 'error'
                             }">

                            <template x-if="item.status === 'running'">
                                <div class="d-flex align-items-center gap-2 text-secondary py-1">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                    <span>Executing command...</span>
                                </div>
                            </template>

                            <template x-if="item.status === 'success'">
                                <div>
                                    <template x-if="item.output && item.output.length > 0">
                                        <pre class="mb-0 font-monospace text-break text-success" x-text="item.output.join('\n')"></pre>
                                    </template>
                                    <template x-if="!item.output || item.output.length === 0">
                                        <span class="fst-italic text-secondary small">(Command completed with exit code 0 and no output)</span>
                                    </template>
                                </div>
                            </template>

                            <template x-if="item.status === 'error'">
                                <div>
                                    <template x-if="item.output && item.output.length > 0">
                                        <pre class="mb-0 font-monospace text-break text-danger" x-text="item.output.join('\n')"></pre>
                                    </template>
                                    <template x-if="item.error">
                                        <div class="text-danger fw-semibold" x-text="item.error"></div>
                                    </template>
                                    <template x-if="(!item.output || item.output.length === 0) && !item.error">
                                        <span class="fst-italic text-danger small" x-text="`Command failed with exit code ${item.resultCode}`"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 3. BOTTOM PART: Command Input and Submit -->
        <div class="flex-shrink-0">
            <form @submit.prevent="run">
                <div class="input-group">
                    <span class="input-group-text font-monospace fw-bold text-success">$</span>
                    <input type="text"
                           x-model="command"
                           x-ref="commandInput"
                           @keydown.up.prevent="historyUp"
                           @keydown.down.prevent="historyDown"
                           class="form-control font-monospace"
                           placeholder="Type a command and press Enter (e.g. dir, ls -la, git status, php artisan)..."
                           :disabled="loading"
                           autocomplete="off">
                    <button type="submit"
                            class="btn btn-primary font-monospace px-4"
                            :disabled="loading || !command.trim() || !secret.trim()">
                        <template x-if="!loading">
                            <span>Run</span>
                        </template>
                        <template x-if="loading">
                            <span class="d-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <span>Running...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </form>
        </div>

    </div>

    <script>
        const pageConfig = {
            commandUrl: @json(route('shell.store')),
            csrfToken: @json(csrf_token()),
        };

        function commandRunner() {
            return {
                secret: localStorage.getItem('shell_secret') || '',
                showSecret: false,
                direction: localStorage.getItem('shell_dir') || 'ltr',
                isJustified: localStorage.getItem('shell_justify') === '1',
                command: '',
                loading: false,
                history: [],
                cmdHistory: [],
                historyPointer: -1,
                commandDraft: '',

                init() {
                    this.$watch('secret', (val) => {
                        try { localStorage.setItem('shell_secret', val); } catch (e) {}
                    });
                    this.$watch('direction', (val) => {
                        try { localStorage.setItem('shell_dir', val); } catch (e) {}
                    });
                    this.$watch('isJustified', (val) => {
                        try { localStorage.setItem('shell_justify', val ? '1' : '0'); } catch (e) {}
                    });
                },

                scrollToBottom() {
                    const body = this.$refs.terminalBody;
                    if (body) {
                        body.scrollTop = body.scrollHeight;
                    }
                },

                focusInput() {
                    if (this.$refs.commandInput) {
                        this.$refs.commandInput.focus();
                    }
                },

                historyUp() {
                    if (this.cmdHistory.length === 0) return;
                    if (this.historyPointer === -1) {
                        this.commandDraft = this.command;
                        this.historyPointer = this.cmdHistory.length - 1;
                    } else if (this.historyPointer > 0) {
                        this.historyPointer--;
                    }
                    this.command = this.cmdHistory[this.historyPointer];
                },

                historyDown() {
                    if (this.historyPointer === -1) return;
                    if (this.historyPointer < this.cmdHistory.length - 1) {
                        this.historyPointer++;
                        this.command = this.cmdHistory[this.historyPointer];
                    } else {
                        this.historyPointer = -1;
                        this.command = this.commandDraft;
                    }
                },

                clearHistory() {
                    this.history = [];
                    this.focusInput();
                },

                async run() {
                    const secret = this.secret.trim();
                    const command = this.command.trim();

                    if (!secret || !command || this.loading) {
                        return;
                    }

                    // Handle client-side clear command
                    if (command.toLowerCase() === 'clear' || command.toLowerCase() === 'cls') {
                        this.cmdHistory.push(command);
                        this.historyPointer = -1;
                        this.commandDraft = '';
                        this.command = '';
                        this.clearHistory();
                        return;
                    }

                    this.cmdHistory.push(command);
                    this.historyPointer = -1;
                    this.commandDraft = '';
                    this.command = '';

                    const entry = {
                        id: Date.now() + Math.random(),
                        command: command,
                        timestamp: new Date().toLocaleTimeString(),
                        status: 'running',
                        resultCode: null,
                        output: [],
                        error: null,
                    };

                    this.history.push(entry);
                    this.loading = true;

                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });

                    try {
                        const response = await fetch(pageConfig.commandUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': pageConfig.csrfToken,
                            },
                            body: JSON.stringify({
                                secret: secret,
                                command: command,
                            }),
                        });

                        let data = null;
                        try {
                            data = await response.json();
                        } catch (e) {}

                        if (response.ok && data) {
                            entry.resultCode = data.resultCode;
                            entry.output = Array.isArray(data.output) ? data.output : [];
                            entry.status = (data.resultCode === 0) ? 'success' : 'error';
                        } else {
                            entry.status = 'error';
                            entry.resultCode = (data && data.resultCode !== undefined) ? data.resultCode : response.status;
                            if (data && data.output && data.output.length) {
                                entry.output = Array.isArray(data.output) ? data.output : [data.output];
                            } else if (data && data.message) {
                                entry.error = data.message;
                            } else {
                                entry.error = `HTTP ${response.status}: ${response.statusText || 'Request failed'}`;
                            }
                        }
                    } catch (error) {
                        entry.status = 'error';
                        entry.resultCode = -1;
                        entry.error = error.message || 'Network error occurred';
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
