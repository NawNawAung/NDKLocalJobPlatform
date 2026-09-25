import './bootstrap';
import { computed, createApp, h, ref } from 'vue';
import Home from './pages/home.vue';
import JobDetails from './pages/jobDetails.vue';
import JobSearch from './pages/jobSearch.vue';
import PostJob from './pages/postJob.vue';
import CandidatePipeline from './pages/candidatePipeline.vue';
import JobSeekerProfile from './pages/jobSeekerProfile.vue';
import EmployerDashboard from './pages/employerDashboard.vue';
import InsightPage from './pages/insightPage.vue';
import MessagePage from './pages/messagePage.vue';
import SkillsAssessment from './pages/skillsAccessment.vue';
import LoginPage from './pages/login.vue';
import RegisterPage from './pages/register.vue';
import SiteHeader from './components/SiteHeader.vue';
import SiteFooter from './components/SiteFooter.vue';

const pages = {
    '#details': JobDetails,
    '#search': JobSearch,
    '#post-job': PostJob,
    '#pipeline': CandidatePipeline,
    '#profile': JobSeekerProfile,
    '#dashboard': EmployerDashboard,
    '#insights': InsightPage,
    '#messages': MessagePage,
    '#skills-assessment': SkillsAssessment,
};

const currentHash = ref(window.location.hash);
const authBootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
window.addEventListener('hashchange', () => {
    currentHash.value = window.location.hash;
});

createApp({
    setup() {
        const currentPage = computed(() => {
            if (authBootstrap.authPage === 'login') return LoginPage;
            if (authBootstrap.authPage === 'register') return RegisterPage;
            if (authBootstrap.page === 'search' && !currentHash.value) return JobSearch;
            return pages[currentHash.value] ?? Home;
        });

        return () => h('div', { class: 'min-h-screen bg-slate-50 text-slate-900' }, [
            h(SiteHeader, { isAuthenticated: authBootstrap.authenticated, csrfToken: authBootstrap.csrfToken }),
            h('main', { id: 'page-content' }, [h(currentPage.value)]),
            h(SiteFooter),
        ]);
    },
}).mount('#app');
