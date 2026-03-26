<template>
    <div>
        <v-row>
            <v-col cols="12" md="8" offset-md="2">
                <v-card>
                    <v-card-title class="text-h4 d-flex justify-space-between align-center">
                        Мой профиль
                        <v-btn 
                            v-if="!editing" 
                            color="green" 
                            variant="tonal"
                            @click="startEdit"
                            prepend-icon="mdi-pencil"
                        >
                            Редактировать
                        </v-btn>
                    </v-card-title>

                    <v-divider></v-divider>

                    <!-- Режим просмотра -->
                    <v-card-text v-if="!editing" class="pa-0">
                        <v-list lines="three" class="profile-list">
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-avatar color="green-lighten-4">
                                        <v-icon color="green-darken-2" icon="mdi-account"></v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-caption text-grey">Имя</v-list-item-title>
                                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                                    {{ authStore.user?.name || 'Не указано' }}
                                </v-list-item-subtitle>
                            </v-list-item>

                            <v-divider inset></v-divider>

                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-avatar color="green-lighten-4">
                                        <v-icon color="green-darken-2" icon="mdi-email"></v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-caption text-grey">Email</v-list-item-title>
                                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                                    {{ authStore.user?.email || 'Не указано' }}
                                </v-list-item-subtitle>
                            </v-list-item>

                            <v-divider inset></v-divider>

                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-avatar color="green-lighten-4">
                                        <v-icon color="green-darken-2" icon="mdi-phone"></v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-caption text-grey">Телефон</v-list-item-title>
                                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                                    {{ authStore.user?.phone || 'Не указан' }}
                                </v-list-item-subtitle>
                            </v-list-item>

                            <v-divider inset></v-divider>

                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-avatar color="green-lighten-4">
                                        <v-icon color="green-darken-2" icon="mdi-city"></v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-caption text-grey">Город</v-list-item-title>
                                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                                    {{ authStore.user?.city || 'Не указан' }}
                                </v-list-item-subtitle>
                            </v-list-item>

                            <v-divider inset></v-divider>

                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-avatar color="green-lighten-4">
                                        <v-icon color="green-darken-2" icon="mdi-calendar"></v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-caption text-grey">Дата регистрации</v-list-item-title>
                                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                                    {{ formatDate(authStore.user?.created_at) }}
                                </v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                    </v-card-text>

                    <!-- Режим редактирования -->
                    <v-card-text v-else>
                        <v-form ref="formRef" v-model="formValid">
                            <v-text-field
                                v-model="editForm.name"
                                label="Имя"
                                variant="outlined"
                                :rules="[v => !!v || 'Имя обязательно']"
                                prepend-inner-icon="mdi-account"
                            ></v-text-field>

                            <v-text-field
                                v-model="editForm.email"
                                label="Email"
                                type="email"
                                variant="outlined"
                                :rules="[
                                    v => !!v || 'Email обязателен',
                                    v => /.+@.+\..+/.test(v) || 'Введите корректный email'
                                ]"
                                prepend-inner-icon="mdi-email"
                            ></v-text-field>

                            <v-text-field
                                v-model="editForm.phone"
                                label="Телефон"
                                variant="outlined"
                                prepend-inner-icon="mdi-phone"
                                placeholder="+7 999 123-45-67"
                            ></v-text-field>

                            <v-text-field
                                v-model="editForm.city"
                                label="Город"
                                variant="outlined"
                                prepend-inner-icon="mdi-city"
                            ></v-text-field>

                            <v-divider class="my-4"></v-divider>

                            <div class="text-h6 mb-4">Смена пароля</div>

                            <v-text-field
                                v-model="editForm.password"
                                label="Новый пароль"
                                type="password"
                                variant="outlined"
                                prepend-inner-icon="mdi-lock"
                                hint="Оставьте пустым, если не хотите менять пароль"
                                persistent-hint
                            ></v-text-field>

                            <v-text-field
                                v-model="editForm.password_confirmation"
                                label="Подтверждение пароля"
                                type="password"
                                variant="outlined"
                                prepend-inner-icon="mdi-lock-check"
                                :rules="[
                                    v => !editForm.password || v === editForm.password || 'Пароли не совпадают'
                                ]"
                            ></v-text-field>
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions v-if="editing" class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn
                            variant="text"
                            @click="cancelEdit"
                            :disabled="saving"
                        >
                            Отмена
                        </v-btn>
                        <v-btn
                            color="green"
                            @click="saveProfile"
                            :loading="saving"
                            :disabled="!formValid || saving"
                        >
                            Сохранить
                        </v-btn>
                    </v-card-actions>
                </v-card>

                <!-- Сообщение об успехе/ошибке -->
                <v-snackbar
                    v-model="snackbar.show"
                    :timeout="3000"
                    :color="snackbar.color"
                    location="top"
                >
                    {{ snackbar.text }}
                    <template v-slot:actions>
                        <v-btn
                            variant="text"
                            icon="mdi-close"
                            @click="snackbar.show = false"
                        ></v-btn>
                    </template>
                </v-snackbar>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const editing = ref(false)
