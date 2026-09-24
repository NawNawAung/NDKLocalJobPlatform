import './bootstrap';
import { computed, createApp, ref } from 'vue';
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
window.addEventListener('hashchange', () => {
    currentHash.value = window.location.hash;
});

createApp({
    setup() {
        const currentPage = computed(() => pages[currentHash.value] ?? Home);

        return { currentPage };
    },
    template: '<component :is="currentPage" />',
}).mount('#app');
