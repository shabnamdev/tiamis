=== Tiamis – AI Live Chat ===
Contributors: shcd
Tags: live chat, telegram, bale, chatbot, artificial intelligence
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multilingual Telegram and Bale live chat with AI replies, Web Push, heatmaps, REST API and a secure operator inbox.

== Description ==

Tiamis also includes an AI-assisted ticket system for registered users and guests. Internal training files (text-based PDF, XLSX, XLS and TXT) are searched first; when no suitable internal source is found, the configured AI provider can prepare a natural, language-appropriate Persian, Arabic or English answer. Users can escalate an unsuitable automated answer to a human operator.

Ticket portal shortcodes: `[tiamis_tickets]` or `[tiamis_ticket_portal]`. The portal can require WordPress login or allow guest tickets with a full name and mobile number. Guest tickets receive a unique `SHCD-...` tracking code and can be securely reopened with the tracking code plus the original mobile number.

Uploaded ticket-training files are used only by the ticket assistant. The original upload is deleted after text extraction, and ticket-only sources are excluded from the public live-chat knowledge search.

Ticket messages can include administrator-configured JPG, JPEG, PNG, WEBP, GIF, PDF, XLSX, XLS, DOCX, ZIP and audio attachments. Administrators control the allowed extensions, per-file size, attachment count and maximum voice-recording duration. Browser voice recording uses the MediaRecorder API and therefore requires microphone permission and browser support.

Manual operator replies can include a language-specific configurable signature. Ticket messages show delivered and seen indicators, and tickets use color-coded states such as new, under review, in progress, waiting for customer, answered, resolved, closed and reopened.

Ticket attachments are stored in the standard WordPress uploads directory and represented as WordPress media attachments. Site administrators are responsible for configuring server access, retention, backups and privacy controls appropriate to the sensitivity of uploaded support files.


Tiamis – AI Live Chat is a multilingual support system for WordPress. Visitors chat from the website, while administrators answer from WordPress, Telegram, or Bale in one shared inbox.

The plugin supports Persian, Arabic and English, with instant language switching in the administration area and automatic RTL/LTR layout handling.

= Main features =

* Two-way live chat between website visitors and Telegram or Bale operators.
* Direct customer conversations with Telegram and Bale bots.
* Multiple support experts with stacked photos on the floating button and chat header.
* A strict pre-chat gate that blocks the message area until required details are completed.
* A random chat-button animation selected on every page load.
* Shared WordPress inbox with open, pending and closed conversation states.
* Cloudflare Workers AI, OpenRouter, Ollama and OpenAI-compatible providers.
* AI-assisted drafts and optional automatic replies.
* Persian, Arabic and English administration and frontend widget.
* Persian, Arabic and English ticket portal, ticket inbox, settings, statuses and system messages.
* Configurable ticket attachments, live browser voice recording, operator signatures and delivered/seen indicators.
* Real-time backend language switching without reloading the page.
* Optional browser notifications and persistent OneSignal Web Push.
* Click heatmaps and most-clicked element analytics.
* Configurable IP storage: disabled, HMAC-pseudonymized or complete.
* Authenticated REST API and signed outbound webhooks.
* Privacy consent, retention controls, scheduled cleanup, export and erasure tools.
* Encrypted credentials when Sodium or OpenSSL is available.
* Rate limits, nonces, capability checks, honeypot protection and SSRF controls.
* SPA administration with internal navigation, Session Storage cache and an RGB loading indicator.
* Compiled, scoped Tailwind CSS controls with no CDN dependency.
* Vertical settings tabs and a macOS-style developer status console.
* Secure Shabnam WOFF2 or TTF font upload for Tiamis interfaces.
* Database optimization, sortable statistics and safe AUTO_INCREMENT repair.
* Heatmap tracking independent of the chat widget, with SPA route support, sendBeacon delivery and same-site page preview.
* Glassmorphism and neumorphism administration interface based on `#38008a`.
* Real-time WebSocket gateway with AJAX fallback and configurable active/idle sync.
* Offline outbox, idempotent client message IDs and automatic retry after reconnection.
* Conversation assignment, locking, departments, SLA, tasks, tags, tickets and operator roles.
* AI copilot insights, knowledge-base retrieval, sensitive-topic handoff and post-chat quality review.
* File attachments, reply references, message edit/delete and emoji reactions.
* PWA manifest/service worker, resumable conversations and a dynamic Gutenberg chat-button block.
* WordPress 7 Abilities API integration for secure support automations.

= Developer =

Developed by **SHABNAM.DEV**.

Plugin website: `https://shabnam.dev`  
Author website: `https://shcd.ir`

== License, Source and Functionality ==

All functionality included in this WordPress.org package is available without a license key, trial period, usage quota or paid unlock. Optional third-party services may charge for their own processing, but Tiamis does not use payment or license checks to unlock code included in this plugin.

