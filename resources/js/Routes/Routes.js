import { createRouter, createWebHistory } from 'vue-router';

// Import your route modules
import publicRoutes from './public';
import adminRoutes from './admin';
import doctorRoutes from './doctor';
import configurationRoutes from './configuration'; // 👈 NEW IMPORT
import infrastructureRoutes from './infrastructure'; // 👈 NEW IMPORT
import convenationRoutes from './convenation'; // 👈 NEW IMPORT
import crmRoutes from './crm'; // 👈 NEW IMPORT
import TicketManagementRoutes from './ticket-management'; // 👈 NEW IMPORT



// Combine all route arrays
export const allRoutes = [
    ...publicRoutes,
    ...adminRoutes,
    ...doctorRoutes,
    ...configurationRoutes, // 👈 ADDED HERE
    ...infrastructureRoutes,
    ...convenationRoutes,
    ...crmRoutes,
    ...TicketManagementRoutes,



    // {
    //     path: '/:pathMatch(.*)*',
    //     name: 'NotFound',
    //     component: () => import('@/Pages/NotFound.vue'),
    // },
];

const router = createRouter({
    history: createWebHistory(),
    routes: allRoutes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        }
        return { left: 0, top: 0 };
    },
});

// Optional: Global Navigation Guards (Middleware)
router.beforeEach((to, from, next) => {
    // Example Auth Guard
    const isAuthenticated = localStorage.getItem('token'); // Replace with actual auth check
    const requiredAuth = to.meta.requiresAuth;

    if (requiredAuth && !isAuthenticated) {
        return next({ name: 'auth.login' });
    }

    // Example Role Guard
    const requiredRole = to.meta.role;
    if (requiredRole) {
        const userRole = localStorage.getItem('user_role'); // Replace with actual role logic
        if (Array.isArray(requiredRole) && !requiredRole.includes(userRole)) {
            return next({ name: 'home' }); // Or Forbidden page
        } else if (typeof requiredRole === 'string' && userRole !== requiredRole) {
            return next({ name: 'home' });
        }
    }

    next();
});

export default router;