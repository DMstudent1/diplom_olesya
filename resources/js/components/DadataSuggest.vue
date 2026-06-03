<template>
  <div class="dadata-suggest">
    <v-text-field
      :model-value="modelValue"
      :label="label"
      :placeholder="placeholder"
      :prepend-inner-icon="icon"
      :loading="loading"
      :variant="variant"
      :rules="rules"
      @update:model-value="onInput"
      @focus="onFocus"
      @blur="onBlur"
      ref="inputRef"
    />
    
    <!-- Выпадающий список подсказок -->
    <div 
      v-if="showSuggestions && suggestions.length > 0" 
      class="suggestions-dropdown"
      @mouseleave="hideSuggestions"
    >
      <div
        v-for="(suggestion, index) in suggestions"
        :key="index"
        class="suggestion-item"
        @click="selectSuggestion(suggestion)"
        @mouseenter="currentIndex = index"
        :class="{ active: currentIndex === index }"
      >
        <div class="d-flex align-center">
          <v-icon size="20" class="mr-2" color="grey-darken-1">
            {{ getIconForType(suggestion) }}
          </v-icon>
          <div>
            <div class="suggestion-value" v-html="highlightMatch(suggestion.value)"></div>
            <div v-if="suggestion.data?.city" class="suggestion-detail">
              {{ suggestion.data.city }}
            </div>
            <div v-if="suggestion.data?.postal_code" class="suggestion-detail">
              Индекс: {{ suggestion.data.postal_code }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: String,
  label: String,
  placeholder: String,
  icon: String,
  variant: String,
  rules: Array,
  type: {
    type: String,
    default: 'address' // address, party, fio
  },
  minLength: {
    type: Number,
    default: 2
  },
  debounceTime: {
    type: Number,
    default: 300
  }
})

const emit = defineEmits(['update:modelValue', 'select'])

const inputRef = ref(null)
const loading = ref(false)
const suggestions = ref([])
const showSuggestions = ref(false)
const currentIndex = ref(-1)
let debounceTimer = null
let searchQuery = ''

const onInput = (value) => {
  searchQuery = value
  emit('update:modelValue', value)
  
  if (debounceTimer) clearTimeout(debounceTimer)
  
  if (value.length < props.minLength) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  
  debounceTimer = setTimeout(async () => {
    await fetchSuggestions(value)
  }, props.debounceTime)
}

const fetchSuggestions = async (query) => {
  if (!query) return
  
  loading.value = true
  
  try {
    const response = await axios.post('/api/dadata/suggest', {
      query: query,
      type: props.type,
      count: 10
    })
    
    suggestions.value = response.data.suggestions || []
    showSuggestions.value = suggestions.value.length > 0
    currentIndex.value = -1
  } catch (error) {
    console.error('Ошибка DaData:', error)
    suggestions.value = []
  } finally {
    loading.value = false
  }
}

const selectSuggestion = (suggestion) => {
  emit('update:modelValue', suggestion.value)
  emit('select', suggestion)
  showSuggestions.value = false
  currentIndex.value = -1
  
  // Опционально: сохраняем дополнительные данные
  if (props.type === 'address' && suggestion.data) {
    // Можно сохранить детали адреса в store
    console.log('Выбран адрес:', suggestion.data)
  }
}

const onFocus = () => {
  if (suggestions.value.length > 0 && searchQuery.length >= props.minLength) {
    showSuggestions.value = true
  }
}

const onBlur = () => {
  // Даем время на клик по подсказке
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const hideSuggestions = () => {
  showSuggestions.value = false
}

const getIconForType = (suggestion) => {
  if (props.type === 'address') return 'mdi-map-marker'
  if (props.type === 'party') return 'mdi-domain'
  if (props.type === 'fio') return 'mdi-account'
  return 'mdi-magnify'
}

const highlightMatch = (text) => {
  if (!searchQuery || searchQuery.length < 2) return text
  
  const regex = new RegExp(`(${searchQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return text.replace(regex, '<mark class="highlight">$1</mark>')
}

// Обработка клавиатуры
const onKeyDown = (event) => {
  if (!showSuggestions.value) return
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      currentIndex.value = (currentIndex.value + 1) % suggestions.value.length
      break
    case 'ArrowUp':
      event.preventDefault()
      currentIndex.value = currentIndex.value <= 0 
        ? suggestions.value.length - 1 
        : currentIndex.value - 1
      break
    case 'Enter':
      event.preventDefault()
      if (currentIndex.value >= 0 && suggestions.value[currentIndex.value]) {
        selectSuggestion(suggestions.value[currentIndex.value])
      }
      break
    case 'Escape':
      showSuggestions.value = false
      break
  }
}

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
.dadata-suggest {
  position: relative;
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 300px;
  overflow-y: auto;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  margin-top: 4px;
}

.suggestion-item {
  padding: 12px 16px;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.suggestion-item:hover,
.suggestion-item.active {
  background-color: #f5f5f5;
}

.suggestion-value {
  font-size: 14px;
  color: #333;
}

.suggestion-detail {
  font-size: 12px;
  color: #666;
  margin-top: 2px;
}

:deep(.highlight) {
  background-color: #fff3cd;
  font-weight: bold;
  padding: 0;
}
</style>