import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@services/api';

export const useCartStore = defineStore('cart', () => {
    const cart = ref(null);
    const isLoading = ref(false);

    const getCart = async () => {
        isLoading.value = true;

        try {
            // Cookie с токеном отправляется автоматически
            const response = await api.get('/cart');
            console.log('checkAuth response:', response.data);
            cart.value = response.data;
            return { success: true };
        } catch (error) {
            cart.value = null;
            return { success: false, error };
        } finally {
            isLoading.value = false;
        }
    };

    return {
        cart,
        getCart
    };
})