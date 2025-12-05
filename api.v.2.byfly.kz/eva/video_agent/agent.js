const express = require('express');
const axios = require('axios');
const cors = require('cors');
const bodyParser = require('body-parser');

const app = express();
app.use(cors());
app.use(bodyParser.json());

const PORT = 3453;

// 🔐 API Key от D-ID
const D_ID_API_KEY = 'YWRzaG9wa3phbGFAZ21haWwuY29t:FbhnGqwZ9gMBBv_2TUw5k';
const D_ID_API_URL = 'https://api.d-id.com';
const AGENT_ID = 'agt_jPJa7fFk';

const HEADERS = {
    Authorization: `Basic ${D_ID_API_KEY}`,
    'Content-Type': 'application/json'
};

// ✅ 1. Создание WebRTC потока с автозапуском речи
app.get('/create-webrtc-stream', async (req, res) => {
    try {
        console.log('🟡 Creating WebRTC stream...');

        const response = await axios.post(
            `${D_ID_API_URL}/agents/${AGENT_ID}/streams`,
            {
                script: {
                    type: 'text',
                    input: 'Здравствуйте! Чем могу помочь?',
                    provider: {
                        type: 'elevenlabs',
                        voice_id: 'EXAVITQu4vr4xnSDxMaL'
                    }
                },
                config: {
                    driver_url: 'bank://lively/',
                    source_url: 'https://d-id-public-bucket.s3.us-west-2.amazonaws.com/or-roman.jpg',
                    fluent: true,
                    pad_audio: 0.0
                }
            },
            {
                headers: HEADERS,
                timeout: 10000
            }
        );

        console.log('✅ Stream created:', response.data.id);
        console.log('🟢 Response data:', response.data);

        res.json({
            success: true,
            streamId: response.data.id,
            offer: response.data.jsep ?? null, // <- важно!
            iceServers: response.data.ice_servers ?? [],
            sessionId: response.data.session_id
        });
    } catch (error) {
        console.error('❌ Error creating stream:', error.response?.data || error.message);
        res.status(500).json({
            success: false,
            error: error.response?.data || error.message,
            details: 'Failed to create WebRTC stream'
        });
    }
});

// ✅ 2. Отправка SDP-ответа клиента
app.post('/submit-answer', async (req, res) => {
    try {
        const { streamId, answer, sessionId } = req.body;

        if (!streamId || !answer || !sessionId) {
            return res.status(400).json({
                success: false,
                error: 'Missing required fields: streamId, answer, sessionId'
            });
        }

        console.log(`🟢 Submitting SDP answer for stream: ${streamId}`);

        const response = await axios.post(
            `${D_ID_API_URL}/agents/${AGENT_ID}/streams/${streamId}/sdp`,
            { answer, session_id: sessionId },
            {
                headers: HEADERS,
                timeout: 10000
            }
        );

        console.log('✅ Answer submitted successfully');

        res.json({
            success: true,
            status: response.data.status
        });
    } catch (error) {
        console.error('❌ Error submitting answer:', error.response?.data || error.message);
        res.status(500).json({
            success: false,
            error: error.response?.data || error.message,
            details: 'Failed to submit SDP answer'
        });
    }
});

// 🔍 Логирование всех запросов
app.use((req, res, next) => {
    console.log(`[${new Date().toISOString()}] ${req.method} ${req.path}`);
    next();
});

// ✅ 3. Health-check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// 🚀 Запуск сервера
app.listen(PORT, () => {
    console.log(`✅ Сервер запущен на http://localhost:${PORT}`);
});
