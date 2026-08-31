@extends('support.layout')

@section('title', 'Support · Agent desk')

@section('page')
<header>
    <h1>Agent desk</h1>
    <span class="who">support view</span>
    <span class="spacer"></span>
    <button id="availability" class="ghost">Go online</button>
    <span id="socket" class="pill down"><span class="dot"></span> offline</span>
</header>

<main>
    <div>
        <div class="card">
            <h2>Sign in</h2>
            <div class="body">
                <div class="field">
                    <label for="token">Agent access token</label>
                    <input id="token" placeholder="47|Degad...">
                </div>
                <div class="actions" style="margin-top:12px"><button id="load">Open the queue</button></div>
                <div id="authError" class="error"></div>
            </div>
        </div>

        <div class="card">
            <h2>Queue <span class="spacer" style="flex:1"></span>
                <select id="filter" style="width:auto;padding:4px 8px;font-size:12px">
                    <option value="">everything</option>
                    <option value="unassigned">unclaimed</option>
                    <option value="mine">mine</option>
                </select>
            </h2>
            <ul id="tickets" class="tickets"></ul>
            <div id="ticketsEmpty" class="empty">Sign in to see the queue.</div>
        </div>
    </div>

    <div class="card">
        <h2><span id="threadTitle">Conversation</span><span class="spacer" style="flex:1"></span><span id="threadStatus"></span></h2>

        <div class="body" id="detail" hidden>
            <div class="kv" id="requester"></div>
            <div class="actions" style="margin-top:12px">
                <button id="claim">Claim</button>
                <button id="release" class="ghost">Hand back</button>
                <button id="resolve" class="ghost">Resolve</button>
                <button id="close" class="danger">Close</button>
            </div>
            <div id="actionError" class="error"></div>
            <div id="order" class="note" hidden></div>
        </div>

        <div id="thread" class="thread"><div class="empty">Pick a ticket from the queue.</div></div>
        <div class="composer">
            <input id="body" placeholder="Claim the ticket to reply" disabled>
            <button id="send" disabled>Send</button>
        </div>
        <div class="body" style="padding-top:0"><div id="sendError" class="error"></div></div>
    </div>
</main>
@endsection

