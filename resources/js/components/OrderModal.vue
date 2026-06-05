<template>
  <v-dialog v-model="internalVisible" scrollable max-width="800" persistent>
    <v-card>
        <template v-if="!showSuccessScreen">
      <!-- Заголовок с progress bar -->
            
      <v-card-title class="pa-4 pb-0" v-if="!showYooKassaWidget" >
        <div class="d-flex justify-space-between align-center mb-2">
          <h2 class="text-h5 font-weight-bold">Оформление заказа</h2>
          <v-btn icon="mdi-close" variant="text" @click="closeModal" />
        </div>
        
        <!-- Индикатор этапов -->
        <v-progress-linear 
          :model-value="((currentStep + 1) / totalSteps) * 100" 
          height="4"
          color="green-darken-2"
          class="mb-4"
        />
        
        <!-- Шаги -->
        <div class="steps-wrapper">
          <v-row class="ma-0">
            <v-col 
              v-for="(step, index) in steps" 
              :key="index"
              class="text-center pa-0"
              :cols="12 / totalSteps"
            >
              <div 
                class="step-item"
                :class="{
                  'step-active': index === currentStep,
                  'step-completed': index < currentStep,
                  'step-pending': index > currentStep
                }"
              >
                <div class="step-circle">
                  <span v-if="index < currentStep" class="step-check">✓</span>
                  <span v-else>{{ index + 1 }}</span>
                </div>
                <div class="step-label">{{ step.title }}</div>
              </div>
            </v-col>
          </v-row>
        </div>
      </v-card-title>

      <v-divider />

      <!-- Содержание этапов -->
      <v-card-text class="pa-6" style="min-height: 450px;">
        <!-- ЭТАП 1: Выбор пункта выдачи -->
        <div v-if="currentStep === 0">
          <h3 class="text-h6 font-weight-bold mb-4">Выберите пункт выдачи СДЭК</h3>
          
          <v-select
            v-model="selectedPickupPoint"
            :items="pickupPoints"
            item-title="location.address_full"
            item-value="uuid"
            label="Пункт выдачи"
            variant="outlined"
            placeholder="Выберите пункт выдачи"
            :loading="pickupPointsLoading"
            return-object
            class="mb-4"
          >
            <template v-slot:item="{ item, props }">
              <v-list-item v-bind="props">
                <template v-slot:title>
                  <div>{{ item.location?.address_full}}</div>
                </template>
              </v-list-item>
            </template>
            
            <template v-slot:selection="{ item }">
              <div>{{ item.location?.address_full }}</div>
            </template>
          </v-select>

          <!-- Детальная информация о выбранном пункте -->
          <v-card
            v-if="selectedPickupPoint"
            variant="tonal"
            class="mt-4"
            color="info"
          >
            <v-card-text>
              <div class="d-flex flex-column gap-3">
                <!-- Адрес -->
                <div class="d-flex align-start">
                  <v-icon class="me-2" size="small" color="info">mdi-map-marker</v-icon>
                  <div>
                    <strong>Адрес:</strong> 
                    {{ selectedPickupPoint.location?.address_full || selectedPickupPoint.location?.address }}
                  </div>
                </div>

                <!-- Ближайшее метро -->
                <div v-if="selectedPickupPoint.nearest_metro_station" class="d-flex align-start">
                  <v-icon class="me-2" size="small" color="info">mdi-subway</v-icon>
                  <div>
                    <strong>Метро:</strong> 
                    {{ selectedPickupPoint.nearest_metro_station }}
                  </div>
                </div>

                <!-- Режим работы -->
                <div class="d-flex align-start">
                  <v-icon class="me-2" size="small" color="info">mdi-clock-outline</v-icon>
                  <div>
                    <strong>Режим работы:</strong>
                    <div class="text-caption">{{ selectedPickupPoint.work_time }}</div>
                  </div>
                </div>

                <!-- Email -->
                <div v-if="selectedPickupPoint.email" class="d-flex align-start">
                  <v-icon class="me-2" size="small" color="info">mdi-email</v-icon>
                  <div>
                    <strong>Email:</strong>
                    <div class="text-caption">{{ selectedPickupPoint.email }}</div>
                  </div>
                </div>

                <!-- Кнопка открытия на карте -->
                <v-btn
                  v-if="selectedPickupPoint.location?.longitude && selectedPickupPoint.location?.latitude"
                  variant="text"
                  color="info"
                  size="small"
                  class="mt-2"
                  :href="`https://maps.google.com/?q=${selectedPickupPoint.location.latitude},${selectedPickupPoint.location.longitude}`"
                  target="_blank"
                  prepend-icon="mdi-map"
                >
                  Открыть на карте
                </v-btn>
              </div>
            </v-card-text>
          </v-card>

          <v-alert
            v-else
            type="info"
            variant="tonal"
            class="mt-4"
          >
            <v-icon start>mdi-information</v-icon>
            Пожалуйста, выберите удобный для вас пункт выдачи СДЭК
          </v-alert>
        </div>

        <!-- ЭТАП 2: Список товаров + расчет доставки -->
        <div v-if="currentStep === 1">
          <h3 class="text-h6 font-weight-bold mb-4">Ваш заказ</h3>
          
          <!-- Список товаров -->
          <v-card variant="outlined" class="mb-4">
            <v-list density="compact">
              <v-list-item 
                v-for="item in cartItems" 
                :key="item.id"
                class="order-item"
              >
                <template v-slot:prepend>
                  <v-avatar rounded="lg" size="50" class="me-3">
                    <v-img :src="getProductImage(item)" cover>
                      <template v-slot:placeholder>
                        <v-icon>mdi-image</v-icon>
                      </template>
                    </v-img>
                  </v-avatar>
                </template>

                <v-list-item-title class="text-subtitle-2 font-weight-medium">
                  {{ item.name || item.product?.name }}
                </v-list-item-title>
                
                <v-list-item-subtitle>
                  Количество: {{ item.quantity }} шт.
                </v-list-item-subtitle>

                <template v-slot:append>
                  <div class="text-right">
                    <div class="text-subtitle-1 font-weight-bold text-green-darken-3">
                      {{ formatPrice(item.price * item.quantity) }} ₽
                    </div>
                    <div v-if="item.old_price" class="text-caption text-decoration-line-through text-grey">
                      {{ formatPrice(item.old_price * item.quantity) }} ₽
                    </div>
                  </div>
                </template>
              </v-list-item>
            </v-list>
          </v-card>

          <!-- Расчет доставки -->
          <v-card variant="outlined" class="mb-4">
            <v-card-text>
              <!-- Примерный вес -->
              <div v-if="deliveryData.weight_calc > 0 && !deliveryLoading" class="d-flex justify-space-between align-center mb-3">
                <div>
                  <span class="font-weight-bold">Примерный вес</span>
                  <div class="text-caption text-grey">Вес посылки</div>
                </div>
                <div class="text-body-1">
                  {{ formatWeight(deliveryData.weight_calc) }}
                </div>
              </div>

              <!-- Сроки доставки -->
              <div v-if="deliveryData.period_min > 0 && !deliveryLoading" class="d-flex justify-space-between align-center mb-3">
                <div>
                  <span class="font-weight-bold">Срок доставки</span>
                  <div class="text-caption text-grey">Рабочие дни</div>
                </div>
                <div class="text-body-1">
                  {{ deliveryData.period_min }} - {{ deliveryData.period_max }} дня
                </div>
              </div>

              <!-- Диапазон дат -->
              <div v-if="deliveryData.delivery_date_range?.min && !deliveryLoading" class="d-flex justify-space-between align-center mb-3">
                <div>
                  <span class="font-weight-bold">Дата получения</span>
                  <div class="text-caption text-grey">Когда можно забрать</div>
                </div>
                <div class="text-body-1">
                  {{ formatDate(deliveryData.delivery_date_range.min) }} - {{ formatDate(deliveryData.delivery_date_range.max) }}
                </div>
              </div>

              <v-divider class="mb-3" />

              <div class="d-flex justify-space-between align-center mb-3">
                <div>
                  <span class="font-weight-bold">Стоимость товаров</span>
                  <div class="text-caption text-grey">{{ cartItems.length }} позиции(й)</div>
                </div>
                <div class="text-h6 font-weight-bold">
                  {{ formatPrice(actualTotalPrice) }} ₽
                </div>
              </div>

              <div class="d-flex justify-space-between align-center mb-3">
                <div>
                  <span class="font-weight-bold">Стоимость доставки</span>
                  <div class="text-caption text-grey">До пункта выдачи</div>
                </div>
                <div class="text-h6 font-weight-bold text-green-darken-3">
                  <v-progress-circular 
                    v-if="deliveryLoading" 
                    indeterminate 
                    size="24"
                    color="green-darken-2"
                  />
                  <span v-else>
                    {{ formatPrice(deliveryPrice) }} ₽
                  </span>
                </div>
              </div>

              <v-divider class="mb-3" />

              <div class="d-flex justify-space-between align-center">
                <div>
                  <span class="font-weight-bold">Итого к оплате</span>
                  <div class="text-caption text-grey">С учетом доставки</div>
                </div>
                <div class="text-h5 font-weight-bold text-green-darken-3">
                  {{ formatPrice(finalTotal) }} ₽
                </div>
              </div>
            </v-card-text>
          </v-card>
        </div>

        <!-- ЭТАП 3: Оплата через ЮKassa -->
        <div v-if="currentStep === 2">
          <h3 class="text-h6 font-weight-bold mb-4">Оплата заказа</h3>
          
          <v-alert
            type="info"
            variant="tonal"
            class="mb-4"
          >
            <v-icon start>mdi-shield-check</v-icon>
            Оплата проходит через защищенный платежный сервис ЮKassa
          </v-alert>

          <!-- Контейнер для виджета ЮKassa -->
          <div v-if="showYooKassaWidget" ref="yookassaContainer" id="yookassa-widget-container"></div>

          <!-- Кнопка оплаты -->
          <v-btn
            block
            color="green-darken-2"
            size="large"
            rounded="lg"
            class="mt-6"
            :loading="yookassaLoading"
            :disabled="!canSubmit || yookassaLoading"
            v-if="!showYooKassaWidget"
            @click="initYooKassaPayment"
          >
            <v-icon start>mdi-credit-card</v-icon>
            {{ yookassaLoading ? 'Подготовка платежа...' : 'Оплатить онлайн' }}
          </v-btn>
        </div>
      </v-card-text>

      <v-divider />

      <!-- Кнопки навигации -->
      <v-card-actions v-if="!showYooKassaWidget" class="pa-4">
        <v-btn
          v-if="currentStep > 0"
          variant="text"
          @click="prevStep"
          :disabled="loading"
        >
          <v-icon start>mdi-chevron-left</v-icon>
          Назад
        </v-btn>
        
        <v-spacer />
        
        <v-btn
          v-if="currentStep < totalSteps - 1"
          color="green-darken-2"
          variant="flat"
          @click="nextStep"
          :disabled="!canGoNext"
        >
          Далее
          <v-icon end>mdi-chevron-right</v-icon>
        </v-btn>
      </v-card-actions>
      </template>
      <template v-else>
        <v-card-text class="pa-6 text-center">
          <v-icon
            icon="mdi-check-circle"
            size="80"
            color="green-darken-2"
            class="mb-4"
          />
          
          <h2 class="text-h4 font-weight-bold mb-3">
            Спасибо за заказ!
          </h2>
          
          <p class="text-body-1 mb-2">
            Ваш заказ №{{ orderNumber }} успешно оформлен
          </p>
          <p class="text-subtitle-1 text-grey mb-4">
            Сумма заказа: {{ formatPrice(orderTotal) }} ₽
          </p>
          
          <v-alert
            type="success"
            variant="tonal"
            class="mb-4"
          >
            <v-icon start>mdi-email</v-icon>
            Подтверждение отправлено на вашу почту
          </v-alert>
          
          <v-progress-circular
            indeterminate
            size="32"
            color="green-darken-2"
            class="mt-4"
          />
                <p class="text-caption text-grey mt-2">
            Закрывается через 3 секунды...
          </p>
        </v-card-text>
      </template>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  cartItems: {
    type: Array,
    default: () => []
  },
  totalPrice: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['update:modelValue', 'success', 'close'])

