<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    @verbatim
    <style>
        :root {
            --bg: #f4f5f7;
            --surface: #ffffff;
            --line: #e3e6ea;
            --ink: #1f2933;
            --muted: #6b7480;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --accent-soft: #e6f4f1;
            --danger: #b4413c;
            --shadow: 0 1px 2px rgba(31, 41, 51, .06), 0 4px 12px rgba(31, 41, 51, .05);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font: 15px/1.55 ui-sans-serif, system-ui, "Segoe UI", sans-serif;
        }

        header {
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        header h1 { font-size: 17px; margin: 0; font-weight: 650; letter-spacing: -.01em; }
        header .who { font-size: 13px; color: var(--muted); }
        header .spacer { flex: 1; }

        main {
            max-width: 1180px;
            margin: 22px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 22px;
            align-items: start;
        }

        @media (max-width: 900px) { main { grid-template-columns: 1fr; } }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card + .card { margin-top: 18px; }

        .card h2 {
            margin: 0;
            padding: 13px 16px;
            font-size: 13px;
            font-weight: 650;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--muted);
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card .body { padding: 16px; }

        label { display: block; font-size: 12.5px; color: var(--muted); margin-bottom: 5px; font-weight: 550; }
        .field + .field { margin-top: 12px; }

        input, select, textarea {
            width: 100%;
            padding: 9px 11px;
            font: inherit;
            color: var(--ink);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 7px;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        textarea { resize: vertical; min-height: 74px; }

        button {
            font: inherit;
            font-weight: 550;
            padding: 9px 16px;
            border-radius: 7px;
            border: 1px solid transparent;
            background: var(--accent);
            color: #fff;
            cursor: pointer;
        }

        button:hover { background: var(--accent-dark); }
        button:disabled { background: #c8cdd3; cursor: not-allowed; }
        button.ghost { background: var(--surface); color: var(--ink); border-color: var(--line); }
        button.ghost:hover { background: #f0f2f4; }
        button.danger { background: var(--surface); color: var(--danger); border-color: #eccecc; }
        button.danger:hover { background: #fdf4f4; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 999px;
            letter-spacing: .01em;
        }

        .pill.open     { background: #fdf1dc; color: #96591a; }
        .pill.assigned { background: var(--accent-soft); color: var(--accent-dark); }
        .pill.resolved { background: #e9f3dd; color: #4a6b22; }
        .pill.closed   { background: #eceef0; color: #5d666f; }
        .pill.live     { background: var(--accent-soft); color: var(--accent-dark); }
        .pill.down     { background: #f7e7e6; color: var(--danger); }

        .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

        .tickets { list-style: none; margin: 0; padding: 0; max-height: 420px; overflow-y: auto; }

        .tickets li {
            padding: 12px 16px;
            border-bottom: 1px solid var(--line);
            cursor: pointer;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .tickets li:hover { background: #f8f9fa; }
        .tickets li.active { background: var(--accent-soft); box-shadow: inset 3px 0 0 var(--accent); }
        .tickets li:last-child { border-bottom: 0; }
        .tickets .subject { font-weight: 570; font-size: 14px; }
        .tickets .meta { font-size: 12px; color: var(--muted); margin-top: 3px; }
        .tickets .grow { flex: 1; min-width: 0; }
        .tickets .subject, .tickets .meta { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .badge {
            background: var(--accent);
            color: #fff;
            font-size: 11px;
            font-weight: 650;
            border-radius: 999px;
            padding: 1px 7px;
            align-self: center;
        }

        .thread { height: 400px; overflow-y: auto; padding: 18px 16px; background: #fafbfc; }

        .msg { display: flex; margin-bottom: 14px; }
        .msg .bubble { max-width: 74%; padding: 9px 13px; border-radius: 12px; }
        .msg .who { font-size: 11.5px; font-weight: 650; opacity: .75; margin-bottom: 2px; }
        .msg .at { font-size: 11px; opacity: .6; margin-top: 3px; }

        .msg.them .bubble { background: var(--surface); border: 1px solid var(--line); border-bottom-left-radius: 4px; }
        .msg.mine { justify-content: flex-end; }
        .msg.mine .bubble { background: var(--accent); color: #fff; border-bottom-right-radius: 4px; }

        .composer { display: flex; gap: 9px; padding: 13px 16px; border-top: 1px solid var(--line); }
        .composer input { flex: 1; }

        .empty { padding: 30px 16px; text-align: center; color: var(--muted); font-size: 14px; }
        .note { font-size: 12.5px; color: var(--muted); margin-top: 10px; }
        .error { color: var(--danger); font-size: 13px; margin-top: 10px; }
        .kv { font-size: 13px; color: var(--muted); }
        .kv b { color: var(--ink); font-weight: 570; }
    </style>
    @endverbatim
</head>
<body>
@yield('page')

<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

<script>window.REVERB_KEY = @json(config('broadcasting.connections.reverb.key'));</script>

@verbatim
<script>
    const TICKET_CATEGORIES = { 1: 'Order', 2: 'Payment', 3: 'Delivery', 4: 'Product', 5: 'Account', 6: 'Other' };
    const TICKET_STATUSES = { 1: 'open', 2: 'assigned', 3: 'resolved', 4: 'closed' };
    const ORDER_CATEGORY = 1;
    const RESOLVED = 3;
    const CLOSED = 4;

    const byId = id => document.getElementById(id);

    const element = (tag, className) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        return node;
    };

    const escapeHtml = text => String(text ?? '').replace(/[&<>"']/g, character =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);

    const clockTime = value => value ? new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

    const statusPill = status => `<span class="pill ${TICKET_STATUSES[status]}">${TICKET_STATUSES[status]}</span>`;

    const tokenStore = {
        key: null,
        load() { return localStorage.getItem(this.key) ?? ''; },
        save(value) { localStorage.setItem(this.key, value); },
    };

    const authHeaders = () => ({
        Authorization: 'Bearer ' + tokenStore.load().trim(),
        Accept: 'application/json',
    });

    const jsonHeaders = () => ({ ...authHeaders(), 'Content-Type': 'application/json' });

    let socketId = null;

    async function api(method, path, body) {
        const headers = body ? jsonHeaders() : authHeaders();
        if (socketId && method !== 'GET') headers['X-Socket-Id'] = socketId;

        const response = await fetch(path, { method, headers, body: body ? JSON.stringify(body) : undefined });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const details = payload.data && typeof payload.data === 'object'
                ? Object.values(payload.data).flat().join(' ')
                : '';

            throw new Error([payload.message, details].filter(Boolean).join(' - ') || ('HTTP ' + response.status));
        }

        return payload.data;
    }

    let ticketChannel = null;

    function connect(channels) {
        const echo = new Echo({
            broadcaster: 'reverb',
            key: window.REVERB_KEY,
            wsHost: window.location.hostname,
            wsPort: 8080,
            forceTLS: false,
            enabledTransports: ['ws'],
            authEndpoint: '/api/v1/broadcasting/auth',
            auth: { headers: authHeaders() },
        });

        echo.connector.pusher.connection.bind('state_change', state => {
            socketId = echo.socketId();
            showSocketState(state.current);
        });

        ticketChannel = null;
        Object.entries(channels).forEach(([name, events]) => subscribe(echo, name, events));

        return echo;
    }

    function subscribe(echo, name, events) {
        const channel = echo.private(name);
        channel.error(error => showSocketState('refused (' + (error?.status ?? 'error') + ')'));
        Object.entries(events).forEach(([event, handler]) => channel.listen('.' + event, handler));
    }

    /**
     * Follow one conversation at a time, over the connection already open.
     * Leaving the previous channel is what keeps its handlers from firing, so
     * nothing downstream has to check which ticket a message belongs to.
     */
    function watchTicket(echo, ticketId, events) {
        if (ticketChannel) echo.leave(ticketChannel);

        ticketChannel = 'tickets.' + ticketId;
        subscribe(echo, ticketChannel, events);
    }

    function showSocketState(state) {
        const live = state === 'connected';
        byId('socket').className = 'pill ' + (live ? 'live' : 'down');
        byId('socket').innerHTML = '<span class="dot"></span> ' + state;
    }

    function drawMessage(message, mine, theirName) {
        const author = mine ? 'You' : (message.sender_name ?? theirName);
        const row = element('div', 'msg ' + (mine ? 'mine' : 'them'));

        row.innerHTML = `<div class="bubble">
            <div class="who">${escapeHtml(author)}</div>
            <div>${escapeHtml(message.body)}</div>
            <div class="at">${clockTime(message.created_at)}</div>
        </div>`;

        byId('thread').append(row);
        byId('thread').scrollTop = byId('thread').scrollHeight;
    }

    function wireComposer(send) {
        byId('send').onclick = send;

        byId('body').addEventListener('keydown', event => {
            if (event.key !== 'Enter' || event.repeat) return;

            event.preventDefault();
            send();
        });
    }

    function showError(id, error) {
        byId(id).textContent = error?.message ?? '';
    }
</script>
@endverbatim

@stack('script')
</body>
</html>
