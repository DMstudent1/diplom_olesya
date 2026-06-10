<template>
  <div>
    <!-- Hero-секция с параллакс эффектом -->
    <v-parallax
      :src="heroUrl"
      height="80vh"
      class="mb-0"
      fluid
    >
      <div class="hero-overlay d-flex align-center justify-center">
        <div class="text-center">
          <v-card-title style="font-size: 42px;" class="font-weight-bold text-white mb-4 fade-in-up pa-0">
            Leafstory
          </v-card-title>
          <v-card-subtitle style="font-size: 24px;" class="text-white text-opacity-90 fade-in-up-delay pa-0">
            Создаём сады, в которые хочется возвращаться
          </v-card-subtitle>
        </div>
      </div>
    </v-parallax>

    <!-- Блок с преимуществами -->
    <v-container class="py-12">
      <v-row justify="center">
        <v-col cols="12" class="text-center mb-8">
          <v-chip class="section-badge mb-4" size="x-large" color="green-darken-4">
            Почему мы
          </v-chip>
          <v-card-title class="text-h3 font-weight-bold text-green-darken-3 mt-2 pa-0">
            Наши преимущества
          </v-card-title>
          <v-divider class="w-25 mx-auto my-4" color="green-darken-3" thickness="2"></v-divider>
          <v-card-subtitle class="text-subtitle-1 text-grey-darken-1 pa-0">
            Более 15 лет создаём сады, которые радуют каждый день
          </v-card-subtitle>
        </v-col>
      </v-row>

      <v-row>
        <v-col v-for="(item, index) in trustElements" :key="item.title" cols="12" sm="6" md="3">
          <v-card class="value-card text-center pa-6 fade-in-up" elevation="2" rounded="lg" height="100%">
            <div class="value-icon mb-4">
              <v-icon :color="item.color" size="48">{{ item.icon }}</v-icon>
            </div>
            <v-card-title class="text-h6 font-weight-bold mb-3 pa-0">
              {{ item.title }}
            </v-card-title>
            <v-card-text class="text-body-2 text-grey-darken-2 pa-0">
              {{ item.description }}
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Категории с товарами -->
    <v-container fluid class="bg-grey-lighten-4 py-8">
      <v-container>
        <CategoryProducts 
          categoryId="1"
          @add-to-cart="addToCart"
        />
        
        <v-divider class="my-8" :thickness="2"></v-divider>
        
        <CategoryProducts 
          categoryId="2"
          @add-to-cart="addToCart"
        />
        
        <v-divider class="my-8" :thickness="2"></v-divider>
        
        <CategoryProducts 
          categoryId="3"
          @add-to-cart="addToCart"
        />
      </v-container>
    </v-container>

    <!-- Блок с отзывами -->
    <v-container class="py-12">
      <v-row justify="center">
        <v-col cols="12" class="text-center mb-8">
          <v-chip class="section-badge mb-4" size="x-large" color="green-darken-4">
            Отзывы
          </v-chip>
          <v-card-title class="text-h3 font-weight-bold text-green-darken-3 mt-2 pa-0">
            Нас выбирают садоводы
          </v-card-title>
          <v-divider class="w-25 mx-auto my-4" color="green-darken-3" thickness="2"></v-divider>
          <v-card-subtitle class="text-subtitle-1 text-grey-darken-1 pa-0">
            Более 5000 довольных клиентов за последний год
          </v-card-subtitle>
        </v-col>
      </v-row>

      <v-row>
        <v-col v-for="(review, index) in reviews" :key="review.name" cols="12" md="4">
          <v-card class="review-card pa-4 fade-in-up" elevation="2" rounded="lg">
            <div class="d-flex align-center mb-4">
              <v-avatar size="56" class="mr-3">
                <v-img :src="review.avatar" :alt="review.name"></v-img>
              </v-avatar>
              <div>
                <v-card-title class="text-subtitle-1 font-weight-bold pa-0">
                  {{ review.name }}
                </v-card-title>
                <div class="d-flex">
                  <v-icon
                    v-for="n in 5"
                    :key="n"
                    :color="n <= review.rating ? 'amber-darken-1' : 'grey-lighten-1'"
                    size="16"
                    class="mr-1"
                  >
                    mdi-star
                  </v-icon>
                </div>
                <div class="text-caption text-grey-darken-1 mt-1">
                  {{ review.plant }}
                </div>
              </div>
            </div>
            <v-card-text class="text-body-2 text-grey-darken-2 pa-0 pt-2">
              "{{ review.text }}"
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Блок с вопросами и ответами -->
    <v-container fluid class="bg-grey-lighten-4 py-12">
      <v-container>
        <v-row justify="center">
          <v-col cols="12" class="text-center mb-8">
            <v-chip class="section-badge mb-4" size="x-large" color="green-darken-4">
              FAQ
            </v-chip>
            <v-card-title class="text-h3 font-weight-bold text-green-darken-3 mt-2 pa-0">
              Часто задаваемые вопросы
            </v-card-title>
            <v-divider class="w-25 mx-auto my-4" color="green-darken-3" thickness="2"></v-divider>
          </v-col>
        </v-row>

        <v-row justify="center">
          <v-col cols="12" md="10">
            <v-expansion-panels variant="accordion">
              <v-expansion-panel v-for="(faq, index) in faqs" :key="faq.question" class="mb-3">
                <v-expansion-panel-title class="text-subtitle-1 font-weight-medium">
                  {{ faq.question }}
                </v-expansion-panel-title>
                <v-expansion-panel-text class="text-body-2 text-grey-darken-2">
                  {{ faq.answer }}
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </v-col>
        </v-row>
      </v-container>
    </v-container>

    <!-- CTA секция с подпиской -->
    <v-container fluid class="bg-green-darken-3 py-12 text-white text-center">
      <v-card-title class="text-h3 font-weight-bold mb-4 fade-in-up pa-0">
        Будьте в курсе новостей
      </v-card-title>
      <v-card-subtitle class="text-h6 mb-6 fade-in-up-delay text-white text-opacity-90 pa-0">
        Получайте советы по уходу за растениями и акции первыми
      </v-card-subtitle>
      <v-row justify="center">
        <v-col cols="12" sm="8" md="6">
          <v-text-field
            v-model="email"
            placeholder="Ваш email"
            variant="outlined"
            bg-color="white"
            density="comfortable"
            hide-details
            class="subscribe-input"
            @keyup.enter="subscribe"
          >
            <template v-slot:append>
              <v-btn
                color="green-darken-3"
                size="large"
                class="px-6"
                @click="subscribe"
              >
                Подписаться
              </v-btn>
            </template>
          </v-text-field>
        </v-col>
      </v-row>
    </v-container>

    <!-- Snackbar для уведомлений -->
    <v-snackbar
      v-model="snackbar.show"
      :timeout="3000"
      :color="snackbar.color"
      location="top"
    >
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn variant="text" icon="mdi-close" @click="snackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import CategoryProducts from '@/components/CategoryProducts.vue'
import heroUrl from '@/assets/hero-image.jpg'

