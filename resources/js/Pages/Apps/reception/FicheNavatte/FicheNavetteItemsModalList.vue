<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// PrimeVue components
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dropdown from 'primevue/dropdown'
import Toolbar from 'primevue/toolbar'
import ProgressSpinner from 'primevue/progressspinner'
import ConfirmDialog from 'primevue/confirmdialog'
import Card from 'primevue/card'

// Services
import ficheNavetteService from '../../../../Components/Apps/services/Recption/ficheNavetteService'
import prestationService from '../../../../Components/Apps/services/Prestation/prestationService'

// Sub-component
import PrestationFormModal from '../../../../Components/Apps/reception/FicheNavatte/PrestationFormModal.vue'

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  fiche: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['update:visible', 'updated'])

// Composables
const toast = useToast()
const confirm = useConfirm()

// Reactive data
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const loading = ref(false)
const items = ref([])
const prestations = ref([])
const showAddEditModal = ref(false)
const editingPrestation = ref(null)

// Status options
const statusOptions = [
  { label: 'En attente', value: 'pending', severity: 'warning' },
  { label: 'En cours', value: 'in_progress', severity: 'info' },
  { label: 'Terminé', value: 'completed', severity: 'success' },
  { label: 'Annulé', value: 'cancelled', severity: 'danger' },
  { label: 'Requis', value: 'required', severity: 'secondary' }
]

const ficheStatusOptions = [
  { label: 'En attente', value: 'pending' },
  { label: 'En cours', value: 'in_progress' },
  { label: 'Terminé', value: 'completed' },
  { label: 'Annulé', value: 'cancelled' }
]

// Computed
const modalTitle = computed(() => {
  return props.fiche ? `Fiche Navette #${props.fiche.id} - Prestations` : 'Prestations'
})

// Methods
const loadItems = async () => {
  if (!props.fiche?.id) return
  
  loading.value = true
  try {
    const result = await ficheNavetteService.getById(props.fiche.id)
    if (result.success) {
      items.value = result.data.items || []
    }
  } finally {
    loading.value = false
  }
}

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

const openAddPrestationModal = () => {
  editingPrestation.value = null // Set to null for adding new item
  showAddEditModal.value = true
}

const handlePrestationAdded = async (newFiche) => {
  showAddEditModal.value = false
  await loadItems()
  emit('updated', newFiche)
}

const updateItemStatus = async (item, newStatus) => {
  try {
    const result = await ficheNavetteService.updatePrestation(
      props.fiche.id, 
      item.id, 
      { status: newStatus }
    )
    
    if (result.success) {
      const index = items.value.findIndex(i => i.id === item.id)
      if (index !== -1) {
        items.value[index].status = newStatus
      }
      
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Statut mis à jour',
        life: 3000
      })
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors de la mise à jour du statut',
      life: 3000
    })
  }
}

const confirmRemoveItem = (item) => {
  confirm.require({
    message: 'Êtes-vous sûr de vouloir supprimer cette prestation ?',
    header: 'Confirmation de suppression',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Supprimer',
    rejectLabel: 'Annuler',
    accept: () => removeItem(item)
  })
}

const removeItem = async (item) => {
  try {
    const result = await ficheNavetteService.removePrestation(props.fiche.id, item.id)
    
    if (result.success) {
      const index = items.value.findIndex(i => i.id === item.id)
      if (index !== -1) {
        items.value.splice(index, 1)
      }
      
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Prestation supprimée',
        life: 3000
      })
      
      emit('updated', result.data)
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors de la suppression',
      life: 3000
    })
  }
}

const updateFicheStatus = async (newStatus) => {
  try {
    const result = await ficheNavetteService.changeStatus(props.fiche.id, newStatus)
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Statut de la fiche mis à jour',
        life: 3000
      })
      
      emit('updated', result.data)
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors de la mise à jour du statut',
      life: 3000
    })
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const getStatusData = (status) => {
  return statusOptions.find(opt => opt.value === status) || { label: status, severity: 'secondary' }
}

