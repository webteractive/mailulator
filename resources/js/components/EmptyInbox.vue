<template>
  <div class="flex-1 overflow-auto p-6">
    <div class="max-w-md mx-auto space-y-5">
      <div class="flex flex-col items-center text-center space-y-2">
        <div class="h-12 w-12 rounded-full bg-muted flex items-center justify-center">
          <Mailbox class="h-6 w-6 text-muted-foreground" />
        </div>
        <h2 class="text-base font-semibold">No emails yet</h2>
        <p class="text-sm text-muted-foreground">
          Send a message from a Laravel app to fill this inbox.
        </p>
      </div>

      <section class="space-y-2">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">1. Install the package</h3>
        <CodeBlock code="composer require webteractive/mailulator" />
      </section>

      <section class="space-y-2">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">2. Configure the mailer in .env</h3>
        <p class="text-xs text-muted-foreground">
          <strong class="font-semibold text-foreground">In-app:</strong> Mailulator is installed in the same app you send from.
        </p>
        <CodeBlock :code="inAppSnippet" />
        <p class="text-xs text-muted-foreground pt-1">
          <strong class="font-semibold text-foreground">Standalone:</strong> sender is a separate app pointing at this receiver. Get a token from Settings → New inbox (or Regenerate key). Tokens are shown once — store safely.
        </p>
        <CodeBlock :code="standaloneSnippet" />
      </section>

      <section class="space-y-2">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">3. Send a test email</h3>
        <CodeBlock :code="tinkerSnippet" />
      </section>

      <section class="space-y-2">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Or try via curl (standalone)</h3>
        <CodeBlock :code="curlSnippet" />
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Mailbox } from 'lucide-vue-next'
import CodeBlock from './CodeBlock.vue'

const origin = computed(() => window.location.origin)

const inAppSnippet = `MAIL_MAILER=mailulator`

const standaloneSnippet = computed(() => `MAIL_MAILER=mailulator
MAILULATOR_URL=${origin.value}
MAILULATOR_TOKEN=your-inbox-token
MAILULATOR_RECEIVER_ENABLED=false`)

const tinkerSnippet = `Mail::raw('Hello from Laravel', function ($m) {
    $m->to('test@example.com')->subject('Hello');
});`

const curlSnippet = computed(() => `curl -X POST ${origin.value}/api/emails \\
  -H "Authorization: Bearer your-inbox-token" \\
  -H "Content-Type: application/json" \\
  -d '{"from":"you@app.test","to":["rcpt@example.com"],"subject":"Hello","text_body":"Hi"}'`)
</script>
