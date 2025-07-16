<script setup>
import { computed, ref } from 'vue';
import BaseSidebar from '../Sidebars/BaseSidebar.vue'; // Adjust path if necessary
import { useAuthStore } from '../../../stores/auth'; // Assuming auth store path

const authStore = useAuthStore();
const user = computed(() => authStore.user);

const appDetails = {
    name: 'Clinic Configuration',
    icon: 'fas fa-solid fa-gear', // Using a Font Awesome icon directly
    color: '#', // Example color
    backRoute: '/home'
};

// State for managing dropdowns
const isUserAccessManagementOpen = ref(false);
const isConventionManagementOpen = ref(false); // New
const isFacturationBillingOpen = ref(false);   // New
const isClinicServicesResourcesOpen = ref(false); // New
const isSystemAdministrationOpen = ref(false); // New

// Toggle functions for new dropdowns
const toggleUserAccessManagement = () => { isUserAccessManagementOpen.value = !isUserAccessManagementOpen.value; };
const toggleConventionManagement = () => { isConventionManagementOpen.value = !isConventionManagementOpen.value; };
const toggleFacturationBilling = () => { isFacturationBillingOpen.value = !isFacturationBillingOpen.value; };
const toggleClinicServicesResources = () => { isClinicServicesResourcesOpen.value = !isClinicServicesResourcesOpen.value; };
const toggleSystemAdministration = () => { isSystemAdministrationOpen.value = !isSystemAdministrationOpen.value; };


const hasPermission = (requiredRoles) => {
    if (!user.value || !user.value.role) return false;
    const userRole = user.value.role.toLowerCase();
    const rolesArray = Array.isArray(requiredRoles) ? requiredRoles.map(r => r.toLowerCase()) : [requiredRoles.toLowerCase()];
    return rolesArray.includes(userRole);
};
</script>

<template>
    <BaseSidebar
        :user="authStore.user"
        :app-name="appDetails.name"
        :app-icon="appDetails.icon"
        :app-color="appDetails.color"
        :back-route="appDetails.backRoute"
    >
        <template #navigation>
             <li class="nav-item">
                        <router-link to="/convention/Dashborad" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Dashboard</p>
                        </router-link>
                    </li>
            <!-- Convention Management -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isConventionManagementOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleConventionManagement">
                    <i class="nav-icon fas fa-file-contract"></i> <!-- Icon for contracts/conventions -->
                    <p>
                        Convention Management
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isConventionManagementOpen">
                    <li class="nav-item" >
                        <router-link to="/convention/organismes" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Corporate Partners</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/convention/agreements" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Convention Agreements</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/convention/avenants" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Avenants (Amendments)</p>
                        </router-link>
                    </li>
                     <li class="nav-item" >
                        <router-link to="/admin/convention/rule-definitions" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Rule Definitions</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- Facturation & Billing -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isFacturationBillingOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleFacturationBilling">
                    <i class="nav-icon fas fa-money-check-alt"></i> <!-- Icon for billing/invoicing -->
                    <p>
                        Facturation & Billing
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isFacturationBillingOpen">
                    <li class="nav-item" >
                        <router-link to="/admin/billing/proformas" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Proforma Management</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/billing/b2b-invoices" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>B2B Invoices</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/billing/private-invoices" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Private Patient Invoices</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/billing/credit-notes" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Credit Notes</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/billing/payment-reconciliation" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Payment Reconciliation</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- Clinic Services & Resources -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isClinicServicesResourcesOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleClinicServicesResources">
                    <i class="nav-icon fas fa-boxes"></i> <!-- Icon for services/inventory -->
                    <p>
                        Clinic Services & Resources
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isClinicServicesResourcesOpen">
                    <li class="nav-item" >
                        <router-link to="/admin/services/catalog" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Service Catalog (Prestations)</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/services/modalities" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Modalities List</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/services/modality-types" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Modality Types</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/services/resource-scheduling" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Resource Scheduling</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/services/service-bundles" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Service Bundles</p>
                        </router-link>
                    </li>
                </ul>
            </li>

            <!-- System Administration -->
            <li class="nav-item has-treeview" :class="{ 'menu-is-opening menu-open': isSystemAdministrationOpen }">
                <a href="#" class="nav-link" @click.prevent="toggleSystemAdministration">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>
                        System Administration
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" v-show="isSystemAdministrationOpen">
                    <li class="nav-item" >
                        <router-link to="/admin/system/settings" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>System Settings</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/system/document-archive" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Document Archive & Search</p>
                        </router-link>
                    </li>
                    <li class="nav-item" >
                        <router-link to="/admin/system/analytics" active-class="active" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Analytics & Dashboards</p>
                        </router-link>
                    </li>
                </ul>
            </li>
        </template>
    </BaseSidebar>
</template>

<style scoped>
/* No specific styles needed here as BaseSidebar handles most of it */
/* Any overrides or specific layout for this sidebar would go here */
</style>
