<template>
    <div class="test-table-page">
        <DataTable
            title="Товары"
            :headers="headers"
            :items="products"
            :loading="loading"
            :show-add-button="true"
            search-key="name"
            @refresh="loadProducts"
            @add="addProduct"
        >
            <!-- Кастомная колонка с ценой -->
            <template v-slot:item.price="{ item }">
                <span class="font-weight-bold text-green-darken-2">
                    {{ formatPrice(item.price) }}
                </span>
            </template>

            <!-- Кастомная колонка со старой ценой -->
            <template v-slot:item.old_price="{ item }">
                <span v-if="item.old_price" class="text-grey text-decoration-line-through">
                    {{ formatPrice(item.old_price) }}
                </span>
                <span v-else class="text-grey">—</span>
            </template>

            <!-- Кастомная колонка с количеством -->
            <template v-slot:item.count="{ item }">
                <v-chip 
                    :color="getCountColor(item.count)" 
                    size="small"
                    variant="light"
                >
                    {{ item.count }} шт.
                </v-chip>
            </template>

            <!-- Кастомная колонка с характеристиками (краткое отображение) -->
            <template v-slot:item.characteristics="{ item }">
                <v-tooltip location="top" v-if="getCharacteristicsCount(item.characteristics) > 0">
                    <template v-slot:activator="{ props }">
                        <v-chip 
                            v-bind="props"
                            size="small" 
                            color="info" 
                            variant="light"
                        >
                            <v-icon size="small" start>mdi-view-list</v-icon>
                            {{ getCharacteristicsCount(item.characteristics) }} хар-к
                        </v-chip>
                    </template>
                    <div>
                        <div v-for="(value, key) in parseCharacteristics(item.characteristics)" :key="key">
                            <strong>{{ key }}:</strong> {{ value }}
                        </div>
                    </div>
                </v-tooltip>
                <span v-else class="text-grey">—</span>
            </template>

            <!-- Кастомная колонка с датой создания -->
            <template v-slot:item.created_at="{ item }">
                {{ formatDate(item.created_at) }}
            </template>

            <!-- Кастомные действия -->
            <template v-slot:item.actions="{ item }">
                <div class="d-flex gap-2">
                    <v-btn
                        icon="mdi-eye"
                        size="small"
                        variant="text"
                        color="info"
                        @click="viewProduct(item)"
                    ></v-btn>
                    <v-btn
                        icon="mdi-pencil"
                        size="small"
                        variant="text"
                        color="warning"
                        @click="editProduct(item)"
                    ></v-btn>
                    <v-btn
                        icon="mdi-delete"
                        size="small"
                        variant="text"
                        color="error"
                        @click="deleteProduct(item)"
                    ></v-btn>
                </div>
            </template>
        </DataTable>
        
        <!-- Диалог просмотра товара -->
        <v-dialog v-model="dialog" max-width="700px">
            <v-card>
                <v-card-title class="text-h5 bg-green-lighten-4">
                    Информация о товаре
                </v-card-title>
                <v-card-text class="mt-4">
                    <v-list>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-tag"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">ID</v-list-item-title>
                            <v-list-item-subtitle class="text-h6">{{ selectedProduct?.id }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-format-title"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Название</v-list-item-title>
                            <v-list-item-subtitle class="text-h6">{{ selectedProduct?.name }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-folder"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Категория</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedProduct?.category || '—' }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-currency-rub"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Цена</v-list-item-title>
                            <v-list-item-subtitle class="text-h6 text-green-darken-2">
                                {{ formatPrice(selectedProduct?.price) }}
                            </v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-currency-rub"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Старая цена</v-list-item-title>
                            <v-list-item-subtitle>
                                <span v-if="selectedProduct?.old_price" class="text-grey text-decoration-line-through">
                                    {{ formatPrice(selectedProduct?.old_price) }}
                                </span>
                                <span v-else>—</span>
                            </v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-package-variant"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Количество</v-list-item-title>
                            <v-list-item-subtitle>
                                <v-chip :color="getCountColor(selectedProduct?.count)" size="small">
                                    {{ selectedProduct?.count || 0 }} шт.
                                </v-chip>
                            </v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-view-list"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Характеристики</v-list-item-title>
                            <v-list-item-subtitle>
                                <div v-if="getCharacteristicsCount(selectedProduct?.characteristics) > 0">
                                    <v-table density="compact">
                                        <tbody>
                                            <tr v-for="(value, key) in parseCharacteristics(selectedProduct?.characteristics)" :key="key">
                                                <td class="font-weight-bold">{{ key }}:</td>
                                                <td>{{ value }}</td>
                                            </tr>
                                        </tbody>
                                    </v-table>
                                </div>
                                <span v-else class="text-grey">Нет характеристик</span>
                            </v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider v-if="selectedProduct?.description"></v-divider>
                        
                        <v-list-item v-if="selectedProduct?.description">
                            <template v-slot:prepend>
                                <v-icon icon="mdi-text"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Описание</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedProduct?.description }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-calendar"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Дата создания</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDate(selectedProduct?.created_at) }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-calendar-edit"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Дата обновления</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDate(selectedProduct?.updated_at) }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="green" variant="text" @click="dialog = false">
                        Закрыть
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        
        <!-- Диалог добавления/редактирования товара -->
        <v-dialog v-model="editDialog" max-width="800px">
            <v-card>
                <v-card-title class="text-h5 bg-green-lighten-4">
                    {{ editingProduct ? 'Редактирование товара' : 'Добавление товара' }}
                </v-card-title>
                <v-card-text class="mt-4">
                    <v-form ref="formRef" v-model="formValid">
                        <v-text-field
                            v-model="formData.name"
                            label="Название"
                            :rules="[v => !!v || 'Название обязательно']"
                            variant="outlined"
                            density="comfortable"
                        ></v-text-field>
                        
                        <v-select
                            v-model="formData.category_id"
                            :items="categories"
                            item-title="name"
                            item-value="id"
                            label="Категория"
                            :rules="[v => !!v || 'Категория обязательна']"
                            variant="outlined"
                            density="comfortable"
                        ></v-select>
                        
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="formData.price"
                                    label="Цена"
                                    type="number"
                                    :rules="[v => v > 0 || 'Цена должна быть больше 0']"
                                    variant="outlined"
                                    density="comfortable"
                                    prefix="₽"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="formData.old_price"
                                    label="Старая цена (опционально)"
                                    type="number"
                                    variant="outlined"
                                    density="comfortable"
                                    prefix="₽"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        
                        <v-text-field
                            v-model="formData.count"
                            label="Количество"
                            type="number"
                            :rules="[v => v >= 0 || 'Количество не может быть отрицательным']"
                            variant="outlined"
                            density="comfortable"
                            suffix="шт."
                        ></v-text-field>
                        
                        <!-- Блок характеристик -->
                        <v-card variant="outlined" class="mb-4">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-3">
                                <v-icon start>mdi-view-list</v-icon>
                                Характеристики
                                <v-btn
                                    size="small"
                                    color="primary"
                                    variant="text"
                                    @click="addCharacteristic"
                                    class="ml-2"
                                >
                                    <v-icon size="small">mdi-plus</v-icon>
                                    Добавить
                                </v-btn>
                            </v-card-title>
                            <v-card-text >
                                <div v-for="(char, index) in characteristicsList" :key="index" class="mt-3">
                                    <v-row>
                                        <v-col cols="12" md="5">
                                            <v-text-field
                                                v-model="char.key"
                                                label="Название характеристики"
                                                variant="outlined"
                                                density="compact"
                                                placeholder="Цвет"
                                                hide-details
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                v-model="char.value"
                                                label="Значение"
                                                variant="outlined"
                                                density="compact"
                                                placeholder="Розовый"
                                                hide-details
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" md="1" class="d-flex align-center">
                                            <v-btn
                                                icon="mdi-delete"
                                                size="small"
                                                variant="text"
                                                color="error"
                                                @click="removeCharacteristic(index)"
                                            ></v-btn>
                                        </v-col>
                                    </v-row>
                                </div>
                                <div v-if="characteristicsList.length === 0" class="text-center text-grey py-4">
                                    <v-icon icon="mdi-view-list" size="large"></v-icon>
                                    <div>Нет добавленных характеристик</div>
                                    <v-btn
                                        size="small"
                                        color="primary"
                                        variant="text"
                                        @click="addCharacteristic"
                                        class="mt-2"
                                    >
                                        Добавить характеристику
                                    </v-btn>
                                </div>
                            </v-card-text>
                        </v-card>
                        
                        <v-textarea
                            v-model="formData.description"
                            label="Описание"
                            variant="outlined"
                            density="comfortable"
                            rows="3"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="text" @click="closeEditDialog">
                        Отмена
                    </v-btn>
                    <v-btn 
                        color="green" 
                        variant="flat" 
                        @click="saveProduct"
                        :loading="saving"
                        :disabled="!formValid"
                    >
                        Сохранить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        
        <!-- Уведомления -->
        <v-snackbar
            v-model="snackbar.show"
            :timeout="3000"
            :color="snackbar.color"
            location="top"
        >
            {{ snackbar.text }}
            <template v-slot:actions>
                <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false"></v-btn>
            </template>
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import DataTable from '@/components/DataTable.vue'
import axios from 'axios'

