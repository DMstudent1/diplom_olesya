<template>
    <v-app-bar color="green" dark elevation="2" class="px-4" height="120">
        <div class="app-bar-content">
            <!-- Логотип слева -->
            <router-link to="/" class="nav-link text-white">
                <v-img :src="logoUrl" alt="Logo" height="75" width="auto" contain class="logo-img"></v-img>
            </router-link>

            <!-- Центральные навигационные ссылки (Главная, О нас, Категории) -->
            <div class="nav-center">
                <router-link to="/" class="nav-link text-white nav-link-large">
                    Главная
                </router-link>

                <router-link to="/about" class="nav-link text-white nav-link-large">
                    О нас
                </router-link>

                <!-- Выпадающий список категорий -->
                <v-menu open-on-hover offset-y :close-on-content-click="false" @update:model-value="handleMenuOpen">
                    <template v-slot:activator="{ props }">
                        <span v-bind="props" class="nav-link nav-link-large text-white cursor-pointer">
                            Категории
                        </span>
                    </template>
                    <v-card class="categories-menu" width="300">
                        <v-list class="categories-list" :loading="categoriesLoading"
                            :style="{ maxHeight: '400px', overflowY: 'auto' }">
                            <v-list-item v-for="category in categories" :key="category.id"
                                @click="goToCategory(category.id)" class="category-item">
                                <v-list-item-title>{{ category.name }}</v-list-item-title>
                            </v-list-item>

                            <!-- Загрузка -->
                            <v-list-item v-if="categoriesLoading">
                                <v-list-item-title class="text-center">
                                    <v-progress-circular indeterminate size="24"></v-progress-circular>
                                </v-list-item-title>
                            </v-list-item>

                            <!-- Кнопки пагинации -->
                            <div class="pagination-controls pa-2">
                                <v-btn v-if="prevCursor" variant="text" size="small" @click="loadCategories(prevCursor)"
                                    :loading="loadingMore" class="mr-2">
                                    ← Назад
                                </v-btn>
                                <v-btn v-if="nextCursor" variant="text" size="small" @click="loadCategories(nextCursor)"
                                    :loading="loadingMore">
                                    Далее →
                                </v-btn>
                            </div>
                        </v-list>
                    </v-card>
                </v-menu>
            </div>

            <!-- Правая группа (корзина + пользователь) -->
            <div class="nav-right">
                <!-- Корзина -->
                <v-btn to="/cart" v-if="authStore.isAuthenticated && cartStore.cart" variant="text" class="pa-2"
                    height="auto" style="min-width: 60px; min-height: 48px;">
                    <div class="d-flex flex-column align-center py-1">
                        <v-badge :content="cartStore.cart.items_count" :model-value="cartStore.cart.items_count"
                            color="green-darken-3" overlap>
                            <v-icon size="24">mdi-cart-outline</v-icon>
                        </v-badge>
                    </div>
                </v-btn>

                <!-- Меню пользователя -->
                <v-menu open-on-hover offset-y :close-on-content-click="false">
                    <template v-slot:activator="{ props }">
                        <span v-bind="props" class="nav-link nav-link-large text-white cursor-pointer" style="cursor: pointer;">
                            {{ authStore.isAuthenticated && authStore.user ? authStore.user.name : 'Вход и регистрация' }}
                        </span>
                    </template>
                    <v-card v-if="!authStore.isAuthenticated" nav-link-large class="pa-2" width="200">
                        <v-btn to="/login" block variant="text" class="mb-2 justify-start" prepend-icon="mdi-login">
                            Войти
                        </v-btn>

                        <v-divider class="my-1"></v-divider>

                        <v-btn to="/register" nav-link-large block variant="text" class="justify-start" prepend-icon="mdi-account-plus">
                            Зарегистрироваться
                        </v-btn>
                    </v-card>
                    <v-card v-else class="pa-2" width="200">
                        <v-btn to="/orders" block variant="text" class="mb-2 justify-start" prepend-icon="mdi-history">
                            Мои заказы
                        </v-btn>

                        <v-divider class="my-1"></v-divider>

                        <v-btn v-if="authStore.user && hasPermission(authStore.user.permissions, 'categories-create')"
                            to="/categories" block variant="text" class="mb-2 justify-start"
                            prepend-icon="mdi-folder-outline">
                            Категории
                        </v-btn>

                        <v-btn v-if="authStore.user && hasPermission(authStore.user.permissions, 'categories-create')"
                            to="/users" block variant="text" class="mb-2 justify-start"
                            prepend-icon="mdi-account-group-outline">
                            Пользовати
                        </v-btn>
                        <v-btn v-if="authStore.user && hasPermission(authStore.user.permissions, 'products-create')"
                            to="/products" block variant="text" class="mb-2 justify-start"
                            prepend-icon="mdi-package-variant">
                            Товары
                        </v-btn>

                        <v-divider class="my-1"></v-divider>

                        <v-btn to="/profile" block variant="text" class="justify-start" prepend-icon="mdi-account">
                            Профиль
                        </v-btn>
                        <v-btn @click="handleLogout" block variant="text" class="mb-2 justify-start"
                            prepend-icon="mdi-exit-to-app">
                            Выйти
                        </v-btn>
                    </v-card>
                </v-menu>
            </div>
        </div>
    </v-app-bar>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import logoUrl from '@/assets/logo.svg'
