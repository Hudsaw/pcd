// Public/js/polling.js
let lastMessageId = null;
let lastQueueSize = -1;
let pollingInterval = null;

function getBaseUrl() {
    let path = window.location.pathname;
    let basePath = path.substring(0, path.lastIndexOf('/') + 1);
    return basePath;
}

function startPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    // Polling a cada 2 segundos
    pollingInterval = setInterval(function() {
        checkUpdates();
    }, 2000);
    
    console.log('✅ Polling iniciado (atualização a cada 2 segundos)');
    
    var pollingStatus = document.getElementById('pollingStatus');
    if (pollingStatus) {
        pollingStatus.innerHTML = '● Ativo (Polling)';
        pollingStatus.style.color = '#4caf50';
    }
}

function checkUpdates() {
    let baseUrl = getBaseUrl();
    let url = baseUrl + 'polling/atualizar';
    
    if (lastMessageId) {
        url += '?last_id=' + encodeURIComponent(lastMessageId);
        url += '&last_queue_size=' + lastQueueSize;
    } else {
        url += '?last_queue_size=' + lastQueueSize;
    }
    
    fetch(url)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                // Processar novas mensagens
                if (data.new_messages && data.new_messages.length > 0) {
                    data.new_messages.forEach(function(msg) {
                        addMessageRealtime('📨 NOVA MENSAGEM: ' + msg.content);
                    });
                    atualizarTotalMensagens();
                }
                
                // Atualizar tamanho da fila
                if (data.queue_size !== undefined) {
                    let queueSizeElem = document.getElementById('queueSize');
                    if (queueSizeElem) queueSizeElem.innerHTML = data.queue_size;
                    
                    let realtimeQueueElem = document.getElementById('realtimeQueue');
                    if (realtimeQueueElem) {
                        realtimeQueueElem.innerHTML = '📊 Fila: ' + data.queue_size + ' mensagens';
                    }
                    
                    lastQueueSize = data.queue_size;
                }
                
                // Atualizar último ID
                if (data.last_message_id) {
                    lastMessageId = data.last_message_id;
                }
            }
        })
        .catch(function(error) {
            console.error('Erro no polling:', error);
        });
}

function addMessageRealtime(texto) {
    let container = document.getElementById('realtimeArea');
    if (!container) return;
    let div = document.createElement('div');
    div.className = 'message realtime';
    let now = new Date();
    let time = now.toLocaleTimeString();
    div.innerHTML = '[' + time + '] ' + texto;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    if (container.children.length > 10) container.removeChild(container.children[0]);
}

function atualizarTotalMensagens() {
    let baseUrl = getBaseUrl();
    fetch(baseUrl + 'mensagens')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.status === 'success' && Array.isArray(data.messages)) {
                let totalElem = document.getElementById('totalMessages');
                if (totalElem) totalElem.innerHTML = data.messages.length;
            }
        })
        .catch(function(error) {
            console.error('Erro ao atualizar total:', error);
        });
}

// Inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startPolling);
} else {
    startPolling();
}

window.atualizarTotalMensagens = atualizarTotalMensagens;