// Состояние
const loading = ref(false)
const saving = ref(false)
const products = ref([])
const categories = ref([])
const dialog = ref(false)
const editDialog = ref(false)
const selectedProduct = ref(null)
const editingProduct = ref(null)
const formValid = ref(false)
const formRef = ref(null)

// Характеристики
const characteristicsList = ref([])

// Форма
const formData = ref({
    name: '',
    category_id: null,
    price: '',
    old_price: '',
    count: '',
    characteristics: {},
    description: ''
})

// Уведомления
const snackbar = ref({
    show: false,
    text: '',
    color: 'success'
})

// Заголовки таблицы
const headers = [
    { title: 'ID', key: 'id', align: 'start', sortable: true, width: '80px' },
    { title: 'Название', key: 'name', sortable: true },
    { title: 'Категория', key: 'category', sortable: true },
    { title: 'Цена', key: 'price', sortable: true, width: '120px' },
    { title: 'Старая цена', key: 'old_price', sortable: true, width: '120px' },
    { title: 'Количество', key: 'count', sortable: true, width: '120px' },
    { title: 'Дата создания', key: 'created_at', sortable: true },
    { title: 'Действия', key: 'actions', sortable: false, width: '120px', align: 'center' }
]

// Парсинг характеристик из JSON
const parseCharacteristics = (characteristics) => {
    if (!characteristics) return {}
    try {
        if (typeof characteristics === 'string') {
            return JSON.parse(characteristics)
        }
        return characteristics
    } catch (e) {
        return {}
    }
}

