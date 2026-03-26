<template>
    <div class="test-table-page">
        <DataTable
            title="Категории"
            :headers="headers"
            :items="categories"
            :loading="loading"
            :show-add-button="true"
            search-key="name"
            @refresh="loadCategories"
            @add="addCategory"
        >
            <!-- Кастомная колонка с родителем -->
            <template v-slot:item.parent_name="{ item }">
                <span :class="{ 'text-grey': !item.parent_name }">
                    {{ item.parent_name || '—' }}
                </span>
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
                        @click="viewCategory(item)"
                    ></v-btn>
                    <v-btn
                        icon="mdi-pencil"
                        size="small"
                        variant="text"
                        color="warning"
                        @click="editCategory(item)"
                    ></v-btn>
                    <v-btn
                        icon="mdi-delete"
                        size="small"
                        variant="text"
                        color="error"
                        @click="deleteCategory(item)"
                    ></v-btn>
                </div>
            </template>
        </DataTable>
        
        <!-- Диалог просмотра категории -->
        <v-dialog v-model="dialog" max-width="500px">
            <v-card>
                <v-card-title class="text-h5 bg-green-lighten-4">
                    Информация о категории
                </v-card-title>
                <v-card-text class="mt-4">
                    <v-list>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-tag"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">ID</v-list-item-title>
                            <v-list-item-subtitle class="text-h6">{{ selectedCategory?.id }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-format-title"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Название</v-list-item-title>
                            <v-list-item-subtitle class="text-h6">{{ selectedCategory?.name }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-link-variant"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Slug</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedCategory?.slug }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-folder"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Родительская категория</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedCategory?.parent_name || 'Корневая категория' }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider v-if="selectedCategory?.description"></v-divider>
                        
                        <v-list-item v-if="selectedCategory?.description">
                            <template v-slot:prepend>
                                <v-icon icon="mdi-text"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Описание</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedCategory?.description }}</v-list-item-subtitle>
                        </v-list-item>
                        
                        <v-divider></v-divider>
                        
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-calendar"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Дата создания</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDate(selectedCategory?.created_at) }}</v-list-item-subtitle>
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
        
        <!-- Диалог добавления/редактирования категории с автогенерацией slug -->
        <v-dialog v-model="editDialog" max-width="600px">
            <v-card>
                <v-card-title class="text-h5 bg-green-lighten-4">
                    {{ editingCategory ? 'Редактирование категории' : 'Добавление категории' }}
                </v-card-title>
                <v-card-text class="mt-4">
                    <v-form ref="formRef" v-model="formValid">
                        <v-text-field
                            v-model="formData.name"
                            label="Название"
                            :rules="[v => !!v || 'Название обязательно']"
                            variant="outlined"
                            density="comfortable"
                            @input="autoGenerateSlug"
                        ></v-text-field>
                        
                        <v-text-field
                            v-model="formData.slug"
                            label="Slug"
                            :rules="slugRules"
                            variant="outlined"
                            density="comfortable"
                            @input="onManualSlugEdit"
                        >
                            <template v-slot:append-inner>
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props }">
                                        <v-icon 
                                            v-bind="props" 
                                            @click="generateSlugFromName"
                                            class="cursor-pointer"
                                            color="primary"
                                        >
                                            mdi-auto-fix
                                        </v-icon>
                                    </template>
                                    <span>Сгенерировать из названия</span>
                                </v-tooltip>
                            </template>
                        </v-text-field>
                        
                        <v-select
                            v-model="formData.parent_id"
                            :items="parentCategories"
                            item-title="name"
                            item-value="id"
                            label="Родительская категория"
                            variant="outlined"
                            density="comfortable"
                            clearable
                        ></v-select>
                        
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
                        @click="saveCategory"
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
import { ref, onMounted, computed } from 'vue'
import DataTable from '@/components/DataTable.vue'
import axios from 'axios'

// Состояние
const loading = ref(false)
const saving = ref(false)
const categories = ref([])
const dialog = ref(false)
const editDialog = ref(false)
const selectedCategory = ref(null)
const editingCategory = ref(null)
const formValid = ref(false)
const formRef = ref(null)

// Флаги для автогенерации slug
const autoGenerateEnabled = ref(true)
const lastGeneratedName = ref('')
const isManualEdit = ref(false)

