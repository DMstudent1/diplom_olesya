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
                                        <v-icon color="green-darken-2" icon="mdi-map-marker"></v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-caption text-grey">Адрес</v-list-item-title>
                                <v-list-item-subtitle class="text-body-1 font-weight-medium">
                                    {{ authStore.user?.address || 'Не указан' }}
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

                        <!-- Блок Способы оплаты -->
                        <v-divider></v-divider>
                        
                        <div class="payment-methods-section">
                            <div class="d-flex justify-space-between align-center pa-4">
                                <v-card-title class="text-h1">Способы оплаты</v-card-title>
                                <v-btn
                                    color="green"
                                    variant="tonal"
                                    prepend-icon="mdi-plus"
                                    @click="openAddPaymentDialog"
                                >
                                    Добавить карту
                                </v-btn>
                            </div>
                            
                            <v-list class="payment-methods-list">
                                <v-list-item
                                    v-for="(card, index) in paymentMethods"
                                    :key="index"
                                >
                                    <template v-slot:prepend>
                                        <div 
                                            class="card-preview-icon"
                                            :style="{ backgroundColor: getBankColor(card.bankCode) }"
                                        >
                                            <v-icon color="white" size="24">
                                                {{ getCardTypeIcon(card.cardType) }}
                                            </v-icon>
                                        </div>
                                    </template>
                                    
                                    <v-list-item-title>
                                        {{ card.bankName || 'Банк не определен' }}
                                    </v-list-item-title>
                                    
                                    <v-list-item-subtitle>
                                        {{ maskCardNumber(card.number) }}
                                        <v-chip size="x-small" class="ml-2">
                                            {{ card.cardTypeName || 'Карта' }}
                                        </v-chip>
                                    </v-list-item-subtitle>
                                    
                                    <template v-slot:append>
                                        <v-btn
                                            icon="mdi-delete"
                                            variant="text"
                                            color="red"
                                            size="small"
                                            @click="deletePaymentMethod(index)"
                                        />
                                    </template>
                                </v-list-item>
                                
                                <v-list-item v-if="paymentMethods.length === 0">
                                    <v-list-item-title class="text-center text-grey">
                                        Нет добавленных способов оплаты
                                    </v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </div>
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
                            />

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
                            />

                            <v-text-field
                                v-model="editForm.phone"
                                label="Телефон"
                                variant="outlined"
                                prepend-inner-icon="mdi-phone"
                                placeholder="+7 999 123-45-67"
                            />

                            <DadataSuggest
                                v-model="editForm.address"
                                label="Адрес"
                                placeholder="Введите адрес (город, улицу, дом)..."
                                icon="mdi-map-marker"
                                type="address"
                                variant="outlined"
                                @select="onAddressSelect"
                            />

                            <v-divider class="my-4" />

                            <div class="text-h6 mb-4">Смена пароля</div>

                            <v-text-field
                                v-model="editForm.password"
                                label="Новый пароль"
                                type="password"
                                variant="outlined"
                                prepend-inner-icon="mdi-lock"
                                hint="Оставьте пустым, если не хотите менять пароль"
                                persistent-hint
                            />

                            <v-text-field
                                v-model="editForm.password_confirmation"
                                label="Подтверждение пароля"
                                type="password"
                                variant="outlined"
                                prepend-inner-icon="mdi-lock-check"
                                :rules="[
                                    v => !editForm.password || v === editForm.password || 'Пароли не совпадают'
                                ]"
                            />
                        </v-form>
                    </v-card-text>

                    <v-divider />

                    <v-card-actions v-if="editing" class="pa-4">
                        <v-spacer />
                        <v-btn variant="text" @click="cancelEdit" :disabled="saving">
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

                <!-- Диалог добавления карты -->
                <v-dialog v-model="showPaymentDialog" max-width="550">
                    <v-card>
                        <v-card-title class="text-h5">Добавить банковскую карту</v-card-title>
                        <v-card-text>
                            <!-- Превью карты -->
                            <div 
                                class="card-preview mb-4"
                                :style="{ background: cardPreview.backgroundGradient }"
                                v-if="cardPreview.show"
                            >
                                <div class="card-preview-content">
                                    <div class="card-brand">
                                        <div class="payment-system-icon">
                                            {{ getPaymentSystemIcon(cardPreview.paymentSystem) }}
                                        </div>
                                    </div>
                                    <div class="card-number">
                                        {{ cardPreview.formattedNumber || '●●●● ●●●● ●●●● ●●●●' }}
                                    </div>
                                    <div class="card-footer">
                                        <div class="card-holder">
                                            <div class="label">Card Holder</div>
                                            <div class="value">
                                                {{ newCard.holderName.toUpperCase() || 'YOUR NAME' }}
                                            </div>
                                        </div>
                                        <div class="card-expiry">
                                            <div class="label">Expires</div>
                                            <div class="value">
                                                {{ newCard.expiry || 'MM/YY' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-bank">
                                        <span>{{ cardPreview.bankName || '' }}</span>
                                        <span class="card-type-badge" v-if="cardPreview.cardType">
                                            {{ cardPreview.cardType }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <v-form ref="paymentFormRef" v-model="paymentFormValid">
                                <v-text-field
                                    v-model="newCard.number"
                                    label="Номер карты"
                                    variant="outlined"
                                    placeholder="0000 0000 0000 0000"
                                    prepend-inner-icon="mdi-credit-card"
                                    :rules="cardNumberRules"
                                    @update:model-value="onCardNumberChange"
                                />
                                
                                <v-row>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model="newCard.expiry"
                                            label="Срок действия"
                                            variant="outlined"
                                            placeholder="MM/YY"
                                            prepend-inner-icon="mdi-calendar"
                                            :rules="expiryRules"
                                            @update:model-value="formatExpiry"
                                        />
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model="newCard.cvv"
                                            label="CVV"
                                            variant="outlined"
                                            placeholder="123"
                                            type="password"
                                            prepend-inner-icon="mdi-lock"
                                            :rules="cvvRules"
                                            maxlength="4"
                                        />
                                    </v-col>
                                </v-row>
                                
                                <v-text-field
                                    v-model="newCard.holderName"
                                    label="Держатель карты"
                                    variant="outlined"
                                    placeholder="IVAN IVANOV"
                                    prepend-inner-icon="mdi-account"
                                    :rules="[v => !!v || 'Введите имя держателя']"
                                    @update:model-value="updateCardPreview"
                                />
                            </v-form>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer />
                            <v-btn variant="text" @click="closePaymentDialog">Отмена</v-btn>
                            <v-btn
                                color="green"
                                :disabled="!paymentFormValid"
                                @click="addPaymentMethod"
                            >
                                Добавить
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- Snackbar для уведомлений -->
                <v-snackbar
                    v-model="snackbar.show"
                    :timeout="3000"
                    :color="snackbar.color"
                    location="top"
                >
                    {{ snackbar.text }}
                    <template v-slot:actions>
                        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false" />
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
import DadataSuggest from '@/components/DadataSuggest.vue'
import { searchCardBin, validateCardInfo } from 'bankcard'

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
    address: '',
    password: '',
    password_confirmation: ''
})

// Способы оплаты
const paymentMethods = ref([])
const showPaymentDialog = ref(false)
const paymentFormValid = ref(false)
const paymentFormRef = ref(null)

const newCard = reactive({
    number: '',
    expiry: '',
    cvv: '',
    holderName: '',
    bankName: '',
    bankCode: '',
    cardType: '',
    cardTypeName: '',
    paymentSystem: ''
})

// Превью карты
const cardPreview = reactive({
    show: false,
    bankName: '',
    bankCode: '',
    cardType: '',
    paymentSystem: '',
    backgroundGradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    formattedNumber: ''
})

const snackbar = reactive({
    show: false,
    text: '',
    color: 'success'
})

// Цвета банков
const getBankColor = (bankCode) => {
    const colors = {
        'SBER': '#2D6742',
        'TINKOFF': '#FFDD2D',
        'ALFA': '#EF3124',
        'VTB': '#0054AE',
        'GAZPROM': '#1B64B9',
        'RAIFF': '#FF6B00',
        'RSHB': '#8CC63F',
        'OTKR': '#ED6F21',
        'MK': '#005A9C',
        'SOVCOM': '#F26522',
        'POST': '#E21F2E',
        'HOME': '#F58220',
        'RUSSTAND': '#1E88E5',
        'CITI': '#007A5E'
    }
    return colors[bankCode] || '#6B7280'
}

// Иконка типа карты
const getCardTypeIcon = (cardType) => {
    if (cardType === 'DC') return 'mdi-credit-card-outline'
    if (cardType === 'CC') return 'mdi-credit-card'
    return 'mdi-credit-card-multiple'
}

// Иконка платежной системы
const getPaymentSystemIcon = (paymentSystem) => {
    const icons = {
        'VISA': '💳',
        'MASTERCARD': '💳',
        'MIR': '🌍',
        'UNIONPAY': '🎴',
        'JCB': '🌀',
        'AMEX': '💎'
    }
    return icons[paymentSystem] || '💳'
}

// Обновление превью карты с использованием bankcard
const updateCardPreview = () => {
    const cleanNumber = newCard.number.replace(/\s/g, '')
    
    if (cleanNumber.length >= 6) {
        const cardInfo = searchCardBin(cleanNumber)
        
        if (cardInfo) {
            cardPreview.show = true
            cardPreview.bankName = cardInfo.bankName || 'Банк'
            cardPreview.bankCode = cardInfo.bankCode
            cardPreview.cardType = cardInfo.cardType === 'DC' ? 'Дебетовая' : 'Кредитная'
            cardPreview.paymentSystem = cardInfo.paySystem || 'MIR'
            
            // Устанавливаем фон в зависимости от банка
            const bgColor = getBankColor(cardInfo.bankCode)
            cardPreview.backgroundGradient = `linear-gradient(135deg, ${bgColor} 0%, ${bgColor}dd 100%)`
            
            // Обновляем данные в newCard
            newCard.bankName = cardInfo.bankName
            newCard.bankCode = cardInfo.bankCode
            newCard.cardType = cardInfo.cardType
            newCard.cardTypeName = cardInfo.cardType === 'DC' ? 'Дебетовая' : 'Кредитная'
            newCard.paymentSystem = cardInfo.paySystem
            
        } else {
            // Если банк не найден, показываем заглушку
            cardPreview.show = true
            cardPreview.bankName = 'Банк не определен'
            cardPreview.bankCode = 'UNKNOWN'
            cardPreview.cardType = 'Карта'
            cardPreview.backgroundGradient = 'linear-gradient(135deg, #6B7280 0%, #4B5563 100%)'
        }
        
        // Форматируем номер с маскированием
        let formatted = ''
        for (let i = 0; i < cleanNumber.length; i++) {
            if (i > 0 && i % 4 === 0) formatted += ' '
            if (i < 4 || i >= cleanNumber.length - 4) {
                formatted += cleanNumber[i]
            } else {
                formatted += '●'
            }
        }
        // Добавляем заглушки для недостающих цифр
        while (formatted.replace(/\s/g, '').length < 16) {
            if (formatted.length > 0 && formatted.length % 5 === 4) {
                formatted += ' '
            }
            formatted += '●'
        }
        cardPreview.formattedNumber = formatted
        
    } else if (cleanNumber.length > 0) {
        // Частичный ввод
        cardPreview.show = true
        cardPreview.backgroundGradient = 'linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%)'
        cardPreview.bankName = 'Введите номер полностью'
        cardPreview.bankCode = ''
        
        let formatted = ''
        for (let i = 0; i < cleanNumber.length; i++) {
            if (i > 0 && i % 4 === 0) formatted += ' '
            formatted += cleanNumber[i]
        }
        cardPreview.formattedNumber = formatted
    } else {
        cardPreview.show = false
    }
}

// Правила валидации
const cardNumberRules = [
    v => !!v || 'Введите номер карты',
    v => {
        const clean = v?.replace(/\s/g, '') || ''
        if (clean.length !== 16) return 'Введите 16 цифр'
        
        // Валидация через Luhn алгоритм
        const validation = validateCardInfo(clean)
        if (!validation.validated) {
            return validation.message || 'Неверный номер карты'
        }
        return true
    }
]

const expiryRules = [
    v => !!v || 'Введите срок действия',
    v => /^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(v) || 'Неверный формат (MM/YY)'
]

const cvvRules = [
    v => !!v || 'Введите CVV',
    v => /^[0-9]{3,4}$/.test(v) || 'Введите 3 или 4 цифры'
]

const formatCardNumber = (value) => {
    let clean = value.replace(/\s/g, '').replace(/\D/g, '')
    clean = clean.slice(0, 16)
    
    let formatted = ''
    for (let i = 0; i < clean.length; i++) {
        if (i > 0 && i % 4 === 0) formatted += ' '
        formatted += clean[i]
    }
    
    newCard.number = formatted
    updateCardPreview()
}

const onCardNumberChange = (value) => {
    formatCardNumber(value)
}

const formatExpiry = (value) => {
    let clean = value.replace(/\D/g, '')
    clean = clean.slice(0, 4)
    
    if (clean.length >= 3) {
        clean = clean.slice(0, 2) + '/' + clean.slice(2)
    }
    
    newCard.expiry = clean
    updateCardPreview()
}

const maskCardNumber = (number) => {
    const clean = number.replace(/\s/g, '')
    if (clean.length === 16) {
        return `**** **** **** ${clean.slice(-4)}`
    }
    return number
}

const openAddPaymentDialog = () => {
    newCard.number = ''
    newCard.expiry = ''
    newCard.cvv = ''
    newCard.holderName = ''
    newCard.bankName = ''
    newCard.bankCode = ''
    newCard.cardType = ''
    newCard.cardTypeName = ''
    newCard.paymentSystem = ''
    
    cardPreview.show = false
    cardPreview.bankName = ''
    cardPreview.bankCode = ''
    cardPreview.cardType = ''
    cardPreview.backgroundGradient = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    cardPreview.formattedNumber = ''
    
    showPaymentDialog.value = true
}

const closePaymentDialog = () => {
    showPaymentDialog.value = false
}

const addPaymentMethod = async () => {
    if (!paymentFormRef.value) return
    
    const { valid } = await paymentFormRef.value.validate()
    if (!valid) return
    
    const cleanNumber = newCard.number.replace(/\s/g, '')
    const cardInfo = searchCardBin(cleanNumber)
    
    paymentMethods.value.push({
        number: newCard.number,
        expiry: newCard.expiry,
        cvv: newCard.cvv,
        holderName: newCard.holderName,
        bankName: cardInfo?.bankName || newCard.bankName || 'Банк не определен',
        bankCode: cardInfo?.bankCode || newCard.bankCode,
        cardType: cardInfo?.cardType,
        cardTypeName: cardInfo?.cardType === 'DC' ? 'Дебетовая' : 'Кредитная'
    })
    
    showPaymentDialog.value = false
    
    snackbar.text = 'Способ оплаты добавлен'
    snackbar.color = 'success'
    snackbar.show = true
}

const deletePaymentMethod = (index) => {
    paymentMethods.value.splice(index, 1)
    snackbar.text = 'Способ оплаты удален'
    snackbar.color = 'info'
    snackbar.show = true
}

const loadPaymentMethods = async () => {
    // TODO: Загрузить из API
    paymentMethods.value = []
}

const initForm = () => {
    if (authStore.user) {
        editForm.name = authStore.user.name || ''
        editForm.email = authStore.user.email || ''
        editForm.phone = authStore.user.phone || ''
        editForm.address = authStore.user.address || ''
    }
}

const onAddressSelect = (suggestion) => {
    if (suggestion?.data) {
        console.log('Выбран адрес:', suggestion.value)
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
    if (!formRef.value) return
    
    const { valid } = await formRef.value.validate()
    if (!valid) return

    saving.value = true

    const updateData = {}
    
    if (editForm.name !== authStore.user?.name) updateData.name = editForm.name
    if (editForm.email !== authStore.user?.email) updateData.email = editForm.email
    if (editForm.phone !== authStore.user?.phone) updateData.phone = editForm.phone
    if (editForm.address !== authStore.user?.address) updateData.address = editForm.address
    if (editForm.password) updateData.password = editForm.password

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
        console.error('Save error:', error)
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
    loadPaymentMethods()
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

.payment-methods-section {
    margin-top: 8px;
}

.payment-methods-list {
    background: transparent;
}

/* Стили для превью карты */
.card-preview {
    border-radius: 20px;
    padding: 24px;
    min-height: 220px;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.card-preview-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-preview-content {
    position: relative;
    z-index: 2;
}

.card-brand {
    text-align: right;
    margin-bottom: 30px;
}

.payment-system-icon {
    font-size: 32px;
}

.card-number {
    font-size: 20px;
    font-weight: 600;
    letter-spacing: 2px;
    margin-bottom: 24px;
    font-family: 'Courier New', monospace;
    color: white;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.label {
    font-size: 10px;
    opacity: 0.7;
    text-transform: uppercase;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
    color: rgba(255,255,255,0.7);
}

.value {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
}

.card-bank {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 500;
    color: rgba(255,255,255,0.9);
}

.card-type-badge {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
}
</style>