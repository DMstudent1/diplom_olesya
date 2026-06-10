<template>
    <div class="product-detail-page">
        <v-container class="pa-4 pa-md-8">
            <!-- Загрузка -->
            <v-row v-if="loading" justify="center" class="mt-8">
                <v-col cols="12" class="text-center">
                    <v-progress-circular indeterminate size="48" color="#2D6A4F" />
                    <p class="mt-4 text-grey-darken-1">Загрузка товара...</p>
                </v-col>
            </v-row>

            <!-- Товар -->
            <template v-else-if="product">
                <v-row>
                    <!-- Фото -->
                    <v-col cols="12" md="6">
                        <div class="gallery-wrapper">
                            <v-img
                                :src="currentImage"
                                height="500"
                                cover
                                class="main-image"
                                rounded="lg"
                            >
                                <template v-slot:placeholder>
                                    <v-sheet class="d-flex align-center justify-center fill-height" color="grey-lighten-4">
                                        <v-icon size="48" color="grey-lighten-1">mdi-image</v-icon>
                                    </v-sheet>
                                </template>
                            </v-img>

                            <v-row v-if="images.length > 1" class="mt-3" dense>
                                <v-col v-for="(img, index) in images" :key="index" cols="auto">
                                    <v-img
                                        :src="img"
                                        width="80"
                                        height="80"
                                        cover
                                        rounded="md"
                                        class="thumbnail"
                                        :class="{ 'thumbnail-active': currentImage === img }"
                                        @click="currentImage = img"
                                    />
                                </v-col>
                            </v-row>
                        </div>
                    </v-col>

                    <!-- Информация -->
                    <v-col cols="12" md="6">
                        <div class="details-wrapper">
                            <!-- НАЗВАНИЕ -->
                            <h1 class="text-h3 font-weight-regular mb-6">{{ product.name }}</h1>

                            <!-- ХАРАКТЕРИСТИКИ (сокращенные) -->
                            <div v-if="Object.keys(allCharacteristics).length" class="mb-6">
                                <div class="d-flex align-center mb-3">
                                    <h2 class="text-subtitle-1 font-weight-medium text-grey-darken-2">Характеристики</h2>
                                    <v-divider class="mx-3" />
                                </div>
                                
                                <v-table density="compact" class="specs-table">
                                    <tbody>
                                        <tr v-for="(value, key, index) in displayedCharacteristics" :key="key">
                                            <td class="pa-2" style="width: 140px; border-bottom: 1px solid #F5F5F5;">
                                                <span class="text-caption text-grey-darken-1">{{ key }}</span>
                                            </td>
                                            <td class="pa-2" style="border-bottom: 1px solid #F5F5F5;">
                                                <span class="text-body-2 text-green-darken-2">{{ value }}</span>
                                             </td>
                                        </tr>
                                    </tbody>
                                </v-table>
                                
                                <v-btn
                                    v-if="characteristicsCount > 3"
                                    variant="text"
                                    size="small"
                                   color="green-darken-2" 

                                    class="mt-2"
                                    @click="showAllCharacteristics = !showAllCharacteristics"
                                >
                                    <v-icon size="16" class="mr-1">
                                        {{ showAllCharacteristics ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
                                    </v-icon>
                                    {{ showAllCharacteristics ? 'Скрыть' : `Показать все (${characteristicsCount})` }}
                                </v-btn>
                            </div>

                            <!-- ОПИСАНИЕ (сокращенное) -->
                            <div v-if="product.description" class="mb-6">
                                <div class="d-flex align-center mb-3">
                                    <h2 class="text-subtitle-1 font-weight-medium text-grey-darken-2">Описание</h2>
                                    <v-divider class="mx-3" />
                                </div>
                                
                                <div class="description-preview">
                                    <span class="text-body-2 text-grey-darken-3" v-html="displayedDescription"></span>
                                    <span v-if="!expandedDescription && descriptionLength > 300" class="text-grey-darken-1">...</span>
                                </div>
                                
                                <v-btn
                                    v-if="descriptionLength > 300"
                                    variant="text"
                                    size="small"
                                    color="grey-darken-1"
                                    class="mt-2"
                                    @click="expandedDescription = !expandedDescription"
                                >
                                    <v-icon size="16" class="mr-1">
                                        {{ expandedDescription ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
                                    </v-icon>
                                    {{ expandedDescription ? 'Свернуть' : 'Читать далее' }}
                                </v-btn>
                            </div>

                            <v-divider class="my-6" />

                            <!-- ЦЕНА И КОРЗИНА -->
                            <div class="price-section mb-4">
                                <span class="text-h2 font-weight-medium text-green-darken-3">
                                    {{ formatPrice(product.price) }}
                                </span>
                                <span v-if="product.old_price" class="text-subtitle-1 text-grey text-decoration-line-through ml-3">
                                    {{ formatPrice(product.old_price) }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <v-chip
                                    :color="stockStatus.color"
                                    :text="stockStatus.text"
                                    size="small"
                                    variant="tonal"
                                />
                            </div>

                            <v-btn
                                :color="product.count > 0 ? 'green-darken-2' : 'grey'"
                                :disabled="product.count === 0 || addingToCart"
                                :loading="addingToCart"
                                size="large"
                                block
                                @click="addToCart"
                                class="cart-btn"
                            >
                                <v-icon start>{{ product.count > 0 ? 'mdi-cart-outline' : 'mdi-cart-off' }}</v-icon>
                                {{ product.count > 0 ? 'В корзину' : 'Нет в наличии' }}
                            </v-btn>
                        </div>
                    </v-col>
                </v-row>

                <!-- ПОЛНЫЕ ХАРАКТЕРИСТИКИ (если не поместились) -->
                <v-row v-if="showAllCharacteristics && characteristicsCount > 3" class="mt-8">
                    <v-col cols="12">
                        <v-divider class="mb-6" />
                        <h2 class="text-h6 font-weight-regular mb-4">Все характеристики</h2>
                        <v-sheet rounded="lg" variant="outlined">
                            <v-table density="comfortable">
                                <tbody>
                                    <tr v-for="(value, key) in allCharacteristics" :key="key">
                                        <td class="text-grey-darken-1" style="width: 200px; border-bottom: 1px solid #F5F5F5;">
                                            {{ key }}
                                        </td>
                                        <td style="border-bottom: 1px solid #F5F5F5;">
                                            {{ value }}
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-sheet>
                    </v-col>
                </v-row>
            </template>

            <!-- 404 -->
            <v-row v-else-if="!loading && !product" justify="center" class="mt-8">
                <v-col cols="12" md="6" class="text-center">
                    <v-icon size="72" color="grey-lighten-2" class="mb-4">mdi-package-variant-closed</v-icon>
                    <h2 class="text-h5 font-weight-regular mb-4">Товар не найден</h2>
                    <v-btn color="#2D6A4F" variant="outlined" to="/catalog">
                        <v-icon start>mdi-arrow-left</v-icon>
                        Вернуться в каталог
                    </v-btn>
                </v-col>
            </v-row>
        </v-container>

        <!-- Уведомления -->
        <v-snackbar
            v-model="snackbar.show"
            :timeout="3000"
            :color="snackbar.color"
            location="bottom"
            variant="flat"
        >
            {{ snackbar.text }}
            <template v-slot:actions>
                <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" color="white" />
            </template>
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

// Состояние
const loading = ref(false)
const product = ref(null)
const currentImage = ref('')
const addingToCart = ref(false)

// Состояние раскрытия
const showAllCharacteristics = ref(false)
const expandedDescription = ref(false)

// Уведомления
const snackbar = ref({
    show: false,
    text: '',
    color: '#2D6A4F'
})

// Изображения
const images = computed(() => {
    if (!product.value?.media?.length) return []
    return product.value.media.map(m => m.original_url)
})

// Характеристики
const allCharacteristics = computed(() => {
    if (!product.value?.characteristics) return {}
    try {
        return typeof product.value.characteristics === 'string' 
            ? JSON.parse(product.value.characteristics) 
            : product.value.characteristics
    } catch {
        return {}
    }
})

const characteristicsCount = computed(() => Object.keys(allCharacteristics.value).length)

const displayedCharacteristics = computed(() => {
    const entries = Object.entries(allCharacteristics.value)
    if (showAllCharacteristics.value || entries.length <= 3) {
        return allCharacteristics.value
    }
    return Object.fromEntries(entries.slice(0, 3))
})

// Описание
const descriptionLength = computed(() => {
    if (!product.value?.description) return 0
    return product.value.description.length
})

const displayedDescription = computed(() => {
    if (!product.value?.description) return ''
    if (expandedDescription.value || descriptionLength.value <= 300) {
        return product.value.description.replace(/\n/g, '<br>')
    }
    return product.value.description.substring(0, 300).replace(/\n/g, '<br>')
})

// Статус наличия
const stockStatus = computed(() => {
    if (!product.value) return { text: '', color: '' }
    if (product.value.count === 0) {
        return { text: 'Нет в наличии', color: 'grey' }
    } else if (product.value.count < 10) {
        return { text: 'Осталось мало', color: 'orange' }
    }
    return { text: 'В наличии', color: '#2D6A4F' }
})

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

// Добавление в корзину
const addToCart = async () => {
    if (!product.value || product.value.count === 0) return
    
    addingToCart.value = true
    
    try {
        const token = localStorage.getItem('token')
        
        await axios.post('/api/cart', {
            product_id: product.value.id,
            quantity: 1
        }, {
            headers: token ? { Authorization: `Bearer ${token}` } : {}
        })
        
        snackbar.value = {
            show: true,
            text: 'Товар добавлен в корзину',
            color: '#2D6A4F'
        }
        
        window.dispatchEvent(new CustomEvent('cart-updated'))
        
    } catch (error) {
        console.error('Ошибка добавления в корзину:', error)
        
        if (error.response?.status === 401) {
            snackbar.value = {
                show: true,
                text: 'Для добавления в корзину необходимо авторизоваться',
                color: '#E67E22'
            }
            setTimeout(() => {
                router.push('/login')
            }, 2000)
        } else {
            snackbar.value = {
                show: true,
                text: error.response?.data?.message || 'Ошибка добавления в корзину',
                color: '#DC2626'
            }
        }
    } finally {
        addingToCart.value = false
    }
}

// Загрузка товара
const loadProduct = async () => {
    const productId = route.params.id
    if (!productId) return
    
    loading.value = true
    
    try {
        const response = await axios.get(`/api/products/${productId}`)
        product.value = response.data.data || response.data
        currentImage.value = product.value?.media?.[0]?.original_url || ''
        
        // Сбрасываем состояния при загрузке нового товара
        showAllCharacteristics.value = false
        expandedDescription.value = false
        
    } catch (error) {
        console.error('Ошибка загрузки товара:', error)
        product.value = null
    } finally {
        loading.value = false
    }
}

watch(() => route.params.id, () => {
    loadProduct()
})

onMounted(() => {
    loadProduct()
})
</script>

<style scoped>
.product-detail-page {
    background: #FFFFFF;
    min-height: 100vh;
}

.gallery-wrapper {
    position: sticky;
    top: 24px;
}

.main-image {
    cursor: pointer;
    transition: transform 0.3s ease;
}

.main-image:hover {
    transform: scale(1.01);
}

.thumbnail {
    cursor: pointer;
    opacity: 0.5;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.thumbnail:hover {
    opacity: 0.8;
}

.thumbnail-active {
    opacity: 1;
}

.details-wrapper {
    padding-top: 8px;
}

.price-section {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
}

.cart-btn {
    text-transform: none;
    font-weight: 500;
    letter-spacing: normal;
}

.specs-table {
    background: transparent;
}

.description-preview {
    line-height: 1.6;
}

@media (max-width: 768px) {
    .gallery-wrapper {
        position: relative;
        top: 0;
    }
    
    .details-wrapper {
        padding-top: 0;
    }
}
</style>