<template>
    <v-app>
        <AppHeader />
        <v-main class="pl-0 pr-0">
                <router-view />
        </v-main>
        <AppFooter />
    </v-app>
</template>

<script setup>
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
</script>