const authStore = useAuthStore();
const cartStore = useCartStore();
const router = useRouter();

const appName = ref(window.appName || 'No name')
const categories = ref([])
const categoriesLoading = ref(false)
const loadingMore = ref(false)
const nextCursor = ref(null)
const prevCursor = ref(null)

function hasPermission(permissions, permissionName) {
    if (!permissions || !Array.isArray(permissions)) return false;
    return permissions.some(permission => permission.name === permissionName);
}

const handleLogout = async () => {
    try {
        await authStore.logout()
    } catch (error) {
        console.error('Logout error:', error)
    }

    cartStore.cart = null
    window.location.href = '/'
}

// Загрузка категорий с курсорной пагинацией
const loadCategories = async (cursor = null) => {
    if (cursor) {
        loadingMore.value = true
    } else {
        categoriesLoading.value = true
    }

    try {
        let url = '/api/categories?per_page=10'
        if (cursor) {
            url += `&cursor=${cursor}`
        }

        const response = await axios.get(url)

        if (cursor) {
            // Добавляем новые категории к существующим
            categories.value = [...categories.value, ...response.data.data]
        } else {
            categories.value = response.data.data
        }

        nextCursor.value = response.data.next_cursor || null
        prevCursor.value = response.data.prev_cursor || null

    } catch (error) {
        console.error('Ошибка загрузки категорий:', error)
    } finally {
        categoriesLoading.value = false
        loadingMore.value = false
    }
}

// Загрузка при открытии меню
const handleMenuOpen = (isOpen) => {
    if (isOpen && categories.value.length === 0) {
        loadCategories()
    }
}

// Переход на страницу категории
const goToCategory = (categoryId) => {
    router.push(`/categories/${categoryId}`)
}

onMounted(() => {
    // Предзагрузка категорий (опционально)
    // loadCategories()
})
</script>
<style scoped>
/* ✅ Отступы по бокам 20% */
.app-bar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: 80%;
    margin: 0 auto;
    padding: 0 20px;
}

/* Центральная группа навигации */
.nav-center {
    display: flex;
    align-items: center;
    gap: 32px;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

/* Правая группа */
.nav-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Для мобильных устройств */
@media (max-width: 960px) {
    .app-bar-content {
        max-width: 95%;
        padding: 0 10px;
    }
    
    .nav-center {
        gap: 16px;
        position: static;
        transform: none;
        margin: 0 auto;
    }
    
    .nav-link-large {
        font-size: 14px;
    }
}

@media (max-width: 768px) {
    .nav-center {
        gap: 8px;
    }
    
    .nav-link-large {
        font-size: 12px;
    }
}

.nav-link {
    text-decoration: none;
    transition: opacity 0.3s ease;
    font-weight: 500;
    white-space: nowrap;
}

.nav-link-large {
    font-size: 20px;
    padding: 8px 0;
}

.nav-link:hover {
    opacity: 0.8;
    text-decoration: none;
}

.cursor-pointer {
    cursor: pointer;
}

/* Стили меню категорий */
.categories-menu {
    max-height: 500px;
    overflow: hidden;
}

.categories-list {
    max-height: 400px;
    overflow-y: auto;
}

.category-item {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.category-item:hover {
    background-color: #f5f5f5;
}

.pagination-controls {
    display: flex;
    justify-content: space-between;
    border-top: 1px solid #e0e0e0;
    background-color: white;
}
</style>