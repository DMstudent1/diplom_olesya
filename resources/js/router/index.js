import { createRouter, createWebHistory } from 'vue-router';
import DefaultLayout from '@layouts/default.vue';
import Index from '@pages/index.vue';
import Register from '@pages/register.vue';
import Cart from '@pages/cart.vue';
import Profile from '@pages/profile.vue';
import Categories from '@pages/categories.vue';
import Products from '@pages/products.vue';
import Login from '@pages/login.vue';
import ForgotPassword from '@pages/forgotPassword.vue';

const routes = [
    {
        path: '/',
        component: DefaultLayout,
        children: [
            {
                path: '',
                name: 'index',
                component: Index,
                meta: { title: 'Главная' }
            },
        ]
    },
    {
        path: '/register',
        component: DefaultLayout,
        children: [
            {
                path: '',
                name: 'register',
                component: Register,
                meta: { title: 'Регистрация' }
            },
        ]
    },
    {
        path: '/cart',
        component: DefaultLayout,
        children: [
            {
                path: '',
                name: 'cart',
                component: Cart,
                meta: { title: 'Корзина' }
            },
        ]
    },
    {
        path: '/categories',
        component: DefaultLayout,
        children: [
            {
                path: '',
                name: 'categories',
                component: Categories,
                meta: { permission: 'categories-create', title: 'Категории' }
            },
        ]
    },
    {
        path: '/products',
        component: DefaultLayout,
        children: [
            {
                path: '',
                name: 'products',
                component: Products,
                meta: { permission: 'categories-create', title: 'Продукты' }
            },
        ]
    },
    {
        path: '/profile',
        component: DefaultLayout,
        children: [
            {
                path: '',
                name: 'profile',
                component: Profile,
                meta: { title: 'Профиль' }
            },
        ]
    },
    {
        path: '/login',
        component: DefaultLayout,
        children: [
            {
                path: '',
                name: 'login',
                component: Login,
                meta: { title: 'Вход' }
            },
        ]
    },
    {
        path: '/forgot-password',
        component: DefaultLayout,
        children: [
            {
                path: '',
                name: 'forgot-password',
                component: ForgotPassword,
                meta: { title: 'Сброс пароля' }
            },
        ]
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0 };
        }
    }
});

router.beforeEach((to, from, next) => {
    document.title = to.meta.title ? `${to.meta.title} | My App` : 'My App';
    next();
});

function hasPermission(permissions, permissionName) {
    if (!permissions || !Array.isArray(permissions)) return false;
    return permissions.some(p => p.name === permissionName);
}



export default router;