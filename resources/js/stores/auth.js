import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const user = ref({
        name: '',
        role: '',
        email: '',
        avatar: '',
        phone: '',
    });
    const isLoading = ref(true);

    const getUser = async () => {
        try {
            const response = await axios.get('/api/setting/user');
            user.value = response.data.data;
            isLoading.value = false;
        } catch (error) {
            // If 401 (unauthenticated), clear user and set loading to false
            if (error.response && error.response.status === 401) {
                user.value = { name: '', role: '', email: '', avatar: '', phone: '' };
                isLoading.value = false;
                throw error; // Throw the error to be handled by the router.beforeEach guard
            }
            isLoading.value = false;
            throw error; // Re-throw to allow router.beforeEach to catch it for redirects
        }
    };

    const clearUser = () => {
        user.value = { name: '', role: '', email: '', avatar: '', phone: '' };
    };

    return { user, isLoading, getUser };
});