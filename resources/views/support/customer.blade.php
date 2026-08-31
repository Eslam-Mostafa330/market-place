@extends('support.layout')

@section('title', 'Support · Customer')

@section('page')
<header>
    <h1>Support</h1>
    <span class="who">customer view</span>
    <span class="spacer"></span>
    <span id="desk" class="pill closed"><span class="dot"></span> checking the desk</span>
    <span id="socket" class="pill down"><span class="dot"></span> offline</span>
</header>

<main>
    <div>
        <div class="card">
            <h2>Sign in</h2>
            <div class="body">
                <div class="field">
                    <label for="token">Customer access token</label>
                    <input id="token" placeholder="46|qBHn...">
                </div>
                <div class="actions" style="margin-top:12px"><button id="load">Load my tickets</button></div>
                <div id="authError" class="error"></div>
            </div>
        </div>

        <div class="card">
            <h2>My tickets</h2>
            <ul id="tickets" class="tickets"></ul>
            <div id="ticketsEmpty" class="empty">Sign in to see your tickets.</div>
        </div>

        <div class="card">
            <h2>Open a new ticket</h2>
            <div class="body">
                <div class="field">
                    <label for="subject">Subject</label>
                    <input id="subject" placeholder="My order never arrived">
                </div>
                <div class="field">
                    <label for="category">What is it about?</label>
                    <select id="category">
                        <option value="6">Other</option>
                        <option value="1">Order</option>
                        <option value="2">Payment</option>
                        <option value="3">Delivery</option>
                        <option value="4">Product</option>
                        <option value="5">Account</option>
                    </select>
                </div>
                <div class="field" id="orderField" hidden>
                    <label for="orderId">Which order?</label>
                    <select id="orderId"></select>
                </div>
                <div class="field">
                    <label for="message">Message</label>
                    <textarea id="message" placeholder="Tell us what happened"></textarea>
                </div>
                <div class="actions" style="margin-top:12px"><button id="open">Open ticket</button></div>
                <div id="openError" class="error"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2><span id="threadTitle">Conversation</span><span class="spacer" style="flex:1"></span><span id="threadStatus"></span></h2>
        <div id="thread" class="thread"><div class="empty">Pick a ticket, or open a new one.</div></div>
        <div class="composer">
            <input id="body" placeholder="Write a message" disabled>
            <button id="send" disabled>Send</button>
        </div>
        <div class="body" style="padding-top:0"><div id="sendError" class="error"></div></div>
    </div>
</main>
@endsection

@push('script')
@verbatim
<script>
    tokenStore.key = 'support.customer.token';
    byId('token').value = tokenStore.load();

    const TICKETS = '/api/v1/customer/support/tickets';

    let tickets = [];
    let current = null;
    let echo = null;

    function drawTickets() {
        const list = byId('tickets');
        list.innerHTML = '';
        byId('ticketsEmpty').hidden = tickets.length > 0;
        byId('ticketsEmpty').textContent = 'No tickets yet.';

        tickets.forEach(ticket => {
            const row = element('li', ticket.id === current?.id ? 'active' : '');

            row.innerHTML = `
                <div class="grow">
                    <div class="subject">${escapeHtml(ticket.subject)}</div>
                    <div class="meta">${TICKET_CATEGORIES[ticket.category]} &middot; ${statusPill(ticket.status)}</div>
                </div>
                ${ticket.unread_count ? `<span class="badge">${ticket.unread_count}</span>` : ''}`;

            row.onclick = () => openTicket(ticket);
            list.append(row);
        });
    }

    function drawDesk(snapshot) {
        byId('desk').className = 'pill ' + (snapshot.support_available ? 'assigned' : 'closed');
        byId('desk').innerHTML = '<span class="dot"></span> ' + escapeHtml(snapshot.message);
    }

    function drawTicketState() {
        byId('threadStatus').innerHTML = statusPill(current.status);
        byId('body').disabled = current.status === CLOSED;
        byId('send').disabled = current.status === CLOSED;
        byId('body').placeholder = current.status === CLOSED
            ? 'This ticket is closed, open a new one if you still need help'
            : current.status === RESOLVED ? 'Replying will reopen this ticket' : 'Write a message';
    }

    async function loadTickets() {
        tickets = await api('GET', TICKETS);
        drawTickets();
    }

    async function loadOrders() {
        const orders = await api('GET', '/api/v1/customer/orders');

        byId('orderId').innerHTML = orders.length
            ? orders.map(order => `<option value="${order.id}">${escapeHtml(order.order_number)} &middot; ${escapeHtml(order.store_name ?? '')}</option>`).join('')
            : '<option value="">you have no orders yet</option>';
    }

    const loadDesk = async () => drawDesk(await api('GET', '/api/v1/customer/support/availability'));

    async function openTicket(ticket) {
        current = ticket;
        drawTickets();

        byId('threadTitle').textContent = ticket.subject;
        byId('thread').innerHTML = '';

        const messages = await api('GET', `${TICKETS}/${ticket.id}/messages`);
        messages.slice().reverse().forEach(message => drawMessage(message, message.from_desk === false, 'Support'));

        await api('POST', `${TICKETS}/${ticket.id}/read`);
        ticket.unread_count = 0;

        drawTickets();
        drawTicketState();

        watchTicket(echo, ticket.id, {
            'message.sent': message => drawMessage(message, message.from_desk === false, 'Support'),
            'ticket.updated': applyTicketUpdate,
        });
    }

    function applyTicketUpdate(payload) {
        const row = tickets.find(ticket => ticket.id === payload.id);
        if (row) row.status = payload.status;

        if (current?.id === payload.id) {
            current.status = payload.status;
            drawTicketState();
        }

        drawTickets();
    }

    // A desk that went quiet without signing out is corrected by the next read.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && echo) loadDesk().catch(() => {});
    });

    byId('category').onchange = () => {
        byId('orderField').hidden = Number(byId('category').value) !== ORDER_CATEGORY;
    };

    byId('load').onclick = async () => {
        tokenStore.save(byId('token').value);
        showError('authError');

        try {
            await loadTickets();
            await loadOrders();
            await loadDesk();

            if (echo) echo.disconnect();
            echo = connect({ 'support.availability': { 'desk.availability': drawDesk } });
        } catch (error) {
            showError('authError', error);
        }
    };

    byId('open').onclick = async () => {
        showError('openError');
        const category = Number(byId('category').value);

        try {
            const created = await api('POST', TICKETS, {
                subject: byId('subject').value,
                category,
                message: byId('message').value,
                ...(category === ORDER_CATEGORY ? { order_id: byId('orderId').value } : {}),
            });

            byId('subject').value = byId('message').value = '';

            await loadTickets();
            await openTicket(tickets.find(ticket => ticket.id === created.id) ?? created);
        } catch (error) {
            showError('openError', error);
        }
    };

    wireComposer(async () => {
        const input = byId('body');
        const body = input.value.trim();
        if (!body || !current) return;

        input.value = '';
        showError('sendError');

        try {
            drawMessage(await api('POST', `${TICKETS}/${current.id}/messages`, { body }), true, 'Support');
        } catch (error) {
            input.value = body;
            showError('sendError', error);
        }
    });
</script>
@endverbatim
@endpush
