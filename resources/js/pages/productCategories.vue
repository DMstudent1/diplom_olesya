<template>
  <div>
    <!-- Хлебные крошки и заголовок -->
    <v-container>
      <v-breadcrumbs :items="breadcrumbs" class="px-0 pb-0"></v-breadcrumbs>
      
      <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold text-green-darken-3 mb-1">
            {{ categoryName }}
          </h1>
          <p class="text-subtitle-1 text-grey-darken-1">
            {{ productsTotal }} товаров
          </p>
        </div>
        
        <!-- Сортировка -->
        <v-select
          v-model="sortBy"
          :items="sortOptions"
          item-title="title"
          item-value="value"
          label="Сортировать по"
          density="comfortable"
          variant="outlined"
          class="sort-select"
          hide-details
          @update:model-value="resetAndLoad"
        ></v-select>
      </div>
      
      <v-divider class="mb-6"></v-divider>
    </v-container>
    
    <!-- Баннер категории (опционально) -->
    <v-container v-if="categoryBanner">
      <v-alert
        :color="categoryBanner.color"
        variant="tonal"
        class="mb-6"
      >
        <v-row align="center">
          <v-col cols="auto">
            <v-icon :color="categoryBanner.color" size="32">{{ categoryBanner.icon }}</v-icon>
          </v-col>
          <v-col>
            <strong>{{ categoryBanner.text }}</strong>
          </v-col>
        </v-row>
      </v-alert>
    </v-container>
    
    <!-- Сетка товаров с бесконечной прокруткой -->
    <v-container fluid>
      <v-row>
        <v-col
          v-for="product in products"
          :key="product.id"
          cols="12"
          sm="6"
          md="4"
          lg="3"
        >
          <v-card
            :loading="product.loading"
            class="product-card h-100"
            elevation="2"
            hover
            @click="viewProduct(product)"
          >
            <!-- Изображение товара -->
            <v-img
              :src="product.image"
              :alt="product.name"
              height="220"
              cover
              class="bg-grey-lighten-3"
            >
              <template v-slot:placeholder>
                <div class="d-flex align-center justify-center fill-height bg-grey-lighten-3">
                  <v-icon size="48" color="grey-lighten-1">mdi-image</v-icon>
                </div>
              </template>
              
              <!-- Бейдж "Хит" или "Акция" -->
              <v-badge
                v-if="product.badge"
                :color="product.badgeColor || 'red'"
                :content="product.badge"
                class="product-badge"
                location="top end"
              ></v-badge>
            </v-img>
            
            <v-card-item class="pt-3">
              <!-- Название -->
              <v-card-title class="text-subtitle-1 font-weight-medium text-wrap pa-0">
                {{ product.name }}
              </v-card-title>
              
              <!-- Характеристики -->
              <div class="text-caption text-grey-darken-1 mt-1">
                <div v-if="product.size">{{ product.size }}</div>
                <div v-if="product.age">{{ product.age }}</div>
              </div>
            </v-card-item>
            
            <v-divider class="mx-3"></v-divider>
            
            <v-card-actions class="pt-3 pb-3">
              <div class="d-flex align-center justify-space-between w-100">
                <div>
                  <span class="text-h6 font-weight-bold text-green-darken-3">
                    {{ formatPrice(product.price) }}
                  </span>
                  <span v-if="product.oldPrice" class="text-caption text-grey text-decoration-line-through ml-2">
                    {{ formatPrice(product.oldPrice) }}
                  </span>
                </div>
                
                <v-btn
                  color="green-darken-2"
                  variant="flat"
                  size="small"
                  @click.stop="addToCart(product)"
                >
                  <v-icon start>mdi-cart-plus</v-icon>
                  В корзину
                </v-btn>
              </div>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
      
      <!-- Индикатор загрузки -->
      <div v-show="loading" class="text-center py-8">
        <v-progress-circular
          indeterminate
          color="green-darken-2"
          size="48"
        ></v-progress-circular>
        <p class="text-subtitle-1 text-grey-darken-1 mt-3">
          Загружаем товары...
        </p>
      </div>
      
      <!-- Текст "Больше нет товаров" -->
      <div v-if="!hasMore && products.length > 0" class="text-center py-8">
        <v-divider class="mx-auto mb-6" style="max-width: 300px;"></v-divider>
        <v-icon icon="mdi-check-decagram" color="green-darken-2" size="32"></v-icon>
        <p class="text-subtitle-1 text-grey-darken-1 mt-2">
          Вы посмотрели все товары в этой категории ({{ productsTotal }} шт.)
        </p>
      </div>
      
      <!-- Пустое состояние -->
      <v-container v-if="!loading && products.length === 0 && !productsError">
        <v-row justify="center">
          <v-col cols="12" class="text-center py-12">
            <v-icon icon="mdi-leaf-off" size="64" color="grey-lighten-1"></v-icon>
            <h3 class="text-h5 font-weight-medium text-grey-darken-1 mt-4">
              В этой категории пока нет товаров
            </h3>
            <p class="text-grey-darken-1 mt-2">
              Загляните позже — мы постоянно пополняем ассортимент
            </p>
            <v-btn
              color="green-darken-2"
              variant="tonal"
              to="/"
              class="mt-4"
            >
              На главную
            </v-btn>
          </v-col>
        </v-row>
      </v-container>
      
      <!-- Ошибка загрузки -->
      <v-alert
        v-if="productsError"
        type="error"
        variant="tonal"
        class="mt-6"
        closable
        @click:close="productsError = null"
      >
        {{ productsError }}
        <v-btn
          variant="text"
          color="error"
          size="small"
          @click="resetAndLoad"
          class="ml-4"
        >
          Попробовать снова
        </v-btn>
      </v-alert>
    </v-container>
    
    <!-- Snackbar для уведомлений -->
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Пропсы категории (можно передавать через router или props)
const props = defineProps({
  categoryId: {
    type: [String, Number],
    required: true
  }
})

