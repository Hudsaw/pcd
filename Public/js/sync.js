// Public/js/sync.js
function getBaseUrl() {
    var path = window.location.pathname;
    var basePath = path.substring(0, path.lastIndexOf('/') + 1);
    return basePath;
}

function enviarSync() {
    var input = document.getElementById('msgSync');
    var content = input.value;
    if (!content) { alert('Digite uma mensagem'); return; }
    addMessageSync('📤 Enviando: "' + content + '"', 'system');
    
    var baseUrl = getBaseUrl();
    fetch(baseUrl + 'mensagens', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: content, type: 'sync' })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            addMessageSync('✅ RESPOSTA: ' + data.response, 'sync');
            input.value = '';
            if (typeof atualizarTotalMensagens === 'function') atualizarTotalMensagens();
        } else {
            addMessageSync('❌ Erro: ' + (data.error || 'Erro desconhecido'), 'critical');
        }
    })
    .catch(function(err) { 
        addMessageSync('❌ Erro: ' + err.message, 'critical');
        console.error('Fetch error:', err);
    });
}

function buscarMensagens() {
    addMessageSync('📋 Buscando mensagens...', 'system');
    var baseUrl = getBaseUrl();
    fetch(baseUrl + 'mensagens')
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            addMessageSync('📋 ' + data.messages.length + ' mensagens encontradas', 'sync');
            if (data.messages.length > 0) {
                var ultimas = data.messages.slice(-3);
                ultimas.forEach(function(m) {
                    addMessageSync('   - [' + m.timestamp + '] ' + m.content, 'sync');
                });
            }
        }
    })
    .catch(function(err) {
        addMessageSync('❌ Erro ao buscar: ' + err.message, 'critical');
    });
}

addMessageSync('✅ Sync pronto - Use POST /mensagens', 'system');