// Видимость модалки
const internalVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const currentStep = ref(0)
const totalSteps = 3
const loading = ref(false)

const showSuccessScreen = ref(false)
const orderNumber = ref('')
const orderTotal = ref(0)
const paymentMessage = ref(null)

// Данные формы
const selectedPickupPoint = ref(null)

// Состояния загрузки
const pickupPointsLoading = ref(false)

// Данные с сервера
const pickupPoints = ref([])

// Данные доставки
const deliveryPrice = ref(0)
const deliveryLoading = ref(false)
const deliveryData = ref({
  delivery_sum: 0,
  period_min: 0,
  period_max: 0,
  calendar_min: 0,
  calendar_max: 0,
  weight_calc: 0,
  total_sum: 0,
  currency: 'RUB',
  delivery_date_range: {
    min: '',
    max: ''
  }
})

// ЮKassa
const yookassaLoading = ref(false)
const showYooKassaWidget = ref(false)
const yookassaContainer = ref(null)
let yookassaWidget = null

// Шаги
const steps = [
  { title: 'Пункт выдачи' },
  { title: 'Товары и доставка' },
  { title: 'Оплата' }
]

// Вычисляем реальную сумму товаров
const actualTotalPrice = computed(() => {
  if (props.cartItems && props.cartItems.length > 0) {
    return props.cartItems.reduce((sum, item) => {
      const price = Number(item.price) || 0
      const quantity = Number(item.quantity) || 0
      return sum + (price * quantity)
    }, 0)
  }
  return props.totalPrice || 0
})