// Форма
const formData = ref({
    name: '',
    slug: '',
    parent_id: null,
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
    { title: 'Родитель', key: 'parent_name', sortable: true },
    { title: 'Дата создания', key: 'created_at', sortable: true },
    { title: 'Дата изменения', key: 'updated_at', sortable: true },
    { title: 'Действия', key: 'actions', sortable: false, width: '120px', align: 'center' }
]

// Список категорий для выбора родителя
const parentCategories = computed(() => {
    return categories.value.filter(c => c.id !== editingCategory.value?.id)
})

// Правила валидации для slug
const slugRules = computed(() => {
    return [
        v => !!v || 'Slug обязателен',
        v => /^[a-z0-9-]+$/.test(v) || 'Slug может содержать только латинские буквы, цифры и дефисы',
        v => !checkSlugExists(v) || 'Такой slug уже существует'
    ]
})

// Проверка существования slug
const checkSlugExists = (slug) => {
    if (!slug) return false
    
    return categories.value.some(cat => 
        cat.slug === slug && cat.id !== editingCategory.value?.id
    )
}

// Генерация slug из текста с транслитерацией
const generateSlug = (text) => {
    if (!text) return ''
    
    // Транслитерация для кириллицы
    const translitMap = {
        'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e',
        'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm',
        'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
        'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '',
        'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
        'А': 'a', 'Б': 'b', 'В': 'v', 'Г': 'g', 'Д': 'd', 'Е': 'e', 'Ё': 'e',
        'Ж': 'zh', 'З': 'z', 'И': 'i', 'Й': 'y', 'К': 'k', 'Л': 'l', 'М': 'm',
        'Н': 'n', 'О': 'o', 'П': 'p', 'Р': 'r', 'С': 's', 'Т': 't', 'У': 'u',
        'Ф': 'f', 'Х': 'h', 'Ц': 'ts', 'Ч': 'ch', 'Ш': 'sh', 'Щ': 'sch', 'Ъ': '',
        'Ы': 'y', 'Ь': '', 'Э': 'e', 'Ю': 'yu', 'Я': 'ya'
    }
    
    let result = text
    
    // Транслитерация кириллицы
    result = result.replace(/[а-яёА-ЯЁ]/g, char => translitMap[char] || char)
    
    // Приводим к нижнему регистру
    result = result.toLowerCase()
    
    // Удаляем все, кроме букв, цифр, пробелов и дефисов
    result = result.replace(/[^\w\s-]/g, '')
    
    // Заменяем пробелы и подчеркивания на дефисы
    result = result.replace(/[\s_]+/g, '-')
    
    // Убираем множественные дефисы
    result = result.replace(/-+/g, '-')
    
    // Удаляем дефисы в начале и конце
    result = result.replace(/^-+|-+$/g, '')
    
    return result
}

// Автоматическая генерация slug при вводе названия
const autoGenerateSlug = () => {
    if (autoGenerateEnabled.value && !isManualEdit.value) {
        const generatedSlug = generateSlug(formData.value.name)
        
        // Если slug пустой или совпадает с предыдущей сгенерированной версией
        if (!formData.value.slug || formData.value.slug === generateSlug(lastGeneratedName.value)) {
            formData.value.slug = generatedSlug
            lastGeneratedName.value = formData.value.name
        }
    }
}

// Обработка ручного редактирования slug
const onManualSlugEdit = () => {
    isManualEdit.value = true
    autoGenerateEnabled.value = false
}

// Ручная генерация slug из названия
const generateSlugFromName = () => {
    const generatedSlug = generateSlug(formData.value.name)
    formData.value.slug = generatedSlug
    isManualEdit.value = true
    autoGenerateEnabled.value = false
    lastGeneratedName.value = formData.value.name
    
    // Показываем уведомление о генерации
    snackbar.value = {
        show: true,
        text: 'Slug сгенерирован из названия',
        color: 'info'
    }
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

// Загрузка категорий
const loadCategories = async () => {
    loading.value = true
    
    try {
        const response = await axios.post('/api/category/get-datatable', {
            draw: 1,
            start: 0,
            length: 100,
            search: { value: '' }
        })
        
        categories.value = response.data.data || []
        
    } catch (error) {
        console.error('Ошибка загрузки категорий:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка загрузки данных',
            color: 'error'
        }
    } finally {
        loading.value = false
    }
}

// Добавление категории
const addCategory = () => {
    editingCategory.value = null
    formData.value = {
        name: '',
        slug: '',
        parent_id: null,
        description: ''
    }
    resetSlugFlags()
    editDialog.value = true
}

// Редактирование категории
const editCategory = (category) => {
    editingCategory.value = category
    formData.value = {
        name: category.name,
        slug: category.slug,
        parent_id: category.parent_id,
        description: category.description || ''
    }
    
    // Если категория уже имеет slug, отключаем автогенерацию
    if (category.slug) {
        autoGenerateEnabled.value = false
        isManualEdit.value = true
    } else {
        resetSlugFlags()
    }
    
    editDialog.value = true
}

// Сброс флагов автогенерации
const resetSlugFlags = () => {
    autoGenerateEnabled.value = true
    isManualEdit.value = false
    lastGeneratedName.value = ''
}

// Сохранение категории
const saveCategory = async () => {
    if (!formValid.value) return
    
    // Дополнительная проверка уникальности slug
    if (checkSlugExists(formData.value.slug)) {
        snackbar.value = {
            show: true,
            text: 'Категория с таким slug уже существует',
            color: 'error'
        }
        return
    }
    
    saving.value = true
    
    try {
        if (editingCategory.value) {
            // Обновление
            await axios.put(`/api/category/${editingCategory.value.id}`, formData.value)
            snackbar.value = {
                show: true,
                text: 'Категория успешно обновлена',
                color: 'success'
            }
        } else {
            // Создание
            await axios.post('/api/category', formData.value)
            snackbar.value = {
                show: true,
                text: 'Категория успешно создана',
                color: 'success'
            }
        }
        
        closeEditDialog()
        await loadCategories()
        
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
    editingCategory.value = null
    formData.value = {
        name: '',
        slug: '',
        parent_id: null,
        description: ''
    }
    resetSlugFlags()
}

// Удаление категории
const deleteCategory = async (category) => {
    if (!confirm(`Вы уверены, что хотите удалить категорию "${category.name}"?`)) return
    
    try {
        await axios.delete(`/api/category/${category.id}`)
        
        snackbar.value = {
            show: true,
            text: `Категория "${category.name}" удалена`,
            color: 'error'
        }
        
        await loadCategories()
        
    } catch (error) {
        console.error('Ошибка удаления:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка при удалении',
            color: 'error'
        }
    }
}

// Просмотр категории
const viewCategory = (category) => {
    selectedCategory.value = category
    dialog.value = true
}

// Загружаем данные при монтировании
onMounted(() => {
    loadCategories()
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

.cursor-pointer {
    cursor: pointer;
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