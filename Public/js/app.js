// Public/js/app.js

// ============================================
// VARIÁVEIS GLOBAIS
// ============================================
let ws = null;
let isConnected = false;
let lastMessageId = null;

// Detectar URL base corretamente
function getBaseUrl() {
    // Se estiver rodando no servidor PHP embutido
    if (window.location.port === '8000' || window.location.pathname.includes('/Public')) {
        return '';
    }
    return '/Public';
}

const BASE_URL = getBaseUrl();

// ============================================
// FUNÇÕES DE REQUISIÇÃO
// ============================================

async function apiRequest(url, options = {}) {
    const fullUrl = `${BASE_URL}${url}`;
    console.log('Requisição para:', fullUrl);
    
    try {
        const response = await fetch(fullUrl, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            }
        });
        
        const text = await response.text();
        console.log('Resposta:', text.substring(0, 200));
        
        // Tentar parsear JSON
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Resposta não é JSON:', text);
            throw new Error('Servidor retornou HTML em vez de JSON. Verifique se o index.php está acessível.');
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        throw error;
    }
}

// ============================================
// GERENCIAMENTO DO SERVIDOR
// ============================================

async function startServer() {
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');
    
    connectBtn.disabled = true;
    connectBtn.textContent = '🔄 Iniciando...';
    
    addSystemMessage('🔧 Iniciando servidor...');
    
    try {
        const response = await fetch(`../server/start.php?action=start&t=${Date.now()}`);
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            addSystemMessage('⚠️ Servidor WebSocket não encontrado');
            addSystemMessage('💡 Para usar WebSocket, execute manualmente:');
            addSystemMessage('   cd server && php server.php');
            return;
        }
        
        if (data.status === 'started' || data.status === 'already_running') {
            addSystemMessage('✅ Servidor iniciado! Conectando WebSocket...');
            setTimeout(() => connectWebSocket(), 2000);
        } else {
            addSystemMessage('❌ Erro ao iniciar servidor');
        }
    } catch (error) {
        addSystemMessage('⚠️ Servidor WebSocket não disponível');
        addSystemMessage('💡 Usando modo REST apenas');
    } finally {
        connectBtn.disabled = false;
        connectBtn.textContent = '▶️ Conectar Servidor';
    }
}

function connectWebSocket() {
    if (ws && ws.readyState === WebSocket.OPEN) {
        addSystemMessage('WebSocket já está conectado');
        return;
    }
    
    const hostname = window.location.hostname;
    const wsUrl = `ws://${hostname}:8080`;
    
    addSystemMessage(`🔌 Conectando a ${wsUrl}...`);
    
    ws = new WebSocket(wsUrl);
    
    ws.onopen = () => {
        isConnected = true;
        addSystemMessage('✅ WebSocket conectado!');
        updateConnectionStatus(true);
        ws.send(JSON.stringify({ action: 'get_stats' }));
    };
    
    ws.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data);
            handleWebSocketMessage(data);
        } catch (e) {
            console.error('Erro:', e);
        }
    };
    
    ws.onclose = () => {
        isConnected = false;
        addSystemMessage('⚠️ WebSocket desconectado');
        updateConnectionStatus(false);
    };
    
    ws.onerror = (error) => {
        console.error('WebSocket error:', error);
        addSystemMessage('❌ WebSocket não disponível. Usando modo REST.');
    };
}

function updateConnectionStatus(connected) {
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');
    const statusLed = document.getElementById('statusLed');
    const wsStatusText = document.getElementById('wsStatusText');
    const wsLed = document.querySelector('.ws-led');
    
    if (connected) {
        if (connectBtn) connectBtn.style.display = 'none';
        if (disconnectBtn) disconnectBtn.style.display = 'block';
        if (statusLed) statusLed.classList.add('connected');
        if (wsLed) wsLed.classList.add('connected');
        if (wsStatusText) wsStatusText.textContent = 'Conectado';
    } else {
        if (connectBtn) connectBtn.style.display = 'block';
        if (disconnectBtn) disconnectBtn.style.display = 'none';
        if (statusLed) statusLed.classList.remove('connected');
        if (wsLed) wsLed.classList.remove('connected');
        if (wsStatusText) wsStatusText.textContent = 'Desconectado';
    }
}

// ============================================
// MENSAGENS (Sync/Async)
// ============================================

