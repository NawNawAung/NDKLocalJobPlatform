<template>
      <div class="flex flex-col bg-white min-h-screen">
        <div class="flex top-0 z-50 justify-between items-center px-8 py-4 bg-white border border-b">
          <div class="flex gap-4 items-center">
            <div class="flex gap-2 items-center">
              <span class="text-xl font-extrabold tracking-normal leading-7 text-cyan-900">NDK</span>
              <span class="px-2 py-0.5 text-xs font-bold tracking-wider text-blue-800 uppercase bg-blue-100 rounded">MYANMAR</span>
            </div>
            <div class="flex gap-6 items-center ml-8 max-sm:hidden">
              <span class="text-sm font-medium text-gray-500 cursor-pointer">Jobs</span>
              <span class="pb-0.5 text-sm font-semibold text-cyan-900 border-2 border-cyan-900 cursor-pointer">Skills &amp; Badges</span>
              <span class="text-sm font-medium text-gray-500 cursor-pointer">Salary Insights</span>
            </div>
          </div>
          <div class="flex gap-3 items-center">
            <div class="flex justify-center items-center w-8 h-8 cursor-pointer max-sm:hidden">
              <i class="ti ti-search text-xl text-gray-500" />
            </div>
            <div class="flex gap-1.5 items-center px-3 py-1.5 bg-indigo-100 rounded-full cursor-pointer max-sm:hidden">
              <div class="flex justify-center items-center w-4 h-4 bg-indigo-500 rounded-full">
                <div class="w-2 h-2 bg-white rounded-full" />
              </div>
              <span class="text-sm font-medium text-indigo-700">Recruiter Mode</span>
            </div>
            <span class="text-sm font-medium text-gray-700 cursor-pointer max-sm:hidden">Sign In</span>
            <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer max-sm:hidden">Post a Job</div>
            <div class="hidden justify-center items-center w-8 h-8 max-sm:flex">
              <i class="ti ti-menu-2 text-2xl text-gray-700" />
            </div>
          </div>
        </div>
        <div class="flex flex-col items-center px-8 py-14 text-center bg-blue-100">
          <div class="px-3 py-1 mb-5 text-xs font-bold tracking-wide text-white uppercase bg-cyan-900 rounded">NDK VERIFIED BADGES</div>
          <div class="mb-4 text-4xl font-extrabold tracking-normal leading-tight text-gray-900 max-w-[700px] max-sm:text-3xl">Stand Out to Myanmar's Top Employers</div>
          <div class="text-base leading-relaxed max-w-[560px] text-slate-500 max-sm:text-sm">Validate your skills with structured localization assessments aligned with Yangon tech clusters, local banking regulations, and Myanmar labor legislation.</div>
        </div>
        <div class="flex-1 px-8 py-12 bg-white max-sm:px-4">
          <div class="flex gap-8 mx-auto max-w-[1200px] max-md:flex-col">
            <div class="flex-1">
              <div class="mb-6 text-2xl font-bold text-gray-900">Available Skill Assessments</div>
              <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                <div class="flex flex-col gap-3 p-5 bg-white rounded-xl border border border-solid shadow-sm">
                  <div class="flex justify-between items-start">
                    <div class="flex justify-center items-center w-10 h-10 bg-blue-50 rounded-xl">
                      <i class="ti ti-circle-dashed text-xl text-blue-500" />
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded border border border-solid">Advanced</span>
                  </div>
                  <div>
                    <div class="mb-1 text-base font-bold text-gray-900">Odoo Development</div>
                    <div class="text-xs text-gray-500">45 mins · 30 Questions</div>
                  </div>
                  <div class="flex justify-between items-center mt-1">
                    <span class="text-xs font-medium text-gray-500">140+ Jobs seeking this</span>
                    <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Take Test</div>
                  </div>
                </div>
                <div class="flex flex-col gap-3 p-5 bg-white rounded-xl border border border-solid shadow-sm">
                  <div class="flex justify-between items-start">
                    <div class="flex justify-center items-center w-10 h-10 bg-blue-50 rounded-xl">
                      <i class="ti ti-circle-dashed text-xl text-blue-500" />
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded border border border-solid">Intermediate</span>
                  </div>
                  <div>
                    <div class="mb-1 text-base font-bold text-gray-900">English Business Comm.</div>
                    <div class="text-xs text-gray-500">30 mins · 25 Questions</div>
                  </div>
                  <div class="flex justify-between items-center mt-1">
                    <span class="text-xs font-medium text-gray-500">310+ Jobs seeking this</span>
                    <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Take Test</div>
                  </div>
                </div>
                <div class="flex flex-col gap-3 p-5 bg-white rounded-xl border border border-solid shadow-sm">
                  <div class="flex justify-between items-start">
                    <div class="flex justify-center items-center w-10 h-10 bg-blue-50 rounded-xl">
                      <i class="ti ti-circle-dashed text-xl text-blue-500" />
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded border border border-solid">Intermediate</span>
                  </div>
                  <div>
                    <div class="mb-1 text-base font-bold text-gray-900">Accounting Standards</div>
                    <div class="text-xs text-gray-500">40 mins · 30 Questions</div>
                  </div>
                  <div class="flex justify-between items-center mt-1">
                    <span class="text-xs font-medium text-gray-500">220+ Jobs seeking this</span>
                    <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Take Test</div>
                  </div>
                </div>
                <div class="flex flex-col gap-3 p-5 bg-white rounded-xl border border border-solid shadow-sm">
                  <div class="flex justify-between items-start">
                    <div class="flex justify-center items-center w-10 h-10 bg-blue-50 rounded-xl">
                      <i class="ti ti-circle-dashed text-xl text-blue-500" />
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded border border border-solid">Advanced</span>
                  </div>
                  <div>
                    <div class="mb-1 text-base font-bold text-gray-900">Python Programming</div>
                    <div class="text-xs text-gray-500">50 mins · 35 Questions</div>
                  </div>
                  <div class="flex justify-between items-center mt-1">
                    <span class="text-xs font-medium text-gray-500">180+ Jobs seeking this</span>
                    <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Take Test</div>
                  </div>
                </div>
                <div class="flex flex-col gap-3 p-5 bg-white rounded-xl border border border-solid shadow-sm">
                  <div class="flex justify-between items-start">
                    <div class="flex justify-center items-center w-10 h-10 bg-blue-50 rounded-xl">
                      <i class="ti ti-circle-dashed text-xl text-blue-500" />
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded border border border-solid">Beginner</span>
                  </div>
                  <div>
                    <div class="mb-1 text-base font-bold text-gray-900">Data Entry &amp; MS Office</div>
                    <div class="text-xs text-gray-500">25 mins · 20 Questions</div>
                  </div>
                  <div class="flex justify-between items-center mt-1">
                    <span class="text-xs font-medium text-gray-500">150+ Jobs seeking this</span>
                    <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Take Test</div>
                  </div>
                </div>
                <div class="flex flex-col gap-3 p-5 bg-white rounded-xl border border border-solid shadow-sm">
                  <div class="flex justify-between items-start">
                    <div class="flex justify-center items-center w-10 h-10 bg-blue-50 rounded-xl">
                      <i class="ti ti-circle-dashed text-xl text-blue-500" />
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded border border border-solid">Advanced</span>
                  </div>
                  <div>
                    <div class="mb-1 text-base font-bold text-gray-900">Myanmar Labor Law</div>
                    <div class="text-xs text-gray-500">35 mins · 25 Questions</div>
                  </div>
                  <div class="flex justify-between items-center mt-1">
                    <span class="text-xs font-medium text-gray-500">90+ Jobs seeking this</span>
                    <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Take Test</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-5 w-[340px] max-md:w-full">
              <div class="p-6 bg-white rounded-2xl border border border-solid shadow-sm">
                <div class="mb-5 text-lg font-bold text-gray-900">Your Assessment History</div>
                <div class="flex flex-col gap-4">
                  <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-semibold text-gray-900">Odoo Development</span>
                      <span class="text-sm font-bold text-cyan-900">88%</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="px-2 py-0.5 text-xs font-bold tracking-normal text-green-700 uppercase bg-green-100 rounded">VERIFIED EXPERT</span>
                      <div class="flex gap-1.5 items-center">
                        <span class="text-xs text-gray-400">Show on Profile</span>
                        <div class="flex relative items-center w-9 h-5 bg-blue-500 rounded-full cursor-pointer">
                          <div class="absolute right-0.5 w-4 h-4 bg-white rounded-full shadow-[0_1px_2px_rgba(0,0,0,0.2)]" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="border-t border-gray-100" />
                  <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-semibold text-gray-900">English Business Comm.</span>
                      <span class="text-sm font-bold text-cyan-900">94%</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="px-2 py-0.5 text-xs font-bold tracking-normal text-green-700 uppercase bg-green-100 rounded">VERIFIED FLUENT</span>
                      <div class="flex gap-1.5 items-center">
                        <span class="text-xs text-gray-400">Show on Profile</span>
                        <div class="flex relative items-center w-9 h-5 bg-blue-500 rounded-full cursor-pointer">
                          <div class="absolute right-0.5 w-4 h-4 bg-white rounded-full shadow-[0_1px_2px_rgba(0,0,0,0.2)]" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="border-t border-gray-100" />
                  <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-semibold text-gray-900">Python Programming</span>
                      <span class="text-sm font-bold text-cyan-900">62%</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="px-2 py-0.5 text-xs font-bold tracking-normal text-yellow-800 uppercase bg-yellow-100 rounded">VERIFIED PASS</span>
                      <div class="flex gap-1.5 items-center">
                        <span class="text-xs text-gray-400">Show on Profile</span>
                        <div class="flex relative items-center w-9 h-5 bg-gray-300 rounded-full cursor-pointer">
                          <div class="absolute left-0.5 w-4 h-4 bg-white rounded-full shadow-[0_1px_2px_rgba(0,0,0,0.2)]" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="p-6 bg-cyan-800 rounded-2xl">
                <div class="flex gap-2 items-center mb-4">
                  <i class="ti ti-eye text-base text-blue-300" />
                  <span class="text-xs font-bold tracking-wide text-blue-300 uppercase">PREVIEW: HOW EMPLOYERS SEE YOU</span>
                </div>
                <div class="p-4 bg-white rounded-xl">
                  <div class="flex gap-3 items-center mb-3">
                    <div class="flex justify-center items-center w-10 h-10 bg-blue-200 rounded-full">
                      <span class="text-sm font-bold text-cyan-900">ZM</span>
                    </div>
                    <div>
                      <div class="text-sm font-bold text-gray-900">Zayar Min</div>
                      <div class="text-xs text-gray-500">Senior Python Developer</div>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-2 items-center mb-3">
                    <div class="flex gap-1 items-center px-2 py-1 bg-green-100 rounded border border-green-300 border-solid">
                      <i class="ti ti-check text-xs text-green-700" />
                      <span class="text-xs font-bold tracking-normal text-green-700 uppercase">ODOO EXPERT</span>
                    </div>
                    <div class="flex gap-1 items-center px-2 py-1 bg-green-100 rounded border border-green-300 border-solid">
                      <i class="ti ti-check text-xs text-green-700" />
                      <span class="text-xs font-bold tracking-normal text-green-700 uppercase">ENG FLUENT</span>
                    </div>
                  </div>
                  <div class="text-xs leading-normal text-gray-500">Employers at KBZ Group, Wave Money, and Grab prioritize candidates with verified assessment badges.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="px-8 py-10 bg-cyan-900 max-sm:px-4">
          <div class="flex justify-between items-center mx-auto max-w-[1200px] max-sm:flex-col max-sm:gap-6 max-sm:items-start">
            <div class="flex gap-2 items-center">
              <span class="text-lg font-extrabold text-white">NDK</span>
              <span class="text-sm text-blue-300">· Myanmar's Premier Job Platform</span>
            </div>
            <div class="flex gap-6 items-center max-sm:flex-wrap max-sm:gap-4">
              <span class="text-sm text-blue-300 cursor-pointer">About NDK</span>
              <span class="text-sm text-blue-300 cursor-pointer">Privacy Policy</span>
              <span class="text-sm text-blue-300 cursor-pointer">Terms of Service</span>
              <span class="text-sm text-blue-300 cursor-pointer">Contact Support</span>
            </div>
          </div>
          <div class="pt-5 mx-auto mt-6 border-t border-cyan-800 max-w-[1200px]">
            <div class="text-xs text-center text-slate-400">© 2026 NDK Job Platform. Connecting Myanmar's brightest talent with top employers in Yangon, Mandalay, and beyond.</div>
          </div>
        </div>
      </div>
</template>
