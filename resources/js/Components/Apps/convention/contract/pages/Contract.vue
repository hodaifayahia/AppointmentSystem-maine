<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Contract_card from '../cards/Contract_card.vue';
import Contract_content_tab from '../tabs/Contract_content_tab.vue';

import { useToastr } from '../../../../toster'; 


import axios from 'axios';
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

// Get the route and router objects
const route = useRoute();
// const router = useRouter();
const toast = useToastr(); // Use the toastr instance

// Get contract ID from route params as string
const contractId = route.params.id


// Contract data with reactive reference
const contract = ref({
    id: contractId,
    contract_name: '',
    status: 'pending',
    company_name: '',
    is_general: 'no'
});

// Date variables (these are not directly used in template, but kept for data fetching)
const startDate = ref('');
const endDate = ref('');

// Loading state for buttons
const loading = ref({
    activate: false,
    terminate: false
});

// Function to fetch contract data
const fetchContractData = async () => {
    try {
        const response = await axios.get(`/api/conventions/${contractId}`);
        
        if (response.data) {
            contract.value = {
                ...response.data.data,
                id: contractId // Ensure ID is always a string
            };
            
            // Set the date variables
            startDate.value = response.data.data.start_date;
            endDate.value = response.data.data.end_date;
        }
    } catch (error) {
        console.error('Error fetching contract data:', error);
        toast.error('Failed to load contract data'); // Using toastr.error
    }
};

// Fetch data when component is mounted
onMounted(() => {
    fetchContractData();
    
});

// Function to activate the contract
const activateContract = async () => {
    try {
        loading.value.activate = true;
        await axios.patch(`${API_BASE_URL}/api/convention/contracts/contract/${contractId}/activate`);
        
        // Update the contract status locally
        contract.value.status = 'Active';
        
        // Show success toast
        toast.success('The contract has been successfully activated.'); // Using toastr.success
        
        // Refresh data
        fetchContractData();
    } catch (error) {
        toast.error(error.response?.data?.message || 'An error occurred during activation.'); // Using toastr.error
    } finally {
        loading.value.activate = false;
    }
};

// Function to terminate the contract
const terminateContract = async () => {
    try {
        loading.value.terminate = true;
        await axios.patch(`${API_BASE_URL}/api/convention/contracts/contract/${contractId}/expire`);
        
        // Update the contract status locally
        contract.value.status = 'Terminated';
        
        // Show success toast
        toast.success('The contract has been successfully terminated.'); // Using toastr.success
        
        // Refresh data
        fetchContractData();
    } catch (error) {
        toast.error(error.response?.data?.message || 'An error occurred during termination.'); // Using toastr.error
    } finally {
        loading.value.terminate = false;
    }
};

// Function to confirm activation (using native confirm)
const confirmActivate = () => {
    if (window.confirm('Are you sure you want to activate this contract?')) {
        activateContract();
    }
};

// Function to confirm termination (using native confirm)
const confirmTerminate = () => {
    if (window.confirm('Are you sure you want to terminate this contract? This action cannot be undone.')) {
        terminateContract();
    }
};
</script>

<template>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0 text-primary">Contract Details</h4>
                            <div class="d-flex gap-2">
                                <button
                                    v-if="contract.status === 'pending'"
                                    class="btn btn-success"
                                    :disabled="loading.activate"
                                    @click="confirmActivate"
                                >
                                    <span v-if="loading.activate" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <i v-else class="fas fa-check me-1"></i>
                                    {{ loading.activate ? 'Activating...' : 'Activate Contract' }}
                                </button>

                                <button
                                    v-if="contract.status === 'Active' || contract.status === 'pending'"
                                    class="btn btn-danger"
                                    :disabled="loading.terminate"
                                    @click="confirmTerminate"
                                >
                                    <span v-if="loading.terminate" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <i v-else class="fas fa-times me-1"></i>
                                    {{ loading.terminate ? 'Terminating...' : 'Terminate Contract' }}
                                </button>
                            </div>
                        </div>
                        
                        <Contract_card :contract="contract" />

                        <Contract_content_tab :contract="contract" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* These styles should work with Bootstrap */
.card {
    border-radius: 0.75rem;
}

.text-primary {
    color: #007bff !important; /* Example primary color, adjust to your theme */
}

/* Specific styles for Bootstrap buttons for consistency with previous PrimeVue styles */
.btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

/* Adding gap for buttons if not handled by Bootstrap's d-flex gap-2 utility */
.d-flex.gap-2 > .btn {
    margin-right: 0.5rem; /* Adjust as needed */
}

.d-flex.gap-2 > .btn:last-child {
    margin-right: 0;
}
</style>