<template>
  <div class="smart-assistant">
    <!-- Плавающая кнопка-триггер -->
    <div 
      class="assistant-trigger"
      :class="{ 'active': isOpen }"
      @click="toggleAssistant"
    >
      <div class="trigger-icon">
        <v-icon size="24" color="white">
          {{ isOpen ? 'mdi-close' : 'mdi-chat-processing' }}
        </v-icon>
      </div>
      <div class="trigger-pulse"></div>
    </div>

    <!-- Выезжающая панель справа -->
    <transition name="slide-right">
      <div v-if="isOpen" class="assistant-panel">
        <v-card class="assistant-card">
          <!-- Заголовок -->
          <v-card-title class="pa-3 primary white--text">
            <span class="title font-weight-light">ИИ-консультант</span>
          </v-card-title>

          <!-- Область сообщений -->
          <v-card-text class="pa-0">
            <div class="messages-container pa-3">
              <!-- Сообщение если чат пуст -->
              <div v-if="messages.length === 0" class="empty-chat">
                <v-icon size="48" color="grey lighten-2">mdi-robot-outline</v-icon>
                <p class="grey--text mt-2">Начните диалог с помощником</p>
              </div>

              <!-- Сообщения -->
              <div
                v-for="(message, index) in messages"
                :key="index"
                class="message-wrapper"
                :class="message.type"
              >
                <div class="message-bubble">
                  <div class="message-header">
                    <v-icon small class="mr-1">
                      {{ message.type === 'user' ? 'mdi-account-circle' : 'mdi-robot' }}
                    </v-icon>
                    <span class="caption font-weight-bold">
                      {{ message.type === 'user' ? 'Вы' : 'Помощник' }}
                    </span>
                    <span class="caption grey--text ml-2">{{ message.time }}</span>
                  </div>
                  <div class="message-text">
                    {{ message.text }}
                  </div>
                </div>
              </div>

              <!-- Индикатор печати -->
              <div v-if="isTyping" class="message-wrapper assistant">
                <div class="message-bubble">
                  <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span class="ml-2 caption grey--text">Помощник печатает...</span>
                  </div>
                </div>
              </div>
            </div>
          </v-card-text>

          <!-- Поле ввода -->
          <div class="input-area pa-3">
            <v-text-field
              v-model="userInput"
              label="Напишите сообщение..."
              outlined
              dense
              hide-details
              clearable
              background-color="white"
              class="message-input"
              @keyup.enter="sendMessage"
              :disabled="isTyping"
            >
              <template v-slot:append>
                <v-btn
                  icon
                  small
                  color="primary"
                  @click="sendMessage"
                  :disabled="!userInput.trim() || isTyping"
                  class="d-flex justify-center align-center"
                  style="display: flex !important;"
                >
                  <v-icon size="30">mdi-send</v-icon>
                </v-btn>
              </template>
            </v-text-field>
          </div>
        </v-card>
      </div>
    </transition>

    <!-- Оверлей для закрытия по клику вне -->
    <transition name="fade">
      <div v-if="isOpen" class="assistant-overlay" @click="closeAssistant"></div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'SmartAssistant',
  data() {
    return {
      isOpen: false,
      userInput: '',
      isTyping: false,
      messages: [],
      sessionId: null,
      quickActions: [
        { text: 'Помощь', icon: 'mdi-help-circle' },
        { text: 'Контакты', icon: 'mdi-phone' },
        { text: 'Время работы', icon: 'mdi-clock' },
        { text: 'FAQ', icon: 'mdi-frequently-asked-questions' }
      ]
    }
  },
  mounted() {
    // Инициализация сессии
    this.initSession()
    
    // Загружаем историю из sessionStorage
    this.loadChatHistory()
    
    // Если нет сообщений, добавляем приветственное
    if (this.messages.length === 0) {
      this.addWelcomeMessage()
    }
    
    // Добавляем обработчик для Escape
    document.addEventListener('keydown', this.handleEscape)
  },
  beforeDestroy() {
    document.removeEventListener('keydown', this.handleEscape)
  },
  methods: {
    initSession() {
      // Генерируем или получаем ID сессии
      this.sessionId = sessionStorage.getItem('assistant_session_id')
      if (!this.sessionId) {
        this.sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
        sessionStorage.setItem('assistant_session_id', this.sessionId)
      }
    },
    
    loadChatHistory() {
      try {
        const saved = sessionStorage.getItem('assistant_messages')
        if (saved) {
          this.messages = JSON.parse(saved)
        }
      } catch (error) {
        console.error('Ошибка загрузки истории:', error)
      }
    },
    
    saveChatHistory() {
      try {
        // Сохраняем только последние 50 сообщений
        const toSave = this.messages.slice(-50)
        sessionStorage.setItem('assistant_messages', JSON.stringify(toSave))
      } catch (error) {
        console.error('Ошибка сохранения истории:', error)
      }
    },
    
    handleEscape(e) {
      if (e.key === 'Escape' && this.isOpen) {
        this.closeAssistant()
      }
    },
    
    addWelcomeMessage() {
      this.messages.push({
        type: 'assistant',
        text: '👋 Привет! Я виртуальный помощник. Чем могу помочь?',
        time: this.getCurrentTime()
      })
      this.saveChatHistory()
    },
    
    toggleAssistant() {
      this.isOpen = !this.isOpen
      if (this.isOpen) {
        this.$nextTick(() => {
          this.scrollToBottom()
          const input = document.querySelector('.message-input input')
          if (input) input.focus()
        })
      }
    },
    
    closeAssistant() {
      this.isOpen = false
    },
    
    async sendMessage() {
      if (!this.userInput.trim() || this.isTyping) return

      const userMessage = this.userInput.trim()
      
      // Добавляем сообщение пользователя
      this.messages.push({
        type: 'user',
        text: userMessage,
        time: this.getCurrentTime()
      })
      
      this.userInput = ''
      this.saveChatHistory()
      
      // Прокрутка вниз
      this.$nextTick(() => {
        this.scrollToBottom()
      })
      
      // Показываем индикатор печати
      this.isTyping = true
      
      try {
        // Формируем историю для API (последние 10 сообщений)
        const chatHistory = this.messages.slice(-10).map(msg => ({
          type: msg.type,
          text: msg.text
        }))
        
        // Отправляем запрос к бэкенду
        const response = await axios.post('/api/assistant/chat', {
          message: userMessage,
          history: chatHistory,
          session_id: this.sessionId
        })
        console.log(response)
        
        // Проверяем успешность ответа
        if (response.data.success) {
          // Добавляем ответ ассистента
          this.messages.push({
            type: 'assistant',
            text: response.data.reply,
            time: this.getCurrentTime()
          })
        } else {
          // Если ошибка от API
          this.messages.push({
            type: 'assistant',
            text: response.data.reply || 'Извините, произошла ошибка. Попробуйте позже.',
            time: this.getCurrentTime()
          })
        }
        
        this.saveChatHistory()
        
      } catch (error) {
        console.error('Ошибка при отправке сообщения:', error)
        
        // Обработка ошибок
        let errorMessage = 'Извините, сервис временно недоступен. Попробуйте позже.'
        
        if (error.response) {
          // Сервер ответил с ошибкой
          if (error.response.status === 422) {
            errorMessage = 'Пожалуйста, напишите сообщение подлиннее.'
          } else if (error.response.status === 500) {
            errorMessage = 'Сервер временно не работает. Пожалуйста, попробуйте позже.'
          }
        } else if (error.request) {
          // Запрос был отправлен, но ответа нет
          errorMessage = 'Нет связи с сервером. Проверьте интернет-соединение.'
        }
        
        this.messages.push({
          type: 'assistant',
          text: errorMessage,
          time: this.getCurrentTime()
        })
        
        this.saveChatHistory()
        
      } finally {
        this.isTyping = false
        this.$nextTick(() => {
          this.scrollToBottom()
        })
      }
    },
    
    quickAction(action) {
      this.userInput = action
      this.sendMessage()
    },
    
    getCurrentTime() {
      const now = new Date()
      return `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`
    },
    
    scrollToBottom() {
      const container = document.querySelector('.messages-container')
      if (container) {
        container.scrollTop = container.scrollHeight
      }
    },
    
    // Очистка чата (опционально)
    clearChat() {
      if (confirm('Очистить историю чата?')) {
        this.messages = []
        this.addWelcomeMessage()
        this.saveChatHistory()
      }
    }
  }
}
</script>

