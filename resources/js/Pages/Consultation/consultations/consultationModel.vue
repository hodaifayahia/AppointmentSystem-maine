<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToastr } from '../../../Components/toster';
import { useSweetAlert } from '../../../Components/useSweetAlert';
import PlaceholderTab from '../../../Components/Consultation/PlaceholderTab.vue';
import TemplatesTab from '../../../Components/Consultation/TemplatesTab.vue';
import AllergiesTab from '../../../Components/Allergies/AllergiesTab.vue';
// Import the new PrescriptionTab component
import PrescriptionTab from '../../../Pages/Consultation/Prescription/PrescriptionTab.vue'; 

const router = useRouter();
const swal = useSweetAlert();
const toaster = useToastr();

// State
const activeTab = ref('placeholders');
const consultationData = ref({});
const selectedTemplates = ref([]);
const loading = ref(false);
const props = defineProps({
  patientId: {
    type: [Number, String],
    required: true
  }
});
const appointmentId = ref(1);

// Tab management
// Update tabs array to include the new tab
const tabs = [
  { id: 'placeholders', label: 'Placeholders', icon: 'fas fa-puzzle-piece' },
  { id: 'Allergies', label: 'Allergies', icon: 'fas fa-stethoscope' },
  { id: 'Templates', label: 'Templates selection', icon: 'fas fa-file-alt' },
  { id: 'prescription', label: 'Prescription', icon: 'fas fa-prescription ' }, // New tab
];

const setActiveTab = (tabId) => {
  activeTab.value = tabId;
};

const goToNextTab = () => {
  const currentIndex = tabs.findIndex(tab => tab.id === activeTab.value);
  if (currentIndex < tabs.length - 1) {
    activeTab.value = tabs[currentIndex + 1].id;
  }
};

const goToAddConsulationPage = () => {
  goToNextTab();
};

// Handle data updates from child components
const updateConsultationData = (newData) => {
  consultationData.value = { ...consultationData.value, ...newData };
};

const updateSelectedTemplates = (templates) => {
  selectedTemplates.value = templates;
};

onMounted(() => {
  // Initial setup if needed
});
</script>

<template>
  <div class="premium-placeholders-page">
    <div class="premium-container">
      <div class="premium-header">
        <h2 class="premium-title">Consultation</h2>
        <button 
          class="btn-premium-outline" 
          @click="goToAddConsulationPage"
        >
          <i class="fas fa-arrow-right me-2"></i>Next
        </button>
      </div>

      <div class="premium-tabs">
        <div 
          v-for="tab in tabs" 
          :key="tab.id"
          :class="['premium-tab', { active: activeTab === tab.id }]"
          @click="setActiveTab(tab.id)"
        >
          <i :class="[tab.icon, 'me-2']"></i>
          <span>{{ tab.label }}</span>
        </div>
      </div>

      <div class="premium-card">
        <PlaceholderTab
          v-if="activeTab === 'placeholders'"
          :consultation-data="consultationData"
          @update:consultation-data="updateConsultationData"
        />
          <AllergiesTab
          v-if="activeTab === 'Allergies'"
          :consultation-data="consultationData"
          :patient-id="patientId"
          @update:consultation-data="updateConsultationData"
        />

        <TemplatesTab
          v-if="activeTab === 'Templates'"
          :consultation-data="consultationData"
          :selected-templates="selectedTemplates"
          @update:selected-templates="updateSelectedTemplates"
        />

        <PrescriptionTab
          v-if="activeTab === 'prescription'"
          :consultation-data="consultationData"
          :patient-id="patientId"
          :appointmentId="appointmentId"
          @update:consultation-data="updateConsultationData"
        />

      </div>
    </div>
  </div>
</template>

<style scoped>
.premium-placeholders-page {
  padding: 2rem;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f0 100%);
  min-height: 100vh;
  width: 100%;
}

.premium-container {
  max-width: 100%;
  margin: 0 auto;
}

.premium-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  width: 100%;
}

.premium-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.premium-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.btn-premium-outline {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  transition: all 0.4s;
  background: transparent;
  border: 1px solid #3a5bb1;
  color: #3a5bb1;
}

.btn-premium-outline:hover {
  background: #3a5bb1;
  color: white;
}

.premium-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  overflow-x: auto;
  padding-bottom: 0.5rem;
}

.premium-tab {
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  background-color: #f1f5f9;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: all 0.3s;
  white-space: nowrap;
}

.premium-tab:hover {
  background-color: #e2e8f0;
  color: #334155;
}

.premium-tab.active {
  background-color: #3a5bb1;
  color: white;
}

@media (max-width: 768px) {
  .premium-placeholders-page {
    padding: 1rem;
  }

  .premium-title {
    font-size: 1.5rem;
  }

  .premium-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .btn-premium-outline {
    width: 100%;
    justify-content: center;
  }
}
</style>