// Данные о категории (можно заменить на API запрос)
const categoryInfo = ref({
  name: 'Хвойные растения',
  banner: {
    text: 'Вечнозеленые растения для вашего сада. Доставка по всей России, гарантия 60 дней!',
    icon: 'mdi-pine-tree',
    color: 'green-darken-2'
  }
})

const breadcrumbs = computed(() => [
  {
    title: 'Главная',
    href: '/'
  },
  {
    title: 'Каталог',
    href: '/catalog'
  },
  {
    title: categoryInfo.value.name,
    disabled: true
  }
])

// Состояние пагинации
const products = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const currentPage = ref(1)
const productsTotal = ref(0)
const hasMore = ref(true)
const productsError = ref(null)
const sortBy = ref('popular')

const sortOptions = [
  { title: 'По популярности', value: 'popular' },
  { title: 'Сначала дешевле', value: 'price_asc' },
  { title: 'Сначала дороже', value: 'price_desc' },
  { title: 'По названию (А-Я)', value: 'name_asc' },
  { title: 'По названию (Я-А)', value: 'name_desc' },
  { title: 'Новинки', value: 'new' }
]

// Эмуляция API запроса (замените на реальный вызов API)
const fetchProducts = async (page, sort, resetProducts = false) => {
  if (resetProducts) {
    loading.value = true
  } else {
    loadingMore.value = true
  }
  productsError.value = null
  
  try {
    // Эмулируем задержку сети
    await new Promise(resolve => setTimeout(resolve, 800))
    
    // Пример данных - замените на реальный запрос к вашему API
    // Например: const response = await api.getProductsByCategory(props.categoryId, page, sort)
    
    const mockProducts = generateMockProducts(page, sort)
    
    const newProducts = mockProducts.items
    productsTotal.value = mockProducts.total
    
    if (resetProducts) {
      products.value = newProducts
      currentPage.value = 1
    } else {
      products.value = [...products.value, ...newProducts]
    }
    
    hasMore.value = mockProducts.hasMore
    currentPage.value = page
    
    return mockProducts
  } catch (error) {
    console.error('Ошибка загрузки товаров:', error)
    productsError.value = 'Не удалось загрузить товары. Проверьте соединение и попробуйте снова.'
    throw error
  } finally {
    if (resetProducts) {
      loading.value = false
    } else {
      loadingMore.value = false
    }
  }
}