<style scoped>
/* Все стили остаются без изменений */
.smart-assistant {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
}

/* Триггер-кнопка */
.assistant-trigger {
  position: relative;
  width: 56px;
  height: 56px;
  cursor: pointer;
  z-index: 1001;
}

.trigger-icon {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
  position: relative;
  z-index: 2;
}

.assistant-trigger:hover .trigger-icon {
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.trigger-pulse {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(102, 126, 234, 0.4);
  border-radius: 50%;
  animation: pulse 2s infinite;
  z-index: 1;
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 0.6;
  }
  100% {
    transform: scale(1.5);
    opacity: 0;
  }
}

/* Выезжающая панель */
.assistant-panel {
  position: fixed;
  top: 20px;
  bottom: 90px;
  right: 20px;
  width: 380px;
  max-width: calc(100vw - 40px);
  z-index: 1000;
}

.assistant-card {
  border-radius: 16px !important;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
  background: #f8f9fa;
}

/* Заголовок */
.status-indicator {
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 10px;
  border-radius: 20px;
}

/* Область сообщений */
.messages-container {
  height: 350px;
  overflow-y: auto;
  overflow-x: hidden;
  background: #f8f9fa;
  scroll-behavior: smooth;
}

/* Стили сообщений */
.message-wrapper {
  margin-bottom: 16px;
  animation: messageSlideIn 0.3s ease-out;
}

