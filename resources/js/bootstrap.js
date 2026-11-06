import axios from 'axios';
import { requestAuthentication } from './authPrompt.js';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            const requestPath = new URL(error.config?.url ?? '/', window.location.origin).pathname;
            requestAuthentication(requestPath.startsWith('/api/admin/') ? 'admin' : error.config?.authPromptContext ?? 'account');
        }
        return Promise.reject(error);
    },
);
