<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Chip from 'primevue/chip'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'
import ProgressSpinner from 'primevue/progressspinner'
import Divider from 'primevue/divider'
import Dialog from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import Avatar from 'primevue/avatar'

// Components
import PrestationPackageSelectionModal from '../../../../Components/Apps/reception/FicheNavatte/PrestationPackageSelectionModal.vue'
import PrestationItemCard from '../../../../Components/Apps/reception/FicheNavatte/PrestationItemCard.vue'
import FicheNavetteItemCreate from '../../../../Components/Apps/reception/FicheNavatteItem/FicheNavetteItemCreate.vue'
import ConventionCompaniesDisplay from '../../../../Components/Apps/reception/FicheNavatteItem/ConventionCompaniesDisplay.vue'

// Services - Fix the import path from 'Recption' to 'Reception'
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService'
import prestationService from '../../../../Components/Apps/services/Prestation/prestationService'
import prestationPackageService from '../../../../Components/Apps/services/Prestation/prestationPackageService'

// Composables
const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

// State
const fiche = ref(null)
const items = ref([])
const prestations = ref([])
const packages = ref([])
const doctors = ref([])
const loading = ref(false)
const showSelectionModal = ref(false)
const showAddItemsModal = ref(false)

// Computed
const ficheId = computed(() => route.params.id)

const groupedItems = computed(() => {
  const groups = {}
  
  items.value.forEach(item => {
    const key = item.package_id ? `package_${item.package_id}` : `prestation_${item.prestation_id}`
    
    if (!groups[key]) {
      groups[key] = {
        type: item.package_id ? 'package' : 'prestation',
        id: item.package_id || item.prestation_id,
        name: item.package_id ? item.package?.name : item.prestation?.name,
        doctor_id: item.doctor_id,
        doctor_name: item.doctor?.name,
        items: [],
        total_price: 0
      }
    }
    
    groups[key].items.push(item)
    groups[key].total_price += parseFloat(item.final_price || 0)
  })
  
  return Object.values(groups)
})

const totalAmount = computed(() => {
  return items.value.reduce((total, item) => total + parseFloat(item.final_price || 0), 0)
})

// Updated method to load fiche and items separately using correct service methods
const loadFiche = async () => {
  loading.value = true
  try {
    // Get fiche details from ficheNavetteController
    const ficheResult = await ficheNavetteService.getById(ficheId.value)
    if (ficheResult.success) {
      fiche.value = ficheResult.data
    } else {
      throw new Error(ficheResult.message || 'Failed to load fiche')
    }

    // Get items from ficheNavetteItemController
    const itemsResult = await ficheNavetteService.getFicheNavetteItems(ficheId.value)
    if (itemsResult.success) {
      items.value = itemsResult.data || []
    } else {
      console.warn('Failed to load items:', itemsResult.message)
      items.value = []
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to load fiche navette',
      life: 3000
    })
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

const loadPackages = async () => {
  try {
    const result = await prestationPackageService.getAll()
    if (result.success) {
      packages.value = result.data
    }
  } catch (error) {
    console.error('Error loading packages:', error)
  }
}

const loadDoctors = async () => {
  try {
    // Add your doctor service call here
    // const result = await doctorService.getAll()
    // if (result.success) {
    //   doctors.value = result.data
    // }
    
    // Mock data for now
    doctors.value = [
      { id: 1, name: 'Dr. Martin', specialization: 'Cardiology' },
      { id: 2, name: 'Dr. Sarah', specialization: 'Neurology' },
      { id: 3, name: 'Dr. Ahmed', specialization: 'Radiology' }
    ]
  } catch (error) {
    console.error('Error loading doctors:', error)
  }
}

// router navigation to reception.FicheNavetteItems.type-selection
const goToTypeSelection = () => {
  router.push({ name: 'reception.FicheNavetteItems.create', params: { id: ficheId.value } })
}

const handleItemAdded = async (newItem) => {
  showSelectionModal.value = false
  await loadFicheDetails() // Reload both fiche and items
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Item added successfully',
    life: 3000
  })
}

// Updated remove item method to use ficheNavetteItemController
const removeItem = async (itemId) => {
  try {
    const result = await ficheNavetteService.removeFicheNavetteItem(ficheId.value, itemId)
    if (result.success) {
      await loadFicheDetails() // Reload both fiche and items
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Item removed successfully',
        life: 3000
      })
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to remove item',
      life: 3000
    })
  }
}

const confirmRemoveItem = (itemId) => {
  confirm.require({
    message: 'Are you sure you want to remove this item?',
    header: 'Remove Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => removeItem(itemId)
  })
}

const goBack = () => {
  router.push('/reception/fiche-navette')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const getStatusSeverity = (status) => {
  const statusMap = {
    'pending': 'warning',
    'in_progress': 'info',
    'completed': 'success',
    'cancelled': 'danger',
    'required': 'secondary'
  }
  return statusMap[status] || 'secondary'
}

// Method to open the add items modal
const openAddItemsModal = () => {
  showAddItemsModal.value = true
}

// Updated handle items added method
const onItemsAdded = (updatedFiche) => {
  showAddItemsModal.value = false
  // Refresh both fiche and items data
  loadFicheDetails()
  
  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'Items added successfully',
    life: 3000
  })
}

// Updated method to load fiche details after adding items
const loadFicheDetails = async () => {
  loading.value = true
  try {
    // Get fiche details from ficheNavetteController
    const ficheResult = await ficheNavetteService.getById(ficheId.value)
    if (ficheResult.success) {
      fiche.value = ficheResult.data
    }

    // Get items from ficheNavetteItemController
    const itemsResult = await ficheNavetteService.getFicheNavetteItems(ficheId.value)
    if (itemsResult.success) {
      items.value = itemsResult.data || []
    }
  } catch (error) {
    console.error('Error loading fiche details:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadFiche(),
    loadPrestations(),
    loadPackages(),
    loadDoctors()
  ])
})
</script>

