<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Distribuído</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>🔗 Sistema Distribuído</h1>
        <div class="subtitle">Síncrono | Assíncrono | REST | Polling | gRPC | Mutex</div>

        <!-- Status Bar -->
        <div class="status-bar">
            <div class="status-item">
                <span>🔄 Polling:</span>
                <span id="pollingStatus">● Iniciando...</span>
            </div>
            <div class="status-item">
                <span>📨 Fila:</span>
                <span id="queueSize"><?php echo $queueSize; ?></span>
            </div>
            <div class="status-item">
                <span>📝 Processadas:</span>
                <span id="totalMessages"><?php echo count($messages); ?></span>
            </div>
        </div>

        <div class="grid">
            <!-- SÍNCRONO + REST -->
            <div class="card">
                <h2>📞 Comunicação SÍNCRONA + REST</h2>
                <p><span class="badge badge-success">GET/POST /mensagens</span> Resposta imediata</p>
                <div class="form-group">
                    <label>Mensagem:</label>
                    <input type="text" id="msgSync" placeholder="Digite sua mensagem...">
                </div>
                <button onclick="enviarSync()">📤 POST /mensagens (Sync)</button>
                <button onclick="buscarMensagens()">📋 GET /mensagens</button>
                <div id="syncArea" class="message-area"></div>
            </div>

            <!-- ASSÍNCRONO + FILA -->
            <div class="card">
                <h2>📨 Comunicação ASSÍNCRONA</h2>
                <p><span class="badge badge-info">Fila + Processamento</span> Mensagem entra na fila</p>
                <div class="form-group">
                    <label>Mensagem:</label>
                    <input type="text" id="msgAsync" placeholder="Digite sua mensagem...">
                </div>
                <button onclick="enviarAsync()">📤 Enfileirar (Assíncrono)</button>
                <button onclick="processarFila()">⚙️ Processar Fila</button>
                <div id="asyncArea" class="message-area"></div>
            </div>
        </div>

        <div class="grid">
            <!-- gRPC -->
            <div class="card">
                <h2>🔌 gRPC - Chamada entre Serviços</h2>
                <p><span class="badge badge-purple">Protocol Buffers</span> RPC de alta performance</p>
                <div class="form-group">
                    <label>Mensagem:</label>
                    <input type="text" id="msgGrpc" placeholder="Digite sua mensagem...">
                </div>
                <button onclick="chamarGrpc()">📞 Chamar gRPC</button>
                <div id="grpcArea" class="message-area"></div>
            </div>

            <!-- Polling - Tempo Real -->
            <div class="card">
                <h2>⚡ Atualização em TEMPO REAL (Polling)</h2>
                <p><span class="badge badge-warning">Polling a cada 2 segundos</span> Compatível com qualquer hospedagem</p>
                <div id="realtimeQueue" class="queue-item">Aguardando conexão...</div>
                <div id="realtimeArea" class="message-area"></div>
            </div>
        </div>

        <!-- Explicação da Região Crítica -->
        <div class="card">
            <h2>🔐 REGIÃO CRÍTICA - Controle de Concorrência</h2>
            <div class="mutex-info">
                <h3>🎯 QUAL recurso representa a região crítica?</h3>
                <p><strong>A fila de mensagens (queue.json)</strong> - recurso compartilhado</p>
                
                <h3>⚠️ QUAL problema poderia ocorrer?</h3>
                <p><strong>Race Condition</strong> - dois processos acessando a fila ao mesmo tempo:</p>
                <ul>
                    <li>Perda de mensagens</li>
                    <li>Processamento duplicado</li>
                    <li>Corrupção do JSON</li>
                </ul>
                
                <h3>✅ QUAL solução foi utilizada?</h3>
                <p><strong>MUTEX (Exclusão Mútua)</strong> com <code>flock()</code> do PHP</p>
                <ul>
                    <li>Operações enqueue/dequeue protegidas pelo Mutex</li>
                    <li>Método <code>synchronized()</code> garante exclusão mútua</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- JavaScripts -->
    <script src="js/sync.js"></script>
    <script src="js/async.js"></script>
    <script src="js/grpc.js"></script>
    <script src="js/polling.js"></script>
    
    <script>
        setTimeout(function() {
            console.log('=== Verificação de Funções ===');
            console.log('enviarSync:', typeof enviarSync);
            console.log('buscarMensagens:', typeof buscarMensagens);
            console.log('enviarAsync:', typeof enviarAsync);
            console.log('processarFila:', typeof processarFila);
            console.log('chamarGrpc:', typeof chamarGrpc);
        }, 500);
    </script>
</body>
</html>