<template>
    <v-card class="data-table-card" elevation="2" rounded="lg">
        <!-- Заголовок -->
        <v-card-title class="d-flex align-center justify-space-between flex-wrap gap-3 pa-4">
            <div class="d-flex align-center gap-3">
                <v-icon icon="mdi-table" size="28" color="green"></v-icon>
                <span class="text-h5 font-weight-medium">{{ title }}</span>
            </div>
            
            <div class="d-flex align-center gap-2">
                <!-- Кнопка Добавить -->
                <v-btn
                    v-if="showAddButton"
                    color="green"
                    variant="flat"
                    prepend-icon="mdi-plus"
                    @click="handleAdd"
                    :disabled="loading"
                >
                    Добавить
                </v-btn>
                
                <!-- Поиск -->
                <v-text-field
                    v-model="search"
                    density="compact"
                    variant="outlined"
                    placeholder="Поиск..."
                    prepend-inner-icon="mdi-magnify"
                    hide-details
                    clearable
                    class="search-field"
                    style="max-width: 250px"
                ></v-text-field>
                
                <!-- Обновление -->
                <v-btn
                    icon="mdi-refresh"
                    variant="text"
                    color="grey"
                    @click="refresh"
                    :loading="loading"
                ></v-btn>
            </div>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <!-- Таблица -->
        <v-data-table
            :headers="headers"
            :items="filteredItems"
            :loading="loading"
            :items-per-page="itemsPerPage"
            :items-per-page-options="itemsPerPageOptions"
            hover
            class="data-table"
            :items-per-page-text="'Записей на странице'"
        >
            <!-- Слот для кастомного отображения ячеек -->
            <template v-for="(_, slot) in $slots" v-slot:[slot]="scope">
                <slot :name="slot" v-bind="scope" />
            </template>
            
            <!-- Статус загрузки -->
            <template v-slot:loading>
                <v-skeleton-loader type="table-row@10"></v-skeleton-loader>
            </template>
            
            <!-- Нет данных -->
            <template v-slot:no-data>
                <div class="text-center pa-8">
                    <v-icon icon="mdi-database-off" size="48" color="grey-lighten-1"></v-icon>
                    <p class="text-grey mt-2">Нет данных для отображения</p>
                    <v-btn
                        v-if="showAddButton"
                        color="green"
                        variant="tonal"
                        class="mt-3"
                        prepend-icon="mdi-plus"
                        @click="handleAdd"
                    >
                        Добавить первую запись
                    </v-btn>
                </div>
            </template>
        </v-data-table>
    </v-card>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    title: {
        type: String,
        default: 'Таблица данных'
    },
    headers: {
        type: Array,
        required: true
    },
    items: {
        type: Array,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    },
    searchKey: {
        type: String,
        default: 'name'
    },
    showAddButton: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['refresh', 'add'])

const search = ref('')
const itemsPerPage = ref(10)
const itemsPerPageOptions = ref([
    { title: '10', value: 10 },
    { title: '25', value: 25 },
    { title: '50', value: 50 },
    { title: '100', value: 100 }
])

const totalItems = computed(() => props.items.length)

const filteredItems = computed(() => {
    if (!search.value) return props.items
    
    const searchLower = search.value.toLowerCase()
    return props.items.filter(item => {
        return Object.values(item).some(value => 
            String(value).toLowerCase().includes(searchLower)
        )
    })
})

const refresh = () => {
    emit('refresh')
}

const handleAdd = () => {
    emit('add')
}
</script>

<style scoped>
.data-table-card {
    background: white;
}

.data-table :deep(.v-data-table__th) {
    background: #f5f5f5;
    font-weight: 600;
    font-size: 14px;
}

.data-table :deep(.v-data-table__tr:hover) {
    background: #f8f9fa;
    cursor: pointer;
}

.search-field :deep(.v-field) {
    border-radius: 8px;
}

.gap-2 {
    gap: 8px;
}

.gap-3 {
    gap: 12px;
}

/* Стили для русского текста пагинации */
.data-table :deep(.v-pagination__list) {
    gap: 4px;
}

.data-table :deep(.v-data-table-footer__items-per-page) {
    font-size: 14px;
}

.data-table :deep(.v-data-table-footer__info) {
    font-size: 14px;
}
</style>