The distributed JavaScript is readable and unminified. The Tailwind source file is included at `assets/src/shcd-tiamis-admin.css`, the build configuration is included at `tailwind.config.js`, and the compiled scoped stylesheet is included at `assets/css/shcd-tiamis-admin.css`. A compatible build can be generated with the Tailwind CSS CLI using:

`npx tailwindcss -c tailwind.config.js -i assets/src/shcd-tiamis-admin.css -o assets/css/shcd-tiamis-admin.css --minify`

== Installation ==

1. Upload the `tiamis-ai-live-chat` ZIP from **Plugins > Add New > Upload Plugin**.
2. Activate **Tiamis – AI Live Chat**.
3. Open **Tiamis Chat > Settings**.
4. Configure the widget, Telegram or Bale bots, AI, notifications, privacy and analytics.
5. Add `[shcd_tiamis]` to a page only when an inline widget is required. By default, the floating widget is rendered automatically.

The plugin creates these tables using the active WordPress database prefix:

* `{prefix}shcd_tiamis_conversations`
* `{prefix}shcd_tiamis_messages`
* `{prefix}shcd_tiamis_events`
* `{prefix}shcd_tiamis_telegram_map`
* `{prefix}shcd_tiamis_bale_map`
* `{prefix}shcd_tiamis_reports`
* `{prefix}shcd_tiamis_notes`
* `{prefix}shcd_tiamis_blocks`
* `{prefix}shcd_tiamis_ratings`
* `{prefix}shcd_tiamis_tasks`
* `{prefix}shcd_tiamis_tags`
* `{prefix}shcd_tiamis_conversation_tags`
* `{prefix}shcd_tiamis_tickets`
* `{prefix}shcd_tiamis_attachments`
* `{prefix}shcd_tiamis_reactions`
* `{prefix}shcd_tiamis_knowledge`
* `{prefix}shcd_tiamis_audit`

== Telegram Setup ==

1. Create a Telegram bot with BotFather and enter its token in the plugin settings.
2. Add the Telegram Chat IDs of operators or the support group.
3. On Iranian or restricted hosting, select **Polling**. Tiamis fetches updates through the Telegram Bot API, so Telegram does not need inbound access to the WordPress host.
4. On low-traffic sites, add the protected polling URL shown in the settings to the hosting cron and run it once per minute.
5. If the host cannot reach `api.telegram.org`, configure a trusted HTTPS Bot API relay in the Telegram API base URL field.
6. Use **Webhook** only when the site has public HTTPS connectivity and the server can receive Telegram requests.
7. Operators should reply directly to the forwarded bot message so the answer is routed to the correct visitor.

Polling and Webhook are mutually exclusive. Webhook requests are checked with the official secret-token header, and duplicate update IDs are ignored.

== Cloudflare Workers AI Setup ==

1. Create a Cloudflare API token with Workers AI permission.
2. Copy the Cloudflare Account ID.
3. Select **Cloudflare Workers AI** as the provider.
4. Enter the Account ID, API token and model identifier.
5. Start with AI draft mode before enabling automatic replies.

The plugin builds the Cloudflare account/model endpoint internally and does not print API tokens in debug logs.

== Privacy and Security ==

Depending on configuration, the plugin may store conversation text, contact information, page URL, referrer, campaign parameters, user agent and IP information. Heatmap collection stores technical selectors, normalized coordinates and viewport dimensions; it does not store text entered into form fields.

When consent is required, session creation and analytics storage are blocked until the visitor accepts. Administrators can configure retention periods and use WordPress privacy export and erasure tools.

Debug logging is disabled by default. Logs are written only when both `WP_DEBUG` and the plugin debug option are enabled, and credential-like values are redacted.

== External Services ==

External connections are disabled until the site administrator enables and configures the related integration. The website chat and WordPress operator inbox remain functional without Telegram, Bale, AI or OneSignal.

