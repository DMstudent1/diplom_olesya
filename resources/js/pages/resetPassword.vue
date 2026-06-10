<template>
    <v-row justify="center" align="center" class="fill-height">
        <v-col cols="12" sm="10" md="8" lg="6" xl="4">
            <v-card class="pa-4" elevation="10">
                <v-card-title class="text-h4 text-center py-4">
                    Новый пароль
                </v-card-title>
                
                <v-card-text>
                    <div class="text-center mb-4 text-medium-emphasis">
                        Установите новый пароль для вашей учётной записи
                    </div>
                    
                    <v-form ref="formRef" v-model="valid">
                        <v-text-field
                            v-model="email"
                            label="Email"
                            type="email"
                            variant="outlined"
                            :rules="emailRules"
                            prepend-inner-icon="mdi-email"
                            class="mb-3"
                            disabled
                        ></v-text-field>
                        
                        <v-text-field
                            v-model="form.password"
                            label="Новый пароль"
                            :type="showPassword ? 'text' : 'password'"
                            variant="outlined"
                            :rules="passwordRules"
                            prepend-inner-icon="mdi-lock"
                            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                            @click:append-inner="showPassword = !showPassword"
                            class="mb-2"
                        ></v-text-field>
                        
                        <v-progress-linear
                            :model-value="passwordStrength"
                            :color="strengthColor"
                            height="8"
                            class="mb-2"
                        ></v-progress-linear>
                        
                        <div class="d-flex justify-space-between mb-4">
                            <span class="text-caption">Сложность пароля:</span>
                            <span class="text-caption font-weight-bold" :style="{ color: strengthColor }">
                                {{ strengthText }}
                            </span>
                        </div>
                        
                        <v-text-field
                            v-model="form.passwordConfirmation"
                            label="Подтверждение пароля"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            variant="outlined"
                            :rules="passwordConfirmationRules"
                            prepend-inner-icon="mdi-lock-check"
                            :append-inner-icon="showPasswordConfirmation ? 'mdi-eye-off' : 'mdi-eye'"
                            @click:append-inner="showPasswordConfirmation = !showPasswordConfirmation"
                            class="mb-4"
                        ></v-text-field>
                        
                        <v-btn
                            color="green"
                            size="large"
                            block
                            :disabled="!valid || !isPasswordStrongEnough || loading"
                            :loading="loading"
                            @click="submitForm"
                        >
                            Установить новый пароль
                        </v-btn>
                        
                        <v-alert
                            v-if="errorMessage"
                            type="error"
                            variant="tonal"
                            class="mt-4"
                            closable
                            @click:close="errorMessage = ''"
                        >
                            {{ errorMessage }}
                        </v-alert>
                        
                        <v-alert
                            v-if="successMessage"
                            type="success"
                            variant="tonal"
                            class="mt-4"
                            closable
                            @click:close="successMessage = ''"
                        >
                            <div>
                                <strong>✓ {{ successMessage }}</strong>
                                <br>
                                <span class="text-caption">Перенаправление на страницу входа...</span>
                            </div>
                        </v-alert>
                    </v-form>
                </v-card-text>
                
                <v-card-actions class="justify-center">
                    <router-link to="/login" class="text-decoration-none">
                        <v-btn variant="text" color="green" size="default">
                            Вернуться ко входу
                        </v-btn>
                    </router-link>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const form = ref({
    password: '',
    passwordConfirmation: ''
})

const email = ref('')
const token = ref('')
const formRef = ref(null)
const valid = ref(false)
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Получаем параметры из URL
onMounted(() => {
    // Получаем из query параметров (рекомендуемый способ)
    token.value = route.query.token
    email.value = route.query.email || ''
    
    // Если нет в query, пробуем из params (альтернативный способ)
    if (!token.value && route.params.token) {
        token.value = route.params.token
    }
    
    console.log('Token:', token.value)
    console.log('Email:', email.value)
    
    if (!token.value || !email.value) {
        errorMessage.value = 'Неверная ссылка для восстановления пароля. Пожалуйста, запросите сброс пароля заново.'
    }
})