// Итоговая сумма
const finalTotal = computed(() => {
  return actualTotalPrice.value + deliveryPrice.value
})

// Проверка возможности перехода к следующему шагу
const canGoNext = computed(() => {
  if (currentStep.value === 0) {
    return selectedPickupPoint.value !== null
  }
  return true
})

// Проверка возможности отправки заказа
const canSubmit = computed(() => {
  return selectedPickupPoint.value !== null &&
         props.cartItems && 
         props.cartItems.length > 0
})

// Получить изображение товара
const getProductImage = (item) => {
  if (item.image) return item.image
  if (item.product?.image) return item.product.image
  return `https://picsum.photos/id/${(item.id % 100) + 10}/100/100`
}

// Форматирование цены
const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(Math.round(price))
}

// Форматирование даты
const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

// Форматирование веса
const formatWeight = (grams) => {
  if (grams >= 1000) {
    return `${(grams / 1000).toFixed(1)} кг`
  }
  return `${grams} г`
}

// Загрузка пунктов выдачи
const loadPickupPoints = async () => {
  pickupPointsLoading.value = true
  try {
    const response = await axios.get('/api/order/delivery-points')
    pickupPoints.value = response.data
  } catch (error) {
    console.error('Ошибка загрузки пунктов выдачи:', error)
  } finally {
    pickupPointsLoading.value = false
  }
}

