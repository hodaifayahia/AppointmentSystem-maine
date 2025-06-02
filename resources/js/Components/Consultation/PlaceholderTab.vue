<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';

const toaster = useToastr();

// Props
const props = defineProps({
  consultationData: {
    type: Object,
    default: () => ({})
  }
});

// Emits
const emit = defineEmits(['update:consultation-data']);

// State
const placeholders = ref([]);
const attributes = ref({});
const loading = ref(false);
const error = ref(null);
const selectedPlaceholder = ref(null);

// API methods
const getPlaceholders = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axios.get('/api/placeholders');
    placeholders.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load placeholders';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const getAttributes = async (placeholderId) => {
  try {
    if (!placeholderId) return;
    
    const loadingState = ref(true);
    attributes.value = {
      ...attributes.value,
      [placeholderId]: { loading: loadingState }
    };

    const response = await axios.get(`/api/attributes/${placeholderId}`);
    attributes.value[placeholderId] = response.data.data || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load attributes';
    toaster.error(error.value);
    attributes.value[placeholderId] = [];
  } finally {
    loading.value = false;
  }
};

const togglePlaceholder = async (placeholder) => {
  if (selectedPlaceholder.value && selectedPlaceholder.value.id === placeholder.id) {
    selectedPlaceholder.value = null;
  } else {
    selectedPlaceholder.value = placeholder;
    if (!attributes.value[placeholder.id]) {
      await getAttributes(placeholder.id);
    }
  }
};

// Update consultation data when input changes
const updateConsultationData = (key, value) => {
  const updatedData = { ...props.consultationData, [key]: value };
  emit('update:consultation-data', updatedData);
};

onMounted(() => {
  getPlaceholders();
});
</script>

<template>
  <div class="placeholders-tab">
    <div v-if="loading && !placeholders.length" class="premium-loading-state">
      <div class="premium-spinner"></div>
    </div>

    <div v-if="error && !placeholders.length" class="premium-alert">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <div v-if="!loading && !placeholders.length" class="premium-empty-state">
      <i class="fas fa-puzzle-piece fa-3x text-muted mb-3"></i>
      <p>No placeholders found. Start by adding a new consultation.</p>
    </div>

    <div v-if="placeholders.length" class="premium-accordion">
      <div 
        v-for="placeholder in placeholders" 
        :key="placeholder.id" 
        class="premium-accordion-item"
      >
        <div 
          class="premium-accordion-header" 
          @click="togglePlaceholder(placeholder)"
        >
          <span class="premium-accordion-title">
            {{ placeholder.name || `Placeholder ${placeholder.id}` }}
          </span>
          <i :class="selectedPlaceholder?.id === placeholder.id ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
        </div>
        
        <transition name="fade">
          <div 
            v-if="selectedPlaceholder?.id === placeholder.id" 
            class="premium-accordion-body"
          >
            <div v-if="loading" class="premium-loading-state">
              <div class="premium-spinner small"></div>
              <p>Loading attributes...</p>
            </div>
            
            <div v-else-if="attributes[placeholder.id]?.length" class="premium-attributes-container">
              <!-- Input fields row -->
              <div class="premium-inputs-row">
                <div 
                  v-for="attribute in attributes[placeholder.id].filter(a => a.input_type !== 0)" 
                  :key="attribute.id" 
                  class="premium-form-group premium-input-item"
                >
                  <label class="premium-label">{{ attribute.name }}</label>
                  <input
                    class="premium-input"
                    :value="consultationData[placeholder.name + '.' + attribute.name] || ''"
                    @input="updateConsultationData(placeholder.name + '.' + attribute.name, $event.target.value)"
                    :placeholder="`Enter ${attribute.name} value`"
                  >
                </div>
              </div>

              <!-- Textarea fields row -->
              <div class="premium-textareas-row">
                <div 
                  v-for="attribute in attributes[placeholder.id].filter(a => a.input_type === 0)" 
                  :key="attribute.id" 
                  class="premium-form-group premium-textarea-item"
                >
                  <label class="premium-label">{{ attribute.name }}</label>
                  <textarea
                    class="premium-input-textarea"
                    :value="consultationData[placeholder.name + '_' + attribute.name] || ''"
                    @input="updateConsultationData(placeholder.name + '_' + attribute.name, $event.target.value)"
                    :placeholder="`Enter ${attribute.name} value`"
                  ></textarea>
                </div>
              </div>
            </div>
            
            <div v-else class="premium-no-data">
              No attributes found for this placeholder
            </div>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>

<style scoped>
.premium-form-group {
  margin-bottom: 1.25rem;
  margin-left: 1rem;
}

.premium-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #334155;
  font-size: 0.875rem;
}

.premium-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  font-size: 0.875rem;
  transition: all 0.3s;
}

.premium-input-textarea {
  width: 100%;
  min-height: 120px;
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #334155;
  transition: all 0.3s ease;
  resize: vertical;
}

.premium-textareas-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  width: 100%;
}

.premium-textarea-item {
  width: 100%;
  margin-left: 0;
}

.premium-input-textarea:focus {
  outline: none;
  border-color: #3a5bb1;
  box-shadow: 0 0 0 3px rgba(58, 91, 177, 0.1);
}

.premium-input-textarea::placeholder {
  color: #94a3b8;
  font-style: italic;
}

.premium-input-textarea:hover {
  border-color: #cbd5e1;
}

.premium-input:focus {
  outline: none;
  border-color: #3a5bb1;
  box-shadow: 0 0 0 3px rgba(58, 91, 177, 0.1);
}

.premium-accordion-item {
  border-bottom: 1px solid #e2e8f0;
}

.premium-accordion-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  transition: background-color 0.5s;
}

.premium-accordion-header:hover {
  background-color: #f8fafc;
}

.premium-accordion-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #334155;
}

.premium-accordion-body {
  padding: 1.5rem;
  background-color: #f8fafc;
}

.premium-loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
}

.premium-spinner {
  width: 2.5rem;
  height: 2.5rem;
  border: 3px solid #e2e8f0;
  border-top-color: #3a5bb1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

.premium-spinner.small {
  width: 1.5rem;
  height: 1.5rem;
}

.premium-empty-state {
  text-align: center;
  padding: 3rem;
  color: #64748b;
}

.premium-no-data {
  color: #64748b;
  font-style: italic;
  padding: 1rem;
}

.premium-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 1rem;
  border-radius: 8px;
  margin: 1.5rem;
  display: flex;
  align-items: center;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, max-height 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  max-height: 0;
}
</style>