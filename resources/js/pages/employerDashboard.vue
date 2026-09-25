<script setup>
import { ref } from 'vue';

const dashboardNotice = ref('');
const pausedJobs = ref([]);
const closedJobs = ref([]);
const jobs = [
    { id: 1, title: 'Senior Odoo / Python Developer', location: 'Yangon · Posted 2 days ago', applicants: '23 Candidates', experience: '4.2 Years' },
    { id: 2, title: 'HR Operations Executive', location: 'Yangon · Posted 1 week ago', applicants: '14 Candidates', experience: '2.5 Years' },
];

function notify(message) { dashboardNotice.value = message; }
function togglePaused(job) {
    pausedJobs.value = pausedJobs.value.includes(job.id)
        ? pausedJobs.value.filter((id) => id !== job.id)
        : [...pausedJobs.value, job.id];
    notify(`${job.title} ${pausedJobs.value.includes(job.id) ? 'paused' : 'resumed'}.`);
}
function closeJob(job) {
    if (!closedJobs.value.includes(job.id)) closedJobs.value.push(job.id);
    notify(`${job.title} has been closed.`);
}
</script>

<template>
      <div class="flex flex-col bg-slate-100 min-h-screen">
        <div class="px-10 py-6 max-md:px-6 max-sm:px-4">
          <div class="flex justify-between items-center mb-6">
            <div class="flex gap-3 items-center">
              <div class="flex overflow-hidden justify-center items-center w-12 h-12 bg-gray-200 rounded-xl">
                <img class="w-full h-full object-cover" :src="'/images/companies/kbzLogo.png'" alt="KBZ Group logo" />
              </div>
              <div>
                <div class="flex gap-1.5 items-center">
                  <span class="text-xl font-bold text-gray-900">KBZ Group</span>
                  <i class="ti ti-chevron-down text-base text-gray-500" />
                </div>
                <div class="text-sm text-gray-500">Employer Dashboard · Yangon Head Office</div>
              </div>
            </div>
            <div class="flex gap-2.5 items-center">
              <div class="flex justify-center items-center w-9 h-9 bg-white rounded-lg border border border-solid cursor-pointer">
                <i class="ti ti-clock text-base text-gray-500" />
              </div>
              <a href="#post-job" class="flex gap-1.5 items-center px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg">+ Post New Job</a>
            </div>
          </div>
          <p v-if="dashboardNotice" class="mb-5 rounded-lg bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700" role="status">{{ dashboardNotice }}</p>
          <div class="grid grid-cols-4 gap-4 mb-6 max-md:grid-cols-2 max-sm:grid-cols-1">
            <div class="p-6 bg-white rounded-xl border border border-solid">
              <div class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">Active Job Posts</div>
              <div class="mb-2 text-3xl font-bold leading-none text-gray-900">8 Posts</div>
              <div class="text-sm text-gray-500">2 published this wk</div>
            </div>
            <div class="p-6 bg-white rounded-xl border border border-solid">
              <div class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">Total Applications</div>
              <div class="mb-2 text-3xl font-bold leading-none text-green-600">142 Applicants</div>
              <div class="text-sm text-gray-500">+24 new today</div>
            </div>
            <div class="p-6 bg-white rounded-xl border border border-solid">
              <div class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">Interviews Scheduled</div>
              <div class="mb-2 text-3xl font-bold leading-none text-amber-500">24 Candidates</div>
              <div class="text-sm text-gray-500">6 interviews tomorrow</div>
            </div>
            <div class="p-6 bg-white rounded-xl border border border-solid">
              <div class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">Positions Filled</div>
              <div class="mb-2 text-3xl font-bold leading-none text-gray-900">11 Hired</div>
              <div class="text-sm text-gray-500">Target reached: 92%</div>
            </div>
          </div>
          <div class="flex gap-5 max-md:flex-col">
            <div class="flex-1 p-7 bg-white rounded-xl border border border-solid">
              <div class="mb-1">
                <span class="text-lg font-bold text-gray-900">Active Job Postings</span>
              </div>
              <div class="mb-6 text-sm text-blue-700 cursor-pointer">Manage your published openings and applications</div>
              <div class="flex flex-col gap-4">
                <div v-for="job in jobs" :key="job.id" v-show="!closedJobs.includes(job.id)" class="p-5 rounded-xl border border border-solid">
                  <div class="flex justify-between items-start mb-3">
                    <div>
                      <div class="mb-1 text-base font-semibold text-gray-900">{{ job.title }}</div>
                      <div class="text-sm text-gray-500">{{ job.location }}</div>
                    </div>
                    <div class="px-2.5 py-1 text-xs font-semibold tracking-wider uppercase rounded-md" :class="pausedJobs.includes(job.id) ? 'text-amber-700 bg-amber-100' : 'text-green-600 bg-green-100'">{{ pausedJobs.includes(job.id) ? 'PAUSED' : 'ACTIVE' }}</div>
                  </div>
                  <div class="flex gap-6 items-center mb-4 max-sm:flex-col max-sm:gap-2 max-sm:items-start">
                    <div class="text-sm text-gray-700">
                      <span class="">Total Applicants:</span>
                      <span class="font-semibold text-blue-700">{{ job.applicants }}</span>
                    </div>
                    <div class="text-sm text-gray-700">
                      <span class="">Avg. Experience:</span>
                      <span class="font-semibold">{{ job.experience }}</span>
                    </div>
                  </div>
                  <div class="flex justify-between items-center max-sm:flex-col max-sm:gap-3 max-sm:items-start">
                    <a href="#pipeline" class="flex gap-1.5 items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-lg border border border-solid bg-slate-100">
                      <span class="">View Candidates in ATS</span>
                      <i class="ti ti-arrow-right text-sm" />
                    </a>
                    <div class="flex gap-4 items-center">
                      <button type="button" class="text-sm font-medium text-blue-700" @click="notify(`Editing ${job.title}.`)">Edit</button>
                      <button type="button" class="text-sm font-medium text-amber-500" @click="togglePaused(job)">{{ pausedJobs.includes(job.id) ? 'Resume' : 'Pause' }}</button>
                      <button type="button" class="text-sm font-medium text-red-500" @click="closeJob(job)">Close</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-5 w-[340px] max-md:w-full">
              <div class="p-6 bg-white rounded-xl border border border-solid">
                <div class="mb-4 text-base font-bold text-gray-900">Recent Applicants</div>
                <div class="flex flex-col gap-4">
                  <div class="flex justify-between items-center">
                    <div class="flex gap-2.5 items-center">
                      <div class="flex justify-center items-center w-10 h-10 text-xs font-bold text-blue-700 bg-blue-100 rounded-full">ZMR</div>
                      <div>
                        <div class="text-sm font-semibold text-gray-900">Zayar Min</div>
                        <div class="text-xs text-gray-500">Sr. Developer · Applied 10 mins ago</div>
                      </div>
                    </div>
                    <div class="px-2 py-1 text-xs font-semibold text-green-600 bg-green-100 rounded-md">92% Match</div>
                  </div>
                  <div class="flex justify-between items-center">
                    <div class="flex gap-2.5 items-center">
                      <div class="flex justify-center items-center w-10 h-10 text-xs font-bold text-indigo-600 bg-indigo-100 rounded-full">HTW</div>
                      <div>
                        <div class="text-sm font-semibold text-gray-900">Hla Thi Win</div>
                        <div class="text-xs text-gray-500">UI Developer · Applied 2 hours ago</div>
                      </div>
                    </div>
                    <div class="px-2 py-1 text-xs font-semibold text-green-600 bg-green-100 rounded-md">88% Match</div>
                  </div>
                </div>
              </div>
              <div class="p-6 bg-white rounded-xl border border border-solid">
                <div class="mb-4 text-base font-bold text-gray-900">Views vs Applications</div>
                <div class="flex flex-col gap-3.5">
                  <div>
                    <div class="mb-2 text-sm font-medium text-gray-700">Odoo Developer</div>
                    <div class="flex gap-1 items-center">
                      <div class="h-2.5 bg-gray-300 rounded-full w-[68%]" />
                      <div class="h-2.5 bg-cyan-900 rounded-full w-[22%]" />
                    </div>
                  </div>
                  <div>
                    <div class="mb-2 text-sm font-medium text-gray-700">HR Specialist</div>
                    <div class="flex gap-1 items-center">
                      <div class="h-2.5 bg-gray-300 rounded-full w-[52%]" />
                      <div class="h-2.5 bg-amber-500 rounded-full w-[16%]" />
                    </div>
                  </div>
                </div>
                <div class="flex gap-4 items-center mt-4">
                  <div class="flex gap-1.5 items-center">
                    <div class="w-2.5 h-2.5 bg-gray-300 rounded-full" />
                    <span class="text-xs text-gray-500">Job Views</span>
                  </div>
                  <div class="flex gap-1.5 items-center">
                    <div class="w-2.5 h-2.5 bg-cyan-900 rounded-full" />
                    <span class="text-xs text-gray-500">Applications</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="p-10 mt-auto bg-slate-200 max-sm:px-4">
          <div class="flex justify-between items-center mb-0 max-sm:flex-col max-sm:gap-4 max-sm:items-start">
            <div class="flex gap-2 items-center">
              <span class="text-lg font-bold text-blue-700">NDK</span>
              <span class="text-sm text-gray-700">· Myanmar's Leading Professional Network</span>
            </div>
            <div class="flex gap-6 items-center max-sm:flex-wrap max-sm:gap-3">
              <span class="text-sm text-gray-700 cursor-pointer">About NDK</span>
              <span class="text-sm text-gray-700 cursor-pointer">Privacy Policy</span>
              <span class="text-sm text-gray-700 cursor-pointer">Terms</span>
              <span class="text-sm text-gray-700 cursor-pointer">Contact Support</span>
            </div>
          </div>
          <div class="mt-6 text-xs text-center text-gray-500">© 2026 NDK Job Platform. Connecting Myanmar's brightest talent with premium local &amp; multinational employers.</div>
        </div>
      </div>
</template>
