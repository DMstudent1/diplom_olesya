import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@services/api';
import { useCartStore } from './cart'; // ✅ Добавьте этот импорт

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const isLoading = ref(false);
    const isAuthenticated = ref(false);

    const checkAuth = async () => {
        isLoading.value = true;
        
        try {
            const response = await api.get('/me');
            console.log('checkAuth response:', response);
            user.value = response.data;
            isAuthenticated.value = true;
            return { success: true };
        } catch (error) {
            user.value = null;
            isAuthenticated.value = false;
            console.log('Не авторизован');
            return { success: false, error };
        } finally {
            isLoading.value = false;
        }
    };

    const login = async (credentials) => {
        isLoading.value = true;
        
        try {
            const response = await api.post('/login', credentials);
            console.log('Login response:', response);
            
            // ✅ После логина сразу получаем данные пользователя
            const userResponse = await api.get('/me');
            user.value = userResponse.data;
            isAuthenticated.value = true;
            
            // ✅ Загружаем корзину
            const cartStore = useCartStore();
            await cartStore.getCart();
            
            return { success: true };
        } catch (error) {
            console.error('Login error:', error);
            user.value = null;
            isAuthenticated.value = false;
            return { 
                success: false, 
                error: error.response?.data?.error || error.response?.data?.message || 'Ошибка входа' 
            };
        } finally {
            isLoading.value = false;
        }
    };

    const register = async (userData) => {
        isLoading.value = true;
        
        try {
            const response = await api.post('/register', userData);
            console.log('Register response:', response);
            
            // ✅ После регистрации сразу получаем данные пользователя
            const userResponse = await api.get('/me');
            user.value = userResponse.data;
            isAuthenticated.value = true;
            
            // ✅ Загружаем корзину
            const cartStore = useCartStore();
            await cartStore.getCart();
            
            return { success: true };
        } catch (error) {
            console.error('Register error:', error);
            user.value = null;
            isAuthenticated.value = false;
            return { 
                success: false, 
                error: error.response?.data?.error || error.response?.data?.message || 'Ошибка регистрации' 
            };
        } finally {
            isLoading.value = false;
        }
    };

    const updateUser = async (data) => {
        try {
            const response = await api.put('/user', data);
            user.value = response.data;
            return { success: true };
        } catch (error) {
            return { 
                success: false, 
                error: error.response?.data?.error || 'Ошибка обновления' 
            };
        }
    };

    const logout = async () => {
        try {
            await api.post('/logout');
        } catch (error) {
            console.error('Ошибка при выходе:', error);
        } finally {
            // ✅ Очищаем данные
            user.value = null;
            isAuthenticated.value = false;
            
            // ✅ Очищаем корзину
            const cartStore = useCartStore();
            cartStore.cart = null;
        }
    };

    return {
        user,
        isLoading,
        isAuthenticated,
        checkAuth,
        login,
        register,
        updateUser,
        logout,
    };
});