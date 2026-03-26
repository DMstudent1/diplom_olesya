<template>
  <v-container class="py-8">
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 font-weight-bold mb-6">Корзина</h1>
      </v-col>
    </v-row>

    <!-- Загрузка -->
    <v-row v-if="cartStore.isLoading">
      <v-col cols="12" class="text-center py-12">
        <v-progress-circular indeterminate color="green-darken-2" size="64" />
        <p class="mt-4 text-body-1">Загрузка корзины...</p>
      </v-col>
    </v-row>

    <!-- Пустая корзина -->
    <v-row v-else-if="!cartStore.cart?.items?.length">
      <v-col cols="12" class="text-center py-12">
        <v-icon size="96" color="grey-lighten-1" class="mb-4">mdi-cart-outline</v-icon>
        <h3 class="text-h5 mb-2">Корзина пуста</h3>
        <p class="text-body-1 text-grey mb-6">Добавьте товары в корзину, чтобы оформить заказ</p>
        <v-btn color="green-darken-2" size="large" to="/catalog">
          <v-icon start>mdi-shopping</v-icon>
          Перейти в каталог
        </v-btn>
      </v-col>
    </v-row>

    <!-- Корзина с товарами -->
    <v-row v-else>
      <!-- Список товаров -->
      <v-col cols="12" md="8">
        <v-card rounded="lg" elevation="2">
          <v-list lines="two">
            <v-list-item 
              v-for="item in cartStore.cart.items" 
              :key="item.id"
              class="cart-item"
            >
              <template v-slot:prepend>
                <v-avatar rounded="lg" size="80" class="me-4">
                  <v-img :src="getProductImage(item)" cover>
                    <template v-slot:placeholder>
                      <div class="d-flex align-center justify-center fill-height bg-grey-lighten-3">
                        <v-icon color="grey-lighten-1">mdi-image</v-icon>
                      </div>
                    </template>
                  </v-img>
                </v-avatar>
              </template>

              <v-list-item-title class="text-subtitle-1 font-weight-bold mb-1">
                {{ item.name || item.product?.name }}
              </v-list-item-title>


              <template v-slot:append>
                <div class="d-flex flex-column align-end gap-2">
                  <div class="d-flex align-center gap-3">
                    <!-- Счетчик количества -->
                    <div class="quantity-controls d-flex align-center">
                      <v-btn
                        icon="mdi-minus"
                        size="small"
                        variant="outlined"
                        :disabled="item.quantity <= 1"
                        @click="updateQuantity(item, item.quantity - 1)"
                      />
                      <span class="mx-3 text-subtitle-1 font-weight-medium" style="min-width: 40px; text-align: center;">
                        {{ item.quantity }}
                      </span>
                      <v-btn
                        icon="mdi-plus"
                        size="small"
                        variant="outlined"
                        :disabled="item.quantity >= (item.product?.count || 99)"
                        @click="updateQuantity(item, item.quantity + 1)"
                      />
                    </div>

                    <!-- Цена -->
                    <div class="text-right" style="min-width: 120px;">
                      <span class="text-h6 font-weight-bold text-green-darken-3">
                        {{ formatPrice(item.price * item.quantity) }} ₽
                      </span>
                      <div v-if="item.old_price" class="text-caption text-decoration-line-through text-grey">
                        {{ formatPrice(item.old_price * item.quantity) }} ₽
                      </div>
                    </div>

                    <!-- Удалить -->
                    <v-btn
                      icon="mdi-delete"
                      size="small"
                      variant="text"
                      color="error"
                      @click="removeItem(item)"
                    />
                  </div>
                </div>
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <!-- Кнопки действий -->
        <div class="d-flex gap-3 mt-4">
          <v-btn
            variant="outlined"
            color="grey-darken-1"
            prepend-icon="mdi-arrow-left"
            to="/catalog"
          >
            Продолжить покупки
          </v-btn>
          <v-btn
            variant="text"
            color="error"
            prepend-icon="mdi-delete-empty"
            @click="clearCart"
          >
            Очистить корзину
          </v-btn>
        </div>
      </v-col>

      <!-- Блок оформления заказа -->
      <v-col cols="12" md="4">
        <v-card rounded="lg" elevation="2" class="sticky-top" style="top: 80px;">
          <v-card-title class="text-h6 font-weight-bold py-4">
            Итого
          </v-card-title>

          <v-divider />

          <v-card-text class="pt-4">
            <div class="d-flex justify-space-between mb-3">
              <span class="text-body-1">Товаров ({{ totalItems }} шт.):</span>
              <span class="text-body-1 font-weight-medium">{{ formatPrice(subtotal) }} ₽</span>
            </div>

            <div v-if="discount > 0" class="d-flex justify-space-between mb-3 text-green-darken-2">
              <span class="text-body-1">Скидка:</span>
              <span class="text-body-1 font-weight-medium">-{{ formatPrice(discount) }} ₽</span>
            </div>

            <v-divider class="my-3" />

            <div class="d-flex justify-space-between mb-4">
              <span class="text-h6 font-weight-bold">Итого к оплате:</span>
              <span class="text-h5 font-weight-bold text-green-darken-3">
                {{ formatPrice(total) }} ₽
              </span>
            </div>

            <v-btn
              block
              color="green-darken-2"
              size="large"
              rounded="lg"
              :loading="checkoutLoading"
              @click="checkout"
            >
              <v-icon start>mdi-cart-check</v-icon>
              Оформить заказ
            </v-btn>

          </v-card-text>
        </v-card>

        <!-- Рекомендации -->
        <v-card rounded="lg" elevation="1" class="mt-4">
          <v-card-text class="text-center">
            <v-icon color="green-darken-2" size="32" class="mb-2">mdi-truck-fast</v-icon>
            <p class="text-caption text-grey mb-0">
              Бесплатная доставка при заказе от 5000 ₽
            </p>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Диалог подтверждения -->
    <v-dialog v-model="dialog.show" max-width="400">
      <v-card>
        <v-card-title class="text-h6">{{ dialog.title }}</v-card-title>
        <v-card-text>{{ dialog.message }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn color="grey-darken-1" variant="text" @click="dialog.show = false">
            Отмена
          </v-btn>
          <v-btn color="error" variant="flat" @click="dialog.confirm">
            {{ dialog.confirmText }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Снэкбар уведомлений -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
      location="top"
    >
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" />
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCartStore } from '@/stores/cart'
import { useRouter } from 'vue-router'
import axios from 'axios'

const cartStore = useCartStore()
const router = useRouter()
const checkoutLoading = ref(false)

// Состояние диалога
const dialog = ref({
  show: false,
  title: '',
  message: '',
  confirm: () => {}
})

// Снэкбар
const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

// Вычисляемые значения
const totalItems = computed(() => {
  return cartStore.cart?.items?.reduce((sum, item) => sum + item.quantity, 0) || 0
})

const subtotal = computed(() => {
  return cartStore.cart?.items?.reduce((sum, item) => sum + (item.price * item.quantity), 0) || 0
})

const discount = computed(() => {
  return cartStore.cart?.items?.reduce((sum, item) => {
    if (item.old_price) {
      return sum + ((item.old_price - item.price) * item.quantity)
    }
    return sum
  }, 0) || 0
})

const total = computed(() => {
  return subtotal.value - discount.value
})

// Получить изображение товара
const getProductImage = (item) => {
  // Если есть изображение в товаре
  if (item.image) return item.image
  if (item.product?.image) return item.product.image
  
  // Фейковые изображения на основе ID
  const images = [
    'https://picsum.photos/id/104/300/200',
    'https://picsum.photos/id/106/300/200',
    'https://picsum.photos/id/10/300/200',
    'https://picsum.photos/id/13/300/200',
    'https://picsum.photos/id/15/300/200',
  ]
  return images[item.id % images.length]
}

// Форматирование цены
const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(Math.round(price))
}