const saving = ref(false)
const formValid = ref(false)
const formRef = ref(null)

const editForm = reactive({
    name: '',
    email: '',
    phone: '',
    city: '',
    password: '',
    password_confirmation: ''
})

const snackbar = reactive({
    show: false,
    text: '',
    color: 'success'
})

const initForm = () => {
    if (authStore.user) {
        editForm.name = authStore.user.name || ''
        editForm.email = authStore.user.email || ''
        editForm.phone = authStore.user.phone || ''
        editForm.city = authStore.user.city || ''
    }
}

const startEdit = () => {
    initForm()
    editing.value = true
}

const cancelEdit = () => {
    editing.value = false
    editForm.password = ''
    editForm.password_confirmation = ''
}

const saveProfile = async () => {
    const { valid } = await formRef.value.validate()
    if (!valid) return

    saving.value = true

    const updateData = {}
    
    if (editForm.name !== authStore.user?.name) {
        updateData.name = editForm.name
    }
    
    if (editForm.email !== authStore.user?.email) {
        updateData.email = editForm.email
    }
    
    if (editForm.phone !== authStore.user?.phone) {
        updateData.phone = editForm.phone
    }
    
    if (editForm.city !== authStore.user?.city) {
        updateData.city = editForm.city
    }
    
    if (editForm.password) {
        updateData.password = editForm.password
    }

    if (Object.keys(updateData).length === 0) {
        snackbar.text = 'Нет изменений для сохранения'
        snackbar.color = 'info'
        snackbar.show = true
        saving.value = false
        editing.value = false
        return
    }

    try {
        const result = await authStore.updateUser(updateData)
        
        if (result.success) {
            snackbar.text = 'Профиль успешно обновлен!'
            snackbar.color = 'success'
            snackbar.show = true
            editing.value = false
            editForm.password = ''
            editForm.password_confirmation = ''
        } else {
            snackbar.text = result.error || 'Ошибка при обновлении профиля'
            snackbar.color = 'error'
            snackbar.show = true
        }
    } catch (error) {
        snackbar.text = 'Произошла ошибка при сохранении'
        snackbar.color = 'error'
        snackbar.show = true
    } finally {
        saving.value = false
    }
}

const formatDate = (date) => {
    if (!date) return 'Не указано'
    return new Date(date).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

onMounted(() => {
    if (!authStore.isAuthenticated) {
        router.push('/login')
        return
    }
    initForm()
})
</script>

<style scoped>
.profile-list {
    background: transparent;
}

.v-list-item {
    padding: 16px 24px;
}

.v-list-item :deep(.v-list-item__prepend) {
    margin-right: 20px;
}

.v-avatar {
    border-radius: 12px;
}
</style>