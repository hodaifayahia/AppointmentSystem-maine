// src/router/routes/configuration.js

const convenationRoutes = [
    {
        path: '/convention', // Base path for all configuration modules
        name: 'convention', // Renamed base name
        meta: { role: ['admin', 'SuperAdmin'], appKey: 'convention' },
        children: [
            // Convention Management
            // {
            //     path: 'Dashborad',
            //     name: 'convention.Dashborad',
            //     component: () => import('../Pages/Apps/convention/Dashborad/Dashborad.vue'), // Placeholder
            //     meta: { title: 'Corporate Partners' }
            // },
            {
                path: 'organismes',
                name: 'convention.organismes',
                component: () => import('../Pages/Apps/convention/Organisme/OrganismeList.vue'), // Placeholder
                meta: { title: ' organismes' }
            },
            {
                path: 'organisme-details/:id',
                name: 'convention.organisme-details',
                component: () => import('../Components/Apps/convention/Organisme/pages/Company.vue'), // Placeholder
                meta: { title: 'organisme-details' },
                props: true,
            },
            {
                path: 'contract/:id',
                name: 'convention.contract',
                component: () => import('../Components/Apps/convention/contract/pages/Contract.vue'), // Placeholder
                meta: { title: 'Contract' },
                props: true,
            },
            // {
            //     path: 'convention/agreements',
            //     name: 'convention.agreements',
            //     component: () => import('../../Pages/Admin/Convention/AgreementList.vue'), // Placeholder
            //     meta: { title: 'Convention Agreements' }
            // },
            // {
            //     path: 'convention/avenants',
            //     name: 'convention.avenants',
            //     component: () => import('../../Pages/Admin/Convention/AvenantList.vue'), // Placeholder
            //     meta: { title: 'Avenants (Amendments)' }
            // },
            // {
            //     path: 'convention/rule-definitions',
            //     name: 'convention.rule-definitions',
            //     component: () => import('../../Pages/Admin/Convention/RuleDefinition.vue'), // Placeholder
            //     meta: { title: 'Rule Definitions' }
            // },
        ]
    },
];

export default convenationRoutes;
