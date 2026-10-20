const http = require('http');
const { Server } = require('socket.io');
const Redis = require('ioredis');

const PORT = process.env.PORT || 6001;
const LARAVEL_URL = process.env.LARAVEL_URL || 'http://nginx';
const SOCKET_CORS_ORIGIN = process.env.SOCKET_CORS_ORIGIN || 'http://localhost:8100';

const httpServer = http.createServer();
const io = new Server(httpServer, {
    cors: {
        origin: SOCKET_CORS_ORIGIN,
        methods: ['GET', 'POST'],
        credentials: true,
    },
});

const redis = new Redis({
    host: process.env.REDIS_HOST || 'redis',
    port: parseInt(process.env.REDIS_PORT || '6379'),
    retryStrategy: (times) => Math.min(times * 500, 5000),
});

redis.on('connect', () => console.log('Redis connected'));
redis.on('error', (err) => console.error('Redis error:', err.message));

// Validate HMAC socket token via Laravel
async function validateToken(token) {
    try {
        const res = await fetch(`${LARAVEL_URL}/api/socket/auth?token=${encodeURIComponent(token)}`);
        if (!res.ok) return null;
        return await res.json();
    } catch (err) {
        console.error('Token validation error:', err.message);
        return null;
    }
}

io.use(async (socket, next) => {
    const token = socket.handshake.query.token;
    if (!token) return next(new Error('missing token'));

    const user = await validateToken(token);
    if (!user) return next(new Error('unauthorized'));

    socket.user = user;
    next();
});

io.on('connection', (socket) => {
    const { userId, roles } = socket.user;
    socket.join(`user:${userId}`);
    if (roles && roles.includes('admin')) {
        socket.join('role:admin');
    }
    console.log(`Client connected: user:${userId}`);

    socket.on('disconnect', () => {
        console.log(`Client disconnected: user:${userId}`);
    });
});

// Subscribe to Redis channels
const subscriber = new Redis({
    host: process.env.REDIS_HOST || 'redis',
    port: parseInt(process.env.REDIS_PORT || '6379'),
    retryStrategy: (times) => Math.min(times * 500, 5000),
});

subscriber.subscribe('notifications', 'chat', (err) => {
    if (err) console.error('Subscribe error:', err.message);
    else console.log('Subscribed to Redis channels: notifications, chat');
});

subscriber.on('message', (channel, message) => {
    try {
        const payload = JSON.parse(message);
        if (channel === 'notifications') {
            io.to(`user:${payload.targetUserId}`).emit('notification', payload);
        } else if (channel === 'chat') {
            io.to(`user:${payload.receiverId}`).emit('chat_message', payload);
        }
    } catch (err) {
        console.error('Message parse error:', err.message);
    }
});

httpServer.listen(PORT, () => {
    console.log(`Socket.IO server listening on port ${PORT}`);
});