// Элементы доверия
const trustElements = ref([
  {
    icon: 'mdi-leaf',
    title: 'Здоровые саженцы',
    description: 'Растения выращены в питомнике, адаптированы к местному климату',
    color: 'green-darken-3'
  },
  {
    icon: 'mdi-shield-check',
    title: 'Гарантия качества',
    description: '30 дней гарантии на приживаемость всех растений',
    color: 'green-darken-3'
  },
  {
    icon: 'mdi-truck-fast',
    title: 'Быстрая доставка',
    description: 'Доставка по городу и области в течение 1-2 дней',
    color: 'green-darken-3'
  },
  {
    icon: 'mdi-headset',
    title: 'Поддержка 24/7',
    description: 'Консультации по посадке и уходу от наших агрономов',
    color: 'green-darken-3'
  }
])

// Отзывы
const reviews = ref([
  {
    name: 'Елена Смирнова',
    plant: 'Туя западная Смарагд',
    rating: 5,
    text: 'Заказывала туи для живой изгороди. Все саженцы пришли в отличном состоянии, прижились быстро. Через год уже шикарная зеленая стена!',
    avatar: 'https://randomuser.me/api/portraits/women/1.jpg'
  },
  {
    name: 'Александр Петров',
    plant: 'Яблоня Медок',
    rating: 5,
    text: 'Колоновидные яблони отлично вписались в небольшой участок. Уже на второй год попробовали первые плоды. Вкусные и сочные!',
    avatar: 'https://randomuser.me/api/portraits/men/2.jpg'
  },
  {
    name: 'Мария Иванова',
    plant: 'Гортензия Ванилла Фрейз',
    rating: 4,
    text: 'Очень красивая гортензия, цветет все лето. Получила подробную консультацию по посадке. Спасибо за качественный посадочный материал!',
    avatar: 'https://randomuser.me/api/portraits/women/3.jpg'
  }
])

