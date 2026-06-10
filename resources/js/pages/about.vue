<template>
  <div>
    <!-- Hero-секция с параллакс эффектом -->
    <v-parallax
      :src="heroUrl"
      height="100vh"
      class="mb-0"
      fluid
    >
      <div class="hero-overlay d-flex align-center justify-center">
        <div class="text-center">
            <v-card-title style="font-size: 42px;" class=" font-weight-bold text-white mb-4 fade-in-up pa-0">
                О компании
            </v-card-title>
            <v-card-subtitle style="font-size: 24px;" class="text-white text-opacity-90 fade-in-up-delay pa-0">
                15 лет заботы о вашем саде
            </v-card-subtitle>
            </div>
    </div>
    </v-parallax>

    <!-- История компании с анимацией при скролле -->
    <v-container class="py-12">
      <v-row justify="center">
        <v-col cols="12" class="text-center mb-8">
          <v-chip class="section-badge mb-4" size="x-large" color="green-darken-4" >
            Наша история
          </v-chip>
          <v-card-title class="text-h3 font-weight-bold text-green-darken-3 mt-2 pa-0">
            От мечты до лидера отрасли
          </v-card-title>
          <v-divider class="w-25 mx-auto my-4" color="green-darken-3" thickness="2"></v-divider>
        </v-col>
      </v-row>

      <v-row align="center" class="mb-12">
        <v-col cols="12" md="6" class="order-last order-md-first">
          <div class="timeline-item slide-in-left">
            <v-chip class="timeline-year mb-3" color="green-darken-3" text-color="white" >
              2010
            </v-chip>
            <div class="timeline-content">
              <v-card-title class="text-h5 font-weight-bold mb-3 pa-0">
                Основание питомника
              </v-card-title>
              <v-card-text class="text-body-1 text-grey-darken-2 pa-0">
                Всё началось с маленького семейного питомника площадью всего 2 гектара. 
                Основатель компании, агроном с 20-летним стажем, решил создать место, 
                где каждый садовод сможет найти здоровые и качественные растения.
              </v-card-text>
              <v-chip color="green-darken-3" size="small" class="mt-3">
                Первые 100 саженцев
              </v-chip>
            </div>
          </div>
        </v-col>
        <v-col cols="12" md="6" class="order-first order-md-last">
          <div class="image-hover slide-in-right">
            <v-img
              :src="historyUrl"
              aspect-ratio="4/3"
              cover
              class="rounded-xl"
            ></v-img>
          </div>
        </v-col>
      </v-row>

      <v-row align="center" class="mb-12">
        <v-col cols="12" md="6">
          <div class="image-hover slide-in-left">
            <v-img
              :src="greenhousesURL"
              aspect-ratio="4/3"
              cover
              class="rounded-xl"
            ></v-img>
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="timeline-item slide-in-right">
            <v-chip class="timeline-year mb-3" color="green-darken-3" text-color="white">
              2015
            </v-chip>
            <div class="timeline-content">
              <v-card-title class="text-h5 font-weight-bold mb-3 pa-0">
                Первый крупный питомник
              </v-card-title>
              <v-card-text class="text-body-1 text-grey-darken-2 pa-0">
                Компания расширилась до 15 гектаров, открылся первый питомник полного цикла. 
                Мы начали не только продавать, но и самостоятельно выращивать растения, 
                внедрив современные системы капельного полива и защиты от вредителей.
              </v-card-text>
              <v-chip color="green-darken-3" size="small" class="mt-3">
                500+ видов растений
              </v-chip>
            </div>
          </div>
        </v-col>
      </v-row>

      <v-row align="center" class="mb-12">
        <v-col cols="12" md="6" class="order-last order-md-first">
          <div class="timeline-item slide-in-left">
            <v-chip class="timeline-year" color="green-darken-3" text-color="white" >
              2020
            </v-chip>
            <div class="timeline-content">
              <v-card-title class="text-h5 font-weight-bold mb-3 pa-0">
                Лидер рынка
              </v-card-title>
              <v-card-text class="text-body-1 text-grey-darken-2 pa-0">
                Открытие трёх собственных питомников в разных климатических зонах России. 
                Мы стали поставщиком для крупнейших ландшафтных студий и садовых центров. 
                Ассортимент превысил 1000 наименований растений.
              </v-card-text>
              <v-chip color="green-darken-3" size="small" class="mt-3">
                Более 50 сотрудников
              </v-chip>
            </div>
          </div>
        </v-col>
        <v-col cols="12" md="6" class="order-first order-md-last">
          <div class="image-hover slide-in-right">
            <v-img
              :src="largegreenhousesURL"
              aspect-ratio="4/3"
              cover
              class="rounded-xl"
            ></v-img>
          </div>
        </v-col>
      </v-row>

      <v-row align="center">
        <v-col cols="12" md="6">
          <div class="image-hover slide-in-left">
            <v-img
              :src="droneURL"
              aspect-ratio="4/3"
              cover
              class="rounded-xl"
            ></v-img>
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="timeline-item slide-in-right">
            <v-chip class="timeline-year mb-3" color="green-darken-3" text-color="white" >
              2025
            </v-chip>
            <div class="timeline-content">
              <v-card-title class="text-h5 font-weight-bold mb-3 pa-0">
                Взгляд в будущее
              </v-card-title>
              <v-card-text class="text-body-1 text-grey-darken-2 pa-0">
                Сегодня мы — это сплочённая команда агрономов, селекционеров и садоводов, 
                которые ежедневно работают над тем, чтобы каждый наш клиент получил 
                не просто растение, а настоящий сад своей мечты. Мы продолжаем расти 
                и внедрять инновации в каждом аспекте нашей работы.
              </v-card-text>
              <v-chip color="green-darken-3" size="small" class="mt-3">
                Более 15 000 клиентов
              </v-chip>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-container>

    <!-- Цифры и достижения с анимацией -->
    <v-container fluid class="bg-green-darken-3 py-12 text-white">
      <v-row justify="center">
        <v-col cols="12" class="text-center mb-8">
          <v-card-title class="text-h3 font-weight-bold pa-0">
            Цифры и факты
          </v-card-title>
          <v-divider class="w-25 mx-auto my-4" color="white" thickness="2"></v-divider>
          <v-card-subtitle class="text-subtitle-1 text-white text-opacity-90 pa-0">
            Результаты нашей работы
          </v-card-subtitle>
        </v-col>
      </v-row>

      <v-row justify="center">
        <v-col v-for="(stat, index) in stats" :key="stat.label" cols="12" sm="3">
          <div class="text-center stat-counter" :data-delay="index * 100">
            <v-card-title class="text-h1 font-weight-bold pa-0">
              <CountUp :end-val="stat.value" :duration="2.5" :options="{ suffix: stat.suffix }" />
            </v-card-title>
            <v-card-subtitle class="text-subtitle-1 mt-2 text-white text-opacity-90 pa-0">
              {{ stat.label }}
            </v-card-subtitle>
          </div>
        </v-col>
      </v-row>
    </v-container>

    <!-- Наши ценности -->
    <v-container class="py-12">
      <v-row justify="center">
        <v-col cols="12" class="text-center mb-8">
          <v-chip class="section-badge mb-4 " size="x-large" color="green-darken-4">
            Философия
          </v-chip>
          <v-card-title class="text-h3 font-weight-bold text-green-darken-3 mt-2 pa-0">
            Наши принципы
          </v-card-title>
          <v-divider class="w-25 mx-auto my-4" color="green-darken-3" thickness="2"></v-divider>
        </v-col>
      </v-row>

      <v-row>
        <v-col v-for="(value, index) in values" :key="value.title" cols="12" md="4">
          <v-card class="value-card text-center pa-6 fade-in-up" elevation="2" rounded="lg" height="100%">
            <div class="value-icon mb-4">
              <v-icon :color="value.color" size="48">{{ value.icon }}</v-icon>
            </div>
            <v-card-title class="text-h5 font-weight-bold mb-3 pa-0">
              {{ value.title }}
            </v-card-title>
            <v-card-text class="text-body-1 text-grey-darken-2 pa-0">
              {{ value.description }}
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- CTA -->
    <v-container fluid class="bg-green-darken-3 py-12 text-white text-center">
      <v-card-title class="text-h3 font-weight-bold mb-4 fade-in-up pa-0">
        Готовы создать сад вашей мечты?
      </v-card-title>
      <v-card-subtitle class="text-h6 mb-6 fade-in-up-delay text-white text-opacity-90 pa-0">
        Присоединяйтесь к 15 000 довольных садоводов
      </v-card-subtitle>
      <v-btn
        to="/categories"
        size="large"
        color="white"
        variant="outlined"
        class="fade-in-up-delay-2"
        elevation="0"
        append-icon="mdi-arrow-right"
      >
        Перейти в каталог
      </v-btn>
    </v-container>

    <!-- Snackbar -->
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
import CountUp from 'vue-countup-v3'
import heroUrl from '@/assets/hero-image.jpg'
import historyUrl from '@/assets/history.jpg'
import greenhousesURL from '@/assets/greenhouses.jpg'
import largegreenhousesURL from '@/assets/largegreenhouses.jpg'
import droneURL from '@/assets/drone.jpg'
const preventClick = (e) => {
  e.stopPropagation()
}

