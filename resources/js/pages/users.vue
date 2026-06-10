<template>
    <div class="users-table-page">
        <DataTable title="Пользователи" :headers="headers" :items="users" :loading="loading" :show-add-button="true"
            search-key="name" @refresh="loadUsers" @add="addUser">
            
            <!-- Кастомная колонка с ролями (Laratrust) -->
            <template v-slot:item.roles="{ item }">
                <div class="d-flex flex-wrap gap-1">
                    <v-chip 
                        v-for="role in item.roles" 
                        :key="role.id"
                        :color="getRoleColor(role.name)" 
                        size="small" 
                        variant="light"
                    >
                        <v-icon size="small" start>mdi-account-badge</v-icon>
                        {{ role.display_name || role.name }}
                    </v-chip>
                    <span v-if="!item.roles || !item.roles.length" class="text-grey">—</span>
                </div>
            </template>

            <!-- Кастомная колонка с телефоном -->
            <template v-slot:item.phone="{ item }">
                <span v-if="item.phone">{{ formatPhone(item.phone) }}</span>
                <span v-else class="text-grey">—</span>
            </template>

            <!-- Кастомная колонка с email -->
            <template v-slot:item.email="{ item }">
                <a :href="`mailto:${item.email}`" class="text-decoration-none">
                    {{ item.email }}
                </a>
            </template>

            <!-- Кастомная колонка с датой создания -->
            <template v-slot:item.created_at="{ item }">
                {{ formatDate(item.created_at) }}
            </template>

            <!-- Кастомная колонка с датой обновления -->
            <template v-slot:item.updated_at="{ item }">
                {{ formatDate(item.updated_at) }}
            </template>

            <!-- Кастомные действия -->
            <template v-slot:item.actions="{ item }">
                <div class="d-flex gap-2">
                    <v-btn icon="mdi-eye" size="small" variant="text" color="info" @click="viewUser(item)"></v-btn>
                    
                    <!-- Кнопка редактирования - скрываем если это текущий пользователь -->
                    <v-btn 
                        v-if="item.id !== currentUser?.id" 
                        icon="mdi-pencil" 
                        size="small" 
                        variant="text" 
                        color="warning"
                        @click="editUser(item)">
                    </v-btn>
                    
                    <!-- Кнопка удаления - скрываем если это текущий пользователь -->
                    <v-btn 
                        v-if="item.id !== currentUser?.id" 
                        icon="mdi-delete" 
                        size="small" 
                        variant="text" 
                        color="error"
                        @click="deleteUser(item)">
                    </v-btn>
                    
                    <!-- Кнопка сброса пароля -->
                    <v-btn 
                        icon="mdi-lock-reset" 
                        size="small" 
                        variant="text" 
                        color="warning"
                        @click="resetUserPassword(item)">
                    </v-btn>
                </div>
            </template>
        </DataTable>

        <!-- Диалог просмотра пользователя -->
        <v-dialog v-model="dialog" max-width="700px">
            <v-card>
                <v-card-title class="text-h5 bg-blue-lighten-4">
                    <v-icon start>mdi-account</v-icon>
                    Информация о пользователе
                </v-card-title>
                
                <v-card-text class="mt-4">
                    <v-list>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-identifier"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">ID</v-list-item-title>
                            <v-list-item-subtitle class="text-h6">{{ selectedUser?.id }}</v-list-item-subtitle>
                        </v-list-item>

                        <v-divider></v-divider>

                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-account-circle"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Имя пользователя</v-list-item-title>
                            <v-list-item-subtitle class="text-h6">{{ selectedUser?.name }}</v-list-item-subtitle>
                        </v-list-item>

                        <v-divider></v-divider>

                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-email"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Email</v-list-item-title>
                            <v-list-item-subtitle>{{ selectedUser?.email }}</v-list-item-subtitle>
                        </v-list-item>

                        <v-divider></v-divider>

                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-phone"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Телефон</v-list-item-title>
                            <v-list-item-subtitle>
                                {{ formatPhone(selectedUser?.phone) || '—' }}
                            </v-list-item-subtitle>
                        </v-list-item>

                        <v-divider></v-divider>

                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-account-badge"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Роли</v-list-item-title>
                            <v-list-item-subtitle>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <v-chip 
                                        v-for="role in selectedUser?.roles" 
                                        :key="role.id"
                                        :color="getRoleColor(role.name)" 
                                        size="small"
                                    >
                                        <v-icon size="small" start>mdi-account-badge</v-icon>
                                        {{ role.display_name || role.name }}
                                    </v-chip>
                                    <span v-if="!selectedUser?.roles?.length" class="text-grey">Нет ролей</span>
                                </div>
                            </v-list-item-subtitle>
                        </v-list-item>

                        <v-divider></v-divider>

                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-calendar"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Дата создания</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDate(selectedUser?.created_at) }}</v-list-item-subtitle>
                        </v-list-item>

                        <v-divider></v-divider>

                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon icon="mdi-calendar-edit"></v-icon>
                            </template>
                            <v-list-item-title class="text-caption text-grey">Дата обновления</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDate(selectedUser?.updated_at) }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue" variant="text" @click="dialog = false">
                        Закрыть
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Диалог добавления/редактирования пользователя -->
        <v-dialog v-model="editDialog" max-width="800px">
            <v-card>
                <v-card-title class="text-h5 bg-blue-lighten-4">
                    <v-icon start>mdi-account</v-icon>
                    {{ editingUser ? 'Редактирование пользователя' : 'Добавление пользователя' }}
                </v-card-title>
                <v-card-text class="mt-4">
                    <v-form ref="formRef" v-model="formValid">
                        <v-text-field 
                            v-model="formData.name" 
                            label="Имя пользователя"
                            :rules="[v => !!v || 'Имя пользователя обязательно']" 
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-account"
                        ></v-text-field>

                        <v-text-field 
                            v-model="formData.email" 
                            label="Email"
                            type="email"
                            :rules="[
                                v => !!v || 'Email обязателен',
                                v => /.+@.+\..+/.test(v) || 'Введите корректный email'
                            ]" 
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-email"
                        ></v-text-field>

                        <v-text-field 
                            v-model="formData.phone" 
                            label="Телефон"
                            type="tel"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-phone"
                            placeholder="+7 (XXX) XXX-XX-XX"
                        ></v-text-field>

                        <!-- Выбор ролей (множественный) -->
                        <v-select 
                            v-model="formData.roles" 
                            :items="availableRoles"
                            item-title="display_name"
                            item-value="name"
                            label="Роли"
                            multiple
                            chips
                            :rules="[v => v && v.length > 0 || 'Выберите хотя бы одну роль']"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-account-badge"
                        >
                            <template v-slot:selection="{ item, index }">
                                <v-chip v-if="index < 2" :color="getRoleColor(item.raw.name)" size="small" class="mr-1">
                                    {{ item.raw.display_name }}
                                </v-chip>
                                <span v-if="index === 2" class="text-grey text-caption">
                                    +{{ formData.roles.length - 2 }}
                                </span>
                            </template>
                        </v-select>

                        <v-text-field 
                            v-if="!editingUser"
                            v-model="formData.password" 
                            label="Пароль"
                            type="password"
                            :rules="[v => !!v || 'Пароль обязателен', v => !v || v.length >= 6 || 'Минимум 6 символов']"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-lock"
                            hint="Минимум 6 символов"
                        ></v-text-field>

                        <v-text-field 
                            v-if="editingUser"
                            v-model="formData.password" 
                            label="Новый пароль (оставьте пустым, чтобы не менять)"
                            type="password"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-lock"
                            hint="Минимум 6 символов"
                        ></v-text-field>

                        <v-text-field 
                            v-if="editingUser && formData.password"
                            v-model="formData.password_confirmation" 
                            label="Подтверждение пароля"
                            type="password"
                            variant="outlined"
                            density="comfortable"
                            prepend-inner-icon="mdi-lock-check"
                            :rules="[v => v === formData.password || 'Пароли не совпадают']"
                        ></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="text" @click="closeEditDialog">
                        Отмена
                    </v-btn>
                    <v-btn color="blue" variant="flat" @click="saveUser" :loading="saving" :disabled="!formValid">
                        Сохранить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Диалог сброса пароля -->
        <v-dialog v-model="resetPasswordDialog" max-width="500px">
            <v-card>
                <v-card-title class="text-h5 bg-warning-lighten-4">
                    <v-icon start>mdi-lock-reset</v-icon>
                    Сброс пароля
                </v-card-title>
                <v-card-text class="mt-4">
                    <p>Вы уверены, что хотите сбросить пароль пользователя <strong>{{ selectedUser?.name }}</strong>?</p>
                    <v-text-field
                        v-model="newPassword"
                        label="Новый пароль"
                        type="password"
                        variant="outlined"
                        density="compact"
                        :rules="[v => !!v || 'Пароль обязателен', v => v.length >= 6 || 'Минимум 6 символов']"
                        class="mt-3"
                    ></v-text-field>
                    <v-text-field
                        v-model="newPasswordConfirmation"
                        label="Подтверждение пароля"
                        type="password"
                        variant="outlined"
                        density="compact"
                        :rules="[v => v === newPassword || 'Пароли не совпадают']"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" variant="text" @click="resetPasswordDialog = false">
                        Отмена
                    </v-btn>
                    <v-btn color="warning" variant="flat" @click="confirmResetPassword" :loading="resettingPassword">
                        Сбросить пароль
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Уведомления -->
        <v-snackbar v-model="snackbar.show" :timeout="3000" :color="snackbar.color" location="top">
            <v-icon start :color="snackbar.color === 'success' ? 'white' : ''">
                {{ snackbar.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
            </v-icon>
            {{ snackbar.text }}
            <template v-slot:actions>
                <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false"></v-btn>
            </template>
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/components/DataTable.vue'
import axios from 'axios'

// Состояние
const loading = ref(false)
const saving = ref(false)
const resettingPassword = ref(false)
const users = ref([])
const availableRoles = ref([])
const currentUser = ref(null)
const dialog = ref(false)
const editDialog = ref(false)
const resetPasswordDialog = ref(false)
const selectedUser = ref(null)
const editingUser = ref(null)
const formValid = ref(false)
const formRef = ref(null)
const newPassword = ref('')
const newPasswordConfirmation = ref('')

// Форма
const formData = ref({
    name: '',
    email: '',
    phone: '',
    roles: [],
    password: '',
    password_confirmation: ''
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
    { title: 'Имя', key: 'name', sortable: true },
    { title: 'Email', key: 'email', sortable: true },
    { title: 'Телефон', key: 'phone', sortable: true, width: '160px' },
    { title: 'Роли', key: 'roles', sortable: false, width: '200px' },
    { title: 'Дата создания', key: 'created_at', sortable: true, width: '180px' },
    { title: 'Дата обновления', key: 'updated_at', sortable: true, width: '180px' },
    { title: 'Действия', key: 'actions', sortable: false, width: '160px', align: 'center' }
]

// Получение цвета для роли
const getRoleColor = (roleName) => {
    const colors = {
        'admin': 'red',
        'moderator': 'orange',
        'user': 'blue'
    }
    return colors[roleName] || 'grey'
}

// Форматирование телефона
const formatPhone = (phone) => {
    if (!phone) return ''
    const cleaned = phone.replace(/\D/g, '')
    
    if (cleaned.length === 11 && (cleaned.startsWith('7') || cleaned.startsWith('8'))) {
        const number = cleaned.startsWith('8') ? '7' + cleaned.slice(1) : cleaned
        return `+${number[0]} (${number.slice(1, 4)}) ${number.slice(4, 7)}-${number.slice(7, 9)}-${number.slice(9, 11)}`
    }
    
    return phone
}

// Форматирование даты
const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    })
}