// Получение количества характеристик
const getCharacteristicsCount = (characteristics) => {
    const chars = parseCharacteristics(characteristics)
    return Object.keys(chars).length
}

// Добавление характеристики
const addCharacteristic = () => {
    characteristicsList.value.push({ key: '', value: '' })
}

// Удаление характеристики
const removeCharacteristic = (index) => {
    characteristicsList.value.splice(index, 1)
}

// Формирование объекта характеристик из списка
const buildCharacteristicsObject = () => {
    const chars = {}
    characteristicsList.value.forEach(char => {
        if (char.key && char.key.trim()) {
            chars[char.key.trim()] = char.value || ''
        }
    })
    return chars
}

// Загрузка характеристик в список
const loadCharacteristicsToList = (characteristics) => {
    const chars = parseCharacteristics(characteristics)
    characteristicsList.value = Object.entries(chars).map(([key, value]) => ({
        key: key,
        value: value
    }))
}

// Форматирование цены
const formatPrice = (price) => {
    if (!price && price !== 0) return '—'
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(price)
}

// Цвет для количества
const getCountColor = (count) => {
    if (!count || count === 0) return 'error'
    if (count < 10) return 'warning'
    return 'success'
}

// Форматирование даты
const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Загрузка категорий для select
const loadCategories = async () => {
    try {
        const response = await axios.post('/api/category/get-datatable', {
            draw: 1,
            start: 0,
            length: 1000,
            search: { value: '' }
        })
        categories.value = response.data.data || []
    } catch (error) {
        console.error('Ошибка загрузки категорий:', error)
    }
}

