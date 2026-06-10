<template>
    <v-row justify="center" align="center" class="fill-height">
        <v-col cols="12" sm="10" md="8" lg="6" xl="4">
            <v-card class="pa-4" elevation="10">
                <v-card-title class="text-h4 text-center py-4">
                    Восстановление пароля
                </v-card-title>
                
                <v-card-text>
                    <div class="text-center mb-4 text-medium-emphasis">
                        Введите email, указанный при регистрации, и мы отправим ссылку для восстановления пароля
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
                            :disabled="loading"
                        ></v-text-field>
                        
                        <v-btn
                            color="green"
                            size="large"
                            block
                            :disabled="!valid || loading"
                            :loading="loading"
                            @click="submitForm"
                        >
                            Отправить ссылку
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
                                <span class="text-caption">Проверьте почту и перейдите по ссылке для восстановления пароля.</span>
                            </div>
                        </v-alert>
                    </v-form>
                </v-card-text>
                
                <v-card-actions class="justify-center">
                    <span class="text-caption">Вспомнили пароль?</span>
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
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const email = ref('')
const formRef = ref(null)
const valid = ref(false)
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Валидация email
const emailRules = [
    v => !!v || 'Введите email',
    v => /.+@.+\..+/.test(v) || 'Введите корректный email'
]

// Автовалидация при изменении email
watch(email, () => {
    if (formRef.value) {
        formRef.value.validate()
    }
})

// Отправка формы
const submitForm = async () => {
    if (!valid.value) return
    
    loading.value = true
    errorMessage.value = ''
    successMessage.value = ''
    
    try {
        const response = await api.post('/forgot-password', {
            email: email.value
        })
        
        console.log('Ссылка отправлена:', response.data)
        
        if (response.data.success) {
            successMessage.value = response.data.message || 'Ссылка для восстановления пароля отправлена на вашу почту!'
            email.value = '' // Очищаем поле
            formRef.value?.resetValidation() // Сбрасываем валидацию
        } else {
            errorMessage.value = response.data.message || 'Произошла ошибка'
        }
        
    } catch (error) {
        console.error('Ошибка при отправке ссылки:', error)
        
        if (error.response) {
            const status = error.response.status
            const data = error.response.data
            
            // Обработка ошибок валидации
            if (status === 422 && data.errors) {
                const errorMessages = Object.values(data.errors).flat()
                errorMessage.value = errorMessages.join(', ')
            } 
            // Ошибка 429 (слишком много попыток)
            else if (status === 429 && data.errors) {
                errorMessage.value = data.errors.email?.[0] || 'Слишком много попыток. Попробуйте позже.'
            }
            // Ошибка 500 (серверная ошибка)
            else if (status === 500) {
                errorMessage.value = 'Ошибка сервера. Попробуйте позже.'
            }
            // Другие ошибки
            else if (data.message) {
                errorMessage.value = data.message
            } else {
                errorMessage.value = 'Произошла ошибка при отправке ссылки. Попробуйте позже.'
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
</style>