<template>
    <v-app>
        <AppHeader />
        <v-main>
            <v-container fluid>
                <router-view />
            </v-container>
        </v-main>
        <AppFooter />
    </v-app>
</template>

<script setup>
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
</script>
