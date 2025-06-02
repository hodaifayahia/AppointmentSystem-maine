<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from './../../Components/toster';
import DocumentPreview from '../../Components/Consultation/DocumentPreview.vue';

import { saveAs } from 'file-saver';
import PatientDocumentHistory from '../../Components/Consultation/PatientDocumentHistory.vue'; // Adjust path if needed

const toaster = useToastr();

// Props
const props = defineProps({
  consultationData: {
    type: Object,
    default: () => ({})
  },
  selectedTemplates: {
    type: Array,
    default: () => []
  }
});

// Emits
const emit = defineEmits(['update:selected-templates']);

// State
const folders = ref([]);
const templates = ref([]);
const selectedFolder = ref(null);
const loading = ref(false);
const error = ref(null);
const previewContent = ref('');
const previewMode = ref(true);
const documentContent = ref('');

// API methods
const getFolders = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/folders');
    folders.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load folders';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const getTemplatesByFolder = async (folderId) => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/templates`, {
      params: { folder_id: folderId }
    });
    templates.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load templates';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

// Folder and template selection
const onFolderSelect = () => {
  if (selectedFolder.value) {
    getTemplatesByFolder(selectedFolder.value.id);
  }
};

const onTemplateSelect = (event) => {
  const templateId = parseInt(event.target.value);
  if (templateId && !props.selectedTemplates.includes(templateId)) {
    const updatedTemplates = [...props.selectedTemplates, templateId];
    emit('update:selected-templates', updatedTemplates);
    generatePreview();
  }
};

// Preview generation
const generatePreview = async () => {
  if (props.selectedTemplates.length === 0) {
    previewContent.value = '<div class="premium-empty-state"><i class="fas fa-eye-slash fa-3x text-muted mb-3"></i><p>Please select at least one template to preview</p></div>';
    return;
  }
  
  try {
    loading.value = true;
    const selectedTemplateObjects = templates.value.filter(t => props.selectedTemplates.includes(t.id));
    
    if (selectedTemplateObjects.length === 0) {
      previewContent.value = '<div class="premium-empty-state"><i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i><p>Selected templates not found</p></div>';
      return;
    }
    
    let combinedContent = '';
    for (const template of selectedTemplateObjects) {
      let content = template.content;
      // Replace placeholders with actual values from consultationData
      Object.keys(props.consultationData).forEach(key => {
        const value = props.consultationData[key];
        if (value) {
          const regex = new RegExp(`(\\{\\{\\s*${key}\\s*\\}\\}|(?<!\\w)${key}(?!\\w))`, 'g');
          content = content.replace(regex, value);
        }
      });
      
      combinedContent += `<div class="premium-template-section">
        <h3 class="premium-template-title">${template.name}</h3>
        <div class="premium-template-content">${content}</div>
      </div>`;
    }
    
    previewContent.value = combinedContent;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to generate preview';
    toaster.error(error.value);
    previewContent.value = '<div class="premium-empty-state"><i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i><p>Error generating preview</p></div>';
  } finally {
    loading.value = false;
  }
};

// Edit mode functions
const toggleEditMode = () => {
  previewMode.value = !previewMode.value;
  if (!previewMode.value) {
    documentContent.value = previewContent.value;
  }
};

const saveEditedContent = () => {
  previewContent.value = documentContent.value;
  previewMode.value = true;
  toaster.success('Changes saved successfully');
};

// Watchers
watch([() => props.consultationData, () => props.selectedTemplates], () => {
  generatePreview();
});

onMounted(() => {
  getFolders();
});
</script>

<template>
  <div class="premium-tab-content">
    <div class="premium-section-title">Template Selection</div>
    <div class="premium-form">
      <div class="premium-form-group mb-4">
        <label class="premium-label">Select Folder</label>
        <select class="premium-select" v-model="selectedFolder" @change="onFolderSelect">
          <option value="" disabled selected>Choose a folder</option>
          <option v-for="folder in folders" :key="folder.id" :value="folder">
            {{ folder.name }}
          </option>
        </select>
      </div>
      
      <div v-if="selectedFolder" class="premium-form-group mb-4">
        <label class="premium-label">Select Template</label>
        <select class="premium-select" @change="onTemplateSelect($event)">
          <option value="" disabled selected>Choose a template</option>
          <option v-for="template in templates" :key="template.id" :value="template.id">
            {{ template.name }}
          </option>
        </select>
      </div>

      <div class="premium-document-preview-section">
        <div class="premium-section-subtitle">Document Preview</div>
        <document-preview
          :preview-content="previewContent"
          :loading="loading"
          :selected-templates="selectedTemplates"
          @update:preview-content="newContent => previewContent = newContent"
          @refresh="generatePreview"
        />
      </div>

      <PatientDocumentHistory :patient-id="props.patientId"/>
    </div>
  </div>
</template>

<style scoped>
/* Keep your existing styles and add new ones for the history section */
.premium-tab-content {
  padding: 1.5rem;
  background-color: #f9fafb;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.premium-section-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 1.5rem;
  text-align: center;
  position: relative;
}

.premium-section-title::after {
  content: '';
  display: block;
  width: 60px;
  height: 3px;
  background-color: #3a5bb1;
  margin: 10px auto 0;
  border-radius: 2px;
}

.premium-section-subtitle {
overflow: hidden;
  font-size: 1.25rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.premium-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.premium-form-group {
  margin-bottom: 1.25rem;
}

.premium-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #334155;
  font-size: 0.875rem;
}

.premium-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  font-size: 0.875rem;
  transition: all 0.3s;
}

.premium-select:focus {
  outline: none;
  border-color: #3a5bb1;
  box-shadow: 0 0 0 3px rgba(58, 91, 177, 0.1);
}

.premium-preview-actions {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.premium-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
  border: none;
}

.premium-btn-secondary {
  background-color: #e0e7f2;
  color: #3a5bb1;
  border: 1px solid #a7c5ed;
}

.premium-btn-secondary:hover {
  background-color: #c7d8ed;
  color: #2d4a9e;
  border-color: #7b9ed9;
}

.premium-btn-primary {
  background-color: #3a5bb1;
  color: white;
}

.premium-btn-primary:hover {
  background-color: #2d4a9e;
}

.premium-generate-btn {
  background-color: #3a5bb1;
  color: white;
  border: 1px solid #3a5bb1;
}
.premium-generate-btn:hover {
  background-color: #2d4a9e;
  border-color: #2d4a9e;
}
.premium-generate-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>