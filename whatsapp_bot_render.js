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
            "--disable-background-networking",
            "--disable-sync",
            "--disable-default-apps",
            "--window-size=1280,720"
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

client.initialize();

function renderPage() {
    if (isReady) {
        return `
        <html>
        <body style="font-family:Arial;text-align:center;padding:40px;">
            <h1>✅ WhatsApp Connected</h1>
            <p>Bot running on Render</p>
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
            <img src="${qrCodeData}" width="300"/>
        </body>
        </html>`;
    }

    return `
    <html>
    <head>
    <meta http-equiv="refresh" content="5">
    </head>
    <body style="font-family:Arial;text-align:center;">
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

app.get("/status", (req, res) => {
    res.json({
        ready: isReady,
        status: status
    });
});

app.listen(PORT, () => {
    console.log("Server running on port " + PORT);
});
