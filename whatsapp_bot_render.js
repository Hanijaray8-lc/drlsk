const express = require("express");
const { Client, LocalAuth } = require("whatsapp-web.js");
const QRCode = require("qrcode");

const app = express();
const PORT = process.env.PORT || 10000;

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

let qrCodeData = null;
let isReady = false;
let status = "Starting...";
let messageQueue = [];
let isSending = false;

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: "render-bot",
        dataPath: "./.wwebjs_auth"
    }),

   webVersionCache: {
    type: "remote",
    remotePath:
        "https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html"
},

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

client.on("loading_screen", (percent, message) => {
    console.log(`Loading ${percent}% - ${message}`);
    status = `Loading ${percent}%`;
});

client.on("qr", async (qr) => {
    console.log("QR RECEIVED");
    status = "QR RECEIVED";
    qrCodeData = await QRCode.toDataURL(qr);
    isReady = false;
});

client.on("authenticated", () => {
    console.log("AUTHENTICATED");
    status = "AUTHENTICATED";
});

client.on("ready", () => {
    console.log("READY");
    status = "READY";
    qrCodeData = null;
    isReady = true;
});

client.on("auth_failure", msg => {
    console.log("AUTH FAILURE:", msg);
    status = "AUTH FAILURE";
});

client.on("disconnected", reason => {
    console.log("DISCONNECTED:", reason);
    status = "DISCONNECTED";
    isReady = false;
});
client.on("error", err => {
    console.log("CLIENT ERROR:", err);
    status = "ERROR: " + err.message;
});
console.log("Initializing WhatsApp...");
client.initialize();

// ==================== MESSAGE QUEUE HANDLER ====================
async function processMessageQueue() {
    if (isSending || messageQueue.length === 0 || !isReady) {
        return;
    }

    isSending = true;

    while (messageQueue.length > 0) {
        const msgObj = messageQueue.shift();
        try {
            // Add delay between messages (500ms) to avoid rate limiting
            await new Promise(r => setTimeout(r, 500));
            
            const phoneNumber = msgObj.phone.includes('@c.us') 
                ? msgObj.phone 
                : msgObj.phone + '@c.us';
            
            await client.sendMessage(phoneNumber, msgObj.message);
            console.log(`✅ Message sent to ${msgObj.phone}`);
            msgObj.status = 'sent';
        } catch (error) {
            console.error(`❌ Failed to send to ${msgObj.phone}:`, error.message);
            msgObj.status = 'failed';
            msgObj.error = error.message;
        }
    }

    isSending = false;
}

// Process queue every 100ms when bot is ready
setInterval(processMessageQueue, 100);
// ================================================================

function renderPage() {
    if (isReady) {
        return `
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 40px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .status { font-size: 18px; color: #27ae60; font-weight: bold; }
                .queue-info { margin-top: 20px; padding: 15px; background: #ecf0f1; border-radius: 5px; }
                .queue-count { font-size: 16px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>✅ WhatsApp Connected</h1>
                <p class="status">Bot running on Render</p>
                <div class="queue-info">
                    <h3>Queue Status</h3>
                    <div class="queue-count">📤 Queued Messages: <strong>${messageQueue.length}</strong></div>
                    <div class="queue-count">📨 Sending: <strong>${isSending ? 'YES' : 'No'}</strong></div>
                </div>
            </div>
        </body>
        </html>`;
    }

    if (qrCodeData) {
        return `
        <html>
        <head>
        <meta http-equiv="refresh" content="5">
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 30px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .status { color: #e74c3c; font-weight: bold; }
            img { border: 2px solid #3498db; border-radius: 5px; }
        </style>
        </head>
        <body>
            <div class="container">
                <h1>📱 WhatsApp Bot</h1>
                <p class="status">Status: ${status}</p>
                <p>Scan with WhatsApp → Linked Devices</p>
                <img src="${qrCodeData}" width="300"/>
            </div>
        </body>
        </html>`;
    }

    return `
    <html>
    <head>
    <meta http-equiv="refresh" content="5">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #3498db; }
    </style>
    </head>
    <body>
        <div class="container">
            <h2>⏳ ${status}</h2>
        </div>
    </body>
    </html>`;
}

app.get("/", (req, res) => {
    res.send(renderPage());
});

app.get("/qr-page", (req, res) => {
    res.send(renderPage());
});

app.get("/status", (req, res) => {
    res.json({
        ready: isReady,
        status: status,
        queueLength: messageQueue.length,
        isSending: isSending
    });
});

// ==================== SEND MESSAGE ENDPOINT ====================
app.post("/send", async (req, res) => {
    // Check if bot is ready
    if (!isReady) {
        return res.status(503).json({
            success: false,
            message: "WhatsApp bot is not ready. Please scan QR code first.",
            queued: 0
        });
    }

    const { messages } = req.body;

    if (!messages || !Array.isArray(messages)) {
        return res.status(400).json({
            success: false,
            message: "Invalid request. Expected 'messages' array."
        });
    }

    if (messages.length === 0) {
        return res.status(400).json({
            success: false,
            message: "No messages provided."
        });
    }

    // Add messages to queue
    const addedMessages = [];
    for (const msg of messages) {
        if (msg.phone && msg.message) {
            const msgObj = {
                id: Date.now() + Math.random(),
                phone: msg.phone,
                message: msg.message,
                status: 'queued',
                addedAt: new Date()
            };
            messageQueue.push(msgObj);
            addedMessages.push(msgObj);
            console.log(`📝 Message queued for ${msg.phone}`);
        }
    }

    // Trigger queue processing immediately
    setTimeout(processMessageQueue, 50);

    res.status(200).json({
        success: true,
        queued: addedMessages.length,
        message: `${addedMessages.length} message(s) queued for sending`,
        messages: addedMessages
    });
});

// ==================== GET QUEUE STATUS ====================
app.get("/queue-status", (req, res) => {
    res.json({
        totalQueued: messageQueue.length,
        isSending: isSending,
        messages: messageQueue
    });
});
// ===========================================================

app.listen(PORT, () => {
    console.log("Server running on port " + PORT);
});
