<template>
    <v-row justify="center" align="center" class="fill-height">
        <v-col cols="12" sm="10" md="8" lg="6" xl="4">
            <v-card class="pa-4" elevation="10">
                <v-card-title class="text-h4 text-center py-4">
                    Вход в аккаунт
                </v-card-title>
                
                <v-card-text>
                    <v-form ref="formRef" v-model="valid">
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
                        <v-checkbox
                            v-model="form.remember"
                            label="Запомнить меня"
                            color="green"
                            class="mb-4"
                        ></v-checkbox>
                        
                        <!-- Отображение ошибки из стора -->
                        <v-alert
                            v-if="loginError"
                            type="error"
                            variant="tonal"
                            class="mb-4"
                            closable
                            @click:close="loginError = ''"
                        >
                            {{ loginError }}
                        </v-alert>
                        
                        <v-btn
                            color="green"
                            size="large"
                            block
                            :disabled="!valid || authStore.isLoading"
                            @click="submitForm"
                            :loading="authStore.isLoading"
                        >
                            Войти
                        </v-btn>
                        <div class="text-center mt-4">
                            <router-link to="/forgot-password" class="text-decoration-none">
                                <span class="text-caption text-green">Забыли пароль?</span>
                            </router-link>
                        </div>
                    </v-form>
                </v-card-text>
                <v-card-actions class="justify-center">
                    <span class="text-caption">Нет аккаунта?</span>
                    <router-link to="/register" class="text-decoration-none">
                        <v-btn variant="text" color="green" size="default">
                            Зарегистрироваться
                        </v-btn>
                    </router-link>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
    email: '',
    password: '',
    remember: false
})

const formRef = ref(null)
const valid = ref(false)
const showPassword = ref(false)
const loginError = ref('') // Локальная ошибка

const emailRules = [
    v => !!v || 'Введите email',
    v => /.+@.+\..+/.test(v) || 'Введите корректный email'
]

const passwordRules = [
    v => !!v || 'Введите пароль',
    v => v.length >= 6 || 'Пароль должен содержать минимум 6 символов'
]

const submitForm = async () => {
    if (!valid.value) return
    
    // Сбрасываем предыдущую ошибку
    loginError.value = ''
    
    const result = await authStore.login({
        email: form.value.email,
        password: form.value.password
    })
    
    if (result.success) {
        // Успешный вход - редирект
        router.push('/')
    } else {
        // Показываем ошибку
        loginError.value = result.error
    }
}
</script>

<style scoped>
.fill-height {
    min-height: calc(100vh - 64px);
}
</style>