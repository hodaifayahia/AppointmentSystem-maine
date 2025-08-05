<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import ScrollPanel from 'primevue/scrollpanel'
import Chip from 'primevue/chip'

import prestationService from '../../services/prestationService'
import ficheNavetteService from '../../services/Recption/ficheNavetteService'

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  fiche: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['update:visible', 'prestation-added'])

// Composables
const toast = useToast()

// Reactive data
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const prestations = ref([])
const selectedPrestation = ref(null)
const requiredPrestations = ref([])
const addingPrestation = ref(false)
const existingPrestations = ref([])

// Form data
const addForm = reactive({
  prestation_id: null,
  appointment_id: null,
  status: 'pending',
  base_price: 0,
  final_price: 0,
  patient_share: 0,
  doctor_share: 0,
  doctor_id: null,
  modality_id: null,
  selected_dependencies: []
})

// Status options
const statusOptions = [
  { label: 'En attente', value: 'pending', severity: 'warning' },
  { label: 'En cours', value: 'in_progress', severity: 'info' },
  { label: 'Terminé', value: 'completed', severity: 'success' },
  { label: 'Annulé', value: 'cancelled', severity: 'danger' },
  { label: 'Requis', value: 'required', severity: 'secondary' }
]

// Computed
const availablePrestations = computed(() => {
  return prestations.value.filter(p => 
    !existingPrestations.value.some(item => item.prestation_id === p.id)
  )
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

// Methods
const loadPrestations = async () => {
  try {
    const result = await prestationService.getAll()
    if (result.success) {
      prestations.value = result.data
    }
  } catch (error) {
    console.error('Error loading prestations:', error)
  }
}

const loadExistingPrestations = async () => {
  if (!props.fiche?.id) return
  
  try {
    const result = await ficheNavetteService.getById(props.fiche.id)
    if (result.success) {
      existingPrestations.value = result.data.items || []
    }
  } catch (error) {
    console.error('Error loading existing prestations:', error)
  }
}

const resetAddForm = () => {
  Object.assign(addForm, {
    prestation_id: null,
    appointment_id: null,
    status: 'pending',
    base_price: 0,
    final_price: 0,
    patient_share: 0,
    doctor_share: 0,
    doctor_id: null,
    modality_id: null,
    selected_dependencies: []
  })
  selectedPrestation.value = null
  requiredPrestations.value = []
}

const onPrestationSelect = (prestationId) => {
  const prestation = prestations.value.find(p => p.id === prestationId)
  if (prestation) {
    selectedPrestation.value = prestation
    addForm.prestation_id = prestationId
    addForm.base_price = prestation.public_price || 0
    addForm.final_price = prestation.public_price || 0
    
    // Load required prestations based on required_prestations_info
    if (prestation.required_prestations_info && Array.isArray(prestation.required_prestations_info)) {
      const requiredIds = prestation.required_prestations_info.filter(id => id !== "!")
      requiredPrestations.value = prestations.value.filter(p => requiredIds.includes(p.id.toString()))
      addForm.selected_dependencies = [...requiredIds]
    } else {
      requiredPrestations.value = []
      addForm.selected_dependencies = []
    }
  }
}

const addPrestationWithDependencies = async () => {
  if (!addForm.prestation_id) {
    toast.add({
      severity: 'warn',
      summary: 'Attention',
      detail: 'Veuillez sélectionner une prestation',
      life: 3000
    })
    return
  }

  addingPrestation.value = true
  
  try {
    const result = await ficheNavetteService.addPrestation(props.fiche.id, {
      ...addForm,
      dependency_prestation_ids: addForm.selected_dependencies
    })
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Prestation ajoutée avec succès',
        life: 3000
      })
      
      emit('prestation-added', result.data)
      resetAddForm()
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
      detail: 'Erreur lors de l\'ajout de la prestation',
      life: 3000
    })
  } finally {
    addingPrestation.value = false
  }
}

const getPrestationTypeColor = (type) => {
  const colors = {
    'consultation': 'info',
    'intervention': 'danger',
    'diagnostic': 'warning',
    'therapeutic': 'success'
  }
  return colors[type] || 'secondary'
}

// Watchers
watch(() => props.visible, (newVal) => {
  if (newVal) {
    resetAddForm()
    loadExistingPrestations()
  }
})

// Lifecycle
onMounted(() => {
  loadPrestations()
})
</script>

