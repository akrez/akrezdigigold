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

        <!-- 1. TOP PART: Button Toolbar (Secret Group + LTR/RTL Group + 3 Text-Align Buttons + Clear) -->
        <div class="btn-toolbar gap-2 mb-3 flex-shrink-0 align-items-center" role="toolbar" aria-label="Shell Toolbar">
            <!-- Secret Input Group -->
            <div class="input-group flex-grow-1" style="min-width: 250px;">
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
            </div>

            <!-- Direction Group (LTR / RTL) -->
            <div class="btn-group" role="group" aria-label="Text Direction">
                <button type="button"
                        class="btn font-monospace"
                        :class="direction === 'ltr' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="direction = 'ltr'">
                    LTR
                </button>
                <button type="button"
                        class="btn font-monospace"
                        :class="direction === 'rtl' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="direction = 'rtl'">
                    RTL
                </button>
            </div>

            <!-- Text Align Group (Left / Center / Right) -->
            <div class="btn-group" role="group" aria-label="Text Alignment">
                <button type="button"
                        class="btn font-monospace"
                        :class="textAlign === 'left' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="textAlign = 'left'">
                    Left
                </button>
                <button type="button"
                        class="btn font-monospace"
                        :class="textAlign === 'center' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="textAlign = 'center'">
                    Center
                </button>
                <button type="button"
                        class="btn font-monospace"
                        :class="textAlign === 'right' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="textAlign = 'right'">
                    Right
                </button>
            </div>

            <!-- Clear Action -->
            <div class="btn-group" role="group" aria-label="Toolbar Actions">
                <button class="btn btn-outline-danger font-monospace" type="button" @click="clearHistory">
                    Clear
                </button>
            </div>
        </div>

        <!-- 2. MIDDLE PART: Shell Output History (div.w-100 per command & response) -->
        <div class="flex-grow-1 overflow-auto bg-black border border-secondary rounded p-3 font-monospace mb-3"
             x-ref="terminalBody"
             :dir="direction"
             :style="{ textAlign: textAlign }">

            <!-- Empty State -->
            <template x-if="history.length === 0">
                <div class="h-100 d-flex flex-column align-items-center justify-content-center text-secondary py-5 user-select-none">
                    <div class="fs-4 opacity-50 mb-2">&gt;_</div>
                    <div class="small">Terminal ready. Enter secret above and type a command below.</div>
                </div>
            </template>

            <!-- Command & Response List -->
            <template x-for="(item, index) in history" :key="item.id">
                <div class="w-100 mb-3">
                    <!-- Command Div (div.w-100) -->
                    <div class="w-100 d-flex align-items-center justify-content-between font-monospace"
                         :class="{
                             'text-warning': item.status === 'running',
                             'text-success': item.status === 'success',
                             'text-danger': item.status === 'error'
                         }">
                        <div class="d-flex align-items-center gap-2 text-break flex-grow-1 me-2">
                            <span class="fw-bold">$</span>
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

                    <!-- Response Div (under the command div) -->
                    <div class="w-100 mt-1 font-monospace"
                         :class="{
                             'text-secondary': item.status === 'running',
                             'text-light': item.status === 'success',
                             'text-danger': item.status === 'error'
                         }"
                         :style="{ textAlign: textAlign }">

                        <!-- Running state -->
                        <template x-if="item.status === 'running'">
                            <div class="text-secondary py-1">
                                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <span>Executing command...</span>
                            </div>
                        </template>

                        <!-- Success state -->
                        <template x-if="item.status === 'success'">
                            <div>
                                <template x-if="item.output && item.output.length > 0">
                                    <pre class="mb-0 font-monospace text-break text-light" :style="{ textAlign: textAlign }" x-text="item.output.join('\n')"></pre>
                                </template>
                                <template x-if="!item.output || item.output.length === 0">
                                    <span class="fst-italic text-secondary small">(Command completed with exit code 0 and no output)</span>
                                </template>
                            </div>
                        </template>

                        <!-- Error state -->
                        <template x-if="item.status === 'error'">
                            <div>
                                <template x-if="item.output && item.output.length > 0">
                                    <pre class="mb-0 font-monospace text-break text-danger" :style="{ textAlign: textAlign }" x-text="item.output.join('\n')"></pre>
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
                        <span x-show="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                        <span x-text="loading ? 'Running...' : 'Run'">Run</span>
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
                textAlign: localStorage.getItem('shell_align') || 'left',
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
                    this.$watch('textAlign', (val) => {
                        try { localStorage.setItem('shell_align', val); } catch (e) {}
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

                    const id = Date.now() + Math.random();
                    this.history = [
                        ...this.history,
                        {
                            id: id,
                            command: command,
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

                        let isSuccess = false;
                        let resultCode = null;
                        let output = [];
                        let errorMsg = null;

                        if (response.ok && data) {
                            resultCode = data.resultCode;
                            output = Array.isArray(data.output) ? data.output : [];
                            isSuccess = (data.resultCode === 0);
                            if (!isSuccess && output.length === 0) {
                                errorMsg = `Command exited with code ${data.resultCode}`;
                            }
                        } else {
                            resultCode = (data && data.resultCode !== undefined) ? data.resultCode : response.status;
                            if (data && data.output && data.output.length) {
                                output = Array.isArray(data.output) ? data.output : [data.output];
                            } else if (data && data.message) {
                                errorMsg = data.message;
                            } else if (response.status === 403) {
                                errorMsg = 'Forbidden: Invalid security key or SHELL_SECRET not configured in .env';
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