// Получение текущего пользователя
const getCurrentUser = async () => {
    try {
        const response = await axios.get('/api/user')
        currentUser.value = response.data
    } catch (error) {
        console.error('Ошибка получения текущего пользователя:', error)
    }
}

// Загрузка ролей
const loadRoles = async () => {
    try {
        const response = await axios.get('/api/users/roles')
        availableRoles.value = response.data.roles || []
    } catch (error) {
        console.error('Ошибка загрузки ролей:', error)
    }
}

// Загрузка пользователей
const loadUsers = async () => {
    loading.value = true

    try {
        const response = await axios.post('/api/users/get-datatable', {
            draw: 1,
            start: 0,
            length: 100,
            search: { value: '' }
        })

        users.value = response.data.data || []

    } catch (error) {
        console.error('Ошибка загрузки пользователей:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка загрузки данных',
            color: 'error'
        }
    } finally {
        loading.value = false
    }
}

// Добавление пользователя
const addUser = () => {
    editingUser.value = null
    formData.value = {
        name: '',
        email: '',
        phone: '',
        roles: [],
        password: '',
        password_confirmation: ''
    }
    editDialog.value = true
}

// Редактирование пользователя
const editUser = (user) => {
    // Проверка на клиенте
    if (user.id === currentUser.value?.id) {
        snackbar.value = {
            show: true,
            text: 'Нельзя редактировать самого себя',
            color: 'warning'
        }
        return
    }
    
    editingUser.value = user
    formData.value = {
        name: user.name,
        email: user.email,
        phone: user.phone || '',
        roles: user.roles?.map(r => r.name) || [],
        password: '',
        password_confirmation: ''
    }
    editDialog.value = true
}