// Расчет доставки
const calculateDelivery = async () => {
  if (!selectedPickupPoint.value) {
    deliveryPrice.value = 0
    return
  }
  
  deliveryLoading.value = true
  
  try {
    const response = await axios.post('/api/order/calculator', {
      code: selectedPickupPoint.value.code,
    })
    
    deliveryData.value = response.data
    deliveryPrice.value = response.data.delivery_sum || 0
  } catch (error) {
    console.error('Ошибка расчета доставки:', error)
    deliveryPrice.value = 350
  } finally {
    deliveryLoading.value = false
  }
}

// Инициализация платежа через ЮKassa
// Инициализация платежа через ЮKassa
const initYooKassaPayment = async () => {
  if (!canSubmit.value) return
  
  yookassaLoading.value = true
  
  try {
    // Создаем платеж на бэкенде
    const orderData = {
      pickup_point: {
        code: selectedPickupPoint.value.code,
        uuid: selectedPickupPoint.value.uuid,
        address: selectedPickupPoint.value.location?.address_full,
        work_time: selectedPickupPoint.value.work_time,
        phones: selectedPickupPoint.value.phones
      },
      items: props.cartItems.map(item => ({
        id: item.id,
        name: item.name || item.product?.name,
        quantity: item.quantity,
        price: item.price,
        total: item.price * item.quantity
      })),
      subtotal: actualTotalPrice.value,
      delivery_price: deliveryPrice.value,
      delivery_info: deliveryData.value,
      total: finalTotal.value
    }
    
    // Отправляем запрос на создание платежа
    const response = await axios.post('/api/payments', {
      amount: finalTotal.value
    })
    
    console.log('Response:', response.data)
    
    // ИСПРАВЛЕНО: получаем confirmation_token из правильного места
    let confirmationToken = null
    let paymentId = null
    
    // Ваша структура ответа: response.data.data.confirmation.confirmation_token
    if (response.data && response.data.data) {
      paymentId = response.data.data.id
      confirmationToken = response.data.data.confirmation?.confirmation_token
    } 
    // Альтернативная структура (если изменится)
    else if (response.data && response.data.confirmation) {
      paymentId = response.data.id
      confirmationToken = response.data.confirmation.confirmation_token
    }
    
    console.log('Payment ID:', paymentId)
    console.log('Confirmation Token:', confirmationToken)
    
    if (!confirmationToken) {
      throw new Error('Не получен confirmation_token от сервера')
    }
    
    // Сохраняем данные для последующей проверки
    sessionStorage.setItem('current_payment_id', paymentId)
    sessionStorage.setItem('order_data', JSON.stringify(orderData))
    
    // Загружаем скрипт виджета если его нет
    if (!window.YooMoneyCheckoutWidget) {
      await loadYooKassaScript()
    }
    
    // Показываем виджет
    showYooKassaWidget.value = true
    await nextTick()
    
    // Создаем виджет
    createWidget(confirmationToken)
    
  } catch (error) {
    console.error('Ошибка создания платежа:', error)
    const errorMessage = error.response?.data?.message || 
                        error.response?.data?.error?.description ||
                        error.message || 
                        'Ошибка при подготовке платежа'
    alert(errorMessage)
    showYooKassaWidget.value = false
  } finally {
    yookassaLoading.value = false
  }
}

