<script setup>
import { ref } from 'vue';

const insightsUpdated = ref(false);
</script>

<template>
      <div class="flex flex-col bg-slate-100 min-h-screen">
        <div class="flex justify-between items-center px-6 py-0 bg-white border border-b h-[60px] max-sm:px-4">
          <div class="flex gap-3 items-center">
            <div class="flex gap-2 items-center">
              <div class="flex justify-center items-center w-8 h-8 bg-gray-200 rounded-full">
                <i class="ti ti-user text-base text-gray-500" />
              </div>
              <div class="flex gap-1.5 items-center">
                <span class="text-xl font-bold tracking-normal leading-7 text-blue-700">NDK</span>
                <span class="px-2 py-0.5 text-xs font-semibold tracking-wider text-blue-700 uppercase bg-blue-100 rounded">Myanmar</span>
              </div>
            </div>
          </div>
          <div class="flex gap-8 items-center max-sm:hidden">
            <span class="text-sm text-gray-700 cursor-pointer">Jobs</span>
            <span class="text-sm text-gray-700 cursor-pointer">Skills &amp; Badges</span>
            <span class="pb-0.5 text-sm font-semibold text-blue-700 border-2 border-blue-700 cursor-pointer">Salary Insights</span>
          </div>
          <div class="flex gap-3 items-center">
            <button class="flex justify-center items-center w-9 h-9 rounded-full max-sm:flex">
              <i class="ti ti-search text-lg text-gray-500" />
            </button>
            <div class="flex gap-2 items-center px-3 py-1.5 bg-gray-100 rounded-full max-sm:hidden">
              <div class="w-2 h-2 bg-blue-500 rounded-full" />
              <span class="text-sm font-medium text-gray-700">Recruiter Mode</span>
            </div>
            <span class="text-sm text-gray-700 cursor-pointer max-sm:hidden">Sign In</span>
            <button class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer border-[black] border-[nonepx] max-sm:hidden">Post a Job</button>
            <button class="hidden justify-center items-center w-9 h-9 rounded-full max-sm:flex">
              <i class="ti ti-menu-2 text-xl text-gray-700" />
            </button>
          </div>
        </div>
        <div class="flex gap-3 items-center px-10 py-4 max-sm:flex-wrap max-sm:px-4">
          <div class="flex gap-2 items-center px-3 py-2 bg-white rounded-lg border border border-solid cursor-pointer">
            <i class="ti ti-map-pin text-sm text-gray-500" />
            <span class="text-sm text-gray-700">Yangon (Kamayut, Bahan, Dagon)</span>
            <i class="ti ti-chevron-down text-sm text-gray-500" />
          </div>
          <div class="flex gap-2 items-center px-3 py-2 bg-white rounded-lg border border border-solid cursor-pointer">
            <span class="text-sm text-gray-700">All Industries</span>
            <i class="ti ti-chevron-down text-sm text-gray-500" />
          </div>
          <div class="flex gap-2 items-center px-3 py-2 bg-white rounded-lg border border border-solid cursor-pointer">
            <span class="text-sm text-gray-700">Mid-Senior Level</span>
            <i class="ti ti-chevron-down text-sm text-gray-500" />
          </div>
          <div class="ml-auto max-sm:ml-0">
            <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg cursor-pointer bg-slate-300 border-[black] border-[nonepx] text-slate-600" @click="insightsUpdated = true">Update Insights</button>
          </div>
        </div>
        <p v-if="insightsUpdated" class="mx-10 mb-4 text-sm text-emerald-700 max-sm:mx-4" role="status">Insights updated with the selected filters.</p>
        <div class="flex gap-6 px-10 pb-10 max-md:flex-col max-sm:px-4 max-sm:pb-6">
          <div class="flex flex-col flex-1 gap-6 min-w-0">
            <div class="p-7 bg-white rounded-xl border border border-solid shadow-sm">
              <div class="mb-5">
                <div class="mb-1 text-lg font-bold leading-7 text-gray-900">Monthly Salary Ranges in MMK</div>
                <div class="text-sm text-gray-500">Based on real job postings and verified candidate inputs across Myanmar</div>
              </div>
              <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Software Engineer</span>
                    <span class="text-xs text-gray-500">0.8M - 2.0M MMK</span>
                  </div>
                  <div class="relative w-full h-2 bg-gray-200 rounded-full">
                    <div class="absolute w-9/12 h-2 bg-blue-200 rounded-full left-[0%]" />
                    <div class="absolute -top-0.5 w-3 h-3 bg-blue-500 rounded-full left-[35%]" />
                  </div>
                </div>
                <div class="flex flex-col gap-2">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Product Manager</span>
                    <span class="text-xs text-gray-500">1.0M - 2.5M MMK</span>
                  </div>
                  <div class="relative w-full h-2 bg-gray-200 rounded-full">
                    <div class="absolute h-2 bg-blue-200 rounded-full left-[0%] w-[85%]" />
                    <div class="absolute -top-0.5 w-3 h-3 bg-blue-500 rounded-full left-[45%]" />
                  </div>
                </div>
                <div class="flex flex-col gap-2">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Finance Analyst</span>
                    <span class="text-xs text-gray-500">0.7M - 1.8M MMK</span>
                  </div>
                  <div class="relative w-full h-2 bg-gray-200 rounded-full">
                    <div class="absolute h-2 bg-blue-200 rounded-full left-[0%] w-[65%]" />
                    <div class="absolute -top-0.5 w-3 h-3 bg-blue-500 rounded-full left-[28%]" />
                  </div>
                </div>
                <div class="flex flex-col gap-2">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Sales Executive</span>
                    <span class="text-xs text-gray-500">0.5M - 1.5M MMK</span>
                  </div>
                  <div class="relative w-full h-2 bg-gray-200 rounded-full">
                    <div class="absolute h-2 bg-blue-200 rounded-full left-[0%] w-[55%]" />
                    <div class="absolute -top-0.5 w-3 h-3 bg-blue-500 rounded-full left-[18%]" />
                  </div>
                </div>
                <div class="flex flex-col gap-2">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">HR Generalist</span>
                    <span class="text-xs text-gray-500">0.5M - 1.4M MMK</span>
                  </div>
                  <div class="relative w-full h-2 bg-gray-200 rounded-full">
                    <div class="absolute w-6/12 h-2 bg-blue-200 rounded-full left-[0%]" />
                    <div class="absolute -top-0.5 w-3 h-3 bg-blue-500 rounded-full left-[22%]" />
                  </div>
                </div>
              </div>
              <div class="flex gap-4 items-center mt-4">
                <div class="flex gap-1.5 items-center">
                  <div class="w-3 h-2 bg-blue-200 rounded-sm" />
                  <span class="text-xs text-gray-500">Min - Max range</span>
                </div>
                <div class="flex gap-1.5 items-center">
                  <div class="w-2.5 h-2.5 bg-blue-500 rounded-full" />
                  <span class="text-xs text-gray-500">Median average salary</span>
                </div>
              </div>
            </div>
            <div class="p-7 bg-white rounded-xl border border border-solid shadow-sm">
              <div class="mb-5">
                <div class="text-lg font-bold leading-7 text-gray-900">Industry Breakdown &amp; Growth</div>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                  <thead>
                    <tr class="bg-blue-50">
                      <th class="px-4 py-2.5 text-xs font-semibold text-left text-gray-700 rounded-md">Industry sector</th>
                      <th class="px-4 py-2.5 text-xs font-semibold text-center text-gray-700">Avg Monthly Salary</th>
                      <th class="px-4 py-2.5 text-xs font-semibold text-center text-gray-700 rounded-md">YoY Trend</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="border-b border-gray-100">
                      <td class="px-4 py-3.5 text-sm text-gray-700">Banking &amp; Financial Services</td>
                      <td class="px-4 py-3.5 text-sm font-semibold text-center text-blue-700">1,500,000 MMK</td>
                      <td class="px-4 py-3.5 text-center">
                        <span class="px-2 py-1 text-xs font-semibold text-green-600 bg-green-100 rounded">+14.2% YoY</span>
                      </td>
                    </tr>
                    <tr class="border-b border-gray-100">
                      <td class="px-4 py-3.5 text-sm text-gray-700">Technology &amp; Telecom</td>
                      <td class="px-4 py-3.5 text-sm font-semibold text-center text-blue-700">1,350,000 MMK</td>
                      <td class="px-4 py-3.5 text-center">
                        <span class="px-2 py-1 text-xs font-semibold text-green-600 bg-green-100 rounded">+18.5% YoY</span>
                      </td>
                    </tr>
                    <tr class="border-b border-gray-100">
                      <td class="px-4 py-3.5 text-sm text-gray-700">E-Commerce &amp; Logistics</td>
                      <td class="px-4 py-3.5 text-sm font-semibold text-center text-blue-700">950,000 MMK</td>
                      <td class="px-4 py-3.5 text-center">
                        <span class="px-2 py-1 text-xs font-semibold text-green-600 bg-green-100 rounded">+11.0% YoY</span>
                      </td>
                    </tr>
                    <tr>
                      <td class="px-4 py-3.5 text-sm text-gray-700">FMCG / Manufacturing</td>
                      <td class="px-4 py-3.5 text-sm font-semibold text-center text-blue-700">850,000 MMK</td>
                      <td class="px-4 py-3.5 text-center">
                        <span class="px-2 py-1 text-xs font-semibold text-green-600 bg-green-100 rounded">+4.5% YoY</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="flex flex-col gap-6 w-[380px] max-md:w-full">
            <div class="p-7 bg-white rounded-xl border border border-solid shadow-sm">
              <div class="mb-5">
                <div class="mb-1 text-lg font-bold leading-7 text-gray-900">Career Path Progression Ladder</div>
                <div class="text-sm text-gray-500">Engineering &amp; Product roles benchmarks</div>
              </div>
              <div class="flex flex-col gap-3">
                <div class="flex gap-3 items-start p-3.5 rounded-xl border border border-solid bg-slate-50">
                  <div class="flex justify-center items-center w-8 h-8 bg-cyan-900 rounded-full flex-[shrink]">
                    <span class="text-sm font-bold text-white">4</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-1">
                      <span class="text-sm font-semibold text-gray-900">Director / VP</span>
                      <span class="text-xs text-gray-500">10+ Yrs</span>
                    </div>
                    <div class="text-xs leading-4 text-gray-500">Key Demands: Strategic Leadership, Governance, Portfolio Mgt</div>
                  </div>
                </div>
                <div class="flex gap-3 items-start p-3.5 rounded-xl border border border-solid bg-slate-50">
                  <div class="flex justify-center items-center w-8 h-8 bg-cyan-900 rounded-full flex-[shrink]">
                    <span class="text-sm font-bold text-white">3</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-1">
                      <span class="text-sm font-semibold text-gray-900">Senior / Manager</span>
                      <span class="text-xs text-gray-500">5-8 Yrs</span>
                    </div>
                    <div class="text-xs leading-4 text-gray-500">Key Demands: System Architecture, Team Delivery, Budgeting</div>
                  </div>
                </div>
                <div class="flex gap-3 items-start p-3.5 rounded-xl border border border-solid bg-slate-50">
                  <div class="flex justify-center items-center w-8 h-8 bg-cyan-900 rounded-full flex-[shrink]">
                    <span class="text-sm font-bold text-white">2</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-1">
                      <span class="text-sm font-semibold text-gray-900">Mid-Level Professional</span>
                      <span class="text-xs text-gray-500">2-5 Yrs</span>
                    </div>
                    <div class="text-xs leading-4 text-gray-500">Key Demands: Advanced Frameworks, Independent Output, Mentoring</div>
                  </div>
                </div>
                <div class="flex gap-3 items-start p-3.5 rounded-xl border border border-solid bg-slate-50">
                  <div class="flex justify-center items-center w-8 h-8 bg-cyan-900 rounded-full flex-[shrink]">
                    <span class="text-sm font-bold text-white">1</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-1">
                      <span class="text-sm font-semibold text-gray-900">Associate / Junior</span>
                      <span class="text-xs text-gray-500">0-2 Yrs</span>
                    </div>
                    <div class="text-xs leading-4 text-gray-500">Key Demands: Foundational Code, Database Basics, Conversational English</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="p-7 bg-white rounded-xl border border border-solid shadow-sm">
              <div class="mb-5">
                <div class="text-lg font-bold leading-7 text-gray-900">Myanmar Market Trends</div>
              </div>
              <div class="flex flex-col gap-4">
                <div class="p-4 bg-sky-50 rounded-xl border border-sky-200 border-solid">
                  <div class="mb-1.5 text-xs font-bold tracking-wide text-sky-600 uppercase">IN-DEMAND SKILL VALUE BOOST</div>
                  <div class="mb-1.5 text-sm font-bold text-gray-900">Odoo Development &amp; Customization</div>
                  <div class="text-xs leading-5 text-gray-500">Odoo skills command a +25% salary premium over generic Python developers in Yangon currently.</div>
                </div>
                <div class="p-4 bg-green-50 rounded-xl border border-green-200 border-solid">
                  <div class="mb-1.5 text-xs font-bold tracking-wide text-green-600 uppercase">FASTEST GROWING SECTOR</div>
                  <div class="mb-1.5 text-sm font-bold text-gray-900">Fintech &amp; Micro-Finance Services</div>
                  <div class="text-xs leading-5 text-gray-500">Wave Money, Grab Financial, and local banks like KBZ are driving 64% of technology hiring in Myanmar.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-auto bg-cyan-900">
          <div class="flex justify-between items-center px-10 py-6 max-sm:flex-col max-sm:gap-4 max-sm:px-4 max-sm:text-center">
            <div class="flex gap-2 items-center">
              <span class="text-base font-bold text-white">NDK</span>
              <span class="text-sm text-blue-300">· Myanmar's Premier Job Platform</span>
            </div>
            <div class="flex gap-6 items-center max-sm:flex-wrap max-sm:gap-4 max-sm:justify-center">
              <span class="text-sm text-blue-300 cursor-pointer">About NDK</span>
              <span class="text-sm text-blue-300 cursor-pointer">Privacy Policy</span>
              <span class="text-sm text-blue-300 cursor-pointer">Terms of Service</span>
              <span class="text-sm text-blue-300 cursor-pointer">Contact Support</span>
            </div>
          </div>
          <div class="px-10 py-4 border-t border-cyan-800 max-sm:px-4">
            <div class="text-xs text-center text-slate-500">© 2026 NDK Job Platform. Connecting Myanmar's brightest talent with top employers in Yangon, Mandalay, and beyond.</div>
          </div>
        </div>
      </div>
</template>
