// Public/js/async.js
function addMessageAsync(texto, tipo) {
    tipo = tipo || 'system';
    var container = document.getElementById('asyncArea');
    if (!container) return;
    var div = document.createElement('div');
    div.className = 'message ' + tipo;
    var now = new Date();
    var time = now.toLocaleTimeString();
    div.innerHTML = '[' + time + '] ' + texto;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    if (container.children.length > 20) container.removeChild(container.children[0]);
}

function enviarAsync() {
    var input = document.getElementById('msgAsync');
    var content = input.value;
    if (!content) { alert('Digite uma mensagem'); return; }
    addMessageAsync('📤 Enfileirando: "' + content + '"', 'system');
    
    fetch('mensagens', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: content, type: 'async' })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'queued') {
            addMessageAsync('✅ ENFILEIRADO: ' + data.message, 'async');
            addMessageAsync('   📍 Posição: ' + data.queue_position, 'async');
            input.value = '';
        }
    });
}

function processarFila() {
    addMessageAsync('⚙️ Processando fila...', 'system');
    fetch('processar-fila', { method: 'POST' })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'processed') {
            addMessageAsync('✅ ' + data.message, 'async');
            atualizarTotalMensagens();
        } else if (data.status === 'empty') {
            addMessageAsync('📭 ' + data.message, 'system');
        }
    });
}

addMessageAsync('✅ Async pronto - Mensagens vão para FILA', 'system');