// FAQ
const faqs = ref([
  {
    question: 'Как правильно выбрать саженец?',
    answer: 'Обратите внимание на корневую систему (она должна быть влажной и без повреждений), на ствол (без трещин и наростов) и на почки (живые и упругие). В нашем питомнике все растения проходят строгий отбор перед продажей.'
  },
  {
    question: 'Когда лучше сажать деревья и кустарники?',
    answer: 'Лучшее время для посадки - весна (апрель-май) и осень (сентябрь-октябрь). Саженцы с закрытой корневой системой можно высаживать в течение всего сезона.'
  },
  {
    question: 'Как получить консультацию по уходу?',
    answer: 'Вы можете задать вопрос нашим агрономам по телефону, в чате на сайте или приехав в питомник. Мы всегда рады помочь!'
  },
  {
    question: 'Какая гарантия на растения?',
    answer: 'Мы предоставляем 30 дней гарантии на приживаемость. Если растение не прижилось по нашей вине, мы заменим его или вернем деньги.'
  }
])

const email = ref('')
const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

const addToCart = (product) => {
  snackbar.value = {
    show: true,
    text: `${product.name} добавлен в корзину`,
    color: 'success'
  }
  console.log('Добавлен в корзину:', product)
}

const subscribe = () => {
  if (email.value && email.value.includes('@')) {
    snackbar.value = {
      show: true,
      text: 'Спасибо за подписку!',
      color: 'success'
    }
    email.value = ''
  } else {
    snackbar.value = {
      show: true,
      text: 'Введите корректный email',
      color: 'warning'
    }
  }
}

// Анимация при скролле
onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible')
      }
    })
  }, { threshold: 0.2 })

  document.querySelectorAll('.slide-in-left, .slide-in-right, .fade-in-up, .fade-in-up-delay, .fade-in-up-delay-2').forEach(el => {
    observer.observe(el)
  })
})
</script>

<style scoped>
/* Hero overlay */
.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  pointer-events: none;
}

/* Анимации */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.fade-in-up, .fade-in-up-delay, .fade-in-up-delay-2 {
  opacity: 0;
  animation: fadeInUp 0.8s ease forwards;
}

.fade-in-up { animation-delay: 0.2s; }
.fade-in-up-delay { animation-delay: 0.5s; }
.fade-in-up-delay-2 { animation-delay: 0.8s; }

.slide-in-left {
  opacity: 0;
  transform: translateX(-50px);
  transition: all 0.8s ease;
}

.slide-in-right {
  opacity: 0;
  transform: translateX(50px);
  transition: all 0.8s ease;
}

.slide-in-left.visible,
.slide-in-right.visible {
  opacity: 1;
  transform: translateX(0);
}

/* Section badge */
.section-badge {
  font-size: 1rem;
  letter-spacing: 1px;
  text-transform: uppercase;
  background-color: #e8f5e9;
  color: #2e7d32;
}

/* Value card */
.value-card {
  transition: all 0.3s ease;
  height: 100%;
}

.value-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.value-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto;
  background: #f1f8e9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Review card */
.review-card {
  transition: all 0.3s ease;
  height: 100%;
}

.review-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

/* Subscribe input */
.subscribe-input :deep(.v-field) {
  border-radius: 50px;
}

.subscribe-input :deep(.v-field__append) {
  margin-right: 4px;
}

.subscribe-input :deep(.v-btn) {
  border-radius: 50px;
}

/* Utility */
.w-25 {
  width: 25%;
}

.bg-grey-lighten-4 {
  background-color: #f5f5f5;
}
</style>