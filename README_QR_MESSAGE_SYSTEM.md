# WhatsApp Bot with QR Code & Message Queue System

## 🎯 Overview
This WhatsApp bot system provides:
- **QR Code Scanning** for WhatsApp authentication
- **Message Queuing** for smooth, rate-limited message sending
- **REST API** for sending bulk messages
- **Web UI** for real-time status monitoring

---

## 📋 System Architecture

### Components:
1. **whatsapp_bot_render.js** - Node.js Express server with WhatsApp client
2. **whatsapp_send.php** - PHP handler for bulk message sending
3. **test_send.html** - Web UI for testing message sending
4. **DailyPatientHistory.php** - Patient management interface

### Flow:
```
QR Code Display → WhatsApp Scan → Bot Ready 
    ↓
Messages API (/send) → Queue System → Rate-Limited Sending (500ms between messages)
    ↓
Queue Status Monitoring (/queue-status)
```

---

## 🚀 Quick Start

### 1. **Install Dependencies**
```bash
npm install
```

**Required packages in package.json:**
- express (^4.18.2)
- whatsapp-web.js (^1.34.1)
- qrcode (latest)
- puppeteer (^24.10.2)

### 2. **Start the Bot**
```bash
npm start
# or
node whatsapp_bot_render.js
```

Server runs on: `http://localhost:10000`

### 3. **Scan QR Code**
- Open `http://localhost:10000` in browser
- A QR code will appear if not authenticated
- Open WhatsApp on your phone
- Go to **Settings → Linked Devices → Link a Device**
- Scan the QR code displayed

### 4. **Send Messages**
- Once connected, use `http://localhost:10000/test_send.html` to send messages
- Or use the API directly with cURL/Postman

---

## 🔌 API Endpoints

### **GET /status**
Check bot connection status
```bash
curl http://localhost:10000/status
```

**Response:**
```json
{
  "ready": true,
  "status": "READY",
  "queueLength": 5,
  "isSending": false
}
```

---

### **POST /send**
Queue messages for sending
```bash
curl -X POST http://localhost:10000/send \
  -H "Content-Type: application/json" \
  -d '{
    "messages": [
      {
        "phone": "919876543210",
        "message": "Hello World!"
      }
    ]
  }'
```

**Request Format:**
```json
{
  "messages": [
    {
      "phone": "919876543210",  // Without +, with country code (91 for India)
      "message": "Your message here"
    }
  ]
}
```

**Response (Success):**
```json
{
  "success": true,
  "queued": 1,
  "message": "1 message(s) queued for sending",
  "messages": [
    {
      "id": 1234567890.123,
      "phone": "919876543210",
      "message": "Your message here",
      "status": "queued",
      "addedAt": "2024-05-29T10:30:00.000Z"
    }
  ]
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "WhatsApp bot is not ready. Please scan QR code first.",
  "queued": 0
}
```

---

### **GET /queue-status**
Check current message queue
```bash
curl http://localhost:10000/queue-status
```

**Response:**
```json
{
  "totalQueued": 3,
  "isSending": true,
  "messages": [
    {
      "id": 1234567890.123,
      "phone": "919876543210",
      "message": "Hello",
      "status": "queued",
      "addedAt": "2024-05-29T10:30:00.000Z"
    }
  ]
}
```

---

## 📱 Using PHP to Send Messages

### Via **whatsapp_send.php**
```php
$data = [
    'patients' => json_encode([
        [
            'phone' => '919876543210',
            'name' => 'John Doe'
        ]
    ]),
    'custom_message' => 'Important update',
    'campaign_date' => '2024-06-01',
    'campaign_topic' => 'Health Camp',
    'campaign_start_time' => '09:00',
    'campaign_end_time' => '13:00'
];

$ch = curl_init('http://localhost:10000/send');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['messages' => $messages]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
```

---

## ⚙️ Features Explained

### **Message Queue System**
- Messages are queued instead of sent immediately
- 500ms delay between messages (prevents WhatsApp rate limiting)
- Automatic queue processing every 100ms
- Failed messages are logged with error details