// Создание виджета
// Создание виджета (исправленная версия)
const createWidget = (confirmationToken) => {
  try {
    if (!confirmationToken) {
      console.error('Cannot create widget: confirmation_token is missing')
      paymentMessage.value = {
        text: 'Ошибка: токен оплаты не получен',
        type: 'error'
      }
      showYooKassaWidget.value = false
      return
    }
    
    console.log('Creating YooKassa widget with token:', confirmationToken)
    
    const container = document.getElementById('yookassa-widget-container')
    if (container) {
      container.innerHTML = ''
    }
    
    // Создаем виджет с правильной конфигурацией
    const widget = new window.YooMoneyCheckoutWidget({
      confirmation_token: confirmationToken,
      return_url: window.location.href,
      // Используем callback для обработки событий
      callback: (result) => {
        console.log('Widget callback:', result)
        
        // Проверяем статус платежа
        if (result && result.status === 'success') {
          console.log('Payment successful via callback')
          handlePaymentSuccessWithoutRedirect(result)
        } else if (result && result.status === 'canceled') {
          console.log('Payment canceled via callback')
          paymentMessage.value = {
            text: 'Платеж отменен',
            type: 'warning'
          }
          cleanupYooKassa()
        }
      },
      error_callback: (error) => {
        console.error('Widget error callback:', error)
        paymentMessage.value = {
          text: `Ошибка платежа: ${error.message || 'Неизвестная ошибка'}`,
          type: 'error'
        }
        showYooKassaWidget.value = false
      }
    })
    
    yookassaWidget = widget
    widget.render('yookassa-widget-container')
    console.log('Widget rendered successfully')
    
  } catch (error) {
    console.error('Error creating widget:', error)
    paymentMessage.value = {
      text: `Ошибка создания виджета: ${error.message}`,
      type: 'error'
    }
    showYooKassaWidget.value = false
  }
}

const loadYooKassaScript = () => {
  return new Promise((resolve, reject) => {
    // Проверяем, загружен ли уже скрипт
    if (window.YooMoneyCheckoutWidget) {
      console.log('YooKassa widget already loaded')
      resolve()
      return
    }
    
    // Проверяем, есть ли скрипт в процессе загрузки
    const existingScript = document.querySelector('script[src*="checkout-widget.js"]')
    if (existingScript) {
      console.log('Script already loading, waiting...')
      existingScript.addEventListener('load', () => resolve())
      existingScript.addEventListener('error', () => reject(new Error('Script load error')))
      return
    }
    const script = document.createElement('script')
    script.src = 'https://yookassa.ru/checkout-widget/v1/checkout-widget.js'
    script.async = true
    
    script.onload = () => {
      console.log('YooKassa widget script loaded')
      resolve()
    }
    
    script.onerror = (error) => {
      console.error('Failed to load YooKassa widget script:', error)
      reject(new Error('Не удалось загрузить платежный виджет'))
    }
    document.head.appendChild(script)
  })
}