@push('script')
@verbatim
<script>
    tokenStore.key = 'support.agent.token';
    byId('token').value = tokenStore.load();

    const TICKETS = '/api/v1/support/tickets';
    const AVAILABILITY = '/api/v1/support/availability';
    const ONLINE = 2;
    const OFFLINE = 1;

    let tickets = [];
    let current = null;
    let echo = null;
    let online = false;
    let myId = null;

    function visibleTickets() {
        const filter = byId('filter').value;

        if (filter === 'unassigned') return tickets.filter(ticket => !ticket.agent_name);
        if (filter === 'mine') return tickets.filter(ticket => ticket.is_mine);

        return tickets;
    }

    function drawQueue() {
        const list = byId('tickets');
        const rows = visibleTickets();

        list.innerHTML = '';
        byId('ticketsEmpty').hidden = rows.length > 0;
        byId('ticketsEmpty').textContent = 'Nothing here.';

        rows.forEach(ticket => {
            const heldBy = ticket.is_mine ? 'you' : (ticket.agent_name ?? 'unclaimed');
            const row = element('li', ticket.id === current?.id ? 'active' : '');

            row.innerHTML = `
                <div class="grow">
                    <div class="subject">${escapeHtml(ticket.subject)}</div>
                    <div class="meta">${TICKET_CATEGORIES[ticket.category]} &middot; ${statusPill(ticket.status)} &middot; ${escapeHtml(heldBy)}</div>
                </div>
                ${ticket.unread_count ? `<span class="badge">${ticket.unread_count}</span>` : ''}`;

            row.onclick = () => openTicket(ticket);
            list.append(row);
        });
    }

    function drawDetail() {
        const mine = current.is_mine;
        const closed = current.status === CLOSED;

        byId('detail').hidden = false;
        byId('threadTitle').textContent = current.subject;
        byId('threadStatus').innerHTML = statusPill(current.status);
        byId('requester').innerHTML = `<b>${escapeHtml(current.requester?.name ?? '')}</b> ${escapeHtml(current.requester?.email ?? '')}`;

        byId('claim').disabled = closed || mine;
        byId('release').disabled = closed || !mine;
        byId('resolve').disabled = closed || !mine || current.status === RESOLVED;
        byId('close').disabled = closed || !mine;

        byId('body').disabled = !mine || closed;
        byId('send').disabled = !mine || closed;
        byId('body').placeholder = closed ? 'This ticket is closed'
            : !mine ? 'Claim the ticket to reply'
            : current.status === RESOLVED ? 'Replying will reopen this ticket'
            : 'Write a reply';
    }

    function drawOrder(order) {
        const panel = byId('order');
        panel.hidden = !order;
        if (!order) return;

        const items = (order.items ?? []).map(item => `${item.quantity} x ${escapeHtml(item.product_name)}`).join(', ');

        panel.innerHTML = `<b>Order ${escapeHtml(order.order_number)}</b><br>`
            + `status ${order.order_status} &middot; payment ${order.payment_status} &middot; total ${escapeHtml(String(order.total))}<br>`
            + (items ? `${items}<br>` : '')
            + `placed ${new Date(order.placed_at).toLocaleString()}`
            + (order.delivered_at ? ` &middot; delivered ${new Date(order.delivered_at).toLocaleString()}` : '');
    }

    async function loadQueue() {
        tickets = await api('GET', TICKETS);
        sortQueue();
        drawQueue();
    }

    /**
     * The queue arrives newest conversation first. Keep it so as tickets move.
     */
    function sortQueue() {
        tickets.sort((a, b) => (b.last_message_at ?? '').localeCompare(a.last_message_at ?? ''));
    }

    async function loadOrder() {
        if (!current.order_id) return drawOrder(null);

        drawOrder(await api('GET', `${TICKETS}/${current.id}/order`).catch(() => null));
    }

    async function openTicket(ticket) {
        current = await api('GET', `${TICKETS}/${ticket.id}`);
        current.is_mine = current.agent?.id === myId;

        drawQueue();
        drawDetail();

        byId('thread').innerHTML = '';
        const messages = await api('GET', `${TICKETS}/${current.id}/messages`);
        messages.slice().reverse().forEach(message => drawMessage(message, message.from_desk === true, 'Customer'));

        await loadOrder();
        await api('POST', `${TICKETS}/${current.id}/read`).catch(() => {});

        const row = tickets.find(item => item.id === current.id);
        if (row) {
            row.unread_count = 0;
            drawQueue();
        }

        watchTicket(echo, current.id, {
            'message.sent': message => drawMessage(message, message.from_desk === true, 'Customer'),
        });
    }

    function applyQueueUpdate(payload) {
        const row = tickets.find(ticket => ticket.id === payload.id);
        const spoken = row && payload.last_message_at !== row.last_message_at;
        const unread = (row?.unread_count ?? 0) + (spoken && current?.id !== payload.id ? 1 : 0);
        const merged = { ...(row ?? {}), ...payload, is_mine: payload.agent_id === myId, unread_count: unread };

        if (payload.status === CLOSED) {
            tickets = tickets.filter(ticket => ticket.id !== payload.id);
        } else if (row) {
            Object.assign(row, merged);
        } else {
            tickets.unshift(merged);
        }

        sortQueue();

        if (current?.id === payload.id) {
            current = { ...current, ...merged };
            drawDetail();
        }

        drawQueue();
    }

    async function applyAction(run) {
        showError('actionError');

        try {
            const state = await run();
            current = { ...current, ...state };
            drawDetail();

            const row = tickets.find(ticket => ticket.id === current.id);
            if (row) {
                Object.assign(row, { status: state.status, agent_name: state.agent_name, is_mine: state.is_mine });
                drawQueue();
            }
        } catch (error) {
            showError('actionError', error);
        }
    }

    byId('claim').onclick = () => applyAction(() => api('POST', `${TICKETS}/${current.id}/claim`));
    byId('release').onclick = () => applyAction(() => api('DELETE', `${TICKETS}/${current.id}/claim`));
    byId('resolve').onclick = () => applyAction(() => api('PATCH', `${TICKETS}/${current.id}/status`, { status: RESOLVED }));
    byId('close').onclick = () => applyAction(() => api('PATCH', `${TICKETS}/${current.id}/status`, { status: CLOSED }));

    byId('filter').onchange = drawQueue;

    byId('availability').onclick = async () => {
        online = !online;

        await api('PATCH', AVAILABILITY, { availability: online ? ONLINE : OFFLINE });

        byId('availability').textContent = online ? 'Go offline' : 'Go online';
        byId('availability').className = online ? '' : 'ghost';
    };

    window.addEventListener('pagehide', () => {
        if (!online) return;

        fetch(AVAILABILITY, {
            method: 'PATCH',
            headers: jsonHeaders(),
            body: JSON.stringify({ availability: OFFLINE }),
            keepalive: true,
        });
    });

    byId('load').onclick = async () => {
        tokenStore.save(byId('token').value);
        showError('authError');

        try {
            await loadQueue();
            myId = (await api('GET', '/api/v1/support/profile')).id;

            if (echo) echo.disconnect();
            echo = connect({ 'support.queue': { 'ticket.updated': applyQueueUpdate } });
        } catch (error) {
            showError('authError', error);
        }
    };

    wireComposer(async () => {
        const input = byId('body');
        const body = input.value.trim();
        if (!body || !current) return;

        input.value = '';
        showError('sendError');

        try {
            drawMessage(await api('POST', `${TICKETS}/${current.id}/messages`, { body }), true, 'Customer');
        } catch (error) {
            input.value = body;
            showError('sendError', error);
        }
    });
</script>
@endverbatim
@endpush
