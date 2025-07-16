<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
// No PrimeVue imports needed for this component anymore
// import TabView from "primevue/tabview";
// import TabPanel from "primevue/tabpanel";

import Agreement_table from '../tables/Agreement_table.vue';
import Agreement_para_table from '../tables/Agreement_para_table.vue';
import Annex_table from '../tables/Annex_table.vue';
import Avenant_table from '../tables/Avenant_table.vue';

const props = defineProps({
 contract:{
  type:Object,
  required:true,
 },
 
});

// Use a ref to control which tab is active
const activeTab = ref('agreement'); // 'agreement', 'configurableAgreement', 'annex', 'avenant'

// Method to change the active tab
const selectTab = (tabName) => {
  activeTab.value = tabName;
};

// Ensure the correct tab is active on mount if initial state is not 'agreement'
// Or if you want to handle potential deep links or pre-selected tabs
onMounted(() => {
  // You could add logic here to set initial activeTab based on props or route
  // For simplicity, it defaults to 'agreement'
});

</script>

<template>
  <section class="mt-4">
    <div class="card">
      <div class="card-header p-0">
        <ul class="nav nav-tabs" id="contractContentTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ 'active': activeTab === 'agreement' }"
              @click="selectTab('agreement')"
              id="agreement-tab"
              data-bs-toggle="tab"
              data-bs-target="#agreement"
              type="button"
              role="tab"
              aria-controls="agreement"
              :aria-selected="activeTab === 'agreement' ? 'true' : 'false'"
            >
              Agreement
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ 'active': activeTab === 'configurableAgreement' }"
              @click="selectTab('configurableAgreement')"
              id="configurable-agreement-tab"
              data-bs-toggle="tab"
              data-bs-target="#configurableAgreement"
              type="button"
              role="tab"
              aria-controls="configurableAgreement"
              :aria-selected="activeTab === 'configurableAgreement' ? 'true' : 'false'"
            >
              Configurable Agreement
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ 'active': activeTab === 'annex' }"
              @click="selectTab('annex')"
              id="annex-tab"
              data-bs-toggle="tab"
              data-bs-target="#annex"
              type="button"
              role="tab"
              aria-controls="annex"
              :aria-selected="activeTab === 'annex' ? 'true' : 'false'"
            >
              Annex
            </button>
          </li>
          <li
            class="nav-item"
            role="presentation"
            v-if="contractState === 'Active' || contractState === 'Expired'"
          >
            <button
              class="nav-link"
              :class="{ 'active': activeTab === 'avenant' }"
              @click="selectTab('avenant')"
              id="avenant-tab"
              data-bs-toggle="tab"
              data-bs-target="#avenant"
              type="button"
              role="tab"
              aria-controls="avenant"
              :aria-selected="activeTab === 'avenant' ? 'true' : 'false'"
            >
              Avenant
            </button>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content" id="contractContentTabContent">
          <div
            class="tab-pane fade"
            :class="{ 'show active': activeTab === 'agreement' }"
            id="agreement"
            role="tabpanel"
            aria-labelledby="agreement-tab"
          >
            <Agreement_table :contractState="contract.status" :contractid="contract.id" />
          </div>

          <div
            class="tab-pane fade"
            :class="{ 'show active': activeTab === 'configurableAgreement' }"
            id="configurableAgreement"
            role="tabpanel"
            aria-labelledby="configurable-agreement-tab"
          >
            <Agreement_para_table :contractState="contract.status" :avenantpage="'no'" :contractid="contract.id" />
          </div>

          <div
            class="tab-pane fade"
            :class="{ 'show active': activeTab === 'annex' }"
            id="annex"
            role="tabpanel"
            aria-labelledby="annex-tab"
          >
            <Annex_table :contractState="contract.status" :contractId="contract.id" :isgeneral="false" />
          </div>

          <div
            class="tab-pane fade"
            :class="{ 'show active': activeTab === 'avenant' }"
            id="avenant"
            role="tabpanel"
            aria-labelledby="avenant-tab"
            v-if="contract.status === 'Active' || contract.status === 'Expired'"
          >
            <Avenant_table :contractState="contract.status" :contractid="contract.id" />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Optional: Custom styles to match your theme or override Bootstrap defaults */
.nav-tabs .nav-link {
  color: #495057; /* Default tab text color */
  border: 1px solid transparent;
  border-top-left-radius: 0.25rem;
  border-top-right-radius: 0.25rem;
  padding: 0.75rem 1.25rem;
  margin-bottom: -1px; /* Overlap border with content */
}

.nav-tabs .nav-link.active {
  color: #007bff; /* Active tab text color (Bootstrap primary) */
  background-color: #fff;
  border-color: #dee2e6 #dee2e6 #fff; /* Bottom border matches content background */
}

.card-header {
  border-bottom: 0; /* Remove default card-header border to blend with tabs */
}

/* Ensure tab content padding and look is good */
.tab-content {
  padding-top: 1rem; /* Adjust if needed */
}

.card {
  border-radius: 0.75rem; /* Match other cards */
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}
</style>