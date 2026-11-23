const thread = document.querySelector('[data-chat-thread]');
const form = document.querySelector('[data-chat-form]');

if (thread && form) {
    const messagesUrl = thread.dataset.messagesUrl;
    const myId = Number(thread.dataset.me);
    let lastId = Number(thread.dataset.lastId || 0);
    const input = form.querySelector('input[name="message_text"]');
    const button = form.querySelector('button[type="submit"]');
    let polling = false;

    const scrollToBottom = () => { thread.scrollTop = thread.scrollHeight; };

    const formatTime = (iso) => {
        if (!iso) return '';
        return new Date(iso).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    };

    const appendMessage = (message) => {
        if (thread.querySelector(`[data-message-id="${message.message_id}"]`)) return;

        const mine = Number(message.sender_id) === myId;

        const row = document.createElement('div');
        row.className = `flex ${mine ? 'justify-end' : 'justify-start'}`;

        const wrap = document.createElement('div');
        wrap.className = 'max-w-[75%]';
        wrap.dataset.messageId = message.message_id;

        const bubble = document.createElement('div');
        bubble.className = 'rounded-[14px] border border-[#e1e6e9] bg-white px-4 py-3 text-[14px] text-[#202b3d] break-words whitespace-pre-line';
        bubble.textContent = message.message_text;

        const time = document.createElement('p');
        time.className = `mt-1 text-[10px] text-[#87929e] ${mine ? 'text-right' : ''}`;
        time.textContent = formatTime(message.sent_at);

        wrap.append(bubble, time);
        row.append(wrap);
        thread.append(row);

        lastId = Math.max(lastId, Number(message.message_id));
    };

    const poll = async () => {
        if (polling || document.hidden) return;
        polling = true;
        try {
            const { data } = await window.axios.get(messagesUrl, { params: { after: lastId } });
            if (data.messages.length) {
                data.messages.forEach(appendMessage);
                scrollToBottom();
            }
        } catch (error) {
            // abaikan, akan dicoba lagi pada siklus berikutnya
        } finally {
            polling = false;
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        button.disabled = true;
        try {
            const { data } = await window.axios.post(form.action, { message_text: text });
            appendMessage(data.message);
            input.value = '';
            scrollToBottom();
        } catch (error) {
            input.setCustomValidity(error.response?.data?.errors?.message_text?.[0] ?? 'Pesan gagal dikirim.');
            input.reportValidity();
            input.setCustomValidity('');
        } finally {
            button.disabled = false;
            input.focus();
        }
    });

    setInterval(poll, 3000);
    document.addEventListener('visibilitychange', poll);
    scrollToBottom();
}