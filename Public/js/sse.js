// Public/js/sse.js
var eventSource = null;

function getBaseUrl() {
    var path = window.location.pathname;
    var basePath = path.substring(0, path.lastIndexOf('/') + 1);
    return basePath;
}

function connectSSE() {
    if (eventSource) {
        eventSource.close();
    }
    
    try {
        var baseUrl = getBaseUrl();
        var url = baseUrl + 'sse/stream';
        console.log('Conectando SSE em:', url);
        
        eventSource = new EventSource(url);
        
        eventSource.onopen = function() {
            console.log('✅ SSE conectado');
            var sseStatus = document.getElementById('sseStatus');
            if (sseStatus) {
                sseStatus.innerHTML = '● Conectado';
                sseStatus.style.color = '#4caf50';
            }
        };
        
        eventSource.onmessage = function(e) {
            console.log('Mensagem SSE:', e.data);
            try {
                var data = JSON.parse(e.data);
                if (data.type === 'new_message') {
                    addMessageRealtime('📨 NOVA MENSAGEM: ' + data.content);
                    if (typeof atualizarTotalMensagens === 'function') atualizarTotalMensagens();
                } else if (data.type === 'queue_status') {
                    var queueSizeElem = document.getElementById('queueSize');
                    if (queueSizeElem) queueSizeElem.innerHTML = data.size;
                    var realtimeQueueElem = document.getElementById('realtimeQueue');
                    if (realtimeQueueElem) realtimeQueueElem.innerHTML = '📊 Fila: ' + data.size + ' mensagens';
                }
            } catch (err) {
                console.error('Erro ao parsear SSE:', err);
            }
        };
        
        eventSource.onerror = function(e) {
            console.error('Erro SSE:', e);
            var sseStatus = document.getElementById('sseStatus');
            if (sseStatus) {
                sseStatus.innerHTML = '● Desconectado';
                sseStatus.style.color = '#f44336';
            }
            setTimeout(connectSSE, 3000);
        };
        
    } catch (error) {
        console.error('Erro:', error);
        setTimeout(connectSSE, 5000);
    }
}

function addMessageRealtime(texto) {
    var container = document.getElementById('realtimeArea');
    if (!container) return;
    var div = document.createElement('div');
    div.className = 'message realtime';
    var now = new Date();
    var time = now.toLocaleTimeString();
    div.innerHTML = '[' + time + '] ' + texto;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    if (container.children.length > 10) container.removeChild(container.children[0]);
}

function atualizarTotalMensagens() {
    var baseUrl = getBaseUrl();
    fetch(baseUrl + 'mensagens')
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.status === 'success' && Array.isArray(data.messages)) {
            var totalElem = document.getElementById('totalMessages');
            if (totalElem) totalElem.innerHTML = data.messages.length;
        }
    })
    .catch(function(error) {
        console.error('Erro ao atualizar total:', error);
    });
}

// Inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', connectSSE);
} else {
    connectSSE();
}

window.atualizarTotalMensagens = atualizarTotalMensagens;