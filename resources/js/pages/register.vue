<template>
    <v-row justify="center" align="center" class="fill-height">
        <v-col cols="12" sm="10" md="8" lg="6" xl="4">
            <v-card class="pa-4" elevation="10">
                <v-card-title class="text-h4 text-center py-4">
                    Создать аккаунт
                </v-card-title>
                
                <v-card-text>
                    <v-form ref="formRef" v-model="valid">
                        <v-text-field
                            v-model="form.name"
                            label="ФИО"
                            variant="outlined"
                            :rules="[v => !!v || 'Введите ФИО']"
                            prepend-inner-icon="mdi-account"
                            class="mb-3"
                        ></v-text-field>
                        <v-text-field
                            v-model="form.phone"
                            label="Телефон"
                            variant="outlined"
                            :rules="phoneRules"
                            prepend-inner-icon="mdi-phone"
                            class="mb-3"
                            @input="formatPhone"
                            placeholder="+7 (___) ___-__-__"
                        ></v-text-field>
                        <v-text-field
                            v-model="form.email"
                            label="Email"
                            type="email"
                            variant="outlined"
                            :rules="emailRules"
                            prepend-inner-icon="mdi-email"
                            class="mb-3"
                        ></v-text-field>
                        <v-select
                            v-model="form.city"
                            :items="cities"
                            label="Город"
                            variant="outlined"
                            :rules="[v => !!v || 'Выберите город']"
                            prepend-inner-icon="mdi-city"
                            class="mb-3"
                        ></v-select>
                        <v-text-field
                            v-model="form.password"
                            label="Пароль"
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
                            Создать аккаунт
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
                            {{ successMessage }}
                        </v-alert>
                    </v-form>
                </v-card-text>
                <v-card-actions class="justify-center">
                    <span class="text-caption">Уже есть аккаунт?</span>
                    <router-link to="/login" class="text-decoration-none">
                        <v-btn variant="text" color="green" size="default">
                            Войти
                        </v-btn>
                    </router-link>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const form = ref({
    name: '',
    phone: '',
    email: '',
    city: null,
    password: '',
    passwordConfirmation: ''
})

const formRef = ref(null)
const valid = ref(false)
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

watch(form, () => {
    if (formRef.value) {
        formRef.value.validate()
    }
}, { deep: true })

const cities = [
    'Москва',
    'Санкт-Петербург',
    'Новосибирск',
    'Екатеринбург',
    'Казань',
    'Нижний Новгород',
    'Красноярск',
    'Челябинск',
    'Самара',
    'Уфа'
]

const formatPhone = (event) => {
    let value = event.target.value.replace(/\D/g, '')
    
    if (value.length > 11) value = value.slice(0, 11)
    
    let formatted = ''
    if (value.length > 0) {
        formatted = '+7'
        if (value.length > 1) {
            formatted += ' (' + value.slice(1, 4)
        }
        if (value.length >= 5) {
            formatted += ') ' + value.slice(4, 7)
        }
        if (value.length >= 8) {
            formatted += '-' + value.slice(7, 9)
        }
        if (value.length >= 10) {
            formatted += '-' + value.slice(9, 11)
        }
    }
    
    form.value.phone = formatted
}

const phoneRules = [
    v => !!v || 'Введите номер телефона',
    v => {
        const digits = v.replace(/\D/g, '')
        return digits.length === 11 || 'Введите полный номер телефона (11 цифр)'
    }
]

const emailRules = [
    v => !!v || 'Введите email',
    v => /.+@.+\..+/.test(v) || 'Введите корректный email'
]

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

const submitForm = async () => {
    if (!valid.value || !isPasswordStrongEnough.value) return
    
    loading.value = true
    errorMessage.value = ''
    successMessage.value = ''
    
    const cleanPhone = form.value.phone.replace(/\D/g, '')
    
    const requestData = {
        name: form.value.name,
        city: form.value.city,
        email: form.value.email,
        password: form.value.password,
        phone: cleanPhone
    }
    
    try {
        const response = await api.post('/register', requestData)
        
        console.log('Регистрация успешна:', response.data)
        
        successMessage.value = 'Регистрация успешна! Перенаправление на страницу входа...'
        
        setTimeout(() => {
            router.push('/login')
        }, 2000)
        
    } catch (error) {
        console.error('Ошибка при регистрации:', error)
        
        if (error.response) {
            const status = error.response.status
            const data = error.response.data
            
            if (status === 422 && data.errors) {
                const errorMessages = Object.values(data.errors).flat()
                errorMessage.value = errorMessages.join(', ')
            } else if (data.message) {
                errorMessage.value = data.message
            } else {
                errorMessage.value = 'Произошла ошибка при регистрации. Попробуйте позже.'
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