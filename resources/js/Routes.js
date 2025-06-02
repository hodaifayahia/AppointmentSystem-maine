import Calender from "./Components/Calender.vue";
import ListAppointment from "./Pages/Appointments/ListAppointment.vue";
import DoctorListSchedule from "./Pages/Users/DoctorListSchedule.vue";
import ListUsers from "./Pages/Users/ListUsers.vue";
import ListDoctors from "./Pages/Users/ListDoctors.vue";
import PatientList from "./Pages/Patient/PatientList.vue";
import PatientAppointmentList from "./Pages/Patient/PatientAppointmentList.vue";
import Profile from "./Pages/Profile/Profile.vue";
import specializationList from "./Pages/Specialization/specializationList.vue";
import settings from "./Pages/Setting/settings.vue";
import ExcludeDates from "./Pages/Excludes/ExcludeDates.vue";
import DoctorListAppointment from "./Pages/Appointments/DoctorListAppointment.vue";
import AppointmentLIstDoctor from "./Pages/Users/AppointmentLIstDoctor.vue";
import SpecializationListAppointment from "./Pages/Appointments/SpecializationListAppointment.vue";
import AppointmentPage from "./Pages/Appointments/AppointmentPage.vue";
import PendingList from "./Pages/Pending/PendingList.vue";
import Login from "./auth/Login.vue";
import AppointmentsLIst from "./Pages/Appointments/AppointmentsLIst.vue";
import GeneralWaitlist from "./Components/waitList/GeneralWaitlist.vue";
import DailyWaitlist from "./Components/waitList/DailyWaitlist.vue";
import SpecializationListWaitlist from "./Pages/waitList/SpecializationListWaitlist.vue";
import WaitlistTypes from "./Pages/waitList/WaitlistTypes.vue";
import Waitlist from "./Pages/waitList/Waitlist.vue";
import AppointmentFormWaitlist from "./Components/appointments/appointmentFormWaitlist.vue";
import SettingsDoctor from "./Pages/Setting/settingsDoctor.vue";
import ExcludeDatesDoctor from "./Pages/Excludes/ExcludeDatesDoctor.vue";
import DoctorAvilibilte from "./Components/Doctor/DoctorAvilibilte.vue";
import DoctorListScheduleDForDoctor from "./Components/Doctor/DoctorListScheduleDForDoctor.vue";
import ListUsersCanForce from "./Components/Doctor/ListUsersCanForce.vue";
import ListUsersCanForceAdmin from "./Components/Doctor/ListUsersCanForceAdmin.vue";
import DataTable from "./Components/exclude/DataTable.vue";
import Dashborad from "./Components/Dashborad/Dashborad.vue";
import PlaceholdersList from "./Pages/Consultation/Section/PlaceholdersList.vue";
import AttributesList from "./Pages/Consultation/attributes/attributesList.vue";
import TemplateList from "./Pages/Consultation/template/templateList.vue";
import ConsultationList from "./Pages/Consultation/consultations/consultationList.vue";
import TemplateModel from "./Pages/Consultation/template/templateModel.vue";
import ConsultationModel from "./Pages/Consultation/consultations/consultationModel.vue";
import FolderList from "./Pages/Consultation/folder/FolderList.vue";
// import Logout from "./auth/Logout.vue"; // Assuming you have a Logout component
const adminRoutes = [
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: Dashborad,
    },
    {
        path: '/calander',
        name: 'admin.dashboard',
        component: Calender,
    },
    {
        path: '/login',
        name: 'auth.login',
        component: Login,
    },
    {
        path: '/admin/appointments/specialization',
        name: 'admin.appointments.specialization',
        component: SpecializationListAppointment,
    },
    {
        path: '/admin/appointments/doctors/:id',
        name: 'admin.appointments.doctors',
        component: DoctorListAppointment,
    },
    {
        path: '/admin/appointments/create/:id',
        name: 'admin.appointments.create',
        component: AppointmentPage,
        
    },
    {
        path: '/admin/appointments/create/:id/:specialization_id/:appointmentId',
        name: 'admin.appointments.edit',
        component: AppointmentPage,
      },
    {
        path: '/admin/appointments/:id',
        name: 'admin.appointments',
        component: ListAppointment,
    },
    {
        path: '/admin/pending',
        name: 'admin.pending',
        component: PendingList,
    },
    {
        path: '/admin/Waitlist/:id',
        children: [
            {
                path: 'types',
                name: 'admin.Waitlist.types',
                component: WaitlistTypes,
            },
            {
                path: 'general',
                name: 'admin.Waitlist.General',
                component: GeneralWaitlist,
            },
            {
                path: 'daily',
                name: 'admin.Waitlist.Daily',
                component: DailyWaitlist,
            },
        ],
    },
    {
        path: '/admin/patient',
        name: 'admin.patient',
        component: PatientList,
    },
    {
        path: '/admin/patient/appointments/:id',
        name: 'admin.patient.appointments',
        component: PatientAppointmentList,
    },
    {
        path: '/admin/users',
        name: 'admin.users',
        component: ListUsers,
    },
    {
        path: '/admin/excludeDates',
        name: 'admin.excludeDates',
        component: ExcludeDates,
    },
    {
        path: '/admin/excludeDates/:id',
        name: 'admin.excludeDates.doctor',
        component: DataTable,
    },
    {
        path: '/admin/doctors',
        name: 'admin.doctors',
        component: ListDoctors,
    },
    {
        path: '/admin/doctors/schedule/:id',
        name: 'admin.doctors.schedule',
        component: DoctorListSchedule,
    },
    {
        path: '/admin/doctor/forceAppointment',
        name: 'admin.doctor.users',
        component: ListUsersCanForceAdmin,
    },
    {
        path: '/admin/settings',
        name: 'admin.settings',
        component: SettingsDoctor,
    },
    {
        path: '/admin/Waitlist',  // Corrected path
        name: 'admin.Waitlist.specialization',
        component: SpecializationListWaitlist,
    },
    {
        path: '/admin/specializations',
        name: 'admin.specialization',
        component: specializationList,
    },
    {
        path: '/admin/profile',
        name: 'admin.profile',
        component: Profile,
    },
     {
        path: '/admin/consultations/placeholders',
        name: 'admin.consultation.placeholders',
        component: PlaceholdersList,
    
    },
     {
        path: '/admin/consultations/folderlist',
        name: 'admin.consultation.folderlist',
        component: FolderList,
    
    },
     {
        path: '/admin/consultations/template/:id',
        name: 'admin.consultation.template',
        component: TemplateList,
        
    
    },
     {
        path: '/admin/consultations/template/create',
        name: 'admin.consultation.template.add',
        component: TemplateModel,
    
    },
     {
        path: '/admin/consultations/template/edit/:id',
        name: 'admin.consultation.template.edit',
        component: TemplateModel,
    
    },
     {
        path: '/admin/consultations/Section/:id',
        name: 'admin.section.attributes',
        component: AttributesList,
    
    },
     {
        path: '/admin/consultations/consulation',
        name: 'admin.consultations.consulation',
        component: ConsultationList,
    
    },
     {
        path: '/admin/consultations/consulation/add',
        name: 'admin.consultations.consulation.add',
        component: ConsultationModel,
    
    },
     {
        path: '/admin/consultations/consulation/edit/:id',
        name: 'admin.consultations.consulation.edit',
        component: ConsultationModel,
    
    }
    ];

const doctorRoutes = [
    {
        path: '/doctor/dashboard',
        name: 'doctor.dashboard',
        component: Calender,
    },
    {
        path: '/doctor/appointments',
        name: 'doctor.appointments',
        component: AppointmentLIstDoctor,
    },
    {
        path: '/admin/doctors/schedule',
        name: 'doctors.schedule',
        component: DoctorListScheduleDForDoctor,
    },
    {
        path: '/doctor/avilability',
        name: 'doctor.avilability',
        component: DoctorAvilibilte,
    },
    {
        path: '/doctor/excludeDates',
        name: 'doctor.excludeDates',
        component: ExcludeDatesDoctor,
    },
    {
        path: '/doctor/users',
        name: 'doctor.users',
        component: ListUsersCanForce,
    },
   
];

// Combine all routes
const routes = [
    ...adminRoutes,
    ...doctorRoutes,
];

export default routes;