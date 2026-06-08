<template>
    <div class="category-products-page">
        <v-container fluid class="pa-0">
           

            <!-- Загрузка -->
            <v-row v-if="loading" justify="center" align="center" class="fill-height" style="min-height: 400px">
                <v-col cols="auto">
                    <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                    <p class="mt-4 text-center text-grey">Загрузка товаров...</p>
                </v-col>
            </v-row>

            <!-- Ошибка -->
            <v-row v-else-if="error" justify="center" align="center" class="fill-height" style="min-height: 400px">
                <v-col cols="12" sm="8" md="6" lg="4">
                    <v-card class="error-card text-center pa-6" elevation="2">
                        <v-icon icon="mdi-alert-circle" size="80" color="error"></v-icon>
                        <p class="text-h6 mt-4">{{ error }}</p>
                        <div class="d-flex justify-center gap-3 mt-6">
                            <v-btn color="primary" variant="tonal" @click="fetchProducts">
                                <v-icon start>mdi-reload</v-icon>
                                Повторить
                            </v-btn>
                            <v-btn color="grey" variant="text" @click="goBack">
                                <v-icon start>mdi-arrow-left</v-icon>
                                Назад
                            </v-btn>
                        </div>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Категория не найдена -->
            <v-row v-else-if="notFound" justify="center" align="center" class="fill-height" style="min-height: 400px">
                <v-col cols="12" sm="8" md="6" lg="4">
                    <v-card class="not-found-card text-center pa-6" elevation="2">
                        <v-icon icon="mdi-folder-off" size="80" color="grey"></v-icon>
                        <p class="text-h5 mt-4">Категория не найдена</p>
                        <p class="text-grey mt-2">Категория с ID {{ categoryId }} не существует</p>
                        <div class="d-flex justify-center gap-3 mt-6">
                            <v-btn color="primary" variant="flat" to="/catalog">
                                <v-icon start>mdi-view-grid</v-icon>
                                Весь каталог
                            </v-btn>
                            <v-btn color="grey" variant="text" to="/">
                                <v-icon start>mdi-home</v-icon>
                                На главную
                            </v-btn>
                        </div>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Содержимое категории -->
            <template v-else>
                <!-- Заголовок категории -->
                <div class="category-header mb-6">
                    <v-container>
                        <div class="d-flex align-center justify-space-between flex-wrap gap-3">
                            <div>
                                <h1 class="text-h1 text-sm-h1 text-md-display-1 text-lg-display-3">
                                    {{ categoryName }}
                                </h1>
                                <p class="text-subtitle-1 text-grey">
                                    Товары в наличии: {{ products.length }}
                                </p>
                            </div>
                       
                        </div>
                    </v-container>
                </div>


                <!-- Товары -->
                <v-container>
                    <div v-if="products.length === 0" class="empty-products text-center py-12">
                        <v-icon icon="mdi-package-variant-closed" size="80" color="grey-lighten-1"></v-icon>
                        <p class="text-h5 text-grey mt-4">В этой категории пока нет товаров</p>
                        <p class="text-grey">Загляните позже, мы постоянно пополняем ассортимент</p>
                        <v-btn color="primary" variant="tonal" to="/catalog" class="mt-4">
                            <v-icon start>mdi-view-grid</v-icon>
                            Смотреть все категории
                        </v-btn>
                    </div>

                    <v-row v-else>
                        <v-col
                            v-for="product in products"
                            :key="product.id"
                            cols="12"
                            sm="6"
                            md="4"
                            lg="3"
                            xl="2.4"
                        >
                            <ProductCard
                                :product="product"
                                @add-to-cart="handleAddToCart"
                            />
                        </v-col>
                    </v-row>

                    <!-- Пагинация -->
                    <div v-if="lastPage > 1" class="pagination-wrapper mt-8">
                        <v-pagination
                            v-model="currentPage"
                            :length="lastPage"
                            :total-visible="7"
                            @update:model-value="fetchProducts"
                            color="primary"
                            variant="tonal"
                        ></v-pagination>
                    </div>
                </v-container>
            </template>
        </v-container>

        <!-- Уведомления -->
        <v-snackbar v-model="snackbar.show" :timeout="3000" :color="snackbar.color" location="top">
            <v-icon start :icon="snackbar.icon" size="24"></v-icon>
            {{ snackbar.text }}
            <template v-slot:actions>
                <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false"></v-btn>
            </template>
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import ProductCard from '@/components/ProductCard.vue'

