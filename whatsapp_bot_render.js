const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const fs = require('fs');

const app = express();
app.use(express.json());

let client = null;
let qrCode = null;
let isReady = false;
let status = { ready: false, qr: null };

// Initialize WhatsApp client
function initClient() {
    // Get the path to puppeteer's bundled chromium
    const puppeteerPath = require('puppeteer').executablePath();
    
    client = new Client({
        authStrategy: new LocalAuth({
            dataPath: './whatsapp_session'
        }),
        puppeteer: {
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu'
            ],
            executablePath: puppeteerPath
        }
    });

    client.on('qr', (qr) => {
        console.log('New QR Code generated');
        qrCode = qr;
        status.qr = qr;
        status.ready = false;
        qrcode.generate(qr, { small: true });
    });

    client.on('ready', () => {
        console.log('WhatsApp Client Ready!');
        isReady = true;
        status.ready = true;
        status.qr = null;
        qrCode = null;
    });

    client.on('auth_failure', (msg) => {
        console.error('Auth failed:', msg);
        status.ready = false;
        status.error = msg;
    });

    client.on('disconnected', (reason) => {
        console.log('Client disconnected:', reason);
        isReady = false;
        status.ready = false;
        status.disconnected = reason;
        // Re-initialize after 5 seconds
        setTimeout(() => {
            console.log('Re-initializing client...');
            initClient();
        }, 5000);
    });

    client.initialize();
}

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        ready: isReady,
        timestamp: new Date().toISOString()
    });
});

// Add this endpoint for your PHP to call
app.post('/api/send-bulk', async (req, res) => {
    const { messages } = req.body;
    
    if (!isReady) {
        return res.json({ 
            success: false, 
            message: 'Bot not ready. Visit /qr-page to scan QR code first.' 
        });
    }
    
    const results = [];
    let sent = 0;
    let failed = 0;
    
    for (const msg of messages) {
        try {
            const chatId = `${msg.phone}@c.us`;
            await client.sendMessage(chatId, msg.message);
            sent++;
            results.push({ phone: msg.phone, status: 'sent', name: msg.name });
            console.log(`✅ Sent to ${msg.name} (${msg.phone})`);
        } catch (error) {
            failed++;
            results.push({ phone: msg.phone, status: 'failed', error: error.message, name: msg.name });
            console.error(`❌ Failed to send to ${msg.name} (${msg.phone}):`, error.message);
        }
        await new Promise(r => setTimeout(r, 3000));
    }
    
    res.json({ success: true, sent: sent, failed: failed, total: messages.length, results: results });
});

// API endpoint for PHP to send messages
app.post('/send', async (req, res) => {
    const { patients, message, campaign_name } = req.body;
    
    if (!isReady || !client) {
        return res.json({ success: false, message: 'WhatsApp not ready. Scan QR first.', sent: 0, failed: 0 });
    }
    
    let messagesToSend = [];
    
    if (patients && message) {
        messagesToSend = patients.map(patient => ({
            phone: patient.phone,
            name: patient.name,
            message: message.replace('{name}', patient.name)
        }));
    } else {
        return res.json({ success: false, message: 'Invalid request format', sent: 0, failed: 0 });
    }
    
    let sent = 0;
    let failed = 0;
    const details = [];
    
    for (const msg of messagesToSend) {
        try {
            const chatId = `${msg.phone}@c.us`;
            await client.sendMessage(chatId, msg.message);
            sent++;
            details.push({ name: msg.name, phone: msg.phone, status: 'sent' });
            console.log(`✅ Sent to ${msg.name} (${msg.phone})`);
        } catch (error) {
            failed++;
            details.push({ name: msg.name, phone: msg.phone, status: 'failed', error: error.message });
            console.error(`❌ Failed to send to ${msg.name} (${msg.phone}):`, error.message);
        }
        await new Promise(r => setTimeout(r, 3000));
    }
    
    res.json({ sent: sent, failed: failed, total: messagesToSend.length, details: details });
});

// API endpoint to check status
app.get('/status', (req, res) => {
    res.json(status);
});

// API endpoint to get QR code as JSON
app.get('/qr', (req, res) => {
    if (qrCode) {
        res.json({ qr: qrCode, ready: false, message: 'Scan QR code with WhatsApp' });
    } else if (isReady) {
        res.json({ ready: true, message: 'Already connected', qr: null });
    } else {
        res.json({ ready: false, message: 'Waiting for QR code...', qr: null });
    }
});

// ==================== ADD THIS NEW ENDPOINT ====================
// QR Code HTML Page - Scan this to connect WhatsApp
app.get('/qr-page', (req, res) => {
    if (qrCode) {
        res.send(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Scan QR Code - WhatsApp Bot</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        flex-direction: column;
                        font-family: Arial, sans-serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        margin: 0;
                        padding: 20px;
                    }
                    .container {
                        background: white;
                        padding: 30px;
                        border-radius: 20px;
                        text-align: center;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        max-width: 500px;
                        width: 100%;
                    }
                    h1 {
                        color: #25D366;
                        margin-bottom: 10px;
                    }
                    img {
                        margin: 20px 0;
                        border: 3px solid #25D366;
                        border-radius: 15px;
                        padding: 10px;
                        background: white;
                    }
                    .instructions {
                        color: #666;
                        margin: 20px 0;
                        text-align: left;
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 10px;
                    }
                    .status {
                        color: #ff9800;
                        font-weight: bold;
                        padding: 10px;
                        border-radius: 10px;
                        background: #fff3cd;
                        margin: 15px 0;
                    }
                    .status.connected {
                        background: #d4edda;
                        color: #155724;
                    }
                    button {
                        background: #25D366;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 25px;
                        font-size: 14px;
                        cursor: pointer;
                        margin-top: 10px;
                    }
                    button:hover {
                        background: #128C7E;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>📱 WhatsApp Bot</h1>
                    <p>Connect your WhatsApp to start sending messages</p>
                    
                    <div class="status" id="statusDiv">
                        ⏳ Waiting for QR scan...
                    </div>
                    
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=${encodeURIComponent(qrCode)}" 
                         alt="QR Code" 
                         id="qrImage">
                    
                    <div class="instructions">
                        <h3>📌 How to connect:</h3>
                        <ol>
                            <li>Open <strong>WhatsApp</strong> on your <strong>phone</strong></li>
                            <li>Tap <strong>Settings</strong> (Android) or bottom menu (iPhone)</li>
                            <li>Tap <strong>Linked Devices</strong></li>
                            <li>Tap <strong>Link a Device</strong></li>
                            <li><strong>Scan this QR code</strong> with your phone</li>
                        </ol>
                    </div>
                    
                    <button onclick="location.reload()">⟳ Refresh</button>
                    <p><small>Page auto-checks connection every 5 seconds</small></p>
                </div>
                
                <script>
                    function checkStatus() {
                        fetch('/status')
                            .then(res => res.json())
                            .then(data => {
                                const statusDiv = document.getElementById('statusDiv');
                                const qrImage = document.getElementById('qrImage');
                                if (data.ready === true) {
                                    statusDiv.innerHTML = '✅ CONNECTED! WhatsApp is ready to send messages!';
                                    statusDiv.classList.add('connected');
                                    if (qrImage) qrImage.style.opacity = '0.5';
                                } else if (data.ready === false && data.qr) {
                                    statusDiv.innerHTML = '⏳ Waiting for QR scan... Please scan the code above';
                                    statusDiv.classList.remove('connected');
                                }
                            })
                            .catch(err => console.log('Status check error:', err));
                    }
                    
                    // Check status every 5 seconds
                    setInterval(checkStatus, 5000);
                    checkStatus();
                </script>
            </body>
            </html>
        `);
    } else if (isReady) {
        res.send(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>WhatsApp Connected - Dr. LSK Clinic</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        flex-direction: column;
                        font-family: Arial, sans-serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        margin: 0;
                    }
                    .container {
                        background: white;
                        padding: 50px;
                        border-radius: 24px;
                        text-align: center;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        max-width: 500px;
                    }
                    h1 { color: #28a745; font-size: 48px; margin-bottom: 20px; }
                    h2 { color: #333; margin-bottom: 20px; }
                    p { color: #666; margin-bottom: 30px; line-height: 1.6; }
                    .btn {
                        background: #25D366;
                        color: white;
                        padding: 12px 30px;
                        border: none;
                        border-radius: 30px;
                        font-size: 16px;
                        cursor: pointer;
                        text-decoration: none;
                        display: inline-block;
                    }
                    .btn:hover { background: #128C7E; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>✅</h1>
                    <h2>WhatsApp is Connected!</h2>
                    <p>Your bot is ready to send messages to patients.<br>
                    You can now close this page and use the clinic system.</p>
                    <a href="/status" class="btn">Check Status</a>
                </div>
            </body>
            </html>
        `);
    } else {
        res.send(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Loading WhatsApp Bot</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        flex-direction: column;
                        font-family: Arial, sans-serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        margin: 0;
                    }
                    .container {
                        background: white;
                        padding: 40px;
                        border-radius: 20px;
                        text-align: center;
                    }
                    .loader {
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #25D366;
                        border-radius: 50%;
                        width: 50px;
                        height: 50px;
                        animation: spin 1s linear infinite;
                        margin: 20px auto;
                    }
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>🔄 Loading WhatsApp Bot</h2>
                    <div class="loader"></div>
                    <p>Please wait while the bot initializes...</p>
                    <p><small>Page will auto-refresh in 10 seconds</small></p>
                </div>
                <script>
                    setTimeout(() => location.reload(), 10000);
                </script>
            </body>
            </html>
        `);
    }
});

// Root endpoint - redirect to QR page
app.get('/', (req, res) => {
    res.redirect('/qr-page');
});

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`========================================`);
    console.log(`✅ WhatsApp Bot API server running`);
    console.log(`📡 Port: ${PORT}`);
    console.log(`📱 QR Page: http://localhost:${PORT}/qr-page`);
    console.log(`========================================`);
    initClient();
});