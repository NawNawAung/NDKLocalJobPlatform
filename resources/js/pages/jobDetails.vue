<template>
      <div class="flex flex-col bg-slate-100 min-h-screen">
        <div class="flex justify-between items-center px-8 py-0 bg-white border border-b h-[60px] max-sm:px-4">
          <div class="flex gap-2 items-center">
            <div class="flex gap-1.5 items-center">
              <i class="ti ti-layout-grid text-lg text-gray-500" />
              <span class="text-lg font-bold tracking-normal text-cyan-900">NDK</span>
              <span class="px-1.5 py-0.5 text-xs font-semibold tracking-wider text-blue-800 uppercase bg-blue-100 rounded">MYANMAR</span>
            </div>
          </div>
          <div class="flex gap-8 items-center max-sm:hidden">
            <div class="flex gap-7 items-center">
              <span class="pb-0.5 text-sm font-semibold text-cyan-900 border-2 border-cyan-900 cursor-pointer">Jobs</span>
              <span class="text-sm text-gray-500 cursor-pointer">Companies</span>
              <span class="text-sm text-gray-500 cursor-pointer">Resources</span>
            </div>
          </div>
          <div class="flex gap-3 items-center">
            <i class="ti ti-search text-xl text-gray-500 cursor-pointer max-sm:block" />
            <div class="flex gap-1.5 items-center px-2.5 py-1 rounded-3xl bg-slate-200 max-sm:hidden">
              <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
              <span class="text-sm font-medium text-gray-700">Recruiter Mode</span>
            </div>
            <span class="text-sm font-medium text-gray-700 cursor-pointer max-sm:hidden">Sign In</span>
            <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-md cursor-pointer max-sm:hidden">Post a Job</div>
            <i class="ti ti-menu-2 hidden text-2xl text-gray-700 cursor-pointer max-sm:block" />
          </div>
        </div>
        <div class="px-8 py-6 mx-auto w-full max-w-[1200px] max-sm:px-4">
          <div class="flex gap-1.5 items-center mb-5 cursor-pointer">
            <i class="ti ti-arrow-left text-base text-gray-500" />
            <span class="text-sm text-gray-500">Back to Results</span>
          </div>
          <div class="flex gap-6 max-md:flex-col">
            <div class="flex flex-col flex-1 gap-5">
              <div class="p-7 bg-white rounded-xl border border border-solid max-sm:p-5">
                <div class="flex gap-3 justify-between items-start mb-5 max-sm:flex-col">
                  <div>
                    <div class="mb-2 text-2xl font-bold leading-tight text-gray-900">
                      <span class="">Senior</span>
                      <br />
                      <span class="">Developer</span>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center">
                      <span class="text-sm font-semibold text-blue-500 cursor-pointer">KBZ Group</span>
                      <span class="text-sm text-gray-400">·</span>
                      <span class="text-sm text-gray-500">Yangon, Myanmar</span>
                      <span class="text-sm text-gray-400">·</span>
                      <span class="text-sm text-gray-500">2 days ago</span>
                    </div>
                  </div>
                  <div class="flex gap-2 items-center">
                    <span class="px-3 py-1.5 text-xs font-medium text-blue-800 bg-blue-100 rounded-3xl">Full-time · Mid-Senior Level</span>
                  </div>
                </div>
                <div class="flex gap-10 mb-5 max-sm:gap-5">
                  <div>
                    <div class="mb-1 text-xs font-semibold tracking-wide text-gray-400 uppercase">OFFERED SALARY</div>
                    <div class="text-base font-bold text-gray-900">1,200,000 – 1,800,000 MMK/mo</div>
                  </div>
                  <div>
                    <div class="mb-1 text-xs font-semibold tracking-wide text-gray-400 uppercase">VACANCY</div>
                    <div class="text-base font-bold text-gray-900">2 Posts</div>
                  </div>
                </div>
                <div class="px-5 py-3 w-full text-sm font-semibold text-center text-white bg-cyan-900 rounded-lg cursor-pointer">Quick Apply Now</div>
              </div>
              <div class="p-7 bg-white rounded-xl border border border-solid max-sm:p-5">
                <div class="mb-4 text-lg font-bold text-gray-900">Key Responsibilities</div>
                <div class="flex flex-col gap-2.5">
                  <div class="flex gap-2.5 items-start">
                    <div class="mt-2 w-1.5 h-1.5 bg-gray-500 rounded-full flex-[shrink]"></div>
                    <span class="text-sm leading-relaxed text-gray-700">Design, write, and maintain robust, scalable code for internal core banking solutions.</span>
                  </div>
                  <div class="flex gap-2.5 items-start">
                    <div class="mt-2 w-1.5 h-1.5 bg-gray-500 rounded-full flex-[shrink]"></div>
                    <span class="text-sm leading-relaxed text-gray-700">Collaborate with product managers and other key stakeholders in Yangon headquarters.</span>
                  </div>
                  <div class="flex gap-2.5 items-start">
                    <div class="mt-2 w-1.5 h-1.5 bg-gray-500 rounded-full flex-[shrink]"></div>
                    <span class="text-sm leading-relaxed text-gray-700">Mentor junior developers and participate actively in regular, structured code reviews.</span>
                  </div>
                  <div class="flex gap-2.5 items-start">
                    <div class="mt-2 w-1.5 h-1.5 bg-gray-500 rounded-full flex-[shrink]"></div>
                    <span class="text-sm leading-relaxed text-gray-700">Troubleshoot, debug, and optimize performance bottleneck solutions across services.</span>
                  </div>
                </div>
                <div class="mt-6 mb-4 text-lg font-bold text-gray-900">Requirements</div>
                <div class="flex flex-col gap-2.5">
                  <div class="flex gap-2.5 items-start">
                    <i class="ti ti-check mt-px text-base text-blue-500 flex-[shrink]" />
                    <span class="text-sm leading-relaxed text-gray-700">Bachelor's degree in Computer Science, IT, or equivalent professional engineering experience.</span>
                  </div>
                  <div class="flex gap-2.5 items-start">
                    <i class="ti ti-check mt-px text-base text-blue-500 flex-[shrink]" />
                    <span class="text-sm leading-relaxed text-gray-700">4+ years of professional development experience (Java, Go, or Python preferred).</span>
                  </div>
                  <div class="flex gap-2.5 items-start">
                    <i class="ti ti-check mt-px text-base text-blue-500 flex-[shrink]" />
                    <span class="text-sm leading-relaxed text-gray-700">Good conversational English and fluent Burmese language skills.</span>
                  </div>
                </div>
                <div class="mt-6 mb-4 text-lg font-bold text-gray-900">Benefits Offered</div>
                <div class="flex flex-wrap gap-3">
                  <div class="flex gap-2 items-center px-3.5 py-2.5 bg-white rounded-lg border border border-solid">
                    <i class="ti ti-shield text-base text-gray-500" />
                    <span class="text-sm font-medium text-gray-700">Health Insurance</span>
                  </div>
                  <div class="flex gap-2 items-center px-3.5 py-2.5 bg-white rounded-lg border border border-solid">
                    <i class="ti ti-school text-base text-gray-500" />
                    <span class="text-sm font-medium text-gray-700">Training &amp; Courses</span>
                  </div>
                  <div class="flex gap-2 items-center px-3.5 py-2.5 bg-white rounded-lg border border border-solid">
                    <i class="ti ti-bus text-base text-gray-500" />
                    <span class="text-sm font-medium text-gray-700">Ferry / Transport</span>
                  </div>
                  <div class="flex gap-2 items-center px-3.5 py-2.5 bg-white rounded-lg border border border-solid">
                    <i class="ti ti-tools-kitchen-2 text-base text-gray-500" />
                    <span class="text-sm font-medium text-gray-700">Provided Meals</span>
                  </div>
                </div>
                <div class="flex flex-col gap-2 justify-center items-center p-8 mt-6 bg-blue-50 rounded-xl border-blue-200 border-[dashedpx]">
                  <i class="ti ti-cloud-upload text-4xl text-blue-500" />
                  <div class="text-base font-semibold text-blue-500">Upload your CV &amp; Apply</div>
                  <div class="text-sm text-gray-500">PDF, DOCX formats accepted (Max 5MB)</div>
                  <div class="flex gap-3 items-center mt-2">
                    <div class="px-5 py-2 text-sm font-medium text-gray-700 bg-white rounded-md border border-gray-300 border-solid cursor-pointer">Attach File</div>
                    <div class="px-5 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-md cursor-pointer">Send Application</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-4 w-80 flex-[shrink] max-md:w-full">
              <div class="p-6 rounded-xl bg-slate-400">
                <div class="flex gap-3 items-center mb-5">
                  <div class="flex justify-center items-center w-11 h-11 rounded-lg bg-slate-300 flex-[shrink]">
                    <span class="text-xs font-bold text-cyan-900">KBZ</span>
                  </div>
                  <div>
                    <div class="text-base font-bold text-white">KBZ Group</div>
                    <div class="text-sm text-blue-100">Banking &amp; Finance</div>
                  </div>
                </div>
                <div class="flex flex-col gap-3 mb-5">
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-blue-100">Company Size</span>
                    <span class="text-sm font-medium text-white">500+ Employees</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-blue-100">Location</span>
                    <span class="text-sm font-medium text-white">Yangon, MMR</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-blue-100">Website</span>
                    <span class="text-sm font-medium text-blue-200 underline cursor-pointer">kbzgroup.com.mm</span>
                  </div>
                </div>
                <div class="px-4 py-2.5 text-sm font-semibold text-center text-cyan-900 bg-white rounded-lg cursor-pointer">View Company Profile</div>
              </div>
              <div class="p-5 bg-white rounded-xl border border border-solid">
                <div class="mb-4 text-base font-bold text-gray-900">Similar Jobs</div>
                <div class="flex flex-col gap-3">
                  <div class="p-3.5 rounded-lg border border border-solid cursor-pointer">
                    <div class="mb-1 text-sm font-semibold text-blue-500">Junior Software Engineer</div>
                    <div class="flex justify-between items-center">
                      <span class="text-xs text-gray-500">Yangon</span>
                      <span class="text-xs font-medium text-gray-700">600,000 MMK</span>
                    </div>
                  </div>
                  <div class="p-3.5 rounded-lg border border border-solid cursor-pointer">
                    <div class="mb-1 text-sm font-semibold text-blue-500">Full Stack Developer</div>
                    <div class="flex justify-between items-center">
                      <span class="text-xs text-gray-500">Yangon</span>
                      <span class="text-xs font-medium text-gray-700">1,400,000 MMK</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-2.5">
                <div class="flex gap-2 justify-center items-center px-4 py-3 bg-white rounded-lg border border border-solid cursor-pointer">
                  <i class="ti ti-bookmark text-base text-gray-500" />
                  <span class="text-sm font-medium text-gray-700">Save Job Position</span>
                </div>
                <div class="flex gap-2 justify-center items-center px-4 py-3 bg-white rounded-lg border border border-solid cursor-pointer">
                  <i class="ti ti-share text-base text-gray-500" />
                  <span class="text-sm font-medium text-gray-700">Share Job Posting</span>
                </div>
                <div class="flex gap-2 justify-center items-center px-4 py-3 bg-white rounded-lg border border border-solid cursor-pointer">
                  <i class="ti ti-flag text-base text-gray-500" />
                  <span class="text-sm font-medium text-gray-700">Report Posting</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-auto bg-cyan-900">
          <div class="px-8 py-7 mx-auto max-w-[1200px] max-sm:px-4">
            <div class="flex flex-wrap gap-4 justify-between items-center">
              <div class="flex gap-2 items-center">
                <span class="text-base font-bold text-white">NDK</span>
                <span class="text-sm text-blue-300">· Myanmar's Job Platform</span>
              </div>
              <div class="flex flex-wrap gap-6 items-center max-sm:gap-4">
                <span class="text-sm text-blue-300 cursor-pointer">About NDK</span>
                <span class="text-sm text-blue-300 cursor-pointer">Privacy Policy</span>
                <span class="text-sm text-blue-300 cursor-pointer">Terms</span>
                <span class="text-sm text-blue-300 cursor-pointer">Contact</span>
              </div>
            </div>
            <div class="mt-5 text-xs text-center text-slate-500">© 2026 NDK Job Platform. Structured Wireframe representation.</div>
          </div>
        </div>
      </div>
</template>