async function sendSyncMessage() {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    
    if (!content) {
        addSystemMessage('⚠️ Digite uma mensagem');
        return;
    }
    
    addSystemMessage(`📤 Enviando síncrono: "${content.substring(0, 50)}"`);
    
    if (isConnected && ws) {
        ws.send(JSON.stringify({ action: 'send_sync', content: content }));
        input.value = '';
        return;
    }
    
    // Fallback para REST
    try {
        const data = await apiRequest('/mensagens', {
            method: 'POST',
            body: JSON.stringify({ content: content, type: 'sync' })
        });
        
        if (data.status === 'success') {
            addMessageToUI(data.message);
            addSystemMessage('⚡ Mensagem síncrona entregue');
            updateStats();
        } else {
            addSystemMessage('❌ Erro: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        addSystemMessage('❌ Erro ao enviar: ' + error.message);
    }
    
    input.value = '';
}

async function sendAsyncMessage() {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    
    if (!content) {
        addSystemMessage('⚠️ Digite uma mensagem');
        return;
    }
    
    addSystemMessage(`📨 Enfileirando: "${content.substring(0, 50)}"`);
    
    if (isConnected && ws) {
        ws.send(JSON.stringify({ action: 'send_async', content: content }));
        input.value = '';
        return;
    }
    
    // Fallback para REST
    try {
        const data = await apiRequest('/mensagens', {
            method: 'POST',
            body: JSON.stringify({ content: content, type: 'async' })
        });
        
        if (data.status === 'queued') {
            addSystemMessage(`📨 Mensagem enfileirada!`);
            updateStats();
        } else {
            addSystemMessage('❌ Erro ao enfileirar');
        }
    } catch (error) {
        addSystemMessage('❌ Erro: ' + error.message);
    }
    
    input.value = '';
}

// ============================================
// gRPC
// ============================================

async function sendGrpcMessage() {
    const input = document.getElementById('msgGrpc');
    const content = input.value.trim();
    
    if (!content) {
        addGrpcMessage('⚠️ Digite uma mensagem', 'system');
        return;
    }
    
    addGrpcMessage('🔬 Enviando via gRPC...', 'system');
    
    try {
        const response = await fetch('http://localhost:50052/grpc', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content: content })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            addGrpcMessage(`✅ Mensagem enviada!`, 'success');
            addGrpcMessage(`   📨 "${data.content}"`, 'info');
            input.value = '';
            
            addMessageToUI({
                content: content,
                type: 'grpc',
                timestamp: data.timestamp
            });
            updateStats();
        } else {
            addGrpcMessage('❌ Erro no gRPC', 'critical');
        }
    } catch (error) {
        addGrpcMessage('❌ Servidor gRPC não está rodando!', 'critical');
        addGrpcMessage('   Execute: php server/server.php', 'system');
    }
}

// ============================================
// POLLING
// ============================================

async function pollingUpdate() {
    try {
        const url = `${BASE_URL}/polling/atualizar?last_id=${lastMessageId || ''}&t=${Date.now()}`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.status === 'success') {
            if (data.new_messages && data.new_messages.length > 0) {
                data.new_messages.forEach(msg => addMessageToUI(msg));
                if (data.new_messages.length > 0) {
                    lastMessageId = data.new_messages[data.new_messages.length - 1].id;
                }
            }
            
            if (data.queue_size !== undefined) {
                const queueCount = document.getElementById('queueCount');
                if (queueCount) queueCount.innerText = data.queue_size;
                const metricQueueElem = document.getElementById('metricQueue');
                if (metricQueueElem) metricQueueElem.innerText = data.queue_size;
            }
        }
    } catch (error) {
        // Silencioso
    }
}

// ============================================
// CONFIGURAÇÕES
// ============================================

async function applySyncType() {
    const selected = document.querySelector('input[name="syncType"]:checked');
    if (!selected) return;
    
    const type = selected.value;
    const syncTypeBadge = document.getElementById('syncTypeBadge');
    const criticalSolution = document.getElementById('criticalSolution');
    
    addSystemMessage(`🔄 Aplicando tipo: ${type}...`);
    
    try {
        const data = await apiRequest('/set-sync-type', {
            method: 'POST',
            body: JSON.stringify({ type: type })
        });
        
        if (data.status === 'success') {
            addSystemMessage(`✅ ${data.message}`);
            if (syncTypeBadge) syncTypeBadge.innerText = type.toUpperCase();
            if (criticalSolution) criticalSolution.innerText = type === 'mutex' ? 'Mutex via flock()' : 'Semáforo binário';
        } else {
            addSystemMessage(`❌ Erro: ${data.error}`);
        }
    } catch (error) {
        addSystemMessage(`❌ Erro: ${error.message}`);
    }
}

// ============================================
// UI HELPERS
// ============================================

