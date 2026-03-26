<template>
  <v-container fluid class="py-6">
    <!-- Заголовок категории -->
    <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-6">
      <div>
        <h2 class="text-h3 font-weight-bold text-green-darken-3">
          {{ category }}
        </h2>
      </div>
      
      <v-btn
        :to="`/categories/${categoryId}`"
        variant="outlined"
        color="green-darken-2"
        rounded="pill"
      >
        Все товары категории
        <v-icon end>mdi-arrow-right</v-icon>
      </v-btn>
    </div>

    <!-- Состояния загрузки и ошибок -->
    <v-row v-if="loading" justify="center" class="py-12">
      <v-col cols="auto">
        <v-progress-circular indeterminate color="green-darken-2" size="48" />
      </v-col>
    </v-row>

    <v-alert
      v-else-if="error"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      {{ error }}
    </v-alert>

    <!-- Список товаров -->
    <v-row v-else>
      <v-col
        v-for="product in products"
        :key="product.id"
        cols="12"
        sm="8"
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

    <!-- Если товаров нет -->
    <v-alert
      v-if="!loading && products.length === 0"
      type="info"
      variant="tonal"
      class="text-center"
    >
      <v-icon start>mdi-leaf-off</v-icon>
      В этой категории пока нет товаров в наличии
    </v-alert>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ProductCard from './ProductCard.vue'

const props = defineProps({
  categoryId: {
    type: String,
    required: true,
  }
})

const emit = defineEmits(['add-to-cart'])

const products = ref([])
const category = ref('')
const loading = ref(true)
const error = ref(null)

const fetchProducts = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get(`/api/categories/${props.categoryId}/products`)
    console.log(response)
    products.value = response.data.products
    category.value = response.data.category
  } catch (err) {
    console.error('Ошибка загрузки товаров:', err)
    error.value = 'Не удалось загрузить товары. Попробуйте позже.'
  } finally {
    loading.value = false
  }
}

const handleAddToCart = (product) => {
  emit('add-to-cart', product)
}

onMounted(() => {
  fetchProducts()
})
</script>

<style scoped>
.gap-3 {
  gap: 12px;
}
</style>