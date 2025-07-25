<script setup>
import { defineProps, defineEmits } from 'vue';
import { useRouter } from 'vue-router';
import { useToastr } from '../../../Components/toster';

const router = useRouter();
const toaster = useToastr();

const props = defineProps({
  template: Object,
  index: Number,
  folderid:Number,
  currentDoctorId:Number,
});

const emit = defineEmits(['edit', 'delete']);

const handleEdit = () => {
  console.log('Editing template:', props.template);
  router.push({
    name: 'admin.consultation.template.edit',
    params: { 
      id: props.template.id ,
      folderid:props.folderid,
      doctor_id:props.currentDoctorId,
    },
    query: { 
      name: props.template.name,
      action: 'edit'
    }
  });
};

const handleDelete = () => {
  emit('delete', props.template.id, props.template.name);
};

// Helper to determine badge color based on MIME type
const getMimeTypeBadgeClass = (mimeType) => {
  if (!mimeType) return 'bg-secondary';
  
  switch (mimeType.toLowerCase()) {
    case 'text/html':
      return 'bg-primary';
    case 'text/plain':
      return 'bg-info';
    case 'application/pdf':
      return 'bg-danger';
    case 'application/msword':
      return 'bg-success';
    default:
      return 'bg-secondary';
  }
};


</script>

<template>
  <tr @click="handleEdit" class="template-row">
    <td class="ps-4">{{ index + 1 }}</td>
    <td>
      <div class="d-flex align-items-center">
        <i class="fas fa-file-alt me-2 text-primary"></i>
        <strong>{{ template.name }}</strong>
      </div>
    </td>
    <td>
      <span v-if="template.description" class="description-text">{{ template.description }}</span>
      <span v-else class="text-muted fst-italic">No description</span>
    </td>
    <!-- <td>
      <div v-if="template.doctor" class="d-flex align-items-center">
        <i class="fas fa-user-md me-1 text-secondary"></i>
        {{ template.doctor.name }}
      </div>
      <span v-else class="text-muted fst-italic">No Doctor</span>
    </td> -->
    <td>
      <span 
        v-if="template.mime_type" 
        class="badge rounded-pill"
      >
        {{ (template.mime_type == 'Consultation' ? 'A4' : template.mime_type) }}
      </span>
      <span v-else class="badge rounded-pill bg-secondary">Unknown</span>
    </td>
    <td>
      <div class="btn-group d-flex justify-content-center">
        <button 
          class="btn btn-sm btn-outline-primary action-btn" 
          @click.stop="handleEdit" 
          title="Edit Template"
        >
          <i class="fas fa-edit"></i>
        </button>
        <button 
          class="btn btn-sm btn-outline-danger action-btn" 
          @click.stop="handleDelete" 
          title="Delete Template"
        >
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>
    </td>
  </tr>
</template>

<style scoped>
.template-row {
  cursor: pointer;
  transition: background-color 0.2s;
}

.template-row:hover {
  background-color: rgba(0, 123, 255, 0.05) !important;
}

.description-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-width: 300px;
}

.btn-group {
  display: flex;
  gap: 0.25rem;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border-radius: 0.25rem;
  transition: all 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>