// Статистика
const stats = ref([
  { value: 15, label: 'лет на рынке', suffix: '+' },
  { value: 1000, label: 'видов растений', suffix: '+' },
  { value: 15000, label: 'довольных клиентов', suffix: '+' },
  { value: 50, label: 'сотрудников', suffix: '+' }
])

// Ценности
const values = ref([
  {
    icon: 'mdi-leaf',
    title: 'Качество',
    description: 'Мы выращиваем только здоровые и сильные растения, прошедшие строгий отбор',
    color: 'green-darken-3'
  },
  {
    icon: 'mdi-handshake',
    title: 'Доверие',
    description: 'Честные цены, прозрачные условия и гарантия на все растения',
    color: 'green-darken-3'
  },
  {
    icon: 'mdi-school',
    title: 'Экспертность',
    description: 'Постоянное обучение и внедрение лучших мировых практик',
    color: 'green-darken-3'
  }
])

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

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
  pointer-events: none; /* Отключает клики на всей области */
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

/* Timeline */
.timeline-item {
  position: relative;
  padding-left: 30px;
}

.timeline-year {
  margin-bottom: 16px;
}

.timeline-content {
  border-left: 3px solid #2e7d32;
  padding-left: 20px;
}

/* Image hover */
.image-hover {
  overflow: hidden;
  border-radius: 12px;
}

.image-hover :deep(.v-img) {
  transition: transform 0.5s ease;
}

.image-hover:hover :deep(.v-img) {
  transform: scale(1.05);
}

/* Value card */
.value-card {
  transition: all 0.3s ease;
}

.value-card:hover {
  transform: translateY(-8px);
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

/* Utility */
.w-25 {
  width: 25%;
}
</style>