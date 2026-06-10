<template>
    <a 
    class="product-link"
    @click="handleCardClick"
  >
  <v-card class="product-card" elevation="2" rounded="lg">
    <v-img 
      height="200" 
      cover 
      class="bg-grey-lighten-3" 
      :src="productImage"
      :lazy-src="productThumb"
    >
      <template v-slot:placeholder>
        <div class="d-flex align-center justify-center fill-height">
          <v-icon size="48" color="grey-lighten-1">mdi-leaf</v-icon>
        </div>
      </template>
      
      <!-- Опционально: показываем количество в наличии на изображении -->
      <template v-slot:error>
        <div class="d-flex align-center justify-center fill-height">
          <v-icon size="48" color="grey-lighten-1">mdi-image-off</v-icon>
        </div>
      </template>
    </v-img>

    <v-card-item class="pt-4">
      <v-card-title class="text-subtitle-1 font-weight-bold text-wrap">
        {{ product.name }}
      </v-card-title>

      <div class="d-flex align-center gap-2 mt-2">
        <span v-if="product.old_price" class="text-caption text-decoration-line-through text-grey">
          {{ formatPrice(product.old_price) }} ₽
        </span>
        <span class="text-h6 font-weight-bold text-green-darken-3">
          {{ formatPrice(product.price) }} ₽
        </span>
      </div>

      <div class="d-flex align-center mt-2">
        <v-icon size="small" color="grey" class="mr-1">mdi-package-variant</v-icon>
        <span class="text-caption text-grey">В наличии: {{ product.count }} шт.</span>
      </div>
    </v-card-item>

    <v-card-actions class="px-4 pb-4 pt-0">
      <v-btn 
        block 
        color="green-darken-2" 
        :variant="isInCart ? 'outlined' : 'flat'" 
        rounded="lg" 
        :loading="loading"
        @click.stop="handleCartAction"
      >
        <v-icon start>{{ isInCart ? 'mdi-check' : 'mdi-cart-plus' }}</v-icon>
        {{ getButtonText() }}
      </v-btn>
    </v-card-actions>
  </v-card>
  </a>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'
import { useRouter } from 'vue-router'

const cartStore = useCartStore();
const router = useRouter();
const props = defineProps({
  product: {
    type: Object,
    required: true,
    validator: (value) => {
      return value.id && value.name && typeof value.price === 'number'
    }
  }
})

const emit = defineEmits(['add-to-cart', 'remove-from-cart'])
const loading = ref(false)

const handleCardClick = (e) => {
  // Проверяем, не был ли клик по кнопке или внутри нее
  if (e.target.closest('.v-btn') || e.target.closest('button')) {
    return; // Не редиректим
  }
  
  router.push(`/product/${props.product.id}`); // ✅ Без перезагрузки
}
// Альтернативный вариант, если media это объект
const productImage = computed(() => {
  // Если media массив
  if (Array.isArray(props.product.media) && props.product.media.length > 0) {
    return props.product.media[0].original_url
  }
  // Если media объект
  if (props.product.media && props.product.media.original_url) {
    return props.product.media.original_url
  }
  return getPlaceholderImage()
})

// Получаем миниатюру (для lazy loading)
const productThumb = computed(() => {
  if (props.product.media && props.product.media.length > 0) {
    // Если есть thumb, используем его, иначе original
    return props.product.media[0].thumb_url || props.product.media[0].original_url
  }
  return getPlaceholderImage()
})

// Функция для получения изображения-заглушки на основе категории или ID
const getPlaceholderImage = () => {
  const images = [
    'https://picsum.photos/id/104/300/200', // цветок
    'https://picsum.photos/id/106/300/200', // дерево
    'https://picsum.photos/id/10/300/200',  // лес
    'https://picsum.photos/id/13/300/200',  // лист
    'https://picsum.photos/id/15/300/200',  // природа
  ]
  return images[props.product.id % images.length]
}

// Проверяем, есть ли товар в корзине
const isInCart = computed(() => {
  if (!cartStore.cart?.items) return false
  return cartStore.cart.items.some(item => item.product_id === props.product.id || item.id === props.product.id)
})

// Получаем ID элемента корзины
const getCartItemId = computed(() => {
  if (!cartStore.cart?.items) return null
  const item = cartStore.cart.items.find(item => item.product_id === props.product.id || item.id === props.product.id)
  return item ? item.id : null
})

// Получаем количество товара в корзине
const getItemQuantity = computed(() => {
  if (!cartStore.cart?.items) return 0
  const item = cartStore.cart.items.find(item => item.product_id === props.product.id || item.id === props.product.id)
  return item ? item.quantity : 0
})

// Текст кнопки в зависимости от состояния
const getButtonText = () => {
  if (loading.value) return isInCart.value ? 'Удаление...' : 'Добавление...'
  if (isInCart.value) {
    const quantity = getItemQuantity.value
    return quantity > 1 ? `В корзине (${quantity} шт.)` : 'В корзине'
  }
  return 'В корзину'
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price)
}

// Основной обработчик клика
const handleCartAction = async () => {
  if (isInCart.value) {
    await removeFromCart()
  } else {
    await addToCart()
  }
}

// Добавление в корзину
const addToCart = async () => {
  loading.value = true

  try {
    const response = await axios.post('/api/cart', {
      product_id: props.product.id,
      quantity: 1
    })
    
    // Обновляем корзину после успешного добавления
    await cartStore.getCart()
    
    emit('add-to-cart', props.product)
    
  } catch (error) {
    console.error('Ошибка добавления в корзину:', error)
  } finally {
    loading.value = false
  }
}

// Удаление из корзины
const removeFromCart = async () => {
  const cartItemId = getCartItemId.value
  
  if (!cartItemId) {
    console.error('Не найден ID товара в корзине')
    return
  }
  
  loading.value = true

  try {
    const response = await axios.delete(`/api/cart/remove`, {
      data: {
        product_id: cartItemId,
        quantity: 1
      }
    })
    
    // Обновляем корзину после успешного удаления
    await cartStore.getCart()
    
    emit('remove-from-cart', props.product)
    
  } catch (error) {
    console.error('Ошибка удаления из корзины:', error)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>

.product-link {
  text-decoration: none;
  color: inherit;
  display: block;
  cursor: pointer;
}

.product-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.gap-2 {
  gap: 8px;
}
</style>