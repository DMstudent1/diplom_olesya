<template>
    <div class="user-orders-page">
        <v-container fluid class="pa-0">

            <!-- Заголовок страницы -->
            <div class="page-header mb-6">
                <div class="d-flex flex-wrap justify-space-between align-center gap-3">
                    <div>
                        <h1 class="text-h3 font-weight-bold mb-2">
                            Мои заказы
                        </h1>
                        <p class="text-grey">
                            История ваших заказов и их статусы
                        </p>
                    </div>
                    
                </div>
            </div>

            <!-- Загрузка -->
            <v-row v-if="loading" justify="center" align="center" class="fill-height" style="min-height: 400px">
                <v-col cols="auto">
                    <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                    <p class="mt-4 text-center text-grey">Загрузка заказов...</p>
                </v-col>
            </v-row>

            <!-- Ошибка -->
            <v-row v-else-if="error" justify="center" align="center" class="fill-height" style="min-height: 400px">
                <v-col cols="12" sm="8" md="6" lg="4">
                    <v-card class="error-card text-center pa-6" elevation="2">
                        <v-icon icon="mdi-alert-circle" size="80" color="error"></v-icon>
                        <p class="text-h6 mt-4">{{ error }}</p>
                        <div class="d-flex justify-center gap-3 mt-6">
                            <v-btn color="primary" variant="tonal" @click="loadOrders">
                                <v-icon start>mdi-reload</v-icon>
                                Повторить
                            </v-btn>
                        </div>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Нет заказов -->
            <v-row v-else-if="orders.length === 0" justify="center" align="center" class="fill-height" style="min-height: 400px">
                <v-col cols="12" sm="8" md="6" lg="4">
                    <v-card class="empty-card text-center pa-8" elevation="2">
                        <v-icon icon="mdi-package-variant-closed" size="80" color="grey-lighten-1"></v-icon>
                        <p class="text-h5 mt-4">У вас пока нет заказов</p>
                        <p class="text-grey mt-2">Перейдите в каталог, чтобы сделать первый заказ</p>
                        <v-btn color="primary" variant="flat" to="/catalog" class="mt-4" size="large">
                            <v-icon start>mdi-shopping</v-icon>
                            Перейти в каталог
                        </v-btn>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Список заказов -->
            <v-row v-else justify="center">
                <v-col cols="12" lg="10" xl="8">

                    <!-- Карточки заказов -->
                    <div class="orders-list">
                        <v-card
                            v-for="order in orders"
                            :key="order.id"
                            class="order-card mb-4"
                            elevation="2"
                            :class="{ 'order-canceled': order.status === 'canceled' }"
                        >
                            <!-- Шапка карточки -->
                            <div class="order-header pa-4 bg-grey-lighten-4">
                                <div class="d-flex flex-wrap justify-space-between align-center gap-3">
                                    <div>
                                        <div class="d-flex align-center gap-3 mb-1">
                                            <span class="text-h6 font-weight-bold">
                                                Заказ #{{ order.id }}
                                            </span>
                                            <v-chip :color="getStatusColor(order.status)" size="small" variant="light">
                                                <v-icon start :icon="getStatusIcon(order.status)" size="14"></v-icon>
                                                {{ getStatusText(order.status) }}
                                            </v-chip>
                                        </div>
                                        <div class="text-caption text-grey">
                                            <v-icon icon="mdi-calendar" size="12" class="mr-1"></v-icon>
                                            {{ order.formatted_date || formatDate(order.created_at) }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-subtitle-2 text-grey">Сумма заказа</div>
                                        <div class="text-h5 font-weight-bold text-primary">
                                            {{ formatPrice(order.sum) }} ₽
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Товары в заказе (превью) -->
                            <div class="order-products pa-4">
                                <div class="d-flex align-center justify-space-between mb-3">
                                    <span class="text-subtitle-2 text-grey">
                                        <v-icon icon="mdi-package" size="16" class="mr-1"></v-icon>
                                        Товары ({{ order.products_count }} шт.)
                                    </span>
                                    <v-btn
                                        variant="text"
                                        color="primary"
                                        size="small"
                                        @click="toggleProducts(order.id)"
                                    >
                                        <v-icon start :icon="expandedOrders.includes(order.id) ? 'mdi-chevron-up' : 'mdi-chevron-down'"></v-icon>
                                        {{ expandedOrders.includes(order.id) ? 'Скрыть' : 'Показать' }}
                                    </v-btn>
                                </div>

                                <!-- Краткий список товаров -->
                                <div class="products-preview">
                                    <div v-if="order.preview_image" class="preview-image">
                                        <v-img :src="order.preview_image" width="60" height="60" cover rounded></v-img>
                                    </div>
                                    <div class="preview-text">
                                        <span class="text-body-2">
                                            {{ order.products_count }} {{ declension(order.products_count, ['товар', 'товара', 'товаров']) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Развернутый список товаров -->
                                <v-expand-transition>
                                    <div v-if="expandedOrders.includes(order.id)" class="products-expanded mt-4">
                                        <v-divider class="mb-4"></v-divider>
                                        <div class="d-flex flex-column gap-3">
                                            <div v-if="!order.products || order.products.length === 0" class="text-center pa-4">
                                                <v-progress-circular indeterminate size="32"></v-progress-circular>
                                                <p class="text-caption text-grey mt-2">Загрузка товаров...</p>
                                            </div>
                                            <div v-else v-for="product in order.products" :key="product.id" class="product-item">
                                                <div class="d-flex align-center">
                                                    <div class="product-image">
                                                        <v-img
                                                            v-if="product.media && product.media[0]"
                                                            :src="product.media[0].original_url"
                                                            width="50"
                                                            height="50"
                                                            cover
                                                            rounded
                                                        ></v-img>
                                                        <div v-else class="product-no-image">
                                                            <v-icon icon="mdi-image-off" size="24"></v-icon>
                                                        </div>
                                                    </div>
                                                    <div class="product-info ml-3 flex-grow-1">
                                                        <div class="text-subtitle-2 font-weight-medium">
                                                            {{ product.name }}
                                                        </div>
                                                        <div class="text-caption text-grey">
                                                            {{ formatPrice(product.price) }} ₽
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </v-expand-transition>
                            </div>

                            <!-- Действия -->
                            <v-divider></v-divider>
                            <div class="order-actions pa-4 bg-grey-lighten-5">
                                <div class="d-flex flex-wrap justify-space-between align-center gap-3">
                                    <div class="d-flex gap-2">

                                    

                                        <v-btn
                                            v-if="order.status === 'succeeded'"
                                            color="success"
                                            variant="tonal"
                                            size="small"
                                            @click="repeatOrder(order)"
                                            :loading="repeatingOrders.includes(order.id)"
                                        >
                                            <v-icon start size="18">mdi-repeat</v-icon>
                                            Повторить
                                        </v-btn>
                                    </div>

                                    <div class="text-caption text-grey">
                                        <v-icon icon="mdi-truck" size="14"></v-icon>
                                        {{ order.delivery_uuid ? 'Отслеживается' : 'Ожидает отправки' }}
                                    </div>
                                </div>
                            </div>
                        </v-card>
                    </div>

                    <!-- Пагинация -->
                    <div class="pagination-wrapper mt-6">
                        <v-pagination
                            v-model="currentPage"
                            :length="lastPage"
                            :total-visible="7"
                            @update:model-value="loadOrders"
                            color="primary"
                            variant="tonal"
                        ></v-pagination>
                        <div class="text-center text-caption text-grey mt-2">
                            Показано {{ orders.length }} из {{ total }} заказов
                        </div>
                    </div>
                </v-col>
            </v-row>
        </v-container>

        <!-- Диалог подтверждения отмены -->
        <v-dialog v-model="cancelDialog.show" max-width="500px">
            <v-card>
                <v-card-title class="text-h5 bg-error-lighten-5 pa-4">
                    <v-icon icon="mdi-alert-circle" color="error" size="28" class="mr-2"></v-icon>
                    Отмена заказа #{{ cancelDialog.order?.id }}
                </v-card-title>
                <v-card-text class="pa-6">
                    <p class="text-body-1 mb-3">
                        Вы действительно хотите отменить этот заказ?
                    </p>
                    <v-alert type="warning" variant="tonal" class="mt-3">
                        <div class="text-caption">
                            • Средства будут возвращены на вашу карту<br>
                            • Отменить можно только заказ в статусе "В обработке"<br>
                            • Это действие нельзя будет отменить
                        </div>
                    </v-alert>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="cancelDialog.show = false">
                        Нет, оставить
                    </v-btn>
                    <v-btn color="error" variant="flat" @click="confirmCancel" :loading="cancelling">
                        Да, отменить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Уведомления -->
        <v-snackbar v-model="snackbar.show" :timeout="4000" :color="snackbar.color" location="top" multi-line>
            <v-icon start :icon="snackbar.icon" size="24"></v-icon>
            {{ snackbar.text }}
            <template v-slot:actions>
                <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false"></v-btn>
            </template>
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// Состояние
const loading = ref(false)
const orders = ref([])
const error = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const expandedOrders = ref([])
const cancelling = ref(false)
const repeatingOrders = ref([])

// Фильтры
const filters = reactive({
    status: null
})

// Диалог отмены
const cancelDialog = ref({
    show: false,
    order: null
})

// Уведомления
const snackbar = ref({
    show: false,
    text: '',
    color: 'success',
    icon: 'mdi-check-circle'
})

// Фильтры статусов
const statusFilters = [
    { text: 'Все заказы', value: null },
    { text: 'В обработке', value: 'pending' },
    { text: 'Оплачены', value: 'succeeded' },
    { text: 'В доставке', value: 'processing' },
    { text: 'Доставлены', value: 'delivered' },
    { text: 'Отменены', value: 'canceled' }
]

// Хлебные крошки
const breadcrumbs = [
    { title: 'Главная', disabled: false, href: '/' },
    { title: 'Личный кабинет', disabled: false, href: '/profile' },
    { title: 'Мои заказы', disabled: true }
]

// Загрузка заказов
const loadOrders = async () => {
    loading.value = true
    error.value = null
    
    try {
        const response = await axios.get('/api/orders-paginated', {
            params: {
                page: currentPage.value,
                per_page: 10,
                status: filters.status
            }
        })
        
        if (response.data.success) {
            orders.value = response.data.data || []
            currentPage.value = response.data.current_page
            lastPage.value = response.data.last_page
            total.value = response.data.total
            
            // Загружаем товары для развернутых заказов
            for (const order of orders.value) {
                if (expandedOrders.value.includes(order.id)) {
                    await loadOrderProducts(order)
                }
            }
        } else {
            error.value = 'Не удалось загрузить заказы'
        }
    } catch (err) {
        console.error('Error loading orders:', err)
        error.value = err.response?.data?.message || 'Ошибка при загрузке заказов'
        showNotification(error.value, 'error', 'mdi-alert')
    } finally {
        loading.value = false
    }
}

// Загрузка товаров конкретного заказа
const loadOrderProducts = async (order) => {
    if (order.products && order.products.length > 0) return
    
    try {
        const response = await axios.get(`/api/orders/${order.id}`)
        if (response.data.success) {
            order.products = response.data.order.products
        }
    } catch (err) {
        console.error(`Error loading products for order ${order.id}:`, err)
    }
}

// Переключение отображения товаров
const toggleProducts = async (orderId) => {
    const index = expandedOrders.value.indexOf(orderId)
    if (index === -1) {
        expandedOrders.value.push(orderId)
        const order = orders.value.find(o => o.id === orderId)
        if (order && !order.products) {
            await loadOrderProducts(order)
        }
    } else {
        expandedOrders.value.splice(index, 1)
    }
}

// Открыть диалог отмены
const openCancelDialog = (order) => {
    cancelDialog.value = {
        show: true,
        order: order
    }
}

// Подтверждение отмены
const confirmCancel = async () => {
    if (!cancelDialog.value.order) return
    
    cancelling.value = true
    
    try {
        const response = await axios.post(`/api/orders/${cancelDialog.value.order.id}/cancel`)
        
        if (response.data.success) {
            showNotification('Заказ успешно отменен', 'success', 'mdi-check-circle')
            await loadOrders()
        } else {
            showNotification(response.data.message || 'Не удалось отменить заказ', 'error', 'mdi-alert')
        }
    } catch (err) {
        console.error('Error cancelling order:', err)
        showNotification(err.response?.data?.message || 'Ошибка при отмене заказа', 'error', 'mdi-alert')
    } finally {
        cancelling.value = false
        cancelDialog.value.show = false
    }
}

// Повтор заказа
const repeatOrder = async (order) => {
    repeatingOrders.value.push(order.id)
    
    try {
        const response = await axios.post(`/api/orders/${order.id}/repeat`)
        
        if (response.data.success) {
            showNotification('Товары добавлены в корзину', 'success', 'mdi-cart-plus')
            setTimeout(() => {
                router.push('/cart')
            }, 1500)
        } else {
            showNotification(response.data.message || 'Не удалось повторить заказ', 'error', 'mdi-alert')
        }
    } catch (err) {
        console.error('Error repeating order:', err)
        showNotification(err.response?.data?.message || 'Ошибка при повторении заказа', 'error', 'mdi-alert')
    } finally {
        const index = repeatingOrders.value.indexOf(order.id)
        if (index !== -1) repeatingOrders.value.splice(index, 1)
    }
}

// Получить количество заказов по статусу
const getCountByStatus = (status) => {
    if (!orders.value.length) return 0
    return orders.value.filter(o => o.status === status).length
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

// Форматирование цены
const formatPrice = (price) => {
    if (!price && price !== 0) return '0'
    return new Intl.NumberFormat('ru-RU').format(price)
}

// Форматирование даты
const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

// Склонение слов
const declension = (number, words) => {
    const cases = [2, 0, 1, 1, 1, 2]
    return words[(number % 100 > 4 && number % 100 < 20) ? 2 : cases[(number % 10 < 5) ? number % 10 : 5]]
}

// Получение цвета статуса
const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'succeeded': 'success',
        'canceled': 'error',
        'delivered': 'info',
        'processing': 'primary'
    }
    return colors[status] || 'grey'
}

// Получение иконки статуса
const getStatusIcon = (status) => {
    const icons = {
        'pending': 'mdi-clock-outline',
        'succeeded': 'mdi-check-circle',
        'canceled': 'mdi-close-circle',
        'delivered': 'mdi-truck-check',
        'processing': 'mdi-progress-clock'
    }
    return icons[status] || 'mdi-help-circle'
}

// Получение текста статуса
const getStatusText = (status) => {
    const texts = {
        'pending': 'В обработке',
        'succeeded': 'Оплачен',
        'canceled': 'Отменен',
        'delivered': 'Доставлен',
        'processing': 'В процессе доставки'
    }
    return texts[status] || status
}

// Загрузка при монтировании
onMounted(() => {
    loadOrders()
})
</script>

<style scoped>
.user-orders-page {
    background: #f5f5f5;
    min-height: 100vh;
    padding: 20px 0;
}

.breadcrumbs {
    background: transparent;
    padding: 8px 24px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.status-filter {
    max-width: 250px;
}

.stats-card {
    border-radius: 16px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
}

.stat-label {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

.order-card {
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.order-card.order-canceled {
    opacity: 0.7;
}

.order-header {
    border-bottom: 1px solid #e0e0e0;
}

.order-products {
    background: white;
}

.products-preview {
    display: flex;
    align-items: center;
    gap: 12px;
}

.preview-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    background: #f5f5f5;
}

.preview-text {
    flex: 1;
}

.products-expanded {
    animation: fadeIn 0.3s ease;
}

.product-item {
    padding: 8px;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.product-item:hover {
    background: #f5f5f5;
}

.product-image {
    width: 50px;
    min-width: 50px;
    height: 50px;
    border-radius: 8px;
    overflow: hidden;
    background: #f5f5f5;
}

.product-no-image {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 8px;
}

.order-actions {
    border-top: 1px solid #e0e0e0;
}

.pagination-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.gap-2 {
    gap: 8px;
}

.gap-3 {
    gap: 12px;
}

/* Анимации */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Адаптивность */
@media (max-width: 768px) {
    .user-orders-page {
        padding: 10px 0;
    }
    
    .breadcrumbs {
        padding: 8px 16px;
    }
    
    .page-header {
        padding: 0 16px;
    }
    
    .status-filter {
        max-width: 100%;
        width: 100%;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .stat-value {
        font-size: 20px;
    }
    
    .order-actions .d-flex {
        flex-direction: column;
        align-items: stretch;
    }
    
    .order-actions .d-flex > div {
        width: 100%;
    }
    
    .order-actions .v-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>