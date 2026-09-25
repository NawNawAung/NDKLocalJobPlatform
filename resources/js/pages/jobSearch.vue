<template>
      <div class="flex flex-col bg-gray-100 min-h-screen">
        <div class="flex justify-between items-center px-8 py-0 bg-white border border-b h-[60px] max-sm:px-4">
          <div class="flex gap-3 items-center">
            <div class="flex gap-2 items-center">
              <span class="text-xl font-bold tracking-normal leading-7 text-cyan-900">NDK</span>
              <span class="px-2 py-0.5 text-xs font-semibold tracking-wider text-blue-700 uppercase bg-blue-100 rounded">MYANMAR</span>
            </div>
          </div>
          <div class="flex gap-8 items-center max-sm:hidden">
            <div class="flex gap-8 items-center">
              <span class="py-5 text-sm font-medium text-blue-700 border-2 border-blue-700 cursor-pointer">Jobs</span>
              <span class="text-sm font-medium text-gray-500 cursor-pointer">Companies</span>
              <span class="text-sm font-medium text-gray-500 cursor-pointer">Resources</span>
            </div>
          </div>
          <div class="flex gap-3 items-center">
            <div class="flex justify-center items-center w-9 h-9 cursor-pointer max-sm:flex">
              <i class="ti ti-search text-xl text-gray-500" />
            </div>
            <div class="flex gap-2 items-center px-3 py-1.5 bg-gray-100 rounded-3xl cursor-pointer max-sm:hidden">
              <div class="flex justify-center items-center w-4 h-4 bg-blue-500 rounded-full">
                <div class="w-2 h-2 bg-white rounded-full"></div>
              </div>
              <span class="text-sm font-medium text-gray-700">Recruiter Mode</span>
            </div>
            <span class="text-sm font-medium text-gray-700 cursor-pointer max-sm:hidden">Sign In</span>
            <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer max-sm:hidden">Post a Job</div>
            <div class="hidden justify-center items-center w-9 h-9 cursor-pointer max-sm:flex">
              <i class="ti ti-menu-2 text-2xl text-gray-700" />
            </div>
          </div>
        </div>
        <div class="flex gap-3 items-center px-8 py-4 bg-white border border-b max-sm:flex-col max-sm:px-4">
          <div class="flex gap-2 items-center px-3.5 py-2.5 bg-white rounded-lg border border border-solid w-[280px] max-sm:w-full">
            <i class="ti ti-search text-base text-gray-400" />
            <input
              type="text"
              value="Senior Developer"
              class="w-full text-sm text-gray-700 bg-transparent border-[black] border-[nonepx]"
            />
          </div>
          <div class="flex gap-2 items-center px-3.5 py-2.5 bg-white rounded-lg border border border-solid cursor-pointer w-[180px] max-sm:w-full">
            <i class="ti ti-map-pin text-base text-gray-400" />
            <span class="flex-1 text-sm text-gray-700">Yangon</span>
            <i class="ti ti-chevron-down text-base text-gray-400" />
          </div>
          <span class="ml-1 text-sm text-gray-500 max-sm:ml-0">127 results found</span>
          <div class="flex gap-2 items-center ml-auto max-sm:ml-0">
            <span class="text-sm text-gray-500">Sort by:</span>
            <div class="flex gap-1 items-center px-2.5 py-1.5 bg-white rounded-md border border border-solid cursor-pointer">
              <span class="text-sm font-medium text-gray-700">Relevance</span>
              <i class="ti ti-chevron-down text-sm text-gray-700" />
            </div>
          </div>
        </div>
        <div class="flex flex-1 gap-0 max-sm:flex-col">
          <div class="flex flex-col gap-0 p-6 w-80 bg-white border border-r min-w-[280px] max-sm:w-full max-sm:border max-sm:border-r max-sm:border-b">
            <div class="flex justify-between items-center mb-5">
              <span class="text-base font-semibold text-gray-900">Filters</span>
              <span class="text-sm font-medium text-blue-500 cursor-pointer">Reset All</span>
            </div>
            <div class="mb-5">
              <div class="mb-3 text-xs font-semibold tracking-wide text-blue-500 uppercase">SALARY RANGE</div>
              <div class="flex flex-col gap-2.5">
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Under 500,000 MMK</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-blue-500 rounded border border-blue-500 border-solid cursor-pointer flex-[shrink]">
                    <i class="ti ti-check text-xs text-white" />
                  </div>
                  <span class="text-sm text-gray-700">500,000 – 1,000,000 MMK</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-blue-500 rounded border border-blue-500 border-solid cursor-pointer flex-[shrink]">
                    <i class="ti ti-check text-xs text-white" />
                  </div>
                  <span class="text-sm text-gray-700">1,000,000 – 2,000,000 MMK</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">2,000,000 – 3,000,000 MMK</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">3,000,000+ MMK</span>
                </div>
              </div>
            </div>
            <div class="mb-5">
              <div class="mb-3 text-xs font-semibold tracking-wide text-blue-500 uppercase">EXPERIENCE LEVEL</div>
              <div class="flex flex-col gap-2.5">
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Entry Level</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-blue-500 rounded border border-blue-500 border-solid cursor-pointer flex-[shrink]">
                    <i class="ti ti-check text-xs text-white" />
                  </div>
                  <span class="text-sm text-gray-700">Mid-Level</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-blue-500 rounded border border-blue-500 border-solid cursor-pointer flex-[shrink]">
                    <i class="ti ti-check text-xs text-white" />
                  </div>
                  <span class="text-sm text-gray-700">Senior Level</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Lead / Manager</span>
                </div>
              </div>
            </div>
            <div class="mb-5">
              <div class="mb-3 text-xs font-semibold tracking-wide text-blue-500 uppercase">EMPLOYMENT TYPE</div>
              <div class="flex flex-col gap-2.5">
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-blue-500 rounded border border-blue-500 border-solid cursor-pointer flex-[shrink]">
                    <i class="ti ti-check text-xs text-white" />
                  </div>
                  <span class="text-sm text-gray-700">Full-time</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Part-time</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Contract</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Remote</span>
                </div>
              </div>
            </div>
            <div class="mb-6">
              <div class="mb-3 text-xs font-semibold tracking-wide text-blue-500 uppercase">TOWNSHIP</div>
              <div class="flex flex-col gap-2.5">
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-blue-500 rounded border border-blue-500 border-solid cursor-pointer flex-[shrink]">
                    <i class="ti ti-check text-xs text-white" />
                  </div>
                  <span class="text-sm text-gray-700">Yangon</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Mandalay</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Naypyidaw</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Taunggyi</span>
                </div>
                <div class="flex gap-2.5 items-center">
                  <div class="flex justify-center items-center w-4 h-4 bg-white rounded border border-gray-300 border-solid cursor-pointer flex-[shrink]"></div>
                  <span class="text-sm text-gray-700">Mawlamyine</span>
                </div>
              </div>
            </div>
            <div class="pt-5 border border-t">
              <div class="py-3 text-sm font-semibold text-center text-white bg-cyan-900 rounded-lg cursor-pointer">Apply Filters</div>
            </div>
          </div>
          <div class="flex flex-col flex-1 max-sm:w-full">
            <div class="flex flex-col gap-0 p-4 max-sm:p-3">
              <div class="flex gap-3.5 items-start p-4 mb-2 bg-blue-100 rounded-xl border border-blue-200 border-solid cursor-pointer">
                <div class="flex justify-center items-center w-11 h-11 rounded-lg bg-slate-200 flex-[shrink]">
                  <span class="text-xs font-semibold text-gray-500">LOGO</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex gap-2 justify-between items-start">
                    <div>
                      <span class="text-sm font-semibold leading-5 text-gray-900">Senior Frontend Developer</span>
                      <div class="flex gap-1.5 items-center mt-1">
                        <span class="text-xs font-medium text-blue-500">KBZ Group</span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs text-gray-500">Yangon</span>
                      </div>
                    </div>
                    <div class="flex flex-col gap-2 items-end flex-[shrink]">
                      <span class="text-xs text-gray-400">3d ago</span>
                      <i class="ti ti-bookmark text-base text-gray-400" />
                    </div>
                  </div>
                  <div class="mt-1.5">
                    <span class="text-xs font-semibold text-blue-700">1,200,000 – 1,800,000 MMK</span>
                  </div>
                </div>
              </div>
              <div class="flex gap-3.5 items-start p-4 mb-2 bg-white rounded-xl border border border-solid cursor-pointer">
                <div class="flex justify-center items-center w-11 h-11 rounded-lg bg-slate-200 flex-[shrink]">
                  <span class="text-xs font-semibold text-gray-500">LOGO</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex gap-2 justify-between items-start">
                    <div>
                      <span class="text-sm font-semibold leading-5 text-gray-900">Backend Engineer</span>
                      <div class="flex gap-1.5 items-center mt-1">
                        <span class="text-xs font-medium text-blue-500">Wave Money</span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs text-gray-500">Mandalay</span>
                      </div>
                    </div>
                    <div class="flex flex-col gap-2 items-end flex-[shrink]">
                      <span class="text-xs text-gray-400">5d ago</span>
                      <i class="ti ti-bookmark text-base text-gray-400" />
                    </div>
                  </div>
                  <div class="mt-1.5">
                    <span class="text-xs font-semibold text-blue-700">900,000 – 1,500,000 MMK</span>
                  </div>
                </div>
              </div>
              <div class="flex gap-3.5 items-start p-4 mb-2 bg-white rounded-xl border border border-solid cursor-pointer">
                <div class="flex justify-center items-center w-11 h-11 rounded-lg bg-slate-200 flex-[shrink]">
                  <span class="text-xs font-semibold text-gray-500">LOGO</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex gap-2 justify-between items-start">
                    <div>
                      <span class="text-sm font-semibold leading-5 text-gray-900">Product Manager</span>
                      <div class="flex gap-1.5 items-center mt-1">
                        <span class="text-xs font-medium text-blue-500">Grab Myanmar</span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs text-gray-500">Yangon</span>
                      </div>
                    </div>
                    <div class="flex flex-col gap-2 items-end flex-[shrink]">
                      <span class="text-xs text-gray-400">1w ago</span>
                      <i class="ti ti-bookmark text-base text-gray-400" />
                    </div>
                  </div>
                  <div class="mt-1.5">
                    <span class="text-xs font-semibold text-blue-700">1,500,000 – 2,500,000 MMK</span>
                  </div>
                </div>
              </div>
              <div class="flex gap-3.5 items-start p-4 mb-2 bg-white rounded-xl border border border-solid cursor-pointer">
                <div class="flex justify-center items-center w-11 h-11 rounded-lg bg-slate-200 flex-[shrink]">
                  <span class="text-xs font-semibold text-gray-500">LOGO</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex gap-2 justify-between items-start">
                    <div>
                      <span class="text-sm font-semibold leading-5 text-gray-900">UI/UX Designer</span>
                      <div class="flex gap-1.5 items-center mt-1">
                        <span class="text-xs font-medium text-blue-500">Ooredoo</span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs text-gray-500">Yangon</span>
                      </div>
                    </div>
                    <div class="flex flex-col gap-2 items-end flex-[shrink]">
                      <span class="text-xs text-gray-400">2w ago</span>
                      <i class="ti ti-bookmark text-base text-gray-400" />
                    </div>
                  </div>
                  <div class="mt-1.5">
                    <span class="text-xs font-semibold text-blue-700">800,000 – 1,200,000 MMK</span>
                  </div>
                </div>
              </div>
              <div class="flex gap-1 justify-center items-center mt-2 mb-1">
                <div class="flex justify-center items-center w-8 h-8 text-sm text-gray-500 bg-white rounded-md border border border-solid cursor-pointer">
                  <i class="ti ti-chevron-left text-sm" />
                </div>
                <div class="flex justify-center items-center w-8 h-8 text-sm font-semibold text-white bg-cyan-900 rounded-md cursor-pointer">1</div>
                <div class="flex justify-center items-center w-8 h-8 text-sm text-gray-700 bg-white rounded-md border border border-solid cursor-pointer">2</div>
                <div class="flex justify-center items-center w-8 h-8 text-sm text-gray-700 bg-white rounded-md border border border-solid cursor-pointer">3</div>
                <div class="flex justify-center items-center w-8 h-8 text-sm text-gray-700 bg-white rounded-md border border border-solid cursor-pointer">...</div>
                <div class="flex justify-center items-center w-8 h-8 text-sm text-gray-700 bg-white rounded-md border border border-solid cursor-pointer">12</div>
                <div class="flex justify-center items-center w-8 h-8 text-sm text-gray-500 bg-white rounded-md border border border-solid cursor-pointer">
                  <i class="ti ti-chevron-right text-sm" />
                </div>
              </div>
            </div>
          </div>
          <div class="flex flex-col gap-0 p-7 bg-white border border-l min-w-[340px] w-[380px] max-md:hidden max-sm:hidden">
            <div class="mb-1">
              <span class="text-2xl font-bold leading-8 text-gray-900">Senior Frontend Developer</span>
            </div>
            <div class="flex gap-2 items-center mt-2 mb-4">
              <span class="text-sm font-medium text-blue-500 cursor-pointer">KBZ Group</span>
              <span class="text-xs text-gray-400">•</span>
              <span class="text-sm text-gray-500">Yangon, Myanmar</span>
              <span class="text-xs text-gray-400">•</span>
              <span class="text-sm text-gray-400">3 days ago</span>
            </div>
            <div class="flex gap-2 items-center mb-5">
              <span class="px-2.5 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-3xl border border border-solid">Full-time</span>
              <span class="text-xs text-gray-500">·</span>
              <span class="px-2.5 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-3xl border border border-solid">Mid-Senior Level</span>
            </div>
            <div class="pt-5 mb-5 border border-t">
              <div class="flex gap-10 items-start">
                <div>
                  <div class="mb-1.5 text-xs font-medium tracking-wide text-gray-400 uppercase">OFFERED SALARY</div>
                  <div class="text-sm font-semibold text-gray-900">1,200,000 – 1,800,000 MMK</div>
                </div>
                <div>
                  <div class="mb-1.5 text-xs font-medium tracking-wide text-gray-400 uppercase">VACANCIES</div>
                  <div class="text-sm font-semibold text-gray-900">2 Posts</div>
                </div>
              </div>
            </div>
            <div class="pt-5 mb-4 border border-t">
              <div class="mb-2.5 text-base font-semibold text-gray-900">About This Role</div>
              <div class="text-sm leading-5 text-gray-500">We are looking for a Senior Frontend Developer to lead the evolution of our web-based consumer platforms. You will work closely with the product design team in Yangon.</div>
            </div>
            <div class="mb-4">
              <div class="mb-2.5 text-base font-semibold text-gray-900">Key Requirements</div>
              <div class="flex flex-col gap-2">
                <div class="flex gap-2 items-start">
                  <span class="mt-px text-sm text-gray-500 flex-[shrink]">•</span>
                  <span class="text-sm leading-5 text-gray-500">4+ years of experience with React, TypeScript, and modern web APIs.</span>
                </div>
                <div class="flex gap-2 items-start">
                  <span class="mt-px text-sm text-gray-500 flex-[shrink]">•</span>
                  <span class="text-sm leading-5 text-gray-500">Strong background in responsive design and micro-frontends.</span>
                </div>
                <div class="flex gap-2 items-start">
                  <span class="mt-px text-sm text-gray-500 flex-[shrink]">•</span>
                  <span class="text-sm leading-5 text-gray-500">Fluent Burmese and intermediate business English verbal skills.</span>
                </div>
              </div>
            </div>
            <div class="mb-6">
              <div class="mb-2.5 text-base font-semibold text-gray-900">Benefits</div>
              <div class="flex gap-2 items-center">
                <div class="flex gap-1.5 items-center px-3 py-1.5 bg-white rounded-3xl border border border-solid">
                  <i class="ti ti-heart text-sm text-gray-500" />
                  <span class="text-xs font-medium text-gray-700">Health Insurance</span>
                </div>
                <div class="flex gap-1.5 items-center px-3 py-1.5 bg-white rounded-3xl border border border-solid">
                  <i class="ti ti-school text-sm text-gray-500" />
                  <span class="text-xs font-medium text-gray-700">Training Courses</span>
                </div>
              </div>
            </div>
            <div class="mb-4">
              <div class="py-3.5 text-sm font-semibold text-center text-white bg-cyan-900 rounded-lg cursor-pointer">Quick Apply Now</div>
            </div>
            <div class="flex gap-6 justify-center items-center">
              <span class="text-sm font-medium text-blue-500 cursor-pointer">Save Job</span>
              <span class="text-sm font-medium text-blue-500 cursor-pointer">Share Posting</span>
            </div>
          </div>
        </div>
        <div class="flex justify-between items-center p-8 text-white bg-cyan-900 max-sm:flex-col max-sm:gap-4 max-sm:px-4">
          <div class="flex gap-2 items-center">
            <span class="text-base font-bold text-white">NDK</span>
            <span class="text-sm text-blue-300">· Myanmar's Job Platform</span>
          </div>
          <div class="flex gap-6 items-center max-sm:flex-wrap max-sm:justify-center">
            <span class="text-sm text-gray-300 cursor-pointer">About NDK</span>
            <span class="text-sm text-gray-300 cursor-pointer">Privacy Policy</span>
            <span class="text-sm text-gray-300 cursor-pointer">Terms</span>
            <span class="text-sm text-gray-300 cursor-pointer">Contact</span>
          </div>
        </div>
        <div class="py-4 text-xs text-center text-gray-500 bg-gray-200">© 2026 NDK Job Platform. Structured Wireframe representation.</div>
      </div>
</template>