<template>
  <div class="fiche-items-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <Button 
            icon="pi pi-arrow-left"
            class="p-button-text p-button-secondary"
            @click="goBack"
            v-tooltip.bottom="'Back to Fiche List'"
          />
          <div class="title-section">
            <h1 class="page-title">
              <i class="pi pi-file-edit"></i>
              Fiche Navette #{{ ficheId }}
            </h1>
            <p class="page-subtitle" v-if="fiche">
              {{ fiche.patient_name }} - {{ new Date(fiche.fiche_date).toLocaleDateString() }}
            </p>
          </div>
        </div>
        <div class="header-actions">
       
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <ProgressSpinner />
      <p>Loading fiche navette...</p>
    </div>

    <!-- Main Content -->
    <div v-else-if="fiche" class="main-content">
      <Card class="fiche-info-card mb-4">
        <template #content>
          <div class="fiche-info-grid">
            <div class="info-item">
              <span class="info-label">Patient:</span>
              <div class="patient-info">
                <Avatar icon="pi pi-user" class="mr-2" size="small" />
                <strong>{{ fiche.patient_name }}</strong>
              </div>
            </div>
            <div class="info-item">
              <span class="info-label">Date:</span>
              <span>{{ new Date(fiche.fiche_date).toLocaleDateString() }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Status:</span>
              <Tag 
                :value="fiche.status"
                :severity="getStatusSeverity(fiche.status)"
              />
            </div>
            <div class="info-item">
              <span class="info-label">Total Amount:</span>
              <strong class="total-amount">{{ formatCurrency(totalAmount) }}</strong>
            </div>
          </div>
        </template>
      </Card>
      <FicheNavetteItemCreate
        v-if="fiche"
        :patient-id="fiche.patient_id"
        :fiche-navette-id="fiche.id"
        @created="onItemsAdded"
      ></FicheNavetteItemCreate>
      <Card class="items-section">
        <template #header>
          <div class="section-header">
            <h3>
              <i class="pi pi-list mr-2"></i>
              Items ({{ items.length }})
            </h3>
           
          </div>
        </template>
     
         <FicheNavetteItemCreate
          :patient-id="fiche.patient_id"
          :fiche-navette-id="fiche.id"
          @created="onItemsAdded"
        />
        
        <template #content>
          <!-- Empty State -->
          <div v-if="items.length === 0" class="empty-state">
            <i class="pi pi-inbox empty-icon"></i>
            <h4>No Items Yet</h4>
            <p>Add prestations or packages to this fiche navette</p>
            <Button 
              icon="pi pi-plus"
              label="Add First Item"
              class="p-button-primary"
              @click="openAddItemsModal"
            />
          </div>

          
          <!-- Items Grid -->
          <div v-else class="items-grid">
            <PrestationItemCard
              v-for="group in groupedItems"
              :key="`${group.type}_${group.id}`"
              :group="group"
              :prestations="prestations"
              :packages="packages"
              :doctors="doctors"
              @remove-item="confirmRemoveItem"
              @item-updated="loadFiche"
            />
          </div>
        </template>
      </Card>

      <!-- Add Items Modal -->
      <Dialog 
        v-model:visible="showAddItemsModal"
        header="Add Items to Fiche Navette"
        :style="{ width: '90vw', maxHeight: '90vh' }"
        :modal="true"
        :closable="true"
      >
        <FicheNavetteItemCreate
          v-if="showAddItemsModal && fiche"
          :patient-id="fiche.patient_id"
          :fiche-navette-id="fiche.id"
          mode="add"
          @created="onItemsAdded"
          @cancel="showAddItemsModal = false"
        />
      </Dialog>
    </div>

    <!-- Selection Modal (if needed) -->
    <!-- <PrestationPackageSelectionModal
      v-model:visible="showSelectionModal"
      :fiche-id="ficheId"
      :prestations="prestations"
      :packages="packages"
      :doctors="doctors"
      :existing-items="items"
      @item-added="handleItemAdded"
    /> -->

    <ConfirmDialog />
  </div>
</template>

<!-- Styles remain the same -->
<style scoped>
.fiche-items-page {
  min-height: 100vh;
  background: var(--surface-50);
  padding: 1.5rem;
}

.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.title-section {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 600;
  color: var(--primary-color);
  margin: 0 0 0.25rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-subtitle {
  color: var(--text-color-secondary);
  margin: 0;
  font-size: 1rem;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 1rem;
}

.fiche-info-card {
  background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%);
  border: 1px solid var(--primary-200);
}

.fiche-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-label {
  font-size: 0.875rem;
  color: var(--text-color-secondary);
  font-weight: 500;
}

.patient-info {
  display: flex;
  align-items: center;
}

.total-amount {
  color: var(--primary-color);
  font-size: 1.25rem;
}

.section-header {
  padding: 1rem;
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-header h3 {
  margin: 0;
  color: var(--text-color);
  display: flex;
  align-items: center;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon {
  font-size: 4rem;
  color: var(--text-color-secondary);
  margin-bottom: 1rem;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  padding: 1rem;
}

/* New styles for dependencies display */
.dependency-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 6px;
}

.dependency-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.dependency-name {
  font-weight: 500;
  color: #92400e;
}

.dependency-code {
  color: #d97706;
  font-size: 0.75rem;
}

.dependency-price {
  background: #f59e0b;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
}

@media (max-width: 768px) {
  .fiche-items-page {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-left {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }
  
  .fiche-info-grid {
    grid-template-columns: 1fr;
  }
  
  .items-grid {
    grid-template-columns: 1fr;
  }
  
  .section-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}
</style>