// Обновить количество товара
const updateQuantity = async (item, newQuantity) => {
  if (newQuantity < 1) return
  
  try {
    await axios.put(`/api/cart/`, {
      product_id: item.id,
      quantity: newQuantity
    })
    await cartStore.getCart()
    
    snackbar.value = {
      show: true,
      text: 'Количество обновлено',
      color: 'success'
    }
  } catch (error) {
    console.error('Error updating quantity:', error)
    snackbar.value = {
      show: true,
      text: error.response?.data?.message || 'Ошибка обновления количества',
      color: 'error'
    }
  }
}

// Удалить товар
const removeItem = (item) => {
  dialog.value = {
    show: true,
    title: 'Удаление товара',
    message: `Вы уверены, что хотите удалить "${item.name || item.product?.name}" из корзины?`,
    confirmText: 'Удалить',
    confirm: async () => {
      dialog.value.show = false
      try {
        await axios.delete(`/api/cart/remove`,{
            data: {
                product_id: item.id,
                quantity: item.quantity
            }
        })
        await cartStore.getCart()
        
        snackbar.value = {
          show: true,
          text: 'Товар удален из корзины',
          color: 'success'
        }
      } catch (error) {
        console.error('Error removing item:', error)
        snackbar.value = {
          show: true,
          text: error.response?.data?.message || 'Ошибка удаления товара',
          color: 'error'
        }
      }
    }
  }
}

