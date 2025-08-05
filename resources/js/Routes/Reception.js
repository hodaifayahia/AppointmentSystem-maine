
    

// src/router/routes/reception.js

const receptionRoutes = [
  {
    path: '/reception',
    name: 'reception',
    meta: { role: ['admin', 'SuperAdmin'], appKey: 'reception' },
    children: [
      {
        path: 'dashboard',
        name: 'reception.dashboard',
        // component: () => import('../Pages/Apps/reception/Dashboard/ReceptionDashboard.vue'),
      },
     // fiche  navatte
        {
        path: 'fiche-navette',
        name: 'reception.fiche-navette',
        component: () => import('../Pages/Apps/reception/FicheNavatte/ficheNavetteList.vue'),
      },
    ],
  },
];

export default receptionRoutes;