// Watchers
watch(() => props.visible, (newVal) => {
  if (newVal && props.fiche) {
    loadItems()
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
    :header="modalTitle"
    :style="{ width: '95vw', height: '90vh' }"
    :modal="true"
    :closable="true"
    :draggable="false"
    class="items-modal"
  >
    <div v-if="fiche" class="modal-content">
      <Card class="fiche-header mb-4">
        <template #content>
          <div class="fiche-info-grid">
            <div class="info-item">
              <span class="info-label">Patient:</span>
              <strong>{{ fiche.patient_name }}</strong>
            </div>
            <div class="info-item">
              <span class="info-label">Date:</span>
              <span>{{ new Date(fiche.fiche_date).toLocaleDateString('fr-FR') }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Statut:</span>
              <Dropdown 
                :modelValue="fiche.status"
                :options="ficheStatusOptions"
                optionLabel="label"
                optionValue="value"
                @change="updateFicheStatus($event.value)"
                class="status-dropdown"
              />
            </div>
            <div class="info-item">
              <span class="info-label">Total:</span>
              <strong class="total-amount">{{ formatCurrency(fiche.total_amount) }}</strong>
            </div>
          </div>
        </template>
      </Card>

      <Toolbar class="mb-4">
        <template #start>
          <h3 class="m-0">
            <i class="pi pi-list mr-2"></i>
            Prestations ({{ items.length }})
          </h3>
        </template>
        
        <template #end>
          <Button 
            icon="pi pi-plus" 
            label="Ajouter Prestation" 
            class="p-button-primary"
            @click="openAddPrestationModal"
          />
        </template>
      </Toolbar>

      <DataTable 
        :value="items" 
        :loading="loading"
        class="p-datatable-sm"
        responsiveLayout="scroll"
        :rowHover="true"
        dataKey="id"
      >
        <Column field="prestation.name" header="Prestation" class="prestation-column">
          <template #body="{ data }">
            <div class="prestation-info">
              <div class="prestation-name">{{ data.prestation?.name || 'N/A' }}</div>
              <small class="prestation-code">{{ data.prestation?.code || 'N/A' }}</small>
            </div>
          </template>
        </Column>

        <Column field="status" header="Statut">
          <template #body="{ data }">
            <Dropdown 
              :modelValue="data.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              @change="updateItemStatus(data, $event.value)"
              class="status-dropdown-sm"
            >
              <template #value="slotProps">
                <Tag 
                  :value="getStatusData(slotProps.value).label"
                  :severity="getStatusData(slotProps.value).severity"
                />
              </template>
            </Dropdown>
          </template>
        </Column>

        <Column field="base_price" header="Prix de base">
          <template #body="{ data }">
            <span class="price-display">{{ formatCurrency(data.base_price) }}</span>
          </template>
        </Column>

        <Column field="final_price" header="Prix final">
          <template #body="{ data }">
            <strong class="final-price">{{ formatCurrency(data.final_price) }}</strong>
          </template>
        </Column>

        <Column field="patient_share" header="Part patient">
          <template #body="{ data }">
            <span class="patient-share">{{ formatCurrency(data.patient_share) }}</span>
          </template>
        </Column>

        <Column header="Actions" class="actions-column">
          <template #body="{ data }">
            <div class="action-buttons">
              <Button 
                icon="pi pi-trash" 
                class="p-button-rounded p-button-text p-button-sm p-button-danger"
                v-tooltip.top="'Supprimer'"
                @click="confirmRemoveItem(data)"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="empty-state">
            <i class="pi pi-list empty-icon"></i>
            <h4>Aucune prestation</h4>
            <p>Commencez par ajouter une prestation à cette fiche</p>
            <Button 
              icon="pi pi-plus" 
              label="Ajouter Prestation" 
              class="p-button-primary"
              @click="openAddPrestationModal"
            />
          </div>
        </template>

        <template #loading>
          <div class="loading-state">
            <ProgressSpinner />
            <p>Chargement des prestations...</p>
          </div>
        </template>
      </DataTable>
    </div>

    <PrestationFormModal
      v-if="fiche"
      v-model:visible="showAddEditModal"
      :ficheId="fiche.id"
      :allPrestations="prestations"
      :existingPrestationItems="items"
      :prestationToEdit="editingPrestation"
      @prestation-saved="handlePrestationAdded"
    />

    <ConfirmDialog />
  </Dialog>
</template>

<style scoped>
/* Scoped styles remain the same for the main list component */
.items-modal :deep(.p-dialog-content) {
  padding: 0;
  overflow: hidden;
}

.modal-content {
  height: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.5rem;
  overflow-y: auto;
}

.fiche-header {
  background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%);
  border: 1px solid var(--primary-200);
}

.fiche-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.875rem;
  color: var(--text-color-secondary);
  font-weight: 500;
}

.total-amount {
  color: var(--primary-color);
  font-size: 1.25rem;
}

.status-dropdown {
  width: 120px;
}

.status-dropdown-sm {
  width: 100px;
}

.prestation-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.prestation-name {
  font-weight: 600;
  color: var(--text-color);
}

.prestation-code {
  color: var(--text-color-secondary);
}

.price-display {
  color: var(--text-color);
}

.final-price {
  color: var(--primary-color);
  font-size: 1.1rem;
}

.patient-share {
  color: var(--orange-600);
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon {
  font-size: 3rem;
  color: var(--text-color-secondary);
  margin-bottom: 1rem;
}

.loading-state {
  text-align: center;
  padding: 2rem;
}

.prestation-column {
  min-width: 200px;
}

.actions-column {
  width: 80px;
}

@media (max-width: 768px) {
  .items-modal :deep(.p-dialog) {
    width: 98vw !important;
    height: 95vh !important;
    margin: 1rem;
  }
  
  .fiche-info-grid {
    grid-template-columns: 1fr;
  }
}
</style>