// Сохранение пользователя
const saveUser = async () => {
    if (!formValid.value) return

    if (!editingUser.value && !formData.value.password) {
        snackbar.value = {
            show: true,
            text: 'Пароль обязателен для заполнения',
            color: 'warning'
        }
        return
    }

    if (formData.value.password !== formData.value.password_confirmation) {
        snackbar.value = {
            show: true,
            text: 'Пароли не совпадают',
            color: 'warning'
        }
        return
    }

    saving.value = true

    try {
        const payload = {
            name: formData.value.name,
            email: formData.value.email,
            phone: formData.value.phone || null,
            roles: formData.value.roles
        }

        if (formData.value.password) {
            payload.password = formData.value.password
        }

        let response
        if (editingUser.value) {
            response = await axios.put(`/api/users/${editingUser.value.id}`, payload)
            snackbar.value = {
                show: true,
                text: response.data.message || 'Пользователь успешно обновлен',
                color: 'success'
            }
        } else {
            response = await axios.post('/api/users', payload)
            snackbar.value = {
                show: true,
                text: response.data.message || 'Пользователь успешно создан',
                color: 'success'
            }
        }

        closeEditDialog()
        await loadUsers()

    } catch (error) {
        console.error('Ошибка сохранения:', error)
        
        // Проверка на ошибку "нельзя редактировать себя"
        if (error.response?.status === 403) {
            snackbar.value = {
                show: true,
                text: error.response.data?.message || 'Нельзя редактировать самого себя',
                color: 'error'
            }
            closeEditDialog()
            return
        }
        
        let errorMessage = 'Ошибка сохранения'
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors
            errorMessage = Object.values(errors).flat().join(', ')
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        }
        
        snackbar.value = {
            show: true,
            text: errorMessage,
            color: 'error'
        }
    } finally {
        saving.value = false
    }
}

