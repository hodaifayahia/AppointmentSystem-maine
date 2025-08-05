<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
//primevue components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import ProgressSpinner from 'primevue/progressspinner'
import Dialog from 'primevue/dialog'
import Avatar from 'primevue/avatar'
import ConfirmDialog from 'primevue/confirmdialog'
import Toolbar from 'primevue/toolbar'
import Divider from 'primevue/divider'

import FicheNavetteModal from '../../../../Components/Apps/reception/FicheNavatte/ficheNavetteModal.vue'
import FicheNavetteItemsModal from './FicheNavetteItemsModalList.vue'
import {ficheNavetteService} from '../../../../Components/Apps/services/Recption/ficheNavetteService'

// Composables
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()

// Reactive data
const ficheNavettes = ref([])
const loading = ref(false)
const totalRecords = ref(0)
const searchQuery = ref('')
const showModal = ref(false)
const showItemsModal = ref(false)
const selectedFiche = ref(null)
const modalMode = ref('create')

// Pagination
const lazyParams = ref({
  first: 0,
  rows: 15,
  page: 1
})

// Static data
const statusOptions = [
  { label: 'En attente', value: 'pending', severity: 'warning' },
  { label: 'En cours', value: 'in_progress', severity: 'info' },
  { label: 'Terminé', value: 'completed', severity: 'success' },
  { label: 'Annulé', value: 'cancelled', severity: 'danger' }
]

// Methods
const loadFicheNavettes = async () => {
  loading.value = true
  try {
    const params = {
      page: lazyParams.value.page,
      per_page: lazyParams.value.rows,
      search: searchQuery.value
    }
    
    const result = await ficheNavetteService.getAll(params)
    
    if (result.success) {
      ficheNavettes.value = result.data
      totalRecords.value = result.total
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: result.message,
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

const onPage = (event) => {
  lazyParams.value.first = event.first
  lazyParams.value.rows = event.rows
  lazyParams.value.page = Math.floor(event.first / event.rows) + 1
  loadFicheNavettes()
}

const onSearch = () => {
  lazyParams.value.first = 0
  lazyParams.value.page = 1
  loadFicheNavettes()
}

const openCreateModal = () => {
  selectedFiche.value = null
  modalMode.value = 'create'
  showModal.value = true
}

const openItemsModal = (fiche) => {
  selectedFiche.value = fiche
  showItemsModal.value = true
}

const editFiche = (fiche) => {
  selectedFiche.value = fiche
  modalMode.value = 'edit'
  showModal.value = true
}

const confirmDelete = (fiche) => {
  confirm.require({
    message: `Êtes-vous sûr de vouloir supprimer la fiche navette #${fiche.id} ?`,
    header: 'Confirmation de suppression',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Supprimer',
    rejectLabel: 'Annuler',
    accept: () => deleteFiche(fiche)
  })
}

const deleteFiche = async (fiche) => {
  try {
    const result = await ficheNavetteService.delete(fiche.id)
    
    if (result.success) {
      const index = ficheNavettes.value.findIndex(f => f.id === fiche.id)
      if (index !== -1) {
        ficheNavettes.value.splice(index, 1)
        totalRecords.value -= 1
      }
      
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Fiche navette supprimée avec succès',
        life: 3000
      })
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
      detail: 'Erreur lors de la suppression',
      life: 3000
    })
  }
}

const onFicheSaved = (savedFiche, mode) => {
  showModal.value = false
  
  if (mode === 'create') {
    ficheNavettes.value.unshift(savedFiche)
    totalRecords.value += 1
    
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Fiche navette créée avec succès',
      life: 3000
    })
  } else if (mode === 'edit') {
    const index = ficheNavettes.value.findIndex(f => f.id === savedFiche.id)
    if (index !== -1) {
      ficheNavettes.value[index] = { ...ficheNavettes.value[index], ...savedFiche }
    }
    
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Fiche navette modifiée avec succès',
      life: 3000
    })
  }
}

const onItemsUpdated = (updatedFiche) => {
  const index = ficheNavettes.value.findIndex(f => f.id === updatedFiche.id)
  if (index !== -1) {
    ficheNavettes.value[index] = { ...ficheNavettes.value[index], ...updatedFiche }
  }
}

// Utility functions
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR')
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

// Lifecycle
onMounted(() => {
  loadFicheNavettes()
})
</script>