function addMessageToUI(message) {
    const container = document.getElementById('messagesStream');
    if (!container) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message-item ${message.type === 'sync' ? 'sync' : 'async'}`;
    
    let typeLabel = '📨 Assíncrona';
    if (message.type === 'sync') typeLabel = '⚡ Síncrona';
    else if (message.type === 'grpc') typeLabel = '🔬 gRPC';
    else if (message.type === 'async_processed') typeLabel = '📨 Processada';
    
    messageDiv.innerHTML = `
        <div class="message-bubble">
            <div class="message-meta">
                <span class="message-time">${message.timestamp || new Date().toLocaleTimeString()}</span>
                <span class="message-tech">${typeLabel}</span>
            </div>
            <div class="message-content">${escapeHtml(message.content)}</div>
        </div>
    `;
    
    const welcomeMsg = container.querySelector('.system-message');
    if (welcomeMsg && container.children.length === 1) {
        container.innerHTML = '';
    }
    
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
    
    while (container.children.length > 100) {
        container.removeChild(container.firstChild);
    }
    
    updateStats();
}

function addSystemMessage(text) {
    const container = document.getElementById('messagesStream');
    if (!container) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'system-message';
    messageDiv.innerHTML = `<div class="message-bubble">ℹ️ ${escapeHtml(text)}</div>`;
    
    const welcomeMsg = container.querySelector('.system-message');
    if (welcomeMsg && container.children.length === 1 && welcomeMsg.innerText.includes('Bem-vindo')) {
        container.innerHTML = '';
    }
    
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
    
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                if (messageDiv.parentNode) messageDiv.remove();
            }, 500);
        }
    }, 5000);
}

function addGrpcMessage(text, type) {
    const container = document.getElementById('grpcArea');
    if (!container) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `grpc-message ${type}`;
    messageDiv.innerHTML = `[${new Date().toLocaleTimeString()}] ${escapeHtml(text)}`;
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
    
    while (container.children.length > 50) {
        container.removeChild(container.firstChild);
    }
}

async function updateStats() {
    try {
        const data = await apiRequest('/mensagens');
        
        if (data.status === 'success') {
            const messages = data.messages || [];
            
            const syncCount = messages.filter(m => m.type === 'sync').length;
            const asyncCount = messages.filter(m => m.type === 'async_processed' || m.type === 'async').length;
            
            const elements = {
                totalMessages: document.getElementById('totalMessages'),
                metricTotalSent: document.getElementById('metricTotalSent'),
                metricSync: document.getElementById('metricSync'),
                metricAsync: document.getElementById('metricAsync'),
                liveMessages: document.getElementById('liveMessages')
            };
            
            if (elements.totalMessages) elements.totalMessages.innerText = messages.length;
            if (elements.metricTotalSent) elements.metricTotalSent.innerText = messages.length;
            if (elements.metricSync) elements.metricSync.innerText = syncCount;
            if (elements.metricAsync) elements.metricAsync.innerText = asyncCount;
            if (elements.liveMessages) elements.liveMessages.innerText = messages.length;
        }
    } catch (error) {
        console.log('Erro ao atualizar stats:', error);
    }
    
    // Atualizar fila
    try {
        const queueData = await apiRequest('/polling/atualizar');
        if (queueData.queue_size !== undefined) {
            const queueCount = document.getElementById('queueCount');
            const metricQueueElem = document.getElementById('metricQueue');
            if (queueCount) queueCount.innerText = queueData.queue_size;
            if (metricQueueElem) metricQueueElem.innerText = queueData.queue_size;
        }
    } catch (error) {
        console.log('Erro ao atualizar fila:', error);
    }
}

function refreshMetrics() {
    updateStats();
    addSystemMessage('📊 Métricas atualizadas');
}

function handleWebSocketMessage(data) {
    switch(data.type) {
        case 'history':
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => addMessageToUI(msg));
            }
            updateStats();
            break;
        case 'new_message':
            addMessageToUI(data.message);
            updateStats();
            break;
        case 'sync_response':
            addMessageToUI(data.message);
            addSystemMessage('⚡ Mensagem síncrona entregue');
            break;
        case 'async_response':
            addSystemMessage('📨 Mensagem enfileirada!');
            updateStats();
            break;
        case 'stats':
            const totalElem = document.getElementById('totalMessages');
            if (totalElem && data.total !== undefined) {
                totalElem.innerText = data.total;
            }
            break;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// INICIALIZAÇÃO
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Iniciar polling
    setInterval(pollingUpdate, 3000);
    setInterval(updateStats, 5000);
    updateStats();
    
    // Event listeners
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (e.ctrlKey) sendAsyncMessage();
                else sendSyncMessage();
            }
        });
    }
    
    addSystemMessage('🎉 MessageFlow pronto!');
    addSystemMessage('💡 Modo REST funcionando. Para WebSocket, execute: php server/server.php');
});