// Router
const route = useRoute()
const router = useRouter()

// Получаем ID из URL
const categoryId = computed(() => route.params.id)

// Состояние
const loading = ref(false)
const products = ref([])
const categoryName = ref('')
const error = ref(null)
const notFound = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const showFilters = ref(false)
const sortBy = ref('newest')

// Фильтры
const filters = ref({
    minPrice: null,
    maxPrice: null
})

// Уведомления
const snackbar = ref({
    show: false,
    text: '',
    color: 'success',
    icon: 'mdi-check-circle'
})

// Опции сортировки
const sortOptions = [
    { text: 'Новые', value: 'newest' },
    { text: 'Сначала дешевле', value: 'price_asc' },
    { text: 'Сначала дороже', value: 'price_desc' },
    { text: 'По названию А-Я', value: 'name_asc' },
    { text: 'По названию Я-А', value: 'name_desc' }
]

// Хлебные крошки
const breadcrumbs = computed(() => [
    { title: 'Главная', disabled: false, href: '/' },
    { title: 'Каталог', disabled: false, href: '/catalog' },
    { title: categoryName.value || 'Загрузка...', disabled: true }
])

// Получение товаров
const fetchProducts = async () => {
    loading.value = true
    error.value = null
    notFound.value = false
    
    try {
        // Строим URL с параметрами
        const params = new URLSearchParams()
        params.append('page', currentPage.value)
        params.append('sort', sortBy.value)
        
        if (filters.value.minPrice) {
            params.append('min_price', filters.value.minPrice)
        }
        if (filters.value.maxPrice) {
            params.append('max_price', filters.value.maxPrice)
        }
        
        const response = await axios.get(`/api/categories/${categoryId.value}/products/all?${params.toString()}`)
        
        if (response.data) {
            products.value = response.data.products || []
            categoryName.value = response.data.category || 'Категория'
            lastPage.value = response.data.last_page || 1
        }
    } catch (err) {
        console.error('Ошибка загрузки товаров:', err)
        if (err.response?.status === 404) {
            notFound.value = true
        } else {
            error.value = 'Не удалось загрузить товары. Попробуйте позже.'
            showNotification(error.value, 'error', 'mdi-alert')
        }
    } finally {
        loading.value = false
    }
}

// Применить фильтры
const applyFilters = () => {
    currentPage.value = 1
    fetchProducts()
    showFilters.value = false
}

// Очистить фильтры
const clearFilters = () => {
    filters.value = {
        minPrice: null,
        maxPrice: null
    }
    currentPage.value = 1
    fetchProducts()
    showFilters.value = false
}

// Переключить фильтры
const toggleFilters = () => {
    showFilters.value = !showFilters.value
}

// Добавить в корзину
const handleAddToCart = (product) => {
    // Эмитим событие или вызываем API
    showNotification(`${product.name} добавлен в корзину`, 'success', 'mdi-cart-plus')
}

// Показать уведомление
const showNotification = (text, color, icon) => {
    snackbar.value = {
        show: true,
        text,
        color,
        icon
    }
}

// Вернуться назад
const goBack = () => {
    router.back()
}

// Следим за изменением ID категории
watch(categoryId, () => {
    currentPage.value = 1
    fetchProducts()
})

// Загрузка при монтировании
onMounted(() => {
    fetchProducts()
})
</script>

<style scoped>
.category-products-page {
    background: #f5f5f5;
    min-height: 100vh;
}

.breadcrumbs {
    background: white;
    padding: 12px 24px;
    border-bottom: 1px solid #e0e0e0;
}

.category-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    padding: 16px 0;
    border-bottom: 1px solid #e0e0e0;
}

.sort-select {
    max-width: 200px;
}

.filters-section {
    background: white;
    border-bottom: 1px solid #e0e0e0;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
}

.gap-2 {
    gap: 8px;
}

.gap-3 {
    gap: 12px;
}

/* Адаптивность */
@media (max-width: 768px) {
    .category-header {
        padding: 20px 0;
    }
    
    .sort-select {
        max-width: 100%;
        width: 100%;
    }
    
    .category-header .d-flex {
        flex-direction: column;
        align-items: stretch;
    }
    
    .breadcrumbs {
        padding: 8px 16px;
    }
}
</style>