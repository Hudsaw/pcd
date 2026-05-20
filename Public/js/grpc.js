// Public/js/grpc.js
function addMessageGrpc(texto, tipo) {
    tipo = tipo || 'system';
    var container = document.getElementById('grpcArea');
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

function chamarGrpc() {
    var input = document.getElementById('msgGrpc');
    var content = input.value;
    if (!content) { alert('Digite uma mensagem'); return; }
    addMessageGrpc('📞 Chamando gRPC: "' + content + '"', 'system');
    
    fetch('grpc/enviar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conteudo: content })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            addMessageGrpc('✅ gRPC RESPONSE: ' + data.message, 'grpc');
            addMessageGrpc('   🔧 ' + data.protocol, 'grpc');
            input.value = '';
        }
    });
}

addMessageGrpc('✅ gRPC pronto', 'system');