@keyframes messageSlideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message-wrapper.user {
  display: flex;
  justify-content: flex-end;
}

.message-wrapper.user .message-bubble {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  max-width: 85%;
}

.message-wrapper.assistant .message-bubble {
  background: white;
  color: #333;
  max-width: 85%;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
}

.message-bubble {
  padding: 10px 14px;
  border-radius: 12px;
  word-wrap: break-word;
}

.message-header {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
  font-size: 11px;
  opacity: 0.8;
}

.message-text {
  font-size: 14px;
  line-height: 1.4;
  white-space: pre-wrap;
}

/* Пустой чат */
.empty-chat {
  text-align: center;
  padding: 40px 20px;
}

/* Индикатор печати */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 12px;
}

.typing-indicator span:not(.caption) {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #999;
  display: inline-block;
  animation: typingBounce 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: 0s; }
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBounce {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.4;
  }
  30% {
    transform: translateY(-8px);
    opacity: 1;
  }
}

/* Быстрые действия */
.quick-actions {
  background: white;
  border-top: 1px solid #e0e0e0;
  max-height: 80px;
  overflow-y: auto;
}

/* Поле ввода */
.input-area {
  background: white;
  border-top: 1px solid #e0e0e0;
}

/* Оверлей */
.assistant-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 999;
}

/* Анимации */
.slide-right-enter-active,
.slide-right-leave-active {
  transition: all 0.3s ease;
}

.slide-right-enter,
.slide-right-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
}

/* Прокрутка */
.messages-container::-webkit-scrollbar {
  width: 6px;
}

.messages-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Адаптивность */
@media (max-width: 600px) {
  .assistant-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 100%;
    margin: 0;
    z-index: 1001;
  }
  
  .assistant-card {
    height: 100%;
    border-radius: 0 !important;
  }
  
  .messages-container {
    height: calc(70vh - 180px);
  }
  
  .assistant-trigger {
    bottom: 10px;
    right: 10px;
  }
}
</style>