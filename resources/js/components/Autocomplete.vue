<template>
  <div class="cdek-city-autocomplete">
    <v-text-field
      :model-value="inputDisplayValue"
      :label="label"
      :placeholder="placeholder"
      :prepend-inner-icon="icon"
      :loading="loading"
      :variant="variant"
      :rules="rules"
      :disabled="disabled"
      :clearable="clearable"
      @update:model-value="onInput"
      @focus="onFocus"
      @blur="onBlur"
      @click:clear="onClear"
      ref="inputRef"
    />
    
    <!-- Выпадающий список городов -->
    <div 
      v-if="showSuggestions && cities.length > 0" 
      class="cities-dropdown"
      @mouseleave="hideSuggestions"
    >
      <div
        v-for="(city, index) in cities"
        :key="city.city_uuid || city.code"
        class="city-item"
        @click="selectCity(city)"
        @mouseenter="currentIndex = index"
        :class="{ active: currentIndex === index }"
      >
        <div class="d-flex align-center">
          <v-icon size="20" class="mr-2" color="grey-darken-1">
            mdi-city
          </v-icon>
          <div class="flex-grow-1">
            <!-- Используем v-html, но данные приходят уже экранированными от Laravel -->
            <div class="city-name" v-html="getHighlightedName(city.full_name)"></div>
          </div>
          <v-icon v-if="city.country_code === 'RU'" size="16" color="green-darken-2">
            mdi-russia
          </v-icon>
        </div>
      </div>
    </div>

    <!-- Состояние "ничего не найдено" -->
    <div 
      v-else-if="showSuggestions && searchQuery && searchQuery.length >= minLength && !loading && cities.length === 0" 
      class="cities-dropdown empty"
    >
      <div class="empty-state pa-4 text-center">
        <v-icon size="32" color="grey-lighten-1" class="mb-2">mdi-city-off</v-icon>
        <div class="text-body-2 text-grey">
          Ничего не найдено<br>
          <span class="text-caption">Попробуйте изменить запрос</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed, onBeforeUnmount } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: Object,
    default: null
  },
  label: {
    type: String,
    default: 'Город'
  },
  placeholder: {
    type: String,
    default: 'Начните вводить название города'
  },
  icon: {
    type: String,
    default: 'mdi-map-marker'
  },
  variant: {
    type: String,
    default: 'outlined'
  },
  rules: {
    type: Array,
    default: () => []
  },
  disabled: {
    type: Boolean,
    default: false
  },
  clearable: {
    type: Boolean,
    default: true
  },
  minLength: {
    type: Number,
    default: 2
  },
  debounceTime: {
    type: Number,
    default: 500
  },
  apiUrl: {
    type: String,
    default: '/order/cities'
  }
})

const emit = defineEmits(['update:modelValue', 'select', 'clear'])

const inputRef = ref(null)
const loading = ref(false)
const cities = ref([])
const showSuggestions = ref(false)
const currentIndex = ref(-1)
const searchQuery = ref('')
let debounceTimer = null

const inputDisplayValue = computed(() => {
  if (props.modelValue?.full_name) {
    return props.modelValue.full_name
  }
  return searchQuery.value || ''
})

const onInput = (value) => {
  searchQuery.value = value || ''
  
  if (debounceTimer) clearTimeout(debounceTimer)
  
  if (!value || value.trim() === '') {
    cities.value = []
    showSuggestions.value = false
    
    if (props.modelValue !== null) {
      emit('update:modelValue', null)
      emit('clear')
    }
    return
  }
  
  if (value.length < props.minLength) {
    cities.value = []
    showSuggestions.value = false
    return
  }
  
  debounceTimer = setTimeout(async () => {
    await fetchCities(value)
  }, props.debounceTime)
}

const fetchCities = async (query) => {
  if (!query || query.length < props.minLength) return
  
  loading.value = true
  
  try {
    const response = await axios.get('/api/order/cities', {
      params: { city: query }
    })
    console.log(response)
    
    cities.value = response.data || []
    showSuggestions.value = cities.value.length > 0
    currentIndex.value = -1
    
  } catch (error) {
    console.error('Ошибка загрузки городов СДЭК:', error)
    cities.value = []
    showSuggestions.value = false
  } finally {
    loading.value = false
  }
}