// Очистить корзину
const clearCart = () => {
  dialog.value = {
    show: true,
    title: 'Очистка корзины',
    message: 'Вы уверены, что хотите полностью очистить корзину? Это действие нельзя отменить.',
    confirmText: 'Очистить',
    confirm: async () => {
      dialog.value.show = false
      try {
        await axios.delete('/api/cart')
        await cartStore.getCart()
        
        snackbar.value = {
          show: true,
          text: 'Корзина очищена',
          color: 'success'
        }
      } catch (error) {
        console.error('Error clearing cart:', error)
        snackbar.value = {
          show: true,
          text: error.response?.data?.message || 'Ошибка очистки корзины',
          color: 'error'
        }
      }
    }
  }
}

// Оформить заказ
const checkout = async () => {
  checkoutLoading.value = true
  
  try {
    // Проверяем авторизацию
    const response = await axios.post('/api/checkout')
    
    if (response.data.redirect) {
      // Если нужно перенаправить на страницу оформления заказа
      router.push(response.data.redirect)
    } else {
      // Если нужно показать уведомление об успехе
      snackbar.value = {
        show: true,
        text: 'Заказ успешно оформлен!',
        color: 'success'
      }
      
      // Обновляем корзину
      await cartStore.getCart()
      
      // Перенаправляем на страницу заказов через 2 секунды
      setTimeout(() => {
        router.push('/orders')
      }, 2000)
    }
  } catch (error) {
    console.error('Error during checkout:', error)
    
    if (error.response?.status === 401) {
      // Не авторизован - перенаправляем на регистрацию
      snackbar.value = {
        show: true,
        text: 'Для оформления заказа необходимо войти в систему',
        color: 'warning'
      }
      
      setTimeout(() => {
        router.push('/register')
      }, 1500)
    } else {
      snackbar.value = {
        show: true,
        text: error.response?.data?.message || 'Ошибка оформления заказа',
        color: 'error'
      }
    }
  } finally {
    checkoutLoading.value = false
  }
}

// Загрузка корзины при монтировании
onMounted(async () => {
  await cartStore.getCart()
})
</script>

<style scoped>
.cart-item {
  transition: background-color 0.2s ease;
  border-bottom: 1px solid #e0e0e0;
}

.cart-item:hover {
  background-color: #f5f5f5;
}

.quantity-controls {
  background-color: #f5f5f5;
  border-radius: 8px;
  padding: 4px 8px;
}

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}

.sticky-top {
  position: sticky;
}

@media (max-width: 960px) {
  .sticky-top {
    position: static;
  }
}
</style>