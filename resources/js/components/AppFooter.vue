<template>
    <footer class="footer">
        <v-container>
            <v-row justify="center">
                <v-col cols="auto">
                    <span>&copy; {{ currentYear }} {{ appName }}. Все права защищены.</span>
                </v-col>
            </v-row>
        </v-container>
    </footer>
</template>

<script setup>
import { ref } from 'vue'

const currentYear = ref(new Date().getFullYear())
const appName = ref(window.appName || 'Laravel')
</script>

<style scoped>
.footer {
    background-color: #f5f5f5;
    padding: 24px 0;
    text-align: center;
    border-top: 1px solid #e0e0e0;
    margin-top: auto;
}
</style>