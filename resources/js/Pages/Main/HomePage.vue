<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { storeToRefs } from 'pinia';

const authStore = useAuthStore();
const { user, isLoading } = storeToRefs(authStore);

const router = useRouter();
const viewMode = ref('grid');
const searchQuery = ref('');
const allApps = ref([
  { 
    id: 2, 
    name: 'Calendar', 
    icon: 'fas fa-calendar-alt',
    color: '#10B981',
    route: '/calander'
  },
  { 
    id: 3, 
    name: 'Appointments', 
    icon: 'fas fa-calendar-check',
    color: '#F59E0B',
    route: '/admin/appointments/specialization'
  },
  { 
    id: 5, 
    name: 'Consultation', 
    icon: 'fas fa-stethoscope',
    color: '#8B5CF6',
    route: '/admin/consultations/consultation'
  },
  { 
    id: 6, 
    name: 'Reception', 
    icon: 'fas fa-concierge-bell',
    color: '#3B82F6',
    route: '/reception'
  },
  { 
    id: 7, 
    name: 'Configuration', 
    icon: 'fas fa-cogs',
    color: '#6B7280',
    route: '/admin/configuration'
  },
  { 
    id: 8, 
    name: 'Caisse', 
    icon: 'fas fa-cash-register',
    color: '#14B8A6',
    route: '/admin/caisse'
  },
  { 
    id: 9, 
    name: 'Coffre', 
    icon: 'fas fa-lock',
    color: '#9CA3AF',
    route: '/admin/coffre'
  },
  { 
    id: 10, 
    name: 'Banking', 
    icon: 'fas fa-university',
    color: '#F97316',
    route: '/admin/banking'
  },
  { 
    id: 11, 
    name: 'Convention', 
    icon: 'fas fa-handshake',
    color: '#7C3AED',
    route: '/convention'
  },
  { 
    id: 12, 
    name: 'Facturation', 
    icon: 'fas fa-file-invoice-dollar',
    color: '#0EA5E9',
    route: '/admin/facturation'
  },
  { 
    id: 13, 
    name: 'Infrastructure', 
    icon: 'fas fa-building',
    color: '#EC4899',
    route: '/infrastructure'
  },
   { 
    id: 14, 
    name: 'CRM', 
    icon: 'fas fa-address-book',  // Better for customer relations
    color: '#6366F1',
    route: '/crm'
  },
  { 
    id: 15, 
    name: 'Admission', 
    icon: 'fas fa-hospital-user',
    color: '#EF4444',
    route: '/admin/admission'
  },
  { 
    id: 16, 
    name: 'Emergency', 
    icon: 'fas fa-ambulance',
    color: '#DC2626',
    route: '/admin/emergency'
  },
  { 
    id: 18, 
    name: 'Nursing', 
    icon: 'fas fa-user-nurse',
    color: '#F472B6',
    route: '/admin/Nursing'
  },
  { 
    id: 19, 
    name: 'Radiology', 
    icon: 'fas fa-x-ray',
    color: '#059669',
    route: '/admin/radiology'
  },
  { 
    id: 20, 
    name: 'Hospitalization', 
    icon: 'fas fa-bed',
    color: '#7C3AED',
    route: '/admin/hospitalization'
  },
  { 
    id: 21, 
    name: 'Laboratory', 
    icon: 'fas fa-microscope',
    color: '#0D9488',
    route: '/admin/laboratory'
  },
  { 
    id: 22, 
    name: 'Operating Room', 
    icon: 'fas fa-procedures',
    color: '#D97706',
    route: '/admin/operating-room'
  },
  { 
    id: 23, 
    name: 'Pharmacy', 
    icon: 'fas fa-prescription-bottle-alt',
    color: '#2563EB',
    route: '/admin/pharmacy'
  },
  { 
    id: 24, 
    name: 'Catering', 
    icon: 'fas fa-utensils',
    color: '#F59E0B',
    route: '/admin/catering'
  },
  { 
    id: 25, 
    name: 'Inventory', 
    icon: 'fas fa-boxes',
    color: '#6B7280',
    route: '/admin/inventory'
  },
  { 
    id: 26, 
    name: 'Purchasing', 
    icon: 'fas fa-shopping-cart',
    color: '#EC4899',
    route: '/admin/purchasing'
  },
  { 
    id: 27, 
    name: 'Hygiene', 
    icon: 'fas fa-soap',
    color: '#10B981',
    route: '/admin/hygiene'
  },
  { 
    id: 28, 
    name: 'Biomedical', 
    icon: 'fas fa-dna',
    color: '#8B5CF6',
    route: '/admin/biomedical'
  },
   { 
        id: 29, 
        name: 'Ticket Management', 
        icon: 'fas fa-tags',  // Better for multiple tickets
        color: '#F97316',
        route: '/ticket-management'
    },
  { 
    id: 30, 
    name: 'Catheterization', 
    icon: 'fas fa-heartbeat',
    color: '#14B8A6',
    route: '/admin/catheterization'
  },
  { 
    id: 31, 
    name: 'Archive', 
    icon: 'fas fa-archive',
    color: '#9CA3AF',
    route: '/admin/archive'
  },
  { 
    id: 32, 
    name: 'Human Resources', 
    icon: 'fas fa-id-badge',
    color: '#EF4444',
    route: '/admin/hr'
  },
  { 
    id: 33, 
    name: 'Dashboard', 
    icon: 'fas fa-tachometer-alt',
    color: '#0EA5E9',
    route: '/admin/dashboard'
  },
  { 
    id: 34, 
    name: 'Maintenance', 
    icon: 'fas fa-tools',
    color: '#DC2626',
    route: '/admin/maintenance'
  }
]);


