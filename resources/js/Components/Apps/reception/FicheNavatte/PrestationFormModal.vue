<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue components
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import MultiSelect from 'primevue/multiselect'

// Services
import ficheNavetteService from '../../../../Components/Apps/services/Recption/ficheNavetteService'

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  ficheId: {
    type: Number,
    required: true
  },
  allPrestations: {
    type: Array,
    required: true
  },
  existingPrestationItems: {
    type: Array,
    default: () => []
  },
  prestationToEdit: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['update:visible', 'prestation-saved'])

// Composables
const toast = useToast()

// Reactive data
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const form = reactive({
  prestation_id: null,
  appointment_id: null,
  status: 'pending',
  base_price: 0,
  final_price: 0,
  patient_share: 0,
  doctor_share: 0,
  doctor_id: null,
  modality_id: null,
  dependency_prestation_ids: []
})

// Status options
const statusOptions = [
  { label: 'En attente', value: 'pending', severity: 'warning' },
  { label: 'En cours', value: 'in_progress', severity: 'info' },
  { label: 'Terminé', value: 'completed', severity: 'success' },
  { label: 'Annulé', value: 'cancelled', severity: 'danger' },
  { label: 'Requis', value: 'required', severity: 'secondary' }
]

// Computed properties
const modalHeader = computed(() => props.prestationToEdit ? 'Modifier la prestation' : 'Ajouter une Prestation')

const availableDependencies = computed(() => {
  const currentPrestationId = form.prestation_id
  const existingPrestationIds = props.existingPrestationItems.map(item => item.prestation_id)
  
  return props.allPrestations.filter(p => 
    p.id !== currentPrestationId && 
    !existingPrestationIds.includes(p.id)
  )
})

// Methods
const resetForm = () => {
  Object.assign(form, {
    prestation_id: null,
    appointment_id: null,
    status: 'pending',
    base_price: 0,
    final_price: 0,
    patient_share: 0,
    doctor_share: 0,
    doctor_id: null,
    modality_id: null,
    dependency_prestation_ids: []
  })
}

const savePrestation = async () => {
  if (!form.prestation_id) {
    toast.add({
      severity: 'warn',
      summary: 'Attention',
      detail: 'Veuillez sélectionner une prestation',
      life: 3000
    })
    return
  }
  
  try {
    let result
    if (props.prestationToEdit) {
      // Logic for editing (not fully implemented in the original code, but this is where it would go)
      result = await ficheNavetteService.updatePrestation(
        props.ficheId, 
        props.prestationToEdit.id, 
        form
      )
    } else {
      result = await ficheNavetteService.addPrestation(props.ficheId, form)
    }
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: `Prestation ${props.prestationToEdit ? 'mise à jour' : 'ajoutée'} avec succès`,
        life: 3000
      })
      emit('prestation-saved', result.data.fiche)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: result.message,
        life: 3000
      })
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: `Erreur lors de l'opération`,
      life: 3000
    })
  }
}

// Watchers
watch(dialogVisible, (newVal) => {
  if (newVal) {
    // Populate form for editing, or reset for adding
    if (props.prestationToEdit) {
      // This is a placeholder; you would need to populate the form from `props.prestationToEdit`
      Object.assign(form, {
        prestation_id: props.prestationToEdit.prestation_id,
        status: props.prestationToEdit.status,
        base_price: props.prestationToEdit.base_price,
        final_price: props.prestationToEdit.final_price,
        patient_share: props.prestationToEdit.patient_share,
        // ... and so on for other fields
      })
    } else {
      resetForm()
    }
  }
})
</script>

<template>
  <Dialog 
    v-model:visible="dialogVisible"
    :header="modalHeader"
    :style="{ width: '600px' }"
    :modal="true"
    class="add-prestation-modal"
  >
    <div class="add-form">
      <div class="field">
        <label for="prestation">Prestation *</label>
        <Dropdown 
          id="prestation"
          v-model="form.prestation_id"
          :options="allPrestations"
          optionLabel="name"
          optionValue="id"
          placeholder="Sélectionner une prestation"
          class="w-full"
          filter
          showClear
          :disabled="!!prestationToEdit"
        />
      </div>

      <div class="field">
        <label for="status">Statut</label>
        <Dropdown 
          id="status"
          v-model="form.status"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Sélectionner un statut"
          class="w-full"
        />
      </div>

      <div class="form-grid">
        <div class="field">
          <label for="base-price">Prix de base</label>
          <InputNumber 
            id="base-price"
            v-model="form.base_price"
            mode="currency"
            currency="DZD"
            locale="fr-FR"
            class="w-full"
          />
        </div>

        <div class="field">
          <label for="final-price">Prix final</label>
          <InputNumber 
            id="final-price"
            v-model="form.final_price"
            mode="currency"
            currency="DZD"
            locale="fr-FR"
            class="w-full"
          />
        </div>
      </div>

      <div class="field" v-if="!prestationToEdit">
        <label for="dependencies">Dépendances</label>
        <MultiSelect 
          id="dependencies"
          v-model="form.dependency_prestation_ids"
          :options="availableDependencies"
          optionLabel="name"
          optionValue="id"
          placeholder="Sélectionner les dépendances"
          class="w-full"
          filter
        />
        <small class="help-text">
          <i class="pi pi-info-circle mr-1"></i>
          Les prestations sélectionnées seront automatiquement ajoutées comme requises
        </small>
      </div>
    </div>

    <template #footer>
      <div class="modal-footer">
        <Button 
          label="Annuler" 
          icon="pi pi-times" 
          class="p-button-text"
          @click="emit('update:visible', false)"
        />
        <Button 
          :label="prestationToEdit ? 'Mettre à jour' : 'Ajouter'"
          :icon="prestationToEdit ? 'pi pi-check' : 'pi pi-plus'"
          class="p-button-primary"
          @click="savePrestation"
          :disabled="!form.prestation_id"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.add-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field label {
  font-weight: 600;
  color: var(--text-color);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.help-text {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
  display: flex;
  align-items: center;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-footer {
    flex-direction: column-reverse;
  }
  
  .modal-footer button {
    width: 100%;
  }
}
</style>