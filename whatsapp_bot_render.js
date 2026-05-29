const express = require("express");
const { Client, LocalAuth } = require("whatsapp-web.js");
const QRCode = require("qrcode");

const app = express();
const PORT = process.env.PORT || 10000;

let qrCodeData = null;
let isReady = false;

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: "render-bot"
    }),

    puppeteer: {
        headless: true,
        args: [
            "--no-sandbox",
            "--disable-setuid-sandbox",
            "--disable-dev-shm-usage",
            "--disable-gpu"
        ]
    }
});

client.on("qr", async (qr) => {
    console.log("QR received");
    qrCodeData = await QRCode.toDataURL(qr);
    isReady = false;
});

client.on("authenticated", () => {
    console.log("WhatsApp authenticated");
});

client.on("ready", () => {
    console.log("WhatsApp ready");
    qrCodeData = null;
    isReady = true;
});

client.on("auth_failure", msg => {
    console.log("Auth failure:", msg);
    qrCodeData = null;
    isReady = false;
});

client.on("disconnected", reason => {
    console.log("Disconnected:", reason);
    isReady = false;
});

client.initialize();

app.get("/qr-page", (req, res) => {
    if (isReady) {
        return res.send(`
            <h1>✅ WhatsApp Connected</h1>
            <p>Your bot is running on Render.</p>
        `);
    }

    if (qrCodeData) {
        return res.send(`
            <html>
            <body style="font-family:Arial;text-align:center;padding:30px;">
                <h1>📱 WhatsApp Bot</h1>
                <p>Scan this QR with WhatsApp → Linked Devices</p>
                <img src="${qrCodeData}" width="300" />
                <br><br>
                <button onclick="location.reload()">Refresh</button>
            </body>
            </html>
        `);
    }

    res.send("<h2>Loading WhatsApp...</h2>");
});



app.listen(PORT, () => {
    console.log("Server running on port " + PORT);
});