<template>
  <Dialog 
    v-model:visible="dialogVisible"
    header="Ajouter une Prestation"
    :style="{ width: '900px', maxHeight: '90vh' }"
    :modal="true"
    class="add-prestation-modal"
  >
    <div class="add-form">
      <!-- Prestation Selection -->
      <div class="section">
        <h4 class="section-title">
          <i class="pi pi-search mr-2"></i>
          Sélection de la prestation
        </h4>
        
        <div class="prestations-selection">
          <ScrollPanel style="width: 100%; height: 300px">
            <div class="available-prestations-grid">
              <Card 
                v-for="prestation in availablePrestations" 
                :key="prestation.id"
                class="selectable-prestation-card"
                :class="{ 'selected': addForm.prestation_id === prestation.id }"
                @click="onPrestationSelect(prestation.id)"
              >
                <template #header>
                  <div class="prestation-card-header">
                    <Chip 
                      :label="prestation.type || 'N/A'" 
                      :class="`p-chip-${getPrestationTypeColor(prestation.type)}`"
                      class="type-chip"
                    />
                    <div class="price-display">
                      {{ formatCurrency(prestation.public_price) }}
                    </div>
                  </div>
                </template>
                
                <template #title>
                  <div class="prestation-card-title">{{ prestation.name }}</div>
                </template>
                
                <template #subtitle>
                  <div class="prestation-card-code">{{ prestation.internal_code }}</div>
                </template>
                
                <template #content>
                  <div class="prestation-card-content">
                    <p v-if="prestation.description" class="description">
                      {{ prestation.description.substring(0, 100) }}...
                    </p>
                    
                    <div v-if="prestation.required_prestations_info && prestation.required_prestations_info.length > 1" class="dependencies-info">
                      <small class="dependencies-label">
                        <i class="pi pi-link mr-1"></i>
                        {{ prestation.required_prestations_info.length - 1 }} dépendance(s)
                      </small>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </ScrollPanel>
        </div>
      </div>

      <!-- Selected Prestation Details -->
      <div v-if="selectedPrestation" class="section">
        <h4 class="section-title">
          <i class="pi pi-cog mr-2"></i>
          Configuration de la prestation
        </h4>
        
        <Card class="selected-prestation-details">
          <template #title>{{ selectedPrestation.name }}</template>
          <template #content>
            <div class="form-grid">
              <div class="field">
                <label for="status">Statut</label>
                <Dropdown 
                  id="status"
                  v-model="addForm.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  class="w-full"
                />
              </div>

              <div class="field">
                <label for="base-price">Prix de base</label>
                <InputNumber 
                  id="base-price"
                  v-model="addForm.base_price"
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
                  v-model="addForm.final_price"
                  mode="currency"
                  currency="DZD"
                  locale="fr-FR"
                  class="w-full"
                />
              </div>

              <div class="field">
                <label for="patient-share">Part patient</label>
                <InputNumber 
                  id="patient-share"
                  v-model="addForm.patient_share"
                  mode="currency"
                  currency="DZD"
                  locale="fr-FR"
                  class="w-full"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Dependencies Section -->
      <div v-if="requiredPrestations.length > 0" class="section">
        <h4 class="section-title">
          <i class="pi pi-sitemap mr-2"></i>
          Prestations dépendantes ({{ requiredPrestations.length }})
        </h4>
        
        <div class="dependencies-note">
          <i class="pi pi-info-circle mr-2"></i>
          Les prestations suivantes sont requises et seront automatiquement ajoutées:
        </div>
        
        <div class="dependencies-grid">
          <Card 
            v-for="dependency in requiredPrestations" 
            :key="dependency.id"
            class="dependency-card"
          >
            <template #header>
              <div class="dependency-header">
                <Checkbox 
                  v-model="addForm.selected_dependencies" 
                  :value="dependency.id.toString()"
                  :disabled="true"
                  class="dependency-checkbox"
                />
                <Chip label="Requis" severity="secondary" class="required-chip" />
              </div>
            </template>
            
            <template #title>
              <div class="dependency-title">{{ dependency.name }}</div>
            </template>
            
            <template #subtitle>
              <div class="dependency-code">{{ dependency.internal_code }}</div>
            </template>
            
            <template #content>
              <div class="dependency-price">
                {{ formatCurrency(dependency.public_price) }}
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="modal-footer">
        <Button 
          label="Annuler" 
          icon="pi pi-times" 
          class="p-button-text"
          @click="dialogVisible = false"
          :disabled="addingPrestation"
        />
        <Button 
          label="Ajouter la prestation"
          icon="pi pi-plus"
          class="p-button-primary"
          @click="addPrestationWithDependencies"
          :disabled="!addForm.prestation_id || addingPrestation"
          :loading="addingPrestation"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
/* Include the modal-related styles from the original component */
.add-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  max-height: 70vh;
  overflow-y: auto;
}

.section {
  background: var(--surface-50);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--surface-200);
}

.section-title {
  margin: 0 0 1rem 0;
  color: var(--primary-color);
  font-size: 1.1rem;
  display: flex;
  align-items: center;
}

/* ... (include other relevant styles) ... */
</style>