const filteredApps = computed(() => {
  let filtered = allApps.value;
  console.log('user role:', user.value?.data.role);
  
  
  // Role-based filtering
  if (user.value?.data.role.toLowerCase() === 'doctor') {
    filtered = filtered.filter(app => 
      app.name === 'Calendar' || app.name === 'Consultation' 
    );
  } else if (user.value?.data.role.toLowerCase() === 'receptionist') {
    filtered = filtered.filter(app => 
      app.name === 'Appointments'
    );
  }

  // Search query filtering
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(app => 
      app.name.toLowerCase().includes(query)
    );
  }

  return filtered;
});

// Logout Function
const logout = async () => {
  try {
    await axios.post('/logout');
    router.push('/');
  } catch (error) {
    console.error('Error logging out:', error);
  }
};

const navigateToApp = (app) => {
  router.push(app.route);
};
</script>
<template>
  <div class="odoo-container">
     <!-- Top Navigation Bar -->
    <div class="odoo-navbar">
      <div class="navbar-left">
        <button class="app-switcher" aria-label="Toggle app menu">
          <i class="fas fa-th-large"></i>
          <span class="hidden md:inline">Apps</span>
        </button>
      </div>
      
      <div class="navbar-center">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Search apps, modules, features..." 
            aria-label="Search apps"
            @keyup.enter="searchApps"
          >
          <div class="search-shortcut">⌘K</div>
        </div>
      </div>

      <div class="navbar-right">
        <div class="user-menu">
          <button class="user-button">
            <i class="fas fa-user-circle"></i>
            <span>Admin</span>
            <i class="fas fa-chevron-down"></i>
          </button>
          <div class="dropdown-menu">
            <a href="#" @click.prevent="logout" class="dropdown-item">
              <i class="fas fa-sign-out-alt"></i>
              <span>Logout</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="odoo-main">
      <!-- All Applications Section -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-th-large"></i> All Applications
          </h2>
          <div class="view-toggle">
            <button :class="{active: viewMode === 'grid'}" @click="viewMode = 'grid'">
              <i class="fas fa-th-large"></i>
            </button>
            <button :class="{active: viewMode === 'list'}" @click="viewMode = 'list'">
              <i class="fas fa-list"></i>
            </button>
          </div>
        </div>
        
        <div v-if="viewMode === 'grid'" class="apps-grid">
          <div 
            v-for="app in filteredApps" 
            :key="app.id" 
            class="app-card" 
            v-tooltip="app.name"
            @click="navigateToApp(app)"
          >
            <div class="app-icon" :style="{ backgroundColor: app.color }">
              <i :class="app.icon"></i>
            </div>
            <div class="app-name">{{ app.name }}</div>
          </div>
        </div>
        
        <div v-if="viewMode === 'list'" class="apps-list">
          <div v-for="app in filteredApps" :key="app.id" class="app-list-item" @click="navigateToApp(app)">
            <div class="app-icon" :style="{ backgroundColor: app.color }">
              <i :class="app.icon"></i>
            </div>
            <div class="app-info">
              <div class="app-name">{{ app.name }}</div>
              <div class="app-description">Manage {{ app.name.toLowerCase() }} operations</div>
            </div>
            <button class="app-action">
              <i class="far fa-star"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.odoo-container {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #F8F9FA;
  color: var(--text-primary);
}