<template>
  <div class="fiche-navette-container">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">
            <i class="pi pi-file-edit"></i>
            Fiches Navette
          </h1>
          <p class="page-subtitle">Gestion des fiches de réception</p>
        </div>
      </div>
    </div>

    <!-- Main Content Card -->
    <Card class="main-card">
      <template #content>
        <!-- Toolbar -->
        <Toolbar class="mb-4">
          <template #start>
            <div class="search-container">
              <span class="p-input-icon-left">
                <i class="pi pi-search" />
                <InputText 
                  v-model="searchQuery" 
                  placeholder="Rechercher une fiche navette..." 
                  class="search-input"
                  @keyup.enter="onSearch"
                />
              </span>
              <Button 
                icon="pi pi-search" 
                class="p-button-outlined ml-2"
                @click="onSearch"
              />
            </div>
          </template>
          
          <template #end>
            <Button 
              icon="pi pi-plus" 
              label="Nouvelle Fiche" 
              class="p-button-primary"
              @click="openCreateModal"
            />
          </template>
        </Toolbar>

        <Divider />

        <!-- Data Table -->
        <DataTable 
          :value="ficheNavettes" 
          :loading="loading"
          :paginator="true"
          :rows="lazyParams.rows"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPage"
          class="p-datatable-sm fiche-table"
          responsiveLayout="scroll"
          :rowHover="true"
          stripedRows
          dataKey="id"
          @row-click="(event) => openItemsModal(event.data)"
        >
          <Column field="id" header="ID" sortable class="id-column">
            <template #body="{ data }">
              <Badge :value="`#${data.id}`" severity="info" />
            </template>
          </Column>

          <Column field="patient_name" header="Patient" sortable>
            <template #body="{ data }">
              <div class="patient-info">
                <Avatar 
                  icon="pi pi-user" 
                  class="mr-2" 
                  size="small"
                  shape="circle"
                />
                <div>
                  <div class="patient-name">{{ data.patient_name }}</div>
                  <small class="patient-id">ID: {{ data.patient_id }}</small>
                </div>
              </div>
            </template>
          </Column>

          <Column field="creator_name" header="Créateur" sortable>
            <template #body="{ data }">
              <div class="creator-info">
                <i class="pi pi-user-edit mr-2"></i>
                {{ data.creator_name }}
              </div>
            </template>
          </Column>

          <Column field="fiche_date" header="Date" sortable>
            <template #body="{ data }">
              <div class="date-info">
                <i class="pi pi-calendar mr-2"></i>
                {{ formatDate(data.fiche_date) }}
              </div>
            </template>
          </Column>

          <Column field="status" header="Statut" sortable>
            <template #body="{ data }">
              <Tag 
                :value="getStatusData(data.status).label"
                :severity="getStatusData(data.status).severity"
              />
            </template>
          </Column>

          <!-- <Column field="items_count" header="Services" sortable>
            <template #body="{ data }">
              <Badge 
                :value="data.items_count || 0" 
                :severity="(data.items_count || 0) > 0 ? 'success' : 'warning'"
              />
            </template>
          </Column> -->

          <Column field="total_amount" header="Montant Total" sortable>
            <template #body="{ data }">
              <div class="amount-display">
                <strong>{{ formatCurrency(data.total_amount) }}</strong>
              </div>
            </template>
          </Column>

          <Column header="Actions" class="actions-column">
            <template #body="{ data }">
              <div class="action-buttons" @click.stop>
                <Button 
                  icon="pi pi-list" 
                  class="p-button-rounded p-button-text p-button-sm p-button-info"
                  v-tooltip.top="'Voir les prestations'"
                  @click="openItemsModal(data)"
                />
                <Button 
                  icon="pi pi-pencil" 
                  class="p-button-rounded p-button-text p-button-sm"
                  v-tooltip.top="'Modifier'"
                  @click="editFiche(data)"
                />
                <Button 
                  icon="pi pi-trash" 
                  class="p-button-rounded p-button-text p-button-sm p-button-danger"
                  v-tooltip.top="'Supprimer'"
                  @click="confirmDelete(data)"
                />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="empty-state">
              <i class="pi pi-file-o empty-icon"></i>
              <h3>Aucune fiche navette trouvée</h3>
              <p>Commencez par créer votre première fiche navette</p>
              <Button 
                icon="pi pi-plus" 
                label="Créer une fiche" 
                class="p-button-primary"
                @click="openCreateModal"
              />
            </div>
          </template>

          <template #loading>
            <div class="loading-state">
              <ProgressSpinner />
              <p>Chargement des fiches navette...</p>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <!-- Modals -->
    <FicheNavetteModal 
      v-model:visible="showModal"
      :fiche="selectedFiche"
      :mode="modalMode"
      @saved="onFicheSaved"
    />

    <FicheNavetteItemsModal 
      v-model:visible="showItemsModal"
      :fiche="selectedFiche"
      @updated="onItemsUpdated"
    />

    <ConfirmDialog />
  </div>
</template>

<style scoped>
.fiche-navette-container {
  padding: 1.5rem;
  min-height: 100vh;
  background: var(--surface-50);
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

.title-section {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 600;
  color: var(--primary-color);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-subtitle {
  color: var(--text-color-secondary);
  margin: 0;
  font-size: 1.1rem;
}

.main-card {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border-radius: 12px;
}

.search-container {
  display: flex;
  align-items: center;
}

.search-input {
  width: 350px;
}

.fiche-table {
  margin-top: 1rem;
}

.fiche-table :deep(.p-datatable-tbody tr) {
  cursor: pointer;
  transition: all 0.2s ease;
}

.fiche-table :deep(.p-datatable-tbody tr:hover) {
  background-color: var(--primary-50) !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.patient-info {
  display: flex;
  align-items: center;
}

.patient-name {
  font-weight: 600;
  color: var(--text-color);
}

.patient-id {
  color: var(--text-color-secondary);
}

.creator-info,
.date-info {
  display: flex;
  align-items: center;
  color: var(--text-color);
}

.amount-display {
  color: var(--primary-color);
  font-size: 1.1rem;
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
  font-size: 4rem;
  color: var(--text-color-secondary);
  margin-bottom: 1rem;
}

.loading-state {
  text-align: center;
  padding: 2rem;
}

.id-column {
  width: 80px;
}

.actions-column {
  width: 150px;
}

@media (max-width: 768px) {
  .fiche-navette-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-input {
    width: 100%;
  }
  
  .search-container {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>