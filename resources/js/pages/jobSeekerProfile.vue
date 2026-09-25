<script setup>
import { ref } from 'vue';

const mobileMenuOpen = ref(false);
const profileNotice = ref('');

function announce(message) {
    profileNotice.value = message;
}
</script>

<template>
      <div class="flex flex-col bg-gray-100 min-h-screen">
        <header class="sticky top-0 z-50 bg-white border-b">
          <nav class="flex justify-between items-center px-6 h-14" aria-label="Main navigation">
          <div class="flex gap-2 items-center">
            <div class="flex gap-1.5 items-center">
              <div class="text-lg font-bold tracking-normal leading-6 text-cyan-900">NDK</div>
              <div class="px-1.5 py-0.5 text-xs font-semibold leading-4 text-white bg-cyan-900 rounded">MYANMAR</div>
            </div>
          </div>
          <div class="flex gap-8 items-center max-sm:hidden">
            <a href="#search" class="pb-0.5 text-sm font-semibold leading-5 text-cyan-900 border-b-2 border-cyan-900">Jobs</a>
            <a href="#companies" class="text-sm leading-5 text-gray-500">Companies</a>
            <a href="#pipeline" class="text-sm leading-5 text-gray-500">Dashboard</a>
          </div>
          <div class="flex gap-3 items-center">
            <div class="max-sm:hidden">
              <i class="ti ti-search text-xl text-gray-500 cursor-pointer" />
            </div>
            <div class="flex gap-1.5 items-center px-3 py-1.5 bg-gray-100 rounded-3xl cursor-pointer max-sm:hidden">
              <div class="w-2 h-2 bg-emerald-500 rounded-full" />
              <div class="text-sm font-medium leading-5 text-gray-700">Recruiter Mode</div>
            </div>
            <div class="text-sm leading-5 text-gray-500 cursor-pointer max-sm:hidden">Sign Out</div>
            <a href="#post-job" class="px-4 py-2 text-sm font-semibold leading-5 text-white bg-cyan-900 rounded-md max-sm:hidden">Post a Job</a>
            <button type="button" class="hidden items-center max-sm:flex" :aria-expanded="mobileMenuOpen" aria-label="Toggle navigation menu" @click="mobileMenuOpen = !mobileMenuOpen"><i class="ti ti-menu-2 text-2xl text-gray-700" /></button>
          </div>
          </nav>
          <div v-if="mobileMenuOpen" class="hidden max-sm:flex flex-col gap-3 border-t px-6 py-4 text-sm">
            <a href="#search" @click="mobileMenuOpen = false">Jobs</a><a href="#companies" @click="mobileMenuOpen = false">Companies</a><a href="#pipeline" @click="mobileMenuOpen = false">Dashboard</a><a href="#post-job" @click="mobileMenuOpen = false">Post a Job</a>
          </div>
        </header>
        <div class="overflow-hidden relative w-full h-[180px]">
          <img class="w-full h-full object-cover" :src="'/images/profile-cover.svg'" alt="" />
          <div class="absolute inset-0">
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1440 180" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="glow1" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#00aaff" stop-opacity="0.3"></stop><stop offset="100%" stop-color="#00aaff" stop-opacity="0"></stop></radialGradient><radialGradient id="glow2" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#00ccff" stop-opacity="0.5"></stop><stop offset="100%" stop-color="#00ccff" stop-opacity="0"></stop></radialGradient></defs><ellipse cx="720" cy="90" rx="400" ry="80" fill="url(#glow1)"></ellipse><circle cx="720" cy="90" r="60" fill="url(#glow2)"></circle><line x1="100" y1="30" x2="300" y2="80" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.4"></line><line x1="300" y1="80" x2="500" y2="40" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.4"></line><line x1="500" y1="40" x2="700" y2="90" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.4"></line><line x1="700" y1="90" x2="900" y2="50" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.4"></line><line x1="900" y1="50" x2="1100" y2="100" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.4"></line><line x1="1100" y1="100" x2="1300" y2="60" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.4"></line><line x1="200" y1="150" x2="400" y2="100" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.3"></line><line x1="400" y1="100" x2="600" y2="140" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.3"></line><line x1="600" y1="140" x2="800" y2="80" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.3"></line><line x1="800" y1="80" x2="1000" y2="130" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.3"></line><line x1="1000" y1="130" x2="1200" y2="70" stroke="#00aaff" stroke-width="0.5" stroke-opacity="0.3"></line><circle cx="100" cy="30" r="2" fill="#00ccff" fill-opacity="0.7"></circle><circle cx="300" cy="80" r="2" fill="#00ccff" fill-opacity="0.7"></circle><circle cx="500" cy="40" r="2" fill="#00ccff" fill-opacity="0.7"></circle><circle cx="700" cy="90" r="3" fill="#00eeff" fill-opacity="0.9"></circle><circle cx="900" cy="50" r="2" fill="#00ccff" fill-opacity="0.7"></circle><circle cx="1100" cy="100" r="2" fill="#00ccff" fill-opacity="0.7"></circle><circle cx="1300" cy="60" r="2" fill="#00ccff" fill-opacity="0.7"></circle><circle cx="200" cy="150" r="2" fill="#00ccff" fill-opacity="0.5"></circle><circle cx="400" cy="100" r="2" fill="#00ccff" fill-opacity="0.5"></circle><circle cx="600" cy="140" r="2" fill="#00ccff" fill-opacity="0.5"></circle><circle cx="800" cy="80" r="2" fill="#00ccff" fill-opacity="0.5"></circle><circle cx="1000" cy="130" r="2" fill="#00ccff" fill-opacity="0.5"></circle><circle cx="1200" cy="70" r="2" fill="#00ccff" fill-opacity="0.5"></circle><line x1="300" y1="80" x2="400" y2="100" stroke="#00aaff" stroke-width="0.4" stroke-opacity="0.3"></line><line x1="500" y1="40" x2="600" y2="140" stroke="#00aaff" stroke-width="0.4" stroke-opacity="0.3"></line><line x1="700" y1="90" x2="800" y2="80" stroke="#00aaff" stroke-width="0.4" stroke-opacity="0.3"></line><line x1="900" y1="50" x2="1000" y2="130" stroke="#00aaff" stroke-width="0.4" stroke-opacity="0.3"></line><line x1="1100" y1="100" x2="1200" y2="70" stroke="#00aaff" stroke-width="0.4" stroke-opacity="0.3"></line><circle cx="720" cy="90" r="4" fill="#00eeff" fill-opacity="1"></circle><circle cx="720" cy="90" r="8" fill="#00eeff" fill-opacity="0.3"></circle><circle cx="720" cy="90" r="14" fill="#00eeff" fill-opacity="0.1"></circle></svg>
          </div>
        </div>
        <div class="px-6 py-8 mx-auto w-full max-w-[1200px] max-sm:px-4">
          <div class="flex justify-between items-start mb-2 max-sm:flex-col max-sm:gap-4">
            <div>
              <div class="mb-1 text-3xl font-bold tracking-normal leading-9 text-gray-900">Zayar Min</div>
              <div class="mb-2 text-base font-medium leading-6 text-blue-600">Senior Odoo Developer in Yangon</div>
              <div class="flex flex-wrap gap-4 items-center max-sm:gap-2">
                <div class="flex gap-1 items-center">
                  <i class="ti ti-map-pin text-sm text-gray-500" />
                  <div class="text-sm leading-5 text-gray-500">Kamayut, Yangon</div>
                </div>
                <div class="flex gap-1 items-center">
                  <i class="ti ti-mail text-sm text-gray-500" />
                  <div class="text-sm leading-5 text-gray-500">zayarm@gmail.com</div>
                </div>
                <div class="flex gap-1 items-center">
                  <i class="ti ti-phone text-sm text-gray-500" />
                  <div class="text-sm leading-5 text-gray-500">+95 9 4210 12345</div>
                </div>
              </div>
            </div>
            <button type="button" class="px-5 py-2.5 text-sm font-semibold leading-5 text-white bg-cyan-900 rounded-lg" @click="announce('Profile editing is ready.')">Edit Profile</button>
          </div>
          <div class="flex flex-wrap gap-3 justify-between items-center mt-4">
            <div class="flex gap-2 items-center">
              <div class="flex gap-1.5 items-center px-3 py-1.5 rounded-3xl border border-emerald-500 border-solid">
                <div class="w-2 h-2 bg-emerald-500 rounded-full" />
                <div class="text-xs font-semibold leading-4 text-emerald-500">OPEN TO WORK</div>
              </div>
              <div class="text-sm leading-5 text-gray-500">Actively looking for full-time or contract roles</div>
            </div>
            <div class="flex gap-6 items-center">
              <div class="flex gap-1.5 items-center">
                <div class="text-base font-bold leading-5 text-gray-900">14</div>
                <div class="text-sm leading-5 text-gray-500">Applications Sent</div>
              </div>
              <div class="flex gap-1.5 items-center">
                <div class="text-base font-bold leading-5 text-gray-900">128</div>
                <div class="text-sm leading-5 text-gray-500">Profile Views</div>
              </div>
              <a href="data:text/plain;charset=utf-8,Zayar%20Min%20-%20Senior%20Odoo%20Developer" download="Zayar_Min_CV.txt" class="px-4 py-2 text-sm font-medium leading-5 text-gray-700 bg-white rounded-md border border-gray-300 border-solid">Download CV PDF</a>
            </div>
          </div>
          <p v-if="profileNotice" class="mt-3 rounded-lg bg-blue-50 px-4 py-2 text-sm text-blue-700" role="status">{{ profileNotice }}</p>
        </div>
        <div class="px-6 pb-12 mx-auto w-full max-w-[1200px] max-sm:px-4">
          <div class="flex gap-6 max-md:flex-col">
            <div class="flex flex-col flex-1 gap-5">
              <div class="p-7 bg-white rounded-xl border border border-solid">
                <div class="mb-1 text-lg font-bold leading-7 text-gray-900">About Me</div>
                <div class="mb-4 text-sm leading-5 text-blue-600">Professional summary of skills and career goals</div>
                <div class="text-sm leading-6 text-gray-700">Senior Odoo &amp; ERP Developer with over 6 years of experience configuring, customizing, and deploying robust business systems in Myanmar. Well-versed in Odoo custom module development, Python, PostgreSQL, and JavaScript. Deeply committed to empowering local financial and retail companies by building high-availability tools and clean database integrations. Fluent in English and native Burmese.</div>
              </div>
              <div class="p-7 bg-white rounded-xl border border border-solid">
                <div class="mb-1 text-lg font-bold leading-7 text-gray-900">Professional Experience</div>
                <div class="mb-6 text-sm leading-5 text-blue-600">My employment history and roles</div>
                <div class="mb-7">
                  <div class="flex justify-between items-start mb-1.5 max-sm:flex-col max-sm:gap-1">
                    <div class="text-base font-semibold leading-6 text-gray-900">Lead Odoo Developer</div>
                    <div class="text-sm leading-5 text-gray-500">Jun 2023 - Present</div>
                  </div>
                  <div class="mb-2.5 text-sm leading-5 text-blue-600">KBZ Group · Yangon, Myanmar</div>
                  <div class="flex flex-col gap-1.5">
                    <div class="flex gap-1.5 items-start">
                      <div class="text-sm leading-5 text-gray-700">• Led customization of Odoo v16/v17 modules across banking &amp; hospitality divisions.</div>
                    </div>
                    <div class="flex gap-1.5 items-start">
                      <div class="text-sm leading-5 text-gray-700">• Managed a team of 4 junior Python developers to deliver clean database schemas.</div>
                    </div>
                    <div class="flex gap-1.5 items-start">
                      <div class="text-sm leading-5 text-gray-700">• Enhanced system uptime by 30% through robust API and performance optimization.</div>
                    </div>
                  </div>
                </div>
                <div class="pt-6 border border-t">
                  <div class="flex justify-between items-start mb-1.5 max-sm:flex-col max-sm:gap-1">
                    <div class="text-base font-semibold leading-6 text-gray-900">ERP Systems Engineer</div>
                    <div class="text-sm leading-5 text-gray-500">Jan 2021 - May 2023</div>
                  </div>
                  <div class="mb-2.5 text-sm leading-5 text-blue-600">Wave Money · Bahan, Yangon</div>
                  <div class="flex flex-col gap-1.5">
                    <div class="flex gap-1.5 items-start">
                      <div class="text-sm leading-5 text-gray-700">• Engineered automated payroll &amp; reconciliation modules natively inside Python.</div>
                    </div>
                    <div class="flex gap-1.5 items-start">
                      <div class="text-sm leading-5 text-gray-700">• Scaled secure data migration pipelines for over 100,000 active agency logs.</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="p-7 bg-white rounded-xl border border border-solid">
                <div class="mb-1 text-lg font-bold leading-7 text-gray-900">Education &amp; Certifications</div>
                <div class="mb-5">
                  <div class="mb-0.5 text-base font-semibold leading-6 text-gray-900">B.C.Sc. Computer Science</div>
                  <div class="mb-0.5 text-sm leading-5 text-blue-600">University of Computer Studies, Yangon (UCSY)</div>
                  <div class="text-xs leading-4 text-gray-400">Class of 2019</div>
                </div>
                <div class="pt-5 border border-t">
                  <div class="mb-0.5 text-base font-semibold leading-6 text-gray-900">Certified Odoo Consultant (v16)</div>
                  <div class="mb-0.5 text-sm leading-5 text-blue-600">Odoo Professional Academy</div>
                  <div class="text-xs leading-4 text-gray-400">Licensed 2023</div>
                </div>
              </div>
              <div class="p-7 bg-white rounded-xl border border border-solid">
                <div class="mb-5 text-lg font-bold leading-7 text-gray-900">Skills &amp; Specialties</div>
                <div class="mb-4">
                  <div class="mb-2.5 text-xs font-semibold tracking-wide leading-4 text-blue-600 uppercase">TECHNICAL SKILLS</div>
                  <div class="flex flex-wrap gap-2">
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">Python</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">Odoo ERP</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">PostgreSQL</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">JavaScript</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">Docker</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">REST APIs</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">Git</div>
                  </div>
                </div>
                <div>
                  <div class="mb-2.5 text-xs font-semibold tracking-wide leading-4 text-blue-600 uppercase">SOFT SKILLS &amp; LANGUAGES</div>
                  <div class="flex flex-wrap gap-2">
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">Technical Mentorship</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">Agile Sprints</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">fluent English</div>
                    <div class="px-3 py-1.5 text-sm leading-5 text-gray-700 bg-gray-50 rounded-3xl border border-gray-300 border-solid">native Burmese</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-5 w-80 max-md:w-full">
              <div class="p-6 bg-white rounded-xl border border border-solid">
                <div class="flex justify-between items-center mb-3">
                  <div class="text-base font-semibold leading-6 text-gray-900">Profile Completeness</div>
                  <div class="text-base font-bold leading-6 text-emerald-500">85%</div>
                </div>
                <div class="mb-3 w-full h-2 bg-gray-200 rounded-full">
                  <div class="h-2 bg-emerald-500 rounded-full w-[85%]" />
                </div>
                <div class="text-xs leading-4 text-gray-500">Add a summary video or portfolio links to reach 100%!</div>
              </div>
              <div class="p-6 bg-white rounded-xl border border border-solid">
                <div class="mb-4 text-base font-semibold leading-6 text-gray-900">Uploaded Documents</div>
                <div class="flex gap-3 items-center mb-3">
                  <div class="flex justify-center items-center w-9 h-9 bg-blue-50 rounded-lg flex-[shrink]">
                    <i class="ti ti-file-text text-lg text-blue-600" />
                  </div>
                  <div>
                    <div class="text-sm font-medium leading-5 text-gray-900">Zayar_Min_Resume_2026.pdf</div>
                    <div class="text-xs leading-4 text-gray-400">PDF · 2.4 MB · Updated 2 days ago</div>
                  </div>
                </div>
                <div class="flex gap-3 items-center">
                  <div class="flex justify-center items-center w-9 h-9 bg-blue-50 rounded-lg flex-[shrink]">
                    <i class="ti ti-briefcase text-lg text-blue-600" />
                  </div>
                  <div>
                    <div class="text-sm font-medium leading-5 text-gray-900">ERP_Case_Studies.pdf</div>
                    <div class="text-xs leading-4 text-gray-400">PDF · 4.8 MB · Updated 1 week ago</div>
                  </div>
                </div>
              </div>
              <div class="p-6 bg-white rounded-xl border border border-solid">
                <div class="mb-4 text-base font-semibold leading-6 text-gray-900">Recommended for Your Profile</div>
                <a href="#search" class="block p-4 mb-3 rounded-xl border border border-solid cursor-pointer">
                  <div class="flex justify-between items-center mb-1">
                    <div class="text-sm font-semibold leading-5 text-gray-900">ERP Developer</div>
                    <div class="text-xs leading-4 text-gray-500">Yangon</div>
                  </div>
                  <div class="mb-1.5 text-xs leading-4 text-gray-500">CDSG Group</div>
                  <div class="text-xs font-semibold leading-4 text-emerald-500">1,500,000 - 2,000,000 MMK</div>
                </a>
                <a href="#search" class="block p-4 rounded-xl border border border-solid cursor-pointer">
                  <div class="flex justify-between items-center mb-1">
                    <div class="text-sm font-semibold leading-5 text-gray-900">Senior Python Developer</div>
                    <div class="text-xs leading-4 text-gray-500">Mandalay (Remote)</div>
                  </div>
                  <div class="mb-1.5 text-xs leading-4 text-gray-500">Wave Money</div>
                  <div class="text-xs font-semibold leading-4 text-emerald-500">1,800,000 - 2,500,000 MMK</div>
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-0 bg-cyan-900">
          <div class="px-6 py-8 mx-auto max-w-[1200px] max-sm:px-4">
            <div class="flex justify-between items-center max-sm:flex-col max-sm:gap-4 max-sm:items-start">
              <div class="flex gap-2 items-center">
                <div class="text-base font-bold leading-6 text-white">NDK</div>
                <div class="text-sm leading-5 text-blue-300">· Myanmar's Leading Professional Network</div>
              </div>
              <div class="flex flex-wrap gap-6 items-center max-sm:gap-4">
                <div class="text-sm leading-5 text-blue-300 cursor-pointer">About NDK</div>
                <div class="text-sm leading-5 text-blue-300 cursor-pointer">Privacy Policy</div>
                <div class="text-sm leading-5 text-blue-300 cursor-pointer">Terms</div>
                <div class="text-sm leading-5 text-blue-300 cursor-pointer">Contact Support</div>
              </div>
            </div>
            <div class="pt-5 mt-6 border-t border-cyan-800">
              <div class="text-xs leading-4 text-center text-slate-400">© 2026 NDK Job Platform. Connecting Myanmar's brightest talent with premium local &amp; multinational employers.</div>
            </div>
          </div>
        </div>
      </div>
</template>