/* Navigation Bar */
.odoo-navbar {
  background-color: #2196F3;
  color: white;
  height: 60px;
  display: flex;
  align-items: center;
  padding: 0 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  position: relative;
  z-index: 10;
}

.navbar-left {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  gap: 20px;
}

.navbar-center {
  flex: 1;
  max-width: 600px;
  margin: 0 30px;
}

.app-switcher {
  background: rgba(255,255,255,0.1);
  border: none;
  color: white;
  display: flex;
  align-items: center;
  padding: 8px 16px;
  cursor: pointer;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.app-switcher:hover {
  background: rgba(255,255,255,0.2);
  transform: translateY(-1px);
}

.app-switcher i {
  margin-right: 8px;
  font-size: 16px;
}

.search-bar {
  position: relative;
  width: 100%;
}

.search-bar i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255,255,255,0.7);
  font-size: 14px;
}

.search-bar input {
  width: 100%;
  padding: 10px 14px 10px 40px;
  border-radius: 6px;
  border: none;
  background-color: rgba(255,255,255,0.2);
  color: white;
  font-size: 14px;
  transition: all 0.2s ease;
}

.search-bar input::placeholder {
  color: rgba(255,255,255,0.7);
}

.search-bar input:focus {
  outline: none;
  background-color: rgba(255,255,255,0.3);
  box-shadow: 0 0 0 2px rgba(255,255,255,0.2);
}

.search-shortcut {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.2);
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
}

/* Main Content */
.odoo-main {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  background-color: #F8F9FA;
}

/* Navbar Right Section */
.navbar-right {
  flex: 0 0 auto;
  display: flex;
  align-items: right;
  gap: 40px;
}

.user-menu {
  position: relative;
}

.user-button {
  background: none;
  border: none;
  color: white;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.user-button:hover {
  background: rgba(255, 255, 255, 0.1);
}

.dropdown-menu {
  position: absolute;
  right: 0;
  top: 100%;
  background: white;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 180px;
  padding: 8px 0;
  z-index: 100;
  display: none;
}

.user-menu:hover .dropdown-menu {
  display: block;
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  color: #333;
  text-decoration: none;
  transition: all 0.2s ease;
}

.dropdown-item i {
  margin-right: 8px;
  width: 18px;
  text-align: center;
}

.dropdown-item:hover {
  background: #f5f5f5;
  color: #2196F3;
}

/* Content Area */
.odoo-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.section {
  margin-bottom: 32px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  display: flex;
  align-items: center;
  margin: 0;
}

.section-title i {
  margin-right: 12px;
  font-size: 16px;
  color: #2196F3;
}

.view-toggle {
  display: flex;
  gap: 4px;
  background: #F0F0F0;
  border-radius: 6px;
  padding: 4px;
}

.view-toggle button {
  background: none;
  border: none;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  cursor: pointer;
  color: #666;
  transition: all 0.2s ease;
}

.view-toggle button.active {
  background: white;
  color: #2196F3;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.view-toggle button:hover {
  color: #2196F3;
}

/* Apps Grid */
.apps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 20px;
}

.app-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  padding: 16px 8px;
  border-radius: 8px;
  background-color: white;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  transition: all 0.2s ease;
  position: relative;
}

.app-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.app-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
  color: white;
  font-size: 20px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.app-name {
  font-size: 14px;
  text-align: center;
  color: #333;
  font-weight: 500;
  word-break: break-word;
}

/* Apps List View */
.apps-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.app-list-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.2s ease;
}

.app-list-item:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.app-list-item .app-icon {
  width: 40px;
  height: 40px;
  margin-bottom: 0;
  margin-right: 16px;
  flex-shrink: 0;
}

.app-info {
  flex: 1;
}

.app-description {
  font-size: 13px;
  color: #666;
  margin-top: 2px;
}

.app-action {
  background: none;
  border: none;
  color: #666;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s ease;
}

.app-action:hover {
  background-color: #F5F5F5;
  color: #2196F3;
}

/* Tooltip Styles */
[v-tooltip] {
  position: relative;
}

[v-tooltip]::after {
  content: attr(v-tooltip);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: #333;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s ease;
  margin-bottom: 8px;
}

[v-tooltip]:hover::after {
  opacity: 1;
  visibility: visible;
}
</style>