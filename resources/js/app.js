import { createApp } from 'vue';
import { createPinia } from 'pinia'
import App from './App.vue';
import vuetify from './plugins/vuetify';
import router from './router';
import './bootstrap';

import { MaskInput } from 'vue-3-mask'

const app = createApp(App);
const pinia = createPinia()
app.use(pinia)

app.use(vuetify);
app.use(router);
app.component('MaskInput', MaskInput);
app.mount('#app');

import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';

router.isReady().then(async () => {
    const authStore = useAuthStore();
    const cartStore = useCartStore();
    await authStore.checkAuth();
        if (authStore.isAuthenticated) {
        await cartStore.getCart();
        console.log('Cart loaded:', cartStore.cart);
    } else {
        console.log('User not authenticated, skipping cart load');
    }

    console.log('Auth checked, state:', {
        isAuthenticated: authStore.isAuthenticated,
        user: authStore.user,
        cart: cartStore.cart
    });
});