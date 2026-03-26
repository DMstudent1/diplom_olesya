<template>
    <v-app-bar color="green" dark elevation="2" class="px-4">
        <router-link to="/" class="nav-link text-white mr-6">
            <v-toolbar-title class="font-weight-medium">
                {{ appName }}
            </v-toolbar-title>
        </router-link>

        <v-spacer></v-spacer>

        <router-link to="/" class="nav-link text-white mr-6">
            Главная
        </router-link>
        <v-btn to="/cart" v-if="authStore.isAuthenticated" variant="text" class="pa-2" height="auto"
            style="min-width: 60px; min-height: 48px;">
            <div class="d-flex flex-column align-center py-1">
                <v-badge :content="cartStore.cart.items_count" :model-value="cartStore.cart.items_count" color="green-darken-3" overlap>
                    <v-icon size="24">mdi-cart-outline</v-icon>
                </v-badge>
            </div>
        </v-btn>
        <v-menu open-on-hover offset-y :close-on-content-click="false">
            <template v-slot:activator="{ props }">
                <span v-bind="props" class="nav-link text-white cursor-pointer" style="cursor: pointer;">
                    {{ authStore.isAuthenticated ? authStore.user.name : 'Вход и регистрация' }}
                </span>
            </template>
            <v-card v-if="!authStore.isAuthenticated" class="pa-2" width="200">
                <v-btn to="/login" block variant="text" class="mb-2 justify-start" prepend-icon="mdi-login">
                    Войти
                </v-btn>

                <v-divider class="my-1"></v-divider>

                <v-btn to="/register" block variant="text" class="justify-start" prepend-icon="mdi-account-plus">
                    Зарегистрироваться
                </v-btn>
            </v-card>
            <v-card v-else class="pa-2" width="200">

                <v-btn v-if="hasPermission(authStore.user.permissions, 'categories-create')" to="/categories" block variant="text" class="mb-2 justify-start" prepend-icon="mdi-folder-outline">
                    Категории
                </v-btn>
                <v-btn v-if="hasPermission(authStore.user.permissions, 'products-create')" to="/products" block variant="text" class="mb-2 justify-start" prepend-icon="mdi-package-variant">
                    Товары
                </v-btn>

                <v-divider class="my-1"></v-divider>

                <v-btn to="/profile" block variant="text" class="justify-start" prepend-icon="mdi-account">
                    Профиль
                </v-btn>
                                <v-btn to="/login" block variant="text" class="mb-2 justify-start" prepend-icon="mdi-exit-to-app">
                    Выйти
                </v-btn>
            </v-card>
        </v-menu>
    </v-app-bar>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { storeToRefs } from 'pinia'

const authStore = useAuthStore();
const cartStore = useCartStore();

const appName = ref(window.appName || 'No name')
function hasPermission(permissions, permissionName) {
    if (!permissions || !Array.isArray(permissions)) return false;
    return permissions.some(permission => permission.name === permissionName);
}

</script>

<style scoped>
.nav-link {
    text-decoration: none;
    transition: opacity 0.3s ease;
    font-weight: 500;
}

.nav-link:hover {
    opacity: 0.8;
    text-decoration: none;
}

.cursor-pointer {
    cursor: pointer;
}
</style>