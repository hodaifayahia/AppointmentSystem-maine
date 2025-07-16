<script setup>
import { ref, computed , defineProps } from "vue";
// Removed PrimeVue TabView and TabPanel imports
import Contract_table from '../tables/Contract_table.vue';
import Contacts_table from '../tables/Contacts_table.vue';

const props = defineProps({
  companyId: String,
});

// Reactive state to manage the active tab
const activeTab = ref('contracts'); // 'contracts' or 'contacts'

// Function to set the active tab
const setActiveTab = (tabName) => {
  activeTab.value = tabName;
};
</script>

<template>
  <section class="tab-section">
    <div class="card shadow-sm">
      <div class="card-header p-0 border-bottom-0">
        <!-- Bootstrap Tabs Navigation -->
        <ul class="nav nav-tabs custom-nav-tabs" id="myTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ 'active': activeTab === 'contracts' }"
              @click="setActiveTab('contracts')"
              type="button"
              role="tab"
              aria-controls="contracts-tab-pane"
              :aria-selected="activeTab === 'contracts'"
            >
              Contracts
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ 'active': activeTab === 'contacts' }"
              @click="setActiveTab('contacts')"
              type="button"
              role="tab"
              aria-controls="contacts-tab-pane"
              :aria-selected="activeTab === 'contacts'"
            >
              Contacts
            </button>
          </li>
        </ul>
      </div>
      <div class="card-body p-4">
        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
          <div
            class="tab-pane fade"
            :class="{ 'show active': activeTab === 'contracts' }"
            id="contracts-tab-pane"
            role="tabpanel"
            aria-labelledby="contracts-tab"
            tabindex="0"
          >
            <Contract_table :companyId="props.companyId"/>
          </div>
          <div
            class="tab-pane fade"
            :class="{ 'show active': activeTab === 'contacts' }"
            id="contacts-tab-pane"
            role="tabpanel"
            aria-labelledby="contacts-tab"
            tabindex="0"
          >
            <Contacts_table :companyId="props.companyId" />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Base Section and Card Styles */
.tab-section {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: calc(100vh - 60px); /* Adjust based on your header/footer height */
}

.card {
  background: #ffffff;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.card-header {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
}

/* Custom Tabs Navigation */
.custom-nav-tabs {
  border-bottom: none; /* Remove default Bootstrap border */
  padding: 0 1.5rem; /* Add some padding to align with card body */
}

.custom-nav-tabs .nav-item {
  margin-bottom: -1px; /* To prevent double border effect */
}

.custom-nav-tabs .nav-link {
  border: 1px solid transparent;
  border-top-left-radius: 0.75rem;
  border-top-right-radius: 0.75rem;
  color: #64748b;
  font-weight: 600;
  padding: 1rem 1.5rem;
  transition: all 0.3s ease;
  background-color: transparent;
  position: relative;
  overflow: hidden;
}

.custom-nav-tabs .nav-link::before {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
  transform: scaleX(0);
  transform-origin: bottom right;
  transition: transform 0.3s ease-out;
}

.custom-nav-tabs .nav-link.active {
  color: #1e293b;
  background-color: #ffffff;
  border-color: #e2e8f0 #e2e8f0 #ffffff;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
  font-weight: 700;
}

.custom-nav-tabs .nav-link.active::before {
  transform: scaleX(1);
  transform-origin: bottom left;
}

.custom-nav-tabs .nav-link:not(.active):hover {
  color: #3b82f6;
  background-color: #f8fafc;
}

.tab-content {
  padding-top: 1rem; /* Space between tabs and content */
}

/* Ensure tab panes fill available width */
.tab-pane {
  width: 100%;
}
</style>