// Сброс пароля
const resetUserPassword = (user) => {
    selectedUser.value = user
    newPassword.value = ''
    newPasswordConfirmation.value = ''
    resetPasswordDialog.value = true
}

// Подтверждение сброса пароля
const confirmResetPassword = async () => {
    if (!newPassword.value || newPassword.value.length < 6) {
        snackbar.value = {
            show: true,
            text: 'Пароль должен содержать минимум 6 символов',
            color: 'warning'
        }
        return
    }
    
    if (newPassword.value !== newPasswordConfirmation.value) {
        snackbar.value = {
            show: true,
            text: 'Пароли не совпадают',
            color: 'warning'
        }
        return
    }
    
    resettingPassword.value = true
    
    try {
        await axios.post(`/api/users/${selectedUser.value.id}/reset-password`, {
            password: newPassword.value
        })
        
        snackbar.value = {
            show: true,
            text: `Пароль для пользователя "${selectedUser.value.name}" успешно сброшен`,
            color: 'success'
        }
        
        resetPasswordDialog.value = false
        
    } catch (error) {
        console.error('Ошибка сброса пароля:', error)
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка при сбросе пароля',
            color: 'error'
        }
    } finally {
        resettingPassword.value = false
    }
}

// Закрытие диалога редактирования
const closeEditDialog = () => {
    editDialog.value = false
    editingUser.value = null
    formData.value = {
        name: '',
        email: '',
        phone: '',
        roles: [],
        password: '',
        password_confirmation: ''
    }
}

// Удаление пользователя
const deleteUser = async (user) => {
    // Проверка на клиенте перед подтверждением
    if (user.id === currentUser.value?.id) {
        snackbar.value = {
            show: true,
            text: 'Нельзя удалить самого себя',
            color: 'warning'
        }
        return
    }
    
    if (!confirm(`Вы уверены, что хотите удалить пользователя "${user.name}"?`)) return

    try {
        await axios.delete(`/api/users/${user.id}`)

        snackbar.value = {
            show: true,
            text: `Пользователь "${user.name}" удален`,
            color: 'error'
        }

        await loadUsers()

    } catch (error) {
        console.error('Ошибка удаления:', error)
        
        // Проверка на ошибку "нельзя удалить себя"
        if (error.response?.status === 403) {
            snackbar.value = {
                show: true,
                text: error.response.data?.message || 'Нельзя удалить самого себя',
                color: 'error'
            }
            return
        }
        
        snackbar.value = {
            show: true,
            text: error.response?.data?.message || 'Ошибка при удалении',
            color: 'error'
        }
    }
}

// Просмотр пользователя
const viewUser = (user) => {
    selectedUser.value = user
    dialog.value = true
}

// Загружаем данные при монтировании
onMounted(() => {
    getCurrentUser()
    loadRoles()
    loadUsers()
})
</script>

<style scoped>
.users-table-page {
    background: #f5f5f5;
    min-height: 100vh;
    padding: 20px;
    margin: 0;
}

.gap-2 {
    gap: 8px;
}

.gap-1 {
    gap: 4px;
}

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

@media (max-width: 768px) {
    .users-table-page {
        padding: 10px;
    }
}
</style>