// Генерация mock-данных (замените на реальные данные)
const generateMockProducts = (page, sort) => {
  const itemsPerPage = 12
  const totalItems = 47 // Общее количество товаров в категории
  
  const allProducts = []
  const categories = {
    260: {
      name: 'Хвойные растения',
      prefix: 'Хвойное',
      images: ['pine', 'spruce', 'juniper', 'fir', 'cedar']
    },
    261: {
      name: 'Плодовые деревья',
      prefix: 'Плодовое',
      images: ['apple', 'pear', 'plum', 'cherry', 'apricot']
    },
    262: {
      name: 'Декоративные кустарники',
      prefix: 'Кустарник',
      images: ['hydrangea', 'lilac', 'spirea', 'rose', 'mock-orange']
    }
  }
  
  const category = categories[props.categoryId] || categories['260']
  
  for (let i = 1; i <= totalItems; i++) {
    const price = Math.floor(Math.random() * 5000) + 500
    const hasDiscount = i % 7 === 0
    allProducts.push({
      id: i,
      name: `${category.prefix} ${getRandomName(category.prefix)} ${i}`,
      price: hasDiscount ? Math.floor(price * 1.3) : price,
      oldPrice: hasDiscount ? price : null,
      image: `https://picsum.photos/id/${200 + i}/400/300`,
      size: ['H 40-60 см', 'H 60-80 см', 'H 80-100 см', 'C2', 'C3', 'C5'][Math.floor(Math.random() * 6)],
      age: ['1 год', '2 года', '3 года', '4 года'][Math.floor(Math.random() * 4)],
      badge: i % 10 === 0 ? 'Хит' : (i % 15 === 0 ? 'Акция' : null),
      badgeColor: i % 10 === 0 ? 'orange' : 'red',
      loading: false
    })
  }
  
  // Сортировка
  let sortedProducts = [...allProducts]
  switch (sort) {
    case 'price_asc':
      sortedProducts.sort((a, b) => a.price - b.price)
      break
    case 'price_desc':
      sortedProducts.sort((a, b) => b.price - a.price)
      break
    case 'name_asc':
      sortedProducts.sort((a, b) => a.name.localeCompare(b.name))
      break
    case 'name_desc':
      sortedProducts.sort((a, b) => b.name.localeCompare(a.name))
      break
    case 'new':
      sortedProducts.sort((a, b) => b.id - a.id)
      break
    default: // popular - оставляем как есть
      break
  }
  
  const start = (page - 1) * itemsPerPage
  const end = start + itemsPerPage
  const paginatedItems = sortedProducts.slice(start, end)
  
  return {
    items: paginatedItems,
    total: totalItems,
    hasMore: end < totalItems
  }
}

const getRandomName = (prefix) => {
  const names = {
    'Хвойное': ['Сосна', 'Ель', 'Туя', 'Можжевельник', 'Пихта', 'Лиственница', 'Кедр'],
    'Плодовое': ['Яблоня', 'Груша', 'Слива', 'Вишня', 'Абрикос', 'Персик', 'Черешня'],
    'Кустарник': ['Гортензия', 'Сирень', 'Спирея', 'Роза', 'Чубушник', 'Барбарис', 'Дерен']
  }
  const nameList = names[prefix] || names['Хвойное']
  return nameList[Math.floor(Math.random() * nameList.length)]
}

// Загрузка следующей страницы
const loadMore = async () => {
  if (loadingMore.value || !hasMore.value || loading.value) return
  
  const nextPage = currentPage.value + 1
  await fetchProducts(nextPage, sortBy.value)
}

// Сброс и перезагрузка (при изменении сортировки)
const resetAndLoad = async () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
  await fetchProducts(1, sortBy.value, true)
}

// Обработчик скролла для бесконечной подгрузки
let scrollTimeout = null

const handleScroll = () => {
  if (scrollTimeout) return
  
  scrollTimeout = setTimeout(() => {
    const scrollTop = window.scrollY || document.documentElement.scrollTop
    const windowHeight = window.innerHeight
    const documentHeight = document.documentElement.scrollHeight
    
    // Когда до низа страницы остается 300px
    if (scrollTop + windowHeight >= documentHeight - 300) {
      loadMore()
    }
    scrollTimeout = null
  }, 100)
}

// Форматирование цены
const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}

// Добавление в корзину
const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

const addToCart = (product) => {
  snackbar.value = {
    show: true,
    text: `${product.name} добавлен в корзину`,
    color: 'success'
  }
  // Здесь можно вызвать ваш store/cart composable
  console.log('Добавлен в корзину:', product)
}

// Просмотр товара
const viewProduct = (product) => {
  // router.push(`/product/${product.id}`)
  console.log('Просмотр товара:', product)
}

// Инициализация
onMounted(() => {
  // Загружаем первую страницу
  fetchProducts(1, sortBy.value, true)
  // Добавляем слушатель скролла
  window.addEventListener('scroll', handleScroll)
})

// Очистка слушателя
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
.product-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: pointer;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.product-badge :deep(.v-badge__badge) {
  font-size: 11px;
  padding: 4px 8px;
  margin: 8px;
}

.sort-select {
  max-width: 220px;
}

@media (max-width: 600px) {
  .sort-select {
    max-width: 100%;
    width: 100%;
  }
}
</style>