const selectCity = (city) => {
  if (!city) return
  emit('update:modelValue', city)
  emit('select', city)
  
  searchQuery.value = city.full_name
  showSuggestions.value = false
  currentIndex.value = -1
}

const onClear = () => {
  searchQuery.value = ''
  cities.value = []
  showSuggestions.value = false
  emit('update:modelValue', null)
  emit('clear')
}

const onFocus = () => {
  if (cities.value.length > 0 && searchQuery.value && searchQuery.value.length >= props.minLength) {
    showSuggestions.value = true
  }
}

const onBlur = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const hideSuggestions = () => {
  showSuggestions.value = false
}

// Функция для подсветки безопасная для Laravel
const getHighlightedName = (text) => {
  if (!text) return ''
  if (!searchQuery.value || searchQuery.value.length < 2) return escapeHtml(text)
  
  try {
    const query = searchQuery.value.trim()
    const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    const regex = new RegExp(`(${escapedQuery})`, 'gi')
    
    // Сначала экранируем HTML, потом добавляем подсветку
    const safeText = escapeHtml(text)
    return safeText.replace(regex, '<mark class="highlight">$1</mark>')
  } catch (error) {
    console.error('Highlight error:', error)
    return escapeHtml(text)
  }
}

// Экранирование HTML для защиты от XSS
const escapeHtml = (str) => {
  if (!str) return ''
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

// Альтернативный вариант - если Laravel уже экранирует,
// то можно просто декодировать и подсвечивать
const decodeHtml = (html) => {
  if (!html) return ''
  const txt = document.createElement('textarea')
  txt.innerHTML = html
  return txt.value
}

const onKeyDown = (event) => {
  if (!showSuggestions.value) return
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      currentIndex.value = (currentIndex.value + 1) % cities.value.length
      scrollToActiveItem()
      break
    case 'ArrowUp':
      event.preventDefault()
      currentIndex.value = currentIndex.value <= 0 
        ? cities.value.length - 1 
        : currentIndex.value - 1
      scrollToActiveItem()
      break
    case 'Enter':
      event.preventDefault()
      if (currentIndex.value >= 0 && cities.value[currentIndex.value]) {
        selectCity(cities.value[currentIndex.value])
      }
      break
    case 'Escape':
      showSuggestions.value = false
      break
  }
}

const scrollToActiveItem = () => {
  const activeItem = document.querySelector('.city-item.active')
  if (activeItem) {
    activeItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  }
}

watch(() => props.modelValue, (newValue) => {
  if (newValue?.full_name) {
    searchQuery.value = newValue.full_name
  } else if (!newValue && searchQuery.value) {
    searchQuery.value = ''
    cities.value = []
  }
})

onMounted(() => {
  if (inputRef.value?.$el) {
    const input = inputRef.value.$el.querySelector('input')
    if (input) {
      input.addEventListener('keydown', onKeyDown)
    }
  }
})

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (inputRef.value?.$el) {
    const input = inputRef.value.$el.querySelector('input')
    if (input) {
      input.removeEventListener('keydown', onKeyDown)
    }
  }
})
</script>

<style scoped>
.cdek-city-autocomplete {
  position: relative;
  width: 100%;
}

.cities-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 320px;
  overflow-y: auto;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  margin-top: 4px;
}

.cities-dropdown.empty {
  max-height: 120px;
}

.city-item {
  padding: 12px 16px;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.city-item:last-child {
  border-bottom: none;
}

.city-item:hover,
.city-item.active {
  background-color: #f5f5f5;
}

.city-name {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 2px;
}

.city-name :deep(mark.highlight) {
  background-color: #fff3cd;
  font-weight: 600;
  padding: 0 2px;
  border-radius: 2px;
}

.city-code {
  font-size: 11px;
  color: #999;
}

.empty-state {
  color: #999;
}

.cities-dropdown::-webkit-scrollbar {
  width: 6px;
}

.cities-dropdown::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.cities-dropdown::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.cities-dropdown::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>