// Обработка успешного платежа без редиректа
const handlePaymentSuccessWithoutRedirect = async (paymentData) => {
  try {
    // Показываем индикатор загрузки
    yookassaLoading.value = true
    
    // Получаем сохраненные данные заказа
    const orderData = JSON.parse(sessionStorage.getItem('order_data') || '{}')
    const paymentId = sessionStorage.getItem('current_payment_id')
    
    // Отправляем подтверждение на сервер
    const response = await axios.post('/api/orders', {
      payment_id: paymentId,
      payment_data: paymentData,
      order_data: orderData,
      status: 'paid'
    })
    
    if (response.data.success) {
      // Очищаем корзину на сервере
      await axios.post('/api/cart/clear')
      
      // Очищаем локальную корзину (если есть)
      emit('cart-cleared')
      
      // Закрываем виджет
      cleanupYooKassa()
      
      // Показываем сообщение об успехе в модальном окне
      showPaymentSuccess(orderData, paymentId)
      
      // Эмитим событие успеха
      emit('success', {
        payment_id: paymentId,
        order_data: orderData,
        total: finalTotal.value
      })
    } else {
      throw new Error(response.data.message || 'Ошибка при оформлении заказа')
    }
    
  } catch (error) {
    console.error('Error confirming order:', error)
    showPaymentMessage('Платеж прошел, но произошла ошибка при оформлении заказа. Пожалуйста, свяжитесь с поддержкой.', 'error')
  } finally {
    yookassaLoading.value = false
  }
}

// Показать сообщение об успешной оплате в модалке
const showPaymentSuccess = (orderData, paymentId) => {
  // Меняем содержимое модального окна на сообщение об успехе
  showSuccessScreen.value = true
  orderNumber.value = paymentId
  orderTotal.value = finalTotal.value
  
  // Автоматически закроем модалку через 3 секунды
  setTimeout(() => {
    closeModal()
    showSuccessScreen.value = false
  }, 3000)
}

// Показать сообщение о платеже
const showPaymentMessage = (message, type = 'info') => {
  paymentMessage.value = {
    text: message,
    type: type
  }
  
  // Автоматически скрываем через 5 секунд
  setTimeout(() => {
    paymentMessage.value = null
  }, 5000)
}

// Очистка виджета
const cleanupYooKassa = () => {
  if (yookassaWidget) {
    yookassaWidget.destroy()
    yookassaWidget = null
  }
  showYooKassaWidget.value = false
}

// Навигация
const nextStep = () => {
  if (currentStep.value < totalSteps - 1 && canGoNext.value) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const closeModal = () => {
  cleanupYooKassa()
  internalVisible.value = false
  currentStep.value = 0
  selectedPickupPoint.value = null
  deliveryPrice.value = 0
  emit('close')
}

// Watchers
watch(selectedPickupPoint, () => {
  if (selectedPickupPoint.value) {
    calculateDelivery()
  }
})

watch(currentStep, (newStep, oldStep) => {
  if (newStep === 1 && oldStep === 0) {
    calculateDelivery()
  }
})

watch(internalVisible, (newVal) => {
  if (newVal && pickupPoints.value.length === 0) {
    loadPickupPoints()
  } else if (!newVal) {
    cleanupYooKassa()
    currentStep.value = 0
    selectedPickupPoint.value = null
    deliveryPrice.value = 0
  }
})
</script>

<style scoped>
/* Ваши существующие стили */
.steps-wrapper {
  margin-top: 16px;
}

.step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
}

.step-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
  transition: all 0.3s ease;
}

.step-active .step-circle {
  background-color: #1b5e20;
  color: white;
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(27, 94, 32, 0.3);
}

.step-completed .step-circle {
  background-color: #2e7d32;
  color: white;
}

.step-pending .step-circle {
  background-color: #e0e0e0;
  color: #757575;
}

.step-check {
  font-size: 18px;
  font-weight: bold;
}

.step-label {
  font-size: 12px;
  margin-top: 6px;
  font-weight: 500;
}

.step-active .step-label {
  color: #1b5e20;
  font-weight: bold;
}

.order-item {
  border-bottom: 1px solid #f0f0f0;
}

.order-item:last-child {
  border-bottom: none;
}

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}

#yookassa-widget-container {
  min-height: 400px;
}
</style>