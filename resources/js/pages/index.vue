<template>
  <div>


    <!-- Категории с товарами -->
    <v-container>
      <!-- Блок с преимуществами перед списком категорий -->
      <v-alert
        color="info"
        variant="tonal"
        class="mb-6"
        density="compact"
      >
        <v-row align="center" >
          <v-col cols="auto">
            <v-icon color="green">mdi-truck-fast</v-icon>
          </v-col>
          <v-col>
            <strong>Бесплатная доставка</strong> при заказе от 5000 ₽. <strong>Гарантия приживаемости 30 дней</strong> на все растения.
          </v-col>
        </v-row>
      </v-alert>

      <!-- Категории -->
      <CategoryProducts 
        categoryId="260"
        @add-to-cart="addToCart"
      />
      
      <v-divider class="my-8"></v-divider>
      
      <CategoryProducts 
        categoryId="261"
        @add-to-cart="addToCart"
      />
      
      <v-divider class="my-8"></v-divider>
      
      <CategoryProducts 
        categoryId="262"
        @add-to-cart="addToCart"
      />
    </v-container>

    <!-- Блок с отзывами -->
    <v-container fluid class="bg-grey-lighten-4 py-8 mt-8">
      <v-row justify="center">
        <v-col cols="12" class="text-center mb-6">
          <h2 class="text-h4 font-weight-bold text-green-darken-3">
            Нас выбирают садоводы
          </h2>
          <p class="text-subtitle-1 text-grey-darken-1">
            Более 5000 довольных клиентов за последний год
          </p>
        </v-col>
      </v-row>
    </v-container>

    <!-- Блок с вопросами и ответами -->
    <v-container fluid  class="py-8">
      <v-row justify="center">
        <v-col cols="12" class="text-center mb-6">
          <h2 class="text-h4 font-weight-bold text-green-darken-3">
            Часто задаваемые вопросы
          </h2>
        </v-col>
      </v-row>

      <v-row justify="center">
        <v-col cols="12" md="12">
          <v-expansion-panels variant="accordion">
            <v-expansion-panel v-for="faq in faqs" :key="faq.question">
              <v-expansion-panel-title>
                <strong>{{ faq.question }}</strong>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                {{ faq.answer }}
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-col>
      </v-row>
    </v-container>

    <!-- Блок с подпиской -->
    <v-container fluid class="bg-green-darken-3 py-8 mt-8">
      <v-row justify="center">
        <v-col cols="12" md="8" class="text-center">
          <h2 class="text-h4 font-weight-bold text-white mb-2">
            Будьте в курсе новинок
          </h2>
          <p class="text-white text-opacity-90 mb-4">
            Подпишитесь на рассылку и получайте информацию о новых поступлениях и сезонных скидках
          </p>
          <v-card class="pa-4" elevation="0" rounded="lg">
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="email"
                  label="Ваш email"
                  variant="outlined"
                  hide-details
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-btn
                  color="green-darken-2"
                  size="large"
                  block
                  @click="subscribe"
                >
                  Подписаться
                </v-btn>
              </v-col>
            </v-row>
            <div class="text-caption text-grey mt-2 text-center">
              Никакого спама, только полезные новости
            </div>
          </v-card>
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
import { ref } from 'vue'
import CategoryProducts from '@/components/CategoryProducts.vue'

// Элементы доверия
const trustElements = ref([
  {
    icon: 'mdi-leaf',
    title: 'Здоровые саженцы',
    description: 'Растения выращены в питомнике, адаптированы к местному климату',
    color: 'green-darken-2'
  },
  {
    icon: 'mdi-shield-check',
    title: 'Гарантия качества',
    description: '30 дней гарантии на приживаемость всех растений',
    color: 'green-darken-2'
  },
  {
    icon: 'mdi-truck-fast',
    title: 'Быстрая доставка',
    description: 'Доставка по городу и области в течение 1-2 дней',
    color: 'green-darken-2'
  },
  {
    icon: 'mdi-headset',
    title: 'Поддержка 24/7',
    description: 'Консультации по посадке и уходу от наших агрономов',
    color: 'green-darken-2'
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
  if (email.value) {
    snackbar.value = {
      show: true,
      text: 'Спасибо за подписку!',
      color: 'success'
    }
    email.value = ''
  } else {
    snackbar.value = {
      show: true,
      text: 'Введите email для подписки',
      color: 'warning'
    }
  }
}
</script>

<style scoped>
.h-100 {
  height: 100%;
}

.bg-green-lighten-5 {
  background-color: #f1f8e9;
}

.text-opacity-90 {
  opacity: 0.9;
}
</style>