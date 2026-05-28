const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const puppeteer = require('puppeteer');

const app = express();
app.use(express.json());

let client = null;
let qrCode = null;
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
            executablePath: puppeteer.executablePath(),
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--no-first-run',
                '--no-zygote',
                '--single-process'
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
    });

    client.on('disconnected', (reason) => {
        console.log('WhatsApp disconnected:', reason);

        isReady = false;
        status.ready = false;

        setTimeout(() => {
            initClient();
        }, 5000);
    });

    client.initialize();
}

// Bulk send endpoint
app.post('/api/send-bulk', async (req, res) => {
    const { messages } = req.body;

    if (!isReady) {
        return res.json({
            success: false,
            message: 'Bot not ready. Visit /qr and scan first.'
        });
    }

    const results = [];

    for (const msg of messages) {
        try {
            const chatId = `${msg.phone}@c.us`;

            await client.sendMessage(chatId, msg.message);

            results.push({
                phone: msg.phone,
                status: 'sent'
            });
        } catch (error) {
            results.push({
                phone: msg.phone,
                status: 'failed',
                error: error.message
            });
        }

        await new Promise((r) => setTimeout(r, 3000));
    }

    res.json({
        success: true,
        results
    });
});

// Campaign send endpoint
app.post('/send', async (req, res) => {
    const { patients, message } = req.body;

    if (!isReady || !client) {
        return res.json({
            success: false,
            message: 'WhatsApp not ready. Scan QR first.'
        });
    }

    const results = {
        sent: 0,
        failed: 0,
        details: []
    };

    for (const patient of patients) {
        try {
            const chatId = `${patient.phone}@c.us`;

            const finalMessage = message.replace(
                '{name}',
                patient.name
            );

            await client.sendMessage(
                chatId,
                finalMessage
            );

            results.sent++;

            results.details.push({
                name: patient.name,
                status: 'sent'
            });
        } catch (error) {
            results.failed++;

            results.details.push({
                name: patient.name,
                status: 'failed',
                error: error.message
            });
        }

        await new Promise((r) => setTimeout(r, 3000));
    }

    res.json(results);
});

// Status endpoint
app.get('/status', (req, res) => {
    res.json(status);
});

// QR endpoint
app.get('/qr', (req, res) => {
    if (qrCode) {
        return res.json({
            ready: false,
            qr: qrCode
        });
    }

    if (isReady) {
        return res.json({
            ready: true,
            message: 'Already connected'
        });
    }

    res.json({
        ready: false,
        message: 'Waiting for QR...'
    });
});

// Home route
app.get('/', (req, res) => {
    res.send('WhatsApp bot is running');
});

// Start server
const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log(`API server running on port ${PORT}`);
    initClient();
});