* **Telegram Bot API:** When enabled, the plugin sends support-message text, reply references, attachment URLs and configured bot/chat identifiers to Telegram so operators or customers can exchange messages through the bot. Telegram may also send webhook updates to the site. Documentation: `https://core.telegram.org/bots/api`. Terms: `https://telegram.org/tos`. Privacy: `https://telegram.org/privacy`.
* **Bale Bot API:** When enabled, the plugin sends support-message text, reply references, attachment URLs and configured bot/chat identifiers to Bale so operators or customers can exchange messages through the bot. Bale may also send webhook updates to the site. Documentation: `https://docs.bale.ai/`. Official service information and support: `https://bale.ai/contact`. Bale provides its current service policies and privacy information through its official website and client; administrators should review them before enabling this optional integration.
* **Cloudflare Workers AI:** When selected, the plugin sends the configured system prompt, relevant conversation text and optional site knowledge excerpts to the configured Workers AI model to generate a draft or automatic reply. Documentation: `https://developers.cloudflare.com/workers-ai/`. Terms: `https://www.cloudflare.com/terms/`. Privacy: `https://www.cloudflare.com/privacypolicy/`.
* **OpenRouter:** When selected, the plugin sends the configured system prompt, relevant conversation text and optional site knowledge excerpts to OpenRouter and the chosen model provider to generate a draft or automatic reply. Documentation: `https://openrouter.ai/docs`. Terms: `https://openrouter.ai/terms`. Privacy: `https://openrouter.ai/privacy`.
* **OpenAI-compatible endpoint:** When configured, the plugin sends the configured system prompt, relevant conversation text and optional site knowledge excerpts to the administrator-selected endpoint. The administrator is responsible for documenting that destination. For the OpenAI API, service terms are at `https://openai.com/policies/services-agreement/` and privacy information is at `https://openai.com/policies/row-privacy-policy/`.
* **Ollama:** When selected, the plugin sends the configured prompt and relevant conversation text to the administrator-selected Ollama endpoint. A local endpoint keeps processing on the administrator's server; a remote or Ollama-hosted endpoint is governed by that destination. Terms: `https://ollama.com/terms`. Privacy: `https://ollama.com/privacy`.
* **OneSignal Web Push:** When enabled, the OneSignal browser SDK receives the OneSignal App ID, browser/device subscription identifiers and notification-delivery data so persistent web push can be provided. Terms: `https://onesignal.com/terms`. SDK terms: `https://onesignal.com/tos`. Privacy: `https://onesignal.com/privacy_policy`.
* **Outbound webhook and real-time gateway:** When configured, the plugin sends the event payload selected by the plugin, such as conversation identifiers, message metadata or real-time room payloads, to the administrator-entered HTTPS endpoint. The administrator controls this destination and must document its terms and privacy policy.

The site administrator must review the terms, privacy policy, data-processing requirements and costs of each enabled provider, and must disclose the transfer of visitor data in the site's own privacy notice. Credentials are stored in WordPress and are encrypted when Sodium or OpenSSL is available.

== Frequently Asked Questions ==

= Are Telegram or Bale required? =

No. The WordPress inbox and website chat work without either bot. Telegram and Bale are optional operator and customer channels.

= Is artificial intelligence required? =

No. AI can remain disabled. Operators can answer manually from WordPress, Telegram or Bale.

= Which languages are included? =

Persian, Arabic and English are available for the backend and frontend widget. Persian and Arabic use RTL layout; English uses LTR.

= Does changing the backend language reload the page? =

No. Text, placeholders, navigation labels and RTL/LTR direction update immediately. Plugin pages also use SPA navigation with a short-lived Session Storage cache and a standard WordPress fallback.

= Can I upload the Shabnam font? =

Yes. Upload a Shabnam WOFF2 or TTF file from the Developer tab. The font is applied to Tiamis interfaces and stored in the plugin-managed uploads directory. Font binaries are not bundled with the public plugin package.

= How does AUTO_INCREMENT repair work? =

For an empty table, the next identifier is reset to 1. A populated table safely continues after its highest existing ID. The separate destructive reset action requires typing RESET.

= Does Heatmap work when the chat widget is disabled? =

Yes, when Heatmap is enabled its tracker loads independently. It also tracks History API route changes and retries queued events with sendBeacon or keepalive requests. If privacy consent is required, analytics remain blocked until a valid consented chat session exists.

= Does the plugin create custom database tables? =

Yes. It creates dedicated support, messaging, analytics, automation and integration tables using the WordPress prefix plus the `shcd_tiamis_` table family.

= Are API credentials shown in logs? =

Credential-like values are redacted. Technical logs are written only when WordPress debugging and the plugin debug option are both enabled.

= What happens when the plugin is deleted? =

The standard `uninstall.php` removes the plugin tables, settings, scheduled cleanup event and plugin-owned transient data.

== Changelog ==

= 1.0.0 =
* Added optional Bubble Chat visibility control without disabling inline chat shortcodes.
* Added AI-assisted ticketing, reusable department IDs, internal training-file extraction, escalation to human review and restricted ticket-agent access.
* Added native Persian, Arabic and English ticket copy for the customer portal, operator inbox and ticket settings.
* Added configurable ticket attachments, live voice recording, language-aware operator signatures, delivered/seen indicators, simplified workflow statuses and Normal/Urgent priorities.

* Initial public release of Tiamis – AI Live Chat.
* Added multilingual website chat, a shared WordPress support inbox, Telegram and Bale integrations, AI-assisted replies, browser notifications, click heatmaps, REST API tools, privacy controls, and a responsive operator interface.
* Rewrote Persian, Arabic, and English interface copy for natural grammar, clear instructions, and language-appropriate tone.

== Upgrade Notice ==

= 1.0.0 =
First public release.