### **Rate Limiting**
- Delay: 500ms between each message
- Prevents WhatsApp temporary bans
- Can be adjusted in `processMessageQueue()` function

### **Status Monitoring**
- Real-time status updates on web UI
- Queue size display
- Sending status indicator
- Error tracking

---

## 🔧 Configuration

### Port
Edit `whatsapp_bot_render.js`:
```javascript
const PORT = process.env.PORT || 10000;
```

### Message Delay
Edit `whatsapp_bot_render.js` in `processMessageQueue()`:
```javascript
await new Promise(r => setTimeout(r, 500)); // 500ms delay
```

### Authentication Path
```javascript
dataPath: "./.wwebjs_auth" // Change if needed
```

---

## 📊 Testing Guide

### **Test 1: Single Message**
1. Open `http://localhost:10000/test_send.html`
2. Enter phone: `919876543210`
3. Enter message: "Test message"
4. Click "Send Message"
5. Check WhatsApp on phone

### **Test 2: Check Queue**
1. Send 5 messages quickly
2. Click "Check Queue"
3. Watch messages process with delays

### **Test 3: Bulk via PHP**
1. Use `DailyPatientHistory.php` to select patients
2. Click "Send Messages"
3. Messages queue and send automatically

---

## ⚠️ Troubleshooting

### **"Bot not ready" Error**
- Wait for "READY" status
- Scan QR code again if needed
- Check browser console for errors

### **Messages Not Sending**
- Verify bot status at `/status`
- Check queue at `/queue-status`
- Ensure WhatsApp is logged in on phone
- Wait between messages (500ms delay)

### **QR Code Not Displaying**
- Clear browser cache
- Restart Node.js server
- Check for auth failures in console

### **Connection Drops**
- Re-scan QR code
- Check internet connection
- Restart bot: `npm start`

---

## 🔐 Security Notes

- Keep bot URL protected in production
- Use environment variables for sensitive data
- Validate phone numbers before sending
- Implement request authentication if public

---

## 📝 Logs

Check console output for:
- `✅ Message sent to...` - Successful send
- `❌ Failed to send to...` - Send failure
- `📝 Message queued for...` - Queue addition
- `Loading X% - Y` - Bot initialization

---

## 🚀 Deployment (Render.com)

1. Push code to GitHub
2. Connect GitHub to Render.com
3. Set environment variables:
   - `PUPPETEER_EXECUTABLE_PATH`: Leave blank (auto-detected)
4. Deploy and note the URL
5. Update `RENDER_API_URL` in `whatsapp_send.php`

---

## 📞 API Usage Examples

### Python
```python
import requests
import json

url = "http://localhost:10000/send"
messages = [
    {
        "phone": "919876543210",
        "message": "Hello from Python!"
    }
]

response = requests.post(url, json={"messages": messages})
print(response.json())
```

### JavaScript (Fetch)
```javascript
const messages = [{
    phone: "919876543210",
    message: "Hello from JavaScript!"
}];

fetch("http://localhost:10000/send", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({messages})
})
.then(r => r.json())
.then(data => console.log(data));
```

### cURL
```bash
curl -X POST http://localhost:10000/send \
  -H "Content-Type: application/json" \
  -d '{"messages":[{"phone":"919876543210","message":"Hello!"}]}'
```

---

## ✨ Key Features

✅ **QR Code Authentication** - Simple WhatsApp link via QR  
✅ **Smart Queue System** - Smooth, rate-limited sending  
✅ **REST API** - Easy integration with any backend  
✅ **Real-time Monitoring** - Web UI with live status  
✅ **Bulk Sending** - Send to multiple recipients  
✅ **Error Handling** - Automatic retry with logging  
✅ **Scalable** - Handles hundreds of messages  

---

## 📞 Support

For issues:
1. Check console logs for errors
2. Verify WhatsApp login on phone
3. Test `/status` endpoint
4. Check queue at `/queue-status`
5. Review error messages in responses

---

**Last Updated:** May 29, 2024  
**Version:** 1.0.0
