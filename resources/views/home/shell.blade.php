@extends('layouts.app')

@section('title', 'Akrez Shell')

@section('content')

    <div x-data="commandRunner()" class="container py-4" dir="ltr">

        <form @submit.prevent="run">
            <div class="input-group mb-3">
                <input type="text" x-model="command" class="form-control font-monospace rounded-0" :disabled="loading"
                    autofocus>
                <button type="submit" class="btn rounded-0"
                    :class="success === null ? 'btn-primary' : (success ? 'btn-success' : 'btn-danger')"
                    :disabled="loading || !command.trim()">
                    <span x-show="!loading">Run</span>
                    <span x-show="loading">Running...</span>
                </button>
            </div>
        </form>

        <textarea class="form-control font-monospace bg-dark text-light" x-model="output" rows="25" readonly x-ref="output"
            style="resize: vertical;"></textarea>

    </div>

    <script>
        const pageConfig = {
            commandUrl: @json(route('home.run')),
            csrfToken: @json(csrf_token()),
        };

        function commandRunner() {
            return {
                command: '',
                output: '',
                loading: false,
                success: null,
                async run() {
                    const command = this.command.trim();
                    if (!command || this.loading) {
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

@endsection
