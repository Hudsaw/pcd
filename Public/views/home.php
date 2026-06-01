<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MessageFlow - Plataforma de Mensageria Distribuída</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="messaging-platform">
        <!-- Sidebar Principal -->
        <aside class="platform-sidebar">
            <div class="platform-logo">
                <div class="logo-icon">📨</div>
                <div class="logo-text">
                    <h2>MessageFlow</h2>
                    <p>Distributed Messaging</p>
                </div>
            </div>

            <!-- Controle do Servidor -->
            <div class="server-control">
                <button id="connectBtn" class="btn btn-connect" onclick="startServer()" style="flex:1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px; border-radius: 8px; color: white; cursor: pointer; font-weight: 500;">▶️ Conectar Servidor</button>
                <button id="disconnectBtn" class="btn btn-disconnect" onclick="disconnectWebSocket()" style="display:none;" style="flex:1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px; border-radius: 8px; color: white; cursor: pointer; font-weight: 500;">⏹️ Desconectar</button>
                <div class="ws-status">
                    <span class="ws-led"></span>
                    <span id="wsStatusText">Desconectado</span>
                </div>
            </div>

            <!-- Navegação -->
            <nav class="platform-nav">
                <a href="#" class="nav-item active">
                    <span class="nav-icon">💬</span>
                    <span>Mensagens</span>
                    <span class="nav-badge" id="totalMessages">0</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Análise</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>Configurações</span>
                </a>
            </nav>

            <!-- Status da Conexão -->
            <div class="connection-panel">
                <div class="connection-status">
                    <div class="status-led" id="statusLed"></div>
                    <div class="status-info">
                        <span class="status-label">WebSocket</span>
                        <span class="status-value" id="wsDetailStatus">Desconectado</span>
                    </div>
                </div>
                <div class="connection-details">
                    <div class="detail-row">
                        <span>Endpoint:</span>
                        <code>ws://localhost:8080</code>
                    </div>
                    <div class="detail-row">
                        <span>Protocolo:</span>
                        <code>RFC 6455</code>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Área Principal de Mensagens -->
        <main class="messages-area">
            <div class="chat-header">
                <div class="chat-info">
                    <h1>📨 Fluxo de Mensagens</h1>
                    <p>Comunicação síncrona e assíncrona com processamento distribuído</p>
                </div>
                <div class="header-stats">
                    <div class="header-stat">
                        <span class="stat-number" id="liveMessages">0</span>
                        <span class="stat-label">mensagens/hora</span>
                    </div>
                    <div class="header-stat">
                        <span class="stat-number" id="avgLatency">~50</span>
                        <span class="stat-label">ms latência</span>
                    </div>
                </div>
            </div>

            <!-- Stream de Mensagens -->
            <div class="messages-stream" id="messagesStream">
                <div class="system-message">
                    <div class="message-bubble">🎉 Bem-vindo ao MessageFlow! Conecte-se ao servidor para começar.</div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="composer-area">
                <div class="message-composer">
                    <textarea id="messageInput" class="composer-input" placeholder="Digite sua mensagem... Enter para síncrono, Ctrl+Enter para assíncrono" rows="2"></textarea>
                    <div class="composer-actions">
                        <button class="action-btn sync-btn" onclick="sendSyncMessage()">
                            <span class="btn-icon">⚡</span>
                            <span class="btn-text">Enviar Síncrono</span>
                            <span class="btn-badge">Resposta Imediata</span>
                        </button>
                        <button class="action-btn async-btn" onclick="sendAsyncMessage()">
                            <span class="btn-icon">📨</span>
                            <span class="btn-text">Enfileirar</span>
                            <span class="btn-badge">Processo Assíncrono</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Painel Lateral de Métricas -->
        <aside class="metrics-panel">
            
            <!-- gRPC Card -->
            <div class="grpc-card">
                <div class="card-header">
                    <h3>🔬 gRPC (HTTP/2)</h3>
                    <span class="badge">Real</span>
                </div>
                <div class="grpc-body">
                    <div class="grpc-input-group">
                        <input type="text" id="msgGrpc" class="grpc-input" placeholder="Digite mensagem para gRPC...">
                        <button class="grpc-send-btn" onclick="sendGrpcMessage()">Enviar</button>
                    </div>
                    <div id="grpcArea" class="grpc-messages-area">
                        <div class="grpc-message system">[Sistema] Aguardando mensagens gRPC...</div>
                    </div>
                    <div class="grpc-info">
                        <div class="info-row">
                            <span>Endpoint:</span>
                            <code>http://localhost:50051/grpc</code>
                        </div>
                        <div class="info-row">
                            <span>Protocolo:</span>
                            <span>HTTP/2</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila de Processamento -->
            <div class="queue-card">
                <div class="card-header">
                    <h3>📋 Fila de Processamento</h3>
                    <span class="queue-badge" id="queueCount">0</span>
                </div>
                <div class="queue-list" id="queueList">
                    <div class="queue-empty">Nenhuma mensagem na fila</div>
                </div>
                <div class="queue-info">
                    <div class="info-row">
                        <span>Processador:</span>
                        <span>Background Worker</span>
                    </div>
                    <div class="info-row">
                        <span>Intervalo:</span>
                        <span>3 segundos</span>
                    </div>
                    <div class="info-row">
                        <span>Estratégia:</span>
                        <span>FIFO</span>
                    </div>
                </div>
            </div>

            <!-- Região Crítica -->
            <div class="critical-card">
                <div class="card-header">
                    <h3>⚠️ Região Crítica</h3>
                    <span class="critical-badge">Ativa</span>
                </div>
                <div class="critical-content">
                    <div class="critical-resource">
                        <span class="resource-icon">📁</span>
                        <div class="resource-info">
                            <strong>queue.json</strong>
                            <small>Recurso Compartilhado</small>
                        </div>
                    </div>
                    <div class="critical-explanation">
                        <p><strong>Problema:</strong> Race Condition - perda de dados</p>
                        <p><strong>Solução:</strong> <span id="criticalSolution">Mutex via flock()</span></p>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <script src="js/app.js"></script>
</body>
</html>