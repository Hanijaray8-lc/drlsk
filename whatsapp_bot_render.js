const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');

const app = express();
app.use(express.json());

let client = null;
let qrCode = null;
let isReady = false;

let status = {
    ready: false,
    qr: null,
    error: null
};

// ==============================
// Initialize WhatsApp Client
// ==============================
function initClient() {
    console.log('🚀 Initializing WhatsApp client...');

    client = new Client({
        authStrategy: new LocalAuth({
            dataPath: './whatsapp_session'
        }),

        puppeteer: {
            headless: true,

            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage'
            ]
        }
    });

    client.on('qr', (qr) => {
        console.log('📱 QR generated');

        qrCode = qr;
        isReady = false;

        status.qr = qr;
        status.ready = false;
        status.error = null;

        qrcode.generate(qr, { small: true });
    });

    client.on('authenticated', () => {
        console.log('✅ AUTHENTICATED');
    });

    client.on('loading_screen', (percent, message) => {
        console.log(`Loading ${percent}% - ${message}`);
    });

    client.on('ready', () => {
        console.log('✅ READY');

        isReady = true;
        qrCode = null;

        status.ready = true;
        status.qr = null;
        status.error = null;
    });

    client.on('auth_failure', (msg) => {
        console.error('❌ AUTH FAILURE:', msg);

        status.error = msg;
    });

    client.on('disconnected', (reason) => {
        console.log('⚠️ DISCONNECTED:', reason);

        isReady = false;

        status.ready = false;
        status.qr = null;
    });

    client.initialize().catch((err) => {
        console.error('❌ INIT ERROR:', err);
        status.error = err.message;
    });
}

// ==============================
// Health Check
// ==============================
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        ready: isReady,
        timestamp: new Date().toISOString()
    });
});

// ==============================
// Status
// ==============================
app.get('/status', (req, res) => {
    res.json(status);
});

// ==============================
// QR JSON
// ==============================
app.get('/qr', (req, res) => {
    if (qrCode) {
        return res.json({
            ready: false,
            qr: qrCode,
            message: 'Scan QR with WhatsApp'
        });
    }

    if (isReady) {
        return res.json({
            ready: true,
            qr: null,
            message: 'Connected'
        });
    }

    res.json({
        ready: false,
        qr: null,
        message: 'Waiting for QR...'
    });
});

// ==============================
// QR Page
// ==============================
app.get('/qr-page', (req, res) => {
    if (qrCode) {
        return res.send(`
        <html>
        <head>
            <title>WhatsApp QR</title>
        </head>
        <body style="font-family:Arial;text-align:center;padding:40px;">
            <h1>📱 WhatsApp Bot</h1>
            <p>Scan this QR with WhatsApp → Linked Devices</p>

            <img
                src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=${encodeURIComponent(qrCode)}"
            />

            <br><br>

            <button onclick="location.reload()">
                Refresh
            </button>

            <script>
                setTimeout(() => location.reload(), 10000);
            </script>
        </body>
        </html>
        `);
    }

    if (isReady) {
        return res.send(`
        <html>
        <body style="font-family:Arial;text-align:center;padding:40px;">
            <h1>✅ WhatsApp Connected</h1>
            <p>Bot is ready to send messages.</p>
        </body>
        </html>
        `);
    }

    res.send(`
    <html>
    <body style="font-family:Arial;text-align:center;padding:40px;">
        <h2>🔄 Loading WhatsApp...</h2>

        <script>
            setTimeout(() => location.reload(), 10000);
        </script>
    </body>
    </html>
    `);
});

// ==============================
// Bulk Send
// ==============================
app.post('/api/send-bulk', async (req, res) => {
    const { messages } = req.body;

    if (!isReady || !client) {
        return res.json({
            success: false,
            message: 'WhatsApp not ready. Scan QR first.'
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

            results.push({
                phone: msg.phone,
                name: msg.name,
                status: 'sent'
            });

            console.log(`✅ Sent to ${msg.name}`);
        } catch (error) {
            failed++;

            results.push({
                phone: msg.phone,
                name: msg.name,
                status: 'failed',
                error: error.message
            });

            console.error(error.message);
        }

        await new Promise((r) => setTimeout(r, 3000));
    }

    res.json({
        success: true,
        sent,
        failed,
        total: messages.length,
        results
    });
});

// ==============================
// Campaign Send
// ==============================
app.post('/send', async (req, res) => {
    const { patients, message } = req.body;

    if (!isReady || !client) {
        return res.json({
            success: false,
            message: 'WhatsApp not ready.',
            sent: 0,
            failed: 0
        });
    }

    let sent = 0;
    let failed = 0;
    const details = [];

    for (const patient of patients) {
        try {
            const chatId = `${patient.phone}@c.us`;

            const finalMessage =
                message.replace('{name}', patient.name);

            await client.sendMessage(chatId, finalMessage);

            sent++;

            details.push({
                name: patient.name,
                phone: patient.phone,
                status: 'sent'
            });

            console.log(`✅ Sent to ${patient.name}`);
        } catch (error) {
            failed++;

            details.push({
                name: patient.name,
                phone: patient.phone,
                status: 'failed',
                error: error.message
            });

            console.error(error.message);
        }

        await new Promise((r) => setTimeout(r, 3000));
    }

    res.json({
        sent,
        failed,
        total: patients.length,
        details
    });
});

// ==============================
// Root
// ==============================
app.get('/', (req, res) => {
    res.redirect('/qr-page');
});

// ==============================
// Start Server
// ==============================
const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log('================================');
    console.log('✅ WhatsApp API running');
    console.log(`📡 Port: ${PORT}`);
    console.log('================================');

    initClient();
});
