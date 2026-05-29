const express = require("express");
const { Client, LocalAuth } = require("whatsapp-web.js");
const QRCode = require("qrcode");

const app = express();
app.use(express.json());

let qrCodeData = null;
let isReady = false;

let status = {
    ready: false,
    qr: null
};

// Initialize WhatsApp client
function initClient() {
    client = new Client({
        authStrategy: new LocalAuth({
            dataPath: './whatsapp_session'
        }),

   puppeteer: {
    headless: true,
    executablePath: process.env.PUPPETEER_EXECUTABLE_PATH,

    args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage",
        "--disable-gpu",
        "--disable-extensions",
        "--single-process"
    ]
}
});

    client.on('qr', (qr) => {
        console.log('New QR Code generated');

        qrCode = qr;
        isReady = false;

        status.qr = qr;
        status.ready = false;

        qrcode.generate(qr, { small: true });
    });

    client.on('ready', () => {
        console.log('WhatsApp Client Ready!');

        isReady = true;
        qrCode = null;

        status.ready = true;
        status.qr = null;
    });

    client.on('auth_failure', (msg) => {
        console.error('Auth failed:', msg);

        isReady = false;
        status.ready = false;
        status.error = msg;
    });

    client.on('disconnected', (reason) => {
        console.log('WhatsApp disconnected:', reason);

        isReady = false;
        status.ready = false;

        setTimeout(() => {
            console.log('Re-initializing client...');
            initClient();
        }, 5000);
    });

    client.initialize();
}

// Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        ready: isReady,
        timestamp: new Date().toISOString()
    });
});

// Bulk send
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

            results.push({
                phone: msg.phone,
                status: 'sent',
                name: msg.name
            });

            console.log(`✅ Sent to ${msg.name} (${msg.phone})`);
        } catch (error) {
            failed++;

            results.push({
                phone: msg.phone,
                status: 'failed',
                error: error.message,
                name: msg.name
            });

            console.error(
                `❌ Failed to send to ${msg.name} (${msg.phone}):`,
                error.message
            );
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

// Campaign send
app.post('/send', async (req, res) => {
    const { patients, message } = req.body;

    if (!isReady || !client) {
        return res.json({
            success: false,
            message: 'WhatsApp not ready. Scan QR first.',
            sent: 0,
            failed: 0
        });
    }

    if (!patients || !message) {
        return res.json({
            success: false,
            message: 'Invalid request format',
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

            const finalMessage = message.replace(
                '{name}',
                patient.name
            );

            await client.sendMessage(chatId, finalMessage);

            sent++;

            details.push({
                name: patient.name,
                phone: patient.phone,
                status: 'sent'
            });

            console.log(
                `✅ Sent to ${patient.name} (${patient.phone})`
            );
        } catch (error) {
            failed++;

            details.push({
                name: patient.name,
                phone: patient.phone,
                status: 'failed',
                error: error.message
            });

            console.error(
                `❌ Failed to send to ${patient.name} (${patient.phone})`,
                error.message
            );
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

// Status
app.get('/status', (req, res) => {
    res.json(status);
});

// QR JSON
app.get('/qr', (req, res) => {
    if (qrCode) {
        return res.json({
            qr: qrCode,
            ready: false,
            message: 'Scan QR code with WhatsApp'
        });
    }

    if (isReady) {
        return res.json({
            ready: true,
            message: 'Already connected',
            qr: null
        });
    }

    res.json({
        ready: false,
        message: 'Waiting for QR...',
        qr: null
    });
});

// QR page
app.get('/qr-page', (req, res) => {
    if (qrCode) {
        res.send(`
            <html>
            <body style="font-family:Arial;text-align:center;padding:40px;">
                <h1>📱 WhatsApp Bot</h1>
                <p>Scan this QR code</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=${encodeURIComponent(qrCode)}">
                <br><br>
                <button onclick="location.reload()">Refresh</button>
            </body>
            </html>
        `);
    } else if (isReady) {
        res.send(`
            <html>
            <body style="font-family:Arial;text-align:center;padding:40px;">
                <h1>✅ WhatsApp Connected!</h1>
                <p>Bot is ready.</p>
            </body>
            </html>
        `);
    } else {
        res.send(`
            <html>
            <body style="font-family:Arial;text-align:center;padding:40px;">
                <h2>🔄 Loading WhatsApp Bot...</h2>
                <script>
                    setTimeout(() => location.reload(), 10000);
                </script>
            </body>
            </html>
        `);
    }
});

// Root redirect
app.get('/', (req, res) => {
    res.redirect('/qr-page');
});

// Start
const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log("Server running on port " + PORT);
});
