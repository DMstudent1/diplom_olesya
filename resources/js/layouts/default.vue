<template>
    <v-app>
        <AppHeader />
        <v-main >
                <router-view />
        </v-main>
        <SmartAssistant />
        <AppFooter />
    </v-app>
</template>

<script setup>
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import SmartAssistant from '@/components/SmartAssistant.vue'
</script>