// Загрузка товаров
const loadProducts = async () => {
    loading.value = true
    
    try {
        const response = await axios.post('/api/products/get-datatable', {
            draw: 1,
            start: 0,
            length: 100,
            search: { value: '' }
        })
        
        products.value = response.data.data || []
        
    } catch (error) {
        console.error('Ошибка загрузки товаров:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка загрузки данных',
            color: 'error'
        }
    } finally {
        loading.value = false
    }
}

// Добавление товара
const addProduct = () => {
    editingProduct.value = null
    formData.value = {
        name: '',
        category_id: null,
        price: '',
        old_price: '',
        count: '',
        characteristics: {},
        description: ''
    }
    characteristicsList.value = []
    editDialog.value = true
}

// Редактирование товара
const editProduct = (product) => {
    editingProduct.value = product
    formData.value = {
        name: product.name,
        category_id: product.category_id,
        price: product.price,
        old_price: product.old_price || '',
        count: product.count,
        characteristics: parseCharacteristics(product.characteristics),
        description: product.description || ''
    }
    loadCharacteristicsToList(product.characteristics)
    editDialog.value = true
}

// Сохранение товара
const saveProduct = async () => {
    if (!formValid.value) return
    
    saving.value = true
    
    try {
        const dataToSave = {
            ...formData.value,
            price: Number(formData.value.price),
            old_price: formData.value.old_price ? Number(formData.value.old_price) : null,
            count: Number(formData.value.count) || 0,
            characteristics: JSON.stringify(buildCharacteristicsObject())
        }
        
        if (editingProduct.value) {
            // Обновление
            await axios.put(`/api/products/${editingProduct.value.id}`, dataToSave)
            snackbar.value = {
                show: true,
                text: 'Товар успешно обновлен',
                color: 'success'
            }
        } else {
            // Создание
            await axios.post('/api/products', dataToSave)
            snackbar.value = {
                show: true,
                text: 'Товар успешно создан',
                color: 'success'
            }
        }
        
        closeEditDialog()
        await loadProducts()
        
    } catch (error) {
        console.error('Ошибка сохранения:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка сохранения',
            color: 'error'
        }
    } finally {
        saving.value = false
    }
}

// Закрытие диалога редактирования
const closeEditDialog = () => {
    editDialog.value = false
    editingProduct.value = null
    formData.value = {
        name: '',
        category_id: null,
        price: '',
        old_price: '',
        count: '',
        characteristics: {},
        description: ''
    }
    characteristicsList.value = []
}

// Удаление товара
const deleteProduct = async (product) => {
    if (!confirm(`Вы уверены, что хотите удалить товар "${product.name}"?`)) return
    
    try {
        await axios.delete(`/api/products/${product.id}`)
        
        snackbar.value = {
            show: true,
            text: `Товар "${product.name}" удален`,
            color: 'error'
        }
        
        await loadProducts()
        
    } catch (error) {
        console.error('Ошибка удаления:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка при удалении',
            color: 'error'
        }
    }
}

// Просмотр товара
const viewProduct = (product) => {
    selectedProduct.value = product
    dialog.value = true
}

// Загружаем данные при монтировании
onMounted(() => {
    loadCategories()
    loadProducts()
})
</script>

<style scoped>
.test-table-page {
    background: #f5f5f5;
    min-height: 100vh;
    padding: 20px;
    margin: 0;
}

.gap-2 {
    gap: 8px;
}

/* Стили для таблицы */
:deep(.data-table) {
    background: white;
}

:deep(.data-table .v-data-table__th) {
    background: #f5f5f5;
    font-weight: 600;
}

:deep(.data-table .v-data-table__tr:hover) {
    background: #f8f9fa;
}

/* Адаптивность */
@media (max-width: 768px) {
    .test-table-page {
        padding: 10px;
    }
}
</style>