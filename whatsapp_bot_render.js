const express = require("express");
const { Client, LocalAuth } = require("whatsapp-web.js");
const QRCode = require("qrcode");

const app = express();
const PORT = process.env.PORT || 10000;

let qrCodeData = null;
let isReady = false;
let status = "Starting...";

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: "render-bot",
        dataPath: "./.wwebjs_auth"
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

client.on("loading_screen", (percent, message) => {
    console.log(`Loading ${percent}% - ${message}`);
    status = `Loading ${percent}% - ${message}`;
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
    status = "AUTH FAILURE: " + msg;
});

client.on("disconnected", reason => {
    console.log("DISCONNECTED:", reason);
    status = "DISCONNECTED: " + reason;
    isReady = false;
});

client.initialize();

function renderPage() {
    if (isReady) {
        return `
        <html>
        <body style="font-family:Arial;text-align:center;padding:40px;">
            <h1>✅ WhatsApp Connected</h1>
            <p>Bot is running on Render.</p>
        </body>
        </html>`;
    }

    if (qrCodeData) {
        return `
        <html>
        <head>
        <meta http-equiv="refresh" content="5">
        </head>
        <body style="font-family:Arial;text-align:center;padding:30px;">
            <h1>📱 WhatsApp Bot</h1>
            <p>Status: ${status}</p>
            <p>Scan with WhatsApp → Linked Devices</p>
            <img src="${qrCodeData}" width="300" />
            <br><br>
            <button onclick="location.reload()">Refresh</button>
        </body>
        </html>`;
    }

    return `
    <html>
    <head>
    <meta http-equiv="refresh" content="5">
    </head>
    <body style="font-family:Arial;text-align:center;padding:40px;">
        <h2>${status}</h2>
    </body>
    </html>`;
}

app.get("/", (req, res) => {
    res.send(renderPage());
});

app.get("/qr-page", (req, res) => {
    res.send(renderPage());
});

app.listen(PORT, () => {
    console.log("Server running on port " + PORT);
});
