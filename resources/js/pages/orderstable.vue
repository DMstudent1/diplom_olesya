<template>
    <div class="orders-admin-page">
        <v-container fluid>
            <v-row>
                <v-col cols="12">
                    <v-card>
                        <v-data-table-server
                            v-model:items-per-page="itemsPerPage"
                            :headers="headers"
                            :items="orders"
                            :items-length="totalItems"
                            :loading="loading"
                            :search="filters.search"
                            @update:options="loadOrders"
                            class="elevation-1"
                        >

                            <!-- Пользователь -->
                            <template v-slot:item.user="{ item }">
                                <div>
                                    <div class="font-weight-bold">{{ item.user?.name || '—' }}</div>
                                    <div class="text-caption text-grey">{{ item.user?.email || '—' }}</div>
                                    <div class="text-caption text-grey">{{ formatPhone(item.user?.phone) }}</div>
                                </div>
                            </template>

                            <!-- Статус -->
                            <template v-slot:item.status="{ item }">
                                <v-chip :color="item.status_color" size="small">
                                    <v-icon start size="small">mdi-circle</v-icon>
                                    {{ item.status_label }}
                                </v-chip>
                            </template>

                            <!-- Сумма -->
                            <template v-slot:item.sum="{ item }">
                                <span class="font-weight-bold">{{ formatPrice(item.sum) }} ₽</span>
                            </template>

                            <!-- Товаров -->
                            <template v-slot:item.products_count="{ item }">
                                <v-chip size="small" color="info" variant="light">
                                    {{ item.products_count }} шт.
                                </v-chip>
                            </template>

                            <!-- Дата создания -->
                            <template v-slot:item.created_at="{ item }">
                                <div class="text-caption">
                                    <div>{{ item.created_date }}</div>
                                    <div class="text-grey">{{ formatTime(item.created_at) }}</div>
                                </div>
                            </template>

                            <!-- Действия -->
                            <template v-slot:item.actions="{ item }">
                                <v-btn
                                    icon="mdi-eye"
                                    size="small"
                                    variant="text"
                                    color="info"
                                    @click="viewOrder(item)"
                                ></v-btn>
                            </template>
                        </v-data-table-server>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>

        <!-- Диалог просмотра заказа -->
        <v-dialog v-model="viewDialog" max-width="900px">
            <v-card>
                <v-card-title class="text-h5 bg-info-lighten-4">
                    <v-icon start>mdi-shopping</v-icon>
                    Детали заказа #{{ selectedOrder?.uuid }}
                </v-card-title>
                
                <v-card-text class="mt-4">
                    <!-- Информация о заказе -->
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-card variant="outlined" class="mb-4">
                                <v-card-title class="text-subtitle-1 bg-grey-lighten-3">
                                    <v-icon start>mdi-information</v-icon>
                                    Информация о заказе
                                </v-card-title>
                                <v-card-text>
                                    <v-list density="compact">
                                        <v-list-item>
                                            <v-list-item-title class="text-caption">ID заказа</v-list-item-title>
                                            <v-list-item-subtitle>{{ selectedOrder?.id }}</v-list-item-subtitle>
                                        </v-list-item>
                                        <v-list-item>
                                            <v-list-item-title class="text-caption">UUID</v-list-item-title>
                                            <v-list-item-subtitle>{{ selectedOrder?.uuid }}</v-list-item-subtitle>
                                        </v-list-item>
                                        <v-list-item>
                                            <v-list-item-title class="text-caption">Статус</v-list-item-title>
                                            <v-list-item-subtitle>
                                                <v-chip :color="selectedOrder?.status_color" size="small">
                                                    {{ selectedOrder?.status_label }}
                                                </v-chip>
                                            </v-list-item-subtitle>
                                        </v-list-item>
                                        <v-list-item>
                                            <v-list-item-title class="text-caption">Сумма</v-list-item-title>
                                            <v-list-item-subtitle class="text-h6 font-weight-bold">
                                                {{ formatPrice(selectedOrder?.sum) }} ₽
                                            </v-list-item-subtitle>
                                        </v-list-item>
                                    </v-list>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-card variant="outlined" class="mb-4">
                                <v-card-title class="text-subtitle-1 bg-grey-lighten-3">
                                    <v-icon start>mdi-account</v-icon>
                                    Информация о покупателе
                                </v-card-title>
                                <v-card-text>
                                    <v-list density="compact">
                                        <v-list-item>
                                            <v-list-item-title class="text-caption">Имя</v-list-item-title>
                                            <v-list-item-subtitle>{{ selectedOrder?.user?.name || '—' }}</v-list-item-subtitle>
                                        </v-list-item>
                                        <v-list-item>
                                            <v-list-item-title class="text-caption">Email</v-list-item-title>
                                            <v-list-item-subtitle>{{ selectedOrder?.user?.email || '—' }}</v-list-item-subtitle>
                                        </v-list-item>
                                        <v-list-item>
                                            <v-list-item-title class="text-caption">Телефон</v-list-item-title>
                                            <v-list-item-subtitle>{{ formatPhone(selectedOrder?.user?.phone) }}</v-list-item-subtitle>
                                        </v-list-item>
                                    </v-list>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- Доставка -->
                    <v-card variant="outlined" class="mb-4">
                        <v-card-title class="text-subtitle-1 bg-grey-lighten-3">
                            <v-icon start>mdi-truck</v-icon>
                            Информация о доставке
                        </v-card-title>
                        <v-card-text>
                            <v-list density="compact">
                                <v-list-item>
                                    <v-list-item-title class="text-caption">Пункт выдачи</v-list-item-title>
                                    <v-list-item-subtitle>{{ selectedOrder?.delivery_point || '—' }}</v-list-item-subtitle>
                                </v-list-item>
                                <v-list-item>
                                    <v-list-item-title class="text-caption">UUID доставки</v-list-item-title>
                                    <v-list-item-subtitle class="text-caption font-mono">
                                        {{ selectedOrder?.delivery_uuid || '—' }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                                <v-list-item>
                                    <v-list-item-title class="text-caption">ID платежа</v-list-item-title>
                                    <v-list-item-subtitle class="text-caption font-mono">
                                        {{ selectedOrder?.payment_id || '—' }}
                                    </v-list-item-subtitle>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                    </v-card>

                    <!-- Товары -->
                    <v-card variant="outlined">
                        <v-card-title class="text-subtitle-1 bg-grey-lighten-3">
                            <v-icon start>mdi-package-variant</v-icon>
                            Товары в заказе
                        </v-card-title>
                        <v-card-text class="pa-0">
                            <v-table density="compact">
                                <thead>
                                    <tr>
                                        <th>Товар</th>
                                        <th class="text-right">Цена</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in selectedOrderProducts" :key="product.id">
                                        <td>
                                            <div class="d-flex align-center">
                                                <v-avatar size="40" class="mr-3">
                                                    <v-img :src="product.media?.[0]?.original_url || '/placeholder.jpg'" cover></v-img>
                                                </v-avatar>
                                                <div>
                                                    <div class="font-weight-bold">{{ product.name }}</div>
                                                    <div class="text-caption text-grey">ID: {{ product.id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            {{ formatPrice(product.price) }} ₽
                                        </td>
                                    </tr>
                                    <tr class="bg-grey-lighten-4">
                                        <td class="text-right font-weight-bold">Итого:</td>
                                        <td class="text-right font-weight-bold text-h6">
                                            {{ formatPrice(selectedOrder?.sum) }} ₽
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-card-text>
                    </v-card>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="text" @click="viewDialog = false">
                        Закрыть
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Диалог изменения статуса -->
        <v-dialog v-model="statusDialog" max-width="500px">
            <v-card>
                <v-card-title class="text-h5 bg-warning-lighten-4">
                    <v-icon start>mdi-pencil</v-icon>
                    Изменение статуса заказа
                </v-card-title>
                <v-card-text class="mt-4">
                    <p>Заказ #{{ selectedOrder?.uuid }}</p>
                    <v-select
                        v-model="newStatus"
                        :items="statusOptions"
                        item-title="label"
                        item-value="value"
                        label="Новый статус"
                        variant="outlined"
                        density="comfortable"
                    ></v-select>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="text" @click="statusDialog = false">
                        Отмена
                    </v-btn>
                    <v-btn color="primary" variant="flat" @click="updateStatus" :loading="updatingStatus">
                        Сохранить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Диалог отслеживания доставки -->
        <v-dialog v-model="trackDialog" max-width="600px">
            <v-card>
                <v-card-title class="text-h5 bg-primary-lighten-4">
                    <v-icon start>mdi-truck-fast</v-icon>
                    Отслеживание доставки
                </v-card-title>
                <v-card-text class="mt-4 text-center">
                    <v-icon size="64" color="primary">mdi-truck-delivery</v-icon>
                    <p class="mt-4">Номер доставки СДЭК:</p>
                    <v-chip color="primary" size="large" class="mb-4">
                        {{ selectedOrder?.delivery_uuid }}
                    </v-chip>
                    <p class="text-caption text-grey">
                        Вы можете отследить заказ на сайте СДЭК по номеру доставки
                    </p>
                    <v-btn
                        color="primary"
                        variant="flat"
                        block
                        :href="`https://www.cdek.ru/ru/tracking?order_id=${selectedOrder?.delivery_uuid}`"
                        target="_blank"
                    >
                        <v-icon start>mdi-open-in-new</v-icon>
                        Открыть на сайте СДЭК
                    </v-btn>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="text" @click="trackDialog = false">
                        Закрыть
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Уведомления -->
        <v-snackbar v-model="snackbar.show" :timeout="3000" :color="snackbar.color" location="top">
            <v-icon start :color="snackbar.color === 'success' ? 'white' : ''">
                {{ snackbar.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
            </v-icon>
            {{ snackbar.text }}
            <template v-slot:actions>
                <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false"></v-btn>
            </template>
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Состояние
const loading = ref(false)
const updatingStatus = ref(false)
const orders = ref([])
const totalItems = ref(0)
const itemsPerPage = ref(10)
const viewDialog = ref(false)
const statusDialog = ref(false)
const trackDialog = ref(false)
const selectedOrder = ref(null)
const newStatus = ref('')
const statistics = ref({})

// Фильтры
const filters = ref({
    status: null,
    search: '',
    user_id: null
})

// Заголовки таблицы
const headers = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Покупатель', key: 'user', sortable: false, width: '250px' },
    { title: 'Статус', key: 'status', sortable: true, width: '150px' },
    { title: 'Сумма', key: 'sum', sortable: true, width: '120px' },
    { title: 'Товаров', key: 'products_count', sortable: false, width: '100px' },
    { title: 'Дата создания', key: 'created_at', sortable: true, width: '150px' },
    { title: 'Действия', key: 'actions', sortable: false, width: '120px', align: 'center' }
]

// Опции статусов
const statusOptions = [
    { label: 'Ожидает оплаты', value: 'pending' },
    { label: 'Оплачен', value: 'succeeded' },
    { label: 'Отменен', value: 'canceled' },
    { label: 'В обработке', value: 'processing' },
    { label: 'Доставлен', value: 'delivered' }
]

// Уведомления
const snackbar = ref({
    show: false,
    text: '',
    color: 'success'
})

// Товары выбранного заказа
const selectedOrderProducts = ref([])

// Форматирование цены
const formatPrice = (price) => {
    if (!price) return '0'
    return price.toLocaleString('ru-RU')
}

// Форматирование телефона
const formatPhone = (phone) => {
    if (!phone) return '—'
    const cleaned = phone.replace(/\D/g, '')
    if (cleaned.length === 11) {
        return `+7 (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7, 9)}-${cleaned.slice(9, 11)}`
    }
    return phone
}

// Форматирование времени
const formatTime = (datetime) => {
    if (!datetime) return '—'
    return new Date(datetime).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Обрезка UUID
const truncateUuid = (uuid) => {
    if (!uuid) return '—'
    return uuid.length > 16 ? uuid.substring(0, 16) + '...' : uuid
}

// Загрузка статистики


// Загрузка заказов
const loadOrders = async (options = {}) => {
    loading.value = true

    try {
        const params = new URLSearchParams()
        params.append('draw', 1)
        params.append('length', options.itemsPerPage || itemsPerPage.value)
        params.append('start', ((options.page || 1) - 1) * (options.itemsPerPage || itemsPerPage.value))

        if (filters.value.status) {
            params.append('status', filters.value.status)
        }
        if (filters.value.search) {
            params.append('search', filters.value.search)
        }
        if (filters.value.user_id) {
            params.append('user_id', filters.value.user_id)
        }

        const response = await axios.post('/api/admin/orders/datatable', params)
        
        orders.value = response.data.data
        totalItems.value = response.data.recordsFiltered

    } catch (error) {
        console.error('Ошибка загрузки заказов:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка загрузки данных',
            color: 'error'
        }
    } finally {
        loading.value = false
    }
}

// Дебаунс для поиска
let searchTimeout
const debouncedSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        loadOrders()
    }, 500)
}

// Сброс фильтров
const resetFilters = () => {
    filters.value = {
        status: null,
        search: '',
        user_id: null
    }
    loadOrders()
}

// Просмотр заказа
const viewOrder = async (order) => {
    selectedOrder.value = order
    viewDialog.value = true
    
    // Загружаем детальную информацию
    try {
        const response = await axios.get(`/api/admin/orders/${order.id}`)
        console.log(response)
        selectedOrder.value = response.data.order
        selectedOrderProducts.value = response.data.order.products || []
    } catch (error) {
        console.error('Ошибка загрузки деталей заказа:', error)
    }
}



// Обновление статуса
const updateStatus = async () => {
    if (!newStatus.value) return

    updatingStatus.value = true

    try {
        await axios.put(`/api/admin/orders/${selectedOrder.value.id}/status`, {
            status: newStatus.value
        })

        snackbar.value = {
            show: true,
            text: 'Статус заказа успешно обновлен',
            color: 'success'
        }

        statusDialog.value = false
        viewDialog.value = false
        loadOrders()

    } catch (error) {
        console.error('Ошибка обновления статуса:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка обновления статуса',
            color: 'error'
        }
    } finally {
        updatingStatus.value = false
    }
}

// Отслеживание доставки
const trackDelivery = (order) => {
    selectedOrder.value = order
    trackDialog.value = true
}

// Загрузка при монтировании
onMounted(() => {
        loadOrders()

})
</script>

<style scoped>
.orders-admin-page {
    background: #f5f5f5;
    min-height: 100vh;
    padding: 20px;
}

.font-mono {
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

:deep(.v-data-table-server th) {
    background: #f5f5f5;
    font-weight: 600;
}

:deep(.v-data-table-server tr:hover) {
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .orders-admin-page {
        padding: 10px;
    }
}
</style>