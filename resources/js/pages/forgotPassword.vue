<template>
    <v-row justify="center" align="center" class="fill-height">
        <v-col cols="12" sm="10" md="8" lg="6" xl="4">
            <v-card class="pa-4" elevation="10">
                <v-card-title class="text-h4 text-center py-4">
                    Восстановление пароля
                </v-card-title>
                
                <v-card-text>
                    <v-form ref="formRef" v-model="valid">
                        <!-- Email -->
                        <v-text-field
                            v-model="form.email"
                            label="Email"
                            type="email"
                            variant="outlined"
                            :rules="emailRules"
                            prepend-inner-icon="mdi-email"
                            class="mb-3"
                            placeholder="example@mail.com"
                        ></v-text-field>
                        
                        <!-- Кнопка отправки -->
                        <v-btn
                            color="green"
                            size="large"
                            block
                            :disabled="!valid || loading"
                            @click="submitForm"
                            :loading="loading"
                        >
                            Отправить инструкцию
                        </v-btn>
                        
                        <!-- Сообщение об успешной отправке -->
                        <v-alert
                            v-if="success"
                            type="success"
                            variant="tonal"
                            class="mt-4"
                        >
                            Инструкция по восстановлению пароля отправлена на ваш email
                        </v-alert>
                        
                        <!-- Сообщение об ошибке -->
                        <v-alert
                            v-if="error"
                            type="error"
                            variant="tonal"
                            class="mt-4"
                        >
                            {{ errorMessage }}
                        </v-alert>
                    </v-form>
                </v-card-text>
                
                <!-- Вернуться ко входу -->
                <v-card-actions class="justify-center">
                    <router-link to="/login" class="text-decoration-none">
                        <v-btn variant="text" color="green" size="default" prepend-icon="mdi-arrow-left">
                            Вернуться ко входу
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

const router = useRouter()

// Данные формы
const form = ref({
    email: ''
})

const formRef = ref(null)
const valid = ref(false)
const loading = ref(false)
const success = ref(false)
const error = ref(false)
const errorMessage = ref('')

// Валидация в реальном времени
watch(form, () => {
    if (formRef.value) {
        formRef.value.validate()
    }
}, { deep: true })

// Правила валидации для email
const emailRules = [
    v => !!v || 'Введите email',
    v => /.+@.+\..+/.test(v) || 'Введите корректный email'
]

// Отправка формы
const submitForm = async () => {
    if (!valid.value) return
    
    loading.value = true
    success.value = false
    error.value = false
    
    try {
        // Подготовка данных
        const resetData = {
            email: form.value.email
        }
        
        console.log('Отправка запроса на восстановление:', resetData)
        
        // Здесь будет реальный запрос к API
        // const response = await api.post('/forgot-password', resetData)
        
        // Имитация задержки
        await new Promise(resolve => setTimeout(resolve, 1500))
        
        // Успешная отправка
        success.value = true
        form.value.email = '' // Очищаем поле
        
        // Автоматически скрываем сообщение через 5 секунд
        setTimeout(() => {
            success.value = false
        }, 5000)
        
    } catch (err) {
        console.error('Ошибка восстановления:', err)
        error.value = true
        errorMessage.value = err.response?.data?.message || 'Ошибка отправки. Попробуйте позже.'
        
        // Автоматически скрываем сообщение об ошибке через 5 секунд
        setTimeout(() => {
            error.value = false
        }, 5000)
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