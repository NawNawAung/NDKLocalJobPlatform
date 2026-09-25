<template>
      <div class="flex flex-col bg-slate-100 min-h-screen">
        <div class="flex justify-between items-center px-8 py-0 bg-white border border-b h-[60px] max-sm:px-4">
          <div class="flex gap-2 items-center">
            <div class="flex gap-1.5 items-center">
              <i class="ti ti-user text-base text-gray-500" />
              <span class="text-lg font-bold tracking-normal text-cyan-900">NDK</span>
              <span class="px-1.5 py-0.5 text-xs font-semibold tracking-wider text-blue-600 uppercase bg-indigo-50 rounded">Myanmar</span>
            </div>
          </div>
          <div class="flex gap-8 items-center max-sm:hidden">
            <span class="text-sm text-gray-500 cursor-pointer">Dashboard</span>
            <span class="pb-0.5 text-sm font-semibold text-cyan-900 border-2 border-cyan-900 cursor-pointer">Jobs</span>
            <span class="text-sm text-gray-500 cursor-pointer">Candidates</span>
            <span class="text-sm text-gray-500 cursor-pointer">Messages</span>
          </div>
          <div class="flex gap-3 items-center">
            <i class="ti ti-search text-lg text-gray-500 cursor-pointer max-sm:block" />
            <div class="flex gap-1.5 items-center px-3 py-1.5 text-xs font-medium text-white bg-cyan-900 rounded-3xl cursor-pointer max-sm:hidden">
              <div class="w-2 h-2 bg-green-400 rounded-full"></div>
              <span class="">Recruiter Mode</span>
            </div>
            <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer max-sm:hidden">Post a Job</div>
            <div class="flex justify-center items-center w-8 h-8 text-xs font-bold text-cyan-900 bg-indigo-50 rounded-full">KM</div>
            <i class="ti ti-chevron-down text-sm text-gray-500" />
            <i class="ti ti-menu-2 hidden text-xl text-gray-500 cursor-pointer max-sm:block" />
          </div>
        </div>
        <div class="flex flex-col flex-1 items-center px-4 py-10">
          <div class="mb-8 text-3xl font-bold text-center text-gray-900">Post a New Job Listing</div>
          <div class="flex gap-0 justify-center items-center mb-8 w-full max-w-[600px]">
            <div class="flex flex-col gap-1.5 items-center">
              <div class="flex justify-center items-center w-8 h-8 bg-cyan-900 rounded-full">
                <div class="w-3 h-3 bg-white rounded-full"></div>
              </div>
              <span class="text-xs font-medium text-cyan-900">Basic Info</span>
            </div>
            <div class="flex-1 mx-1 -mt-3.5 h-px bg-gray-300"></div>
            <div class="flex flex-col gap-1.5 items-center">
              <div class="flex justify-center items-center w-8 h-8 bg-white rounded-full border-2 border-gray-300"></div>
              <span class="text-xs font-medium text-gray-400">Requirements</span>
            </div>
            <div class="flex-1 mx-1 -mt-3.5 h-px bg-gray-300"></div>
            <div class="flex flex-col gap-1.5 items-center">
              <div class="flex justify-center items-center w-8 h-8 bg-white rounded-full border-2 border-gray-300"></div>
              <span class="text-xs font-medium text-gray-400">Promotion</span>
            </div>
            <div class="flex-1 mx-1 -mt-3.5 h-px bg-gray-300"></div>
            <div class="flex flex-col gap-1.5 items-center">
              <div class="flex justify-center items-center w-8 h-8 bg-white rounded-full border-2 border-gray-300"></div>
              <span class="text-xs font-medium text-gray-400">Publish</span>
            </div>
          </div>
          <div class="p-10 w-full bg-white rounded-xl shadow-sm max-w-[760px] max-sm:p-5">
            <div class="pb-3 mb-6 text-lg font-semibold text-cyan-900 border border-b">Basic Information</div>
            <div class="flex flex-col gap-5">
              <div class="flex flex-col gap-1.5">
                <div class="flex gap-1 items-center">
                  <span class="text-sm font-medium text-gray-700">Job Title</span>
                  <span class="text-sm text-red-500">*</span>
                </div>
                <input
                  type="text"
                  placeholder="e.g. Senior Frontend Developer"
                  class="px-3.5 py-2.5 w-full text-sm text-gray-400 bg-white rounded-lg border border-gray-300 border-solid"
                />
              </div>
              <div class="flex gap-4 max-sm:flex-col">
                <div class="flex flex-col flex-1 gap-1.5">
                  <div class="flex gap-1 items-center">
                    <span class="text-sm font-medium text-gray-700">Job Category</span>
                    <span class="text-sm text-red-500">*</span>
                  </div>
                  <div class="relative">
                    <select class="px-3.5 py-2.5 w-full text-sm text-gray-700 bg-white rounded-lg border border-gray-300 border-solid cursor-pointer">
                      <option class="">Technology</option>
                    </select>
                    <i class="ti ti-chevron-down absolute right-3 top-2/4 text-base text-gray-500 -translate-y-2/4 pointer-events-none" />
                  </div>
                </div>
                <div class="flex flex-col flex-1 gap-1.5">
                  <div class="flex gap-1 items-center">
                    <span class="text-sm font-medium text-gray-700">Township / Location</span>
                    <span class="text-sm text-red-500">*</span>
                  </div>
                  <div class="relative">
                    <select class="px-3.5 py-2.5 w-full text-sm text-gray-700 bg-white rounded-lg border border-gray-300 border-solid cursor-pointer">
                      <option class="">Yangon</option>
                    </select>
                    <i class="ti ti-chevron-down absolute right-3 top-2/4 text-base text-gray-500 -translate-y-2/4 pointer-events-none" />
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <div class="flex gap-1 items-center">
                  <span class="text-sm font-medium text-gray-700">Work Mode</span>
                  <span class="text-sm text-red-500">*</span>
                </div>
                <div class="flex gap-6 items-center max-sm:flex-col max-sm:gap-3 max-sm:items-start">
                  <label class="flex gap-2 items-center cursor-pointer">
                    <input
                      type="radio"
                      name="workmode"
                      checked='false'
                      class="w-4 h-4 accent-[[#1e3a5f]]"
                    />
                    <span class="text-sm text-gray-700">On-site</span>
                  </label>
                  <label class="flex gap-2 items-center cursor-pointer">
                    <input
                      type="radio"
                      name="workmode"
                      class="w-4 h-4 accent-[[#1e3a5f]]"
                    />
                    <span class="text-sm text-gray-700">Hybrid</span>
                  </label>
                  <label class="flex gap-2 items-center cursor-pointer">
                    <input
                      type="radio"
                      name="workmode"
                      class="w-4 h-4 accent-[[#1e3a5f]]"
                    />
                    <span class="text-sm text-gray-700">Remote</span>
                  </label>
                </div>
              </div>
            </div>
            <div class="pb-3 mt-2 mb-5 text-lg font-semibold text-cyan-900 border border-b">Job Details &amp; Requirements</div>
            <div class="flex flex-col gap-5">
              <div class="flex flex-col gap-1.5">
                <div class="flex gap-1 items-center">
                  <span class="text-sm font-medium text-gray-700">Responsibilities</span>
                  <span class="text-sm text-red-500">*</span>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-300 border-solid">
                  <div class="flex gap-0.5 items-center px-2 py-1.5 border-b border-gray-300 bg-slate-100">
                    <button class="flex justify-center items-center w-7 h-7 text-sm font-bold text-gray-700 rounded">B</button>
                    <button class="flex justify-center items-center w-7 h-7 text-sm text-gray-700 rounded">I</button>
                    <button class="flex justify-center items-center w-7 h-7 text-sm text-gray-700 underline rounded">U</button>
                    <div class="mx-1 w-px bg-gray-300 h-[18px]"></div>
                    <button class="flex justify-center items-center w-7 h-7 text-gray-700 rounded">
                      <i class="ti ti-list text-sm" />
                    </button>
                    <button class="flex justify-center items-center w-7 h-7 text-gray-700 rounded">
                      <i class="ti ti-list-numbers text-sm" />
                    </button>
                    <div class="mx-1 w-px bg-gray-300 h-[18px]"></div>
                    <button class="flex justify-center items-center w-7 h-7 text-gray-700 rounded">
                      <i class="ti ti-link text-sm" />
                    </button>
                  </div>
                  <div class="p-3 text-sm text-gray-700 min-h-[100px]">
                    <div class="flex gap-1.5 items-start mb-1.5">
                      <span class="text-gray-700">•</span>
                      <span class="">Architect and engineer clean front-end application code.</span>
                    </div>
                    <div class="flex gap-1.5 items-start">
                      <span class="text-gray-700">•</span>
                      <span class="">Support and coordinate releases across multiple agile squads.</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <div class="flex gap-1 items-center">
                  <span class="text-sm font-medium text-gray-700">Qualifications</span>
                  <span class="text-sm text-red-500">*</span>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-300 border-solid">
                  <div class="flex gap-0.5 items-center px-2 py-1.5 border-b border-gray-300 bg-slate-100">
                    <button class="flex justify-center items-center w-7 h-7 text-sm font-bold text-gray-700 rounded">B</button>
                    <button class="flex justify-center items-center w-7 h-7 text-sm text-gray-700 rounded">I</button>
                    <button class="flex justify-center items-center w-7 h-7 text-sm text-gray-700 underline rounded">U</button>
                    <div class="mx-1 w-px bg-gray-300 h-[18px]"></div>
                    <button class="flex justify-center items-center w-7 h-7 text-gray-700 rounded">
                      <i class="ti ti-list text-sm" />
                    </button>
                    <button class="flex justify-center items-center w-7 h-7 text-gray-700 rounded">
                      <i class="ti ti-list-numbers text-sm" />
                    </button>
                  </div>
                  <div class="p-3 text-sm text-gray-700 min-h-[100px]">
                    <div class="flex gap-1.5 items-start mb-1.5">
                      <span class="text-gray-700">•</span>
                      <span class="">Bachelor's degree in CS, Computer Engineering, or related technical field.</span>
                    </div>
                    <div class="flex gap-1.5 items-start">
                      <span class="text-gray-700">•</span>
                      <span class="">4+ years of commercial development experience.</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-gray-700">Salary Range (MMK / month)</span>
                <div class="flex gap-3 items-center max-sm:flex-col max-sm:items-start">
                  <input
                    type="text"
                    value="800,000"
                    class="px-3.5 py-2.5 text-sm text-gray-700 rounded-lg border border-gray-300 border-solid w-[180px] max-sm:w-full"
                  />
                  <span class="text-sm text-gray-500">to</span>
                  <div class="flex flex-1 gap-2 items-center max-sm:w-full">
                    <input
                      type="text"
                      value="1,500,000"
                      class="flex-1 px-3.5 py-2.5 text-sm text-gray-700 rounded-lg border border-gray-300 border-solid max-sm:w-full"
                    />
                    <span class="text-sm text-gray-500">MMK / month</span>
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium text-gray-700">Required Skills</span>
                <div class="flex flex-wrap gap-2 items-center px-3 py-2.5 rounded-lg border border-gray-300 border-solid min-h-11">
                  <div class="flex gap-1.5 items-center px-2.5 py-1 text-sm font-medium text-gray-700 rounded-md bg-slate-100">
                    <span class="">React</span>
                    <span class="text-gray-500 cursor-pointer">×</span>
                  </div>
                  <div class="flex gap-1.5 items-center px-2.5 py-1 text-sm font-medium text-gray-700 rounded-md bg-slate-100">
                    <span class="">TypeScript</span>
                    <span class="text-gray-500 cursor-pointer">×</span>
                  </div>
                  <div class="flex gap-1.5 items-center px-2.5 py-1 text-sm font-medium text-gray-700 rounded-md bg-slate-100">
                    <span class="">CSS</span>
                    <span class="text-gray-500 cursor-pointer">×</span>
                  </div>
                  <span class="text-sm text-gray-400 cursor-pointer">+ Add skill...</span>
                </div>
              </div>
            </div>
            <div class="pb-3 mt-2 mb-5 text-lg font-semibold text-cyan-900 border border-b">Application Settings</div>
            <div class="flex gap-4 max-sm:flex-col">
              <div class="flex flex-col flex-1 gap-1.5">
                <div class="flex gap-1 items-center">
                  <span class="text-sm font-medium text-gray-700">Application Deadline</span>
                  <span class="text-sm text-red-500">*</span>
                </div>
                <div class="relative">
                  <input
                    type="text"
                    value="Sep 30, 2026"
                    class="py-2.5 pr-10 pl-3.5 w-full text-sm text-gray-700 rounded-lg border border-gray-300 border-solid"
                  />
                  <i class="ti ti-calendar absolute right-3 top-2/4 text-base text-gray-500 -translate-y-2/4" />
                </div>
              </div>
              <div class="flex flex-col flex-1 gap-1.5">
                <div class="flex gap-1 items-center">
                  <span class="text-sm font-medium text-gray-700">Notification Email</span>
                  <span class="text-sm text-red-500">*</span>
                </div>
                <input
                  type="text"
                  value="hr@company.com.mm"
                  class="px-3.5 py-2.5 w-full text-sm text-gray-700 rounded-lg border border-gray-300 border-solid"
                />
              </div>
            </div>
            <div class="flex flex-col gap-3 items-center mt-6">
              <button class="py-3.5 w-full text-base font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Publish Job Listing</button>
              <span class="text-sm font-medium text-cyan-900 underline cursor-pointer">Save as Draft</span>
            </div>
          </div>
        </div>
        <div class="px-16 py-8 mt-16 w-full bg-cyan-900 max-sm:px-5">
          <div class="flex justify-between items-center max-sm:flex-col max-sm:gap-5 max-sm:items-start">
            <div class="flex gap-2 items-center">
              <span class="text-lg font-bold text-white">NDK</span>
              <span class="text-sm text-blue-300">· Myanmar's Job Platform</span>
            </div>
            <div class="flex gap-6 items-center max-sm:flex-wrap max-sm:gap-4">
              <span class="text-sm text-blue-300 cursor-pointer">About NDK</span>
              <span class="text-sm text-blue-300 cursor-pointer">Privacy Policy</span>
              <span class="text-sm text-blue-300 cursor-pointer">Terms</span>
              <span class="text-sm text-blue-300 cursor-pointer">Contact</span>
            </div>
          </div>
          <div class="mt-6 text-xs text-center text-gray-500">© 2026 NDK Job Platform. Structured Wireframe representation.</div>
        </div>
      </div>
</template>