// Валидация email
const emailRules = [
    v => !!v || 'Email обязателен',
    v => /.+@.+\..+/.test(v) || 'Введите корректный email'
]

// Проверка сложности пароля
const checkPasswordStrength = (password) => {
    let strength = 0
    
    if (!password) return 0
    
    if (password.length >= 8) strength++
    if (password.length >= 12) strength++
    if (/[A-ZА-Я]/.test(password)) strength++
    if (/[a-zа-я]/.test(password)) strength++
    if (/[0-9]/.test(password)) strength++
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++
    
    return Math.min(5, strength)
}

const passwordStrength = computed(() => {
    if (!form.value.password) return 0
    return (checkPasswordStrength(form.value.password) / 5) * 100
})

const strengthColor = computed(() => {
    const strength = passwordStrength.value
    if (strength < 30) return 'red'
    if (strength < 60) return 'orange'
    if (strength < 80) return 'yellow-darken-2'
    return 'green'
})

const strengthText = computed(() => {
    const strength = passwordStrength.value
    if (strength < 30) return 'Слабый'
    if (strength < 60) return 'Средний'
    if (strength < 80) return 'Хороший'
    return 'Сложный'
})

const isPasswordStrongEnough = computed(() => {
    return passwordStrength.value >= 30
})

const passwordRules = [
    v => !!v || 'Введите пароль',
    v => v.length >= 8 || 'Пароль должен содержать минимум 8 символов'
]

const passwordConfirmationRules = [
    v => !!v || 'Подтвердите пароль',
    v => v === form.value.password || 'Пароли не совпадают'
]

// Автовалидация
watch(form, () => {
    if (formRef.value) {
        formRef.value.validate()
    }
}, { deep: true })

// Отправка формы
const submitForm = async () => {
    if (!valid.value || !isPasswordStrongEnough.value) return
    
    loading.value = true
    errorMessage.value = ''
    successMessage.value = ''
    
    try {
        const response = await api.post('reset-password', {
            token: token.value,
            email: email.value,
            password: form.value.password,
            password_confirmation: form.value.passwordConfirmation
        })
        
        console.log('Пароль изменён:', response.data)
        
        if (response.data.success) {
            successMessage.value = response.data.message || 'Пароль успешно изменён!'
            
            // Перенаправляем на страницу входа через 2 секунды
            setTimeout(() => {
                router.push('/login')
            }, 2000)
        } else {
            errorMessage.value = response.data.message || 'Произошла ошибка'
        }
        
    } catch (error) {
        console.error('Ошибка при сбросе пароля:', error)
        
        if (error.response) {
            const status = error.response.status
            const data = error.response.data
            
            // Обработка ошибок валидации
            if (status === 422 && data.errors) {
                const errorMessages = Object.values(data.errors).flat()
                errorMessage.value = errorMessages.join(', ')
            } 
            // Ошибка 400 (неверный токен)
            else if (status === 400) {
                errorMessage.value = 'Неверный или просроченный токен. Пожалуйста, запросите сброс пароля заново.'
            }
            // Ошибка 500 (серверная ошибка)
            else if (status === 500) {
                errorMessage.value = 'Ошибка сервера. Попробуйте позже.'
            }
            // Другие ошибки
            else if (data.message) {
                errorMessage.value = data.message
            } else {
                errorMessage.value = 'Произошла ошибка при сбросе пароля. Попробуйте позже.'
            }
        } else if (error.request) {
            errorMessage.value = 'Ошибка сети. Проверьте подключение к интернету.'
        } else {
            errorMessage.value = 'Произошла ошибка. Попробуйте позже.'
        }
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.fill-height {
    min-height: calc(100vh - 64px);
}

.v-progress-linear {
    transition: all 0.3s ease;
}
</style>