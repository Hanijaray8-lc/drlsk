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
    client = new Client({
        authStrategy: new LocalAuth({
            dataPath: './whatsapp_session'
        }),
        puppeteer: {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
            executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || '/usr/bin/chromium-browser'
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
    });

    client.on('auth_failure', (msg) => {
        console.error('Auth failed:', msg);
        status.ready = false;
    });

    client.initialize();
}


// Add this endpoint for your PHP to call
app.post('/api/send-bulk', async (req, res) => {
    const { messages } = req.body;
    
    if (!isReady) {
        return res.json({ 
            success: false, 
            message: 'Bot not ready. Visit /qr to scan QR code first.' 
        });
    }
    
    const results = [];
    for (const msg of messages) {
        try {
            const chatId = `${msg.phone}@c.us`;
            await client.sendMessage(chatId, msg.message);
            results.push({ phone: msg.phone, status: 'sent' });
        } catch (error) {
            results.push({ phone: msg.phone, status: 'failed', error: error.message });
        }
        await new Promise(r => setTimeout(r, 3000));
    }
    
    res.json({ success: true, results });
});
// API endpoint for PHP to send messages
app.post('/send', async (req, res) => {
    const { patients, message, campaign_name } = req.body;
    
    if (!isReady || !client) {
        return res.json({ success: false, message: 'WhatsApp not ready. Scan QR first.' });
    }
    
    const results = { sent: 0, failed: 0, details: [] };
    
    for (const patient of patients) {
        try {
            const chatId = `${patient.phone}@c.us`;
            const msg = message.replace('{name}', patient.name);
            await client.sendMessage(chatId, msg);
            results.sent++;
            results.details.push({ name: patient.name, status: 'sent' });
        } catch (error) {
            results.failed++;
            results.details.push({ name: patient.name, status: 'failed', error: error.message });
        }
        await new Promise(r => setTimeout(r, 3000));
    }
    
    res.json(results);
});

// API endpoint to check status
app.get('/status', (req, res) => {
    res.json(status);
});

// API endpoint to get QR code
app.get('/qr', (req, res) => {
    if (qrCode) {
        res.json({ qr: qrCode, ready: false });
    } else if (isReady) {
        res.json({ ready: true, message: 'Already connected' });
    } else {
        res.json({ ready: false, message: 'Waiting for QR...' });
    }
});

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`API server running on port ${PORT}`);
    initClient();
});