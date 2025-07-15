import './bootstrap';
import './echo'; // Import Echo configuration

import 'admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import { allRoutes as AppRoutes } from '../js/Routes/Routes.js'; // Import the routes array
import Login from '../js/auth/Login.vue';
import App from '../js/Pages/App.vue';


// ✅ Initialize Pinia first
const pinia = createPinia();

// ✅ Create app instance
const app = createApp(App);
app.use(pinia); // Register Pinia before using the store

// Create the router instance using the imported AppRoutes array
const router = createRouter({
    history: createWebHistory(),
    routes: AppRoutes, // Use the imported routes array here
});


// Example of a global navigation guard for access control
// router.beforeEach((to, from, next) => {
//     const requiresAuth = to.meta.requiresAuth;
//     const requiredRole = to.meta.role;
//     const userRole = 'admin'; // Replace with actual logic to get user's role from auth store

//     if (requiresAuth && !userRole) {
//         next({ name: 'auth.login' }); // Redirect to login if not authenticated
//     } else if (requiresAuth && requiredRole && userRole !== requiredRole) {
//         // Optionally redirect to a forbidden page or back home
//         next({ name: 'home' }); // Or a dedicated '/forbidden' page
//     } else {
//         next();
//     }
// });

app.use(router);

if (window.location.pathname === '/login') {
    const currentApp = createApp(Login);
    currentApp.mount('#login');
} else {
    app.mount('#app');
}
