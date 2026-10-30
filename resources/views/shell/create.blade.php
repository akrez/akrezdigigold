<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Shell</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

    <style>
        textarea {
            resize: vertical;
        }
    </style>
</head>

<body dir="ltr">

    <div x-data="commandRunner()" class="container py-4">

        <form @submit.prevent="run">
            <div class="input-group mb-3">
                <input type="password" x-model="secret" class="form-control font-monospace rounded-0" placeholder="Security key"
                    :disabled="loading" autocomplete="off" autofocus>
            </div>
            <div class="input-group mb-3">
                <input type="text" x-model="command" class="form-control font-monospace rounded-0" :disabled="loading">
                <button type="submit" class="btn rounded-0"
                    :class="success === null ? 'btn-primary' : (success ? 'btn-success' : 'btn-danger')"
                    :disabled="loading || !command.trim() || !secret.trim()">
                    <span x-show="!loading">Run</span>
                    <span x-show="loading">Running...</span>
                </button>
            </div>
        </form>

        <textarea class="form-control font-monospace bg-dark text-light" x-model="output" rows="25" readonly x-ref="output"></textarea>

    </div>

    <script>
        const pageConfig = {
            commandUrl: @json(route('shell.store')),
            csrfToken: @json(csrf_token()),
        };

        function commandRunner() {
            return {
                secret: '',
                command: '',
                output: '',
                loading: false,
                success: null,
                async run() {
                    const secret = this.secret.trim();
                    const command = this.command.trim();
                    if (!secret || !command || this.loading) {
                        return;
                    }
                    this.output += (this.output ? '\n\n' : '') + '$ ' + command;
                    this.$nextTick(() => {
                        this.$refs.output.scrollTop = this.$refs.output.scrollHeight;
                    });
                    try {
                        this.loading = true;
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
                        const data = await response.json();
                        if (response.ok) {
                            this.success = (data.resultCode === 0);
                            this.output += '\n' + data.output.join('\n');
                        } else {
                            throw new Error(data.message ?? `HTTP ${response.status}`);
                        }
                    } catch (error) {
                        this.success = false;
                        this.output += '\n' + error.message;
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => {
                            this.$refs.output.scrollTop = this.$refs.output.scrollHeight;
                        });
                    }
                },
            };
        }
    </script>

</body>

</html>
