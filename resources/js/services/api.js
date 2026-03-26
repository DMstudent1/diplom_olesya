import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    withCredentials: true, // 👈 Критически важно для отправки cookie
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Логируем все запросы
api.interceptors.request.use(config => {
    console.log('=== API REQUEST ===');
    console.log(`${config.method.toUpperCase()} ${config.url}`);
    console.log('With credentials:', config.withCredentials);
    // ❌ НЕ проверяем localStorage - токен в httpOnly cookie
    return config;
});

// Логируем все ответы
api.interceptors.response.use(
    response => {
        console.log('=== API RESPONSE ===');
        console.log(`${response.config.method.toUpperCase()} ${response.config.url}`);
        console.log('Status:', response.status);
        console.log('User data:', response.data);
        return response;
    },
    error => {
        console.error('=== API ERROR ===');
        console.error(`${error.config?.method?.toUpperCase()} ${error.config?.url}`);
        console.error('Status:', error.response?.status);
        console.error('Data:', error.response?.data);
        
        // Если 401 и это не запрос на логин, можно очистить стор
        if (error.response?.status === 401 && error.config?.url !== '/login') {
            // Опционально: очистить данные пользователя
            console.log('Unauthorized, user should be logged out');
        }
        
        return Promise.reject(error);
    }
);

export default api;