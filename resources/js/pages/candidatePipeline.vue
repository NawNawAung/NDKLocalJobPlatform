<template>
    <div
      class="flex relative flex-col bg-slate-50 min-h-[1200px]"
    >
      <div
        class="flex z-0 flex-wrap gap-10 justify-between items-center px-16 py-5 w-full bg-white border-b border-blue-200 border-solid min-h-20 max-md:px-5 max-md:max-w-full"
      >
        <div
          class="flex gap-2.5 items-center self-stretch my-auto whitespace-nowrap text-slate-500"
        >
          <img class="flex relative shrink-0 self-stretch my-auto w-9 min-h-9" src="https://api.builder.io/api/v1/image/assets/TEMP/9bb4d69be3878ffac13fc24985feb586adb119ee?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="logo-icon" />
          <span class="self-stretch my-auto text-2xl font-extrabold">NDK</span>
          <div
            class="flex items-start self-stretch px-1.5 py-0.5 my-auto text-xs font-bold bg-blue-200 rounded"
          >
            <span class="">MYANMAR</span>
          </div>
        </div>
        <div
          class="flex gap-8 self-stretch h-full text-sm font-medium whitespace-nowrap min-w-60 text-neutral-800"
        >
          <div class="flex flex-col justify-center">
            <span class="">Dashboard</span>
          </div>
          <div class="flex flex-col justify-center">
            <span class="">Jobs</span>
          </div>
          <div
            class="flex flex-col justify-center font-bold text-slate-500"
          >
            <span class="">Candidates</span>
            <div
              class="flex mt-1 w-4 rounded-sm bg-slate-500 min-h-0.5"></div>
          </div>
          <div class="flex flex-col justify-center">
            <span class="">Messages</span>
          </div>
        </div>
        <div
          class="flex gap-6 items-center self-stretch my-auto min-w-60"
        >
          <div
            class="flex justify-center items-center self-stretch my-auto min-h-[18px] w-[18px]"
          >
            <img class="flex relative self-stretch my-auto w-[18px]" src="https://api.builder.io/api/v1/image/assets/TEMP/32c7b7f12cd27cc5c8eb0696ac8c1dee816d3383?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="search" />
          </div>
          <div
            class="flex gap-2 items-center self-stretch px-3 py-1.5 my-auto text-xs font-semibold text-white rounded-3xl bg-slate-500"
          >
            <div
              class="flex shrink-0 self-stretch my-auto w-4 h-4 bg-white rounded-lg"></div>
            <span class="self-stretch my-auto">Recruiter Mode</span>
          </div>
          <div
            class="flex items-start self-stretch px-5 py-2.5 my-auto text-sm font-bold text-white rounded-lg bg-slate-500"
          >
            <span class="">Post a Job</span>
          </div>
          <div
            class="flex gap-1.5 items-center self-stretch my-auto"
          >
            <div
              class="flex justify-center items-center self-stretch my-auto w-8 h-8 text-xs font-bold whitespace-nowrap bg-blue-200 rounded-2xl border border-blue-200 border-solid min-h-8 text-slate-500"
            >
              <span class="self-stretch my-auto">KM</span>
            </div>
            <div
              class="flex justify-center items-center self-stretch my-auto w-2.5 min-h-2.5"
            >
              <img class="flex relative self-stretch my-auto w-2.5" src="https://api.builder.io/api/v1/image/assets/TEMP/896462d0f1beea0e5ce3c242ec1c30d75fe63b97?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="chevron-down" />
            </div>
          </div>
        </div>
      </div>
      <div
        class="flex z-0 flex-wrap gap-10 justify-between items-center px-16 pt-8 w-full max-md:px-5 max-md:max-w-full"
      >
        <div
          class="flex flex-col self-stretch my-auto min-w-60"
        >
          <span class="text-2xl font-extrabold text-neutral-800">Candidate Pipeline</span>
          <span class="mt-1 text-sm text-blue-400">Manage and track active applicants for your job postings.</span>
        </div>
        <div
          class="flex gap-2 items-start self-stretch px-5 py-2.5 my-auto bg-white rounded-lg border border-blue-200 border-solid"
        >
          <div
            class="flex justify-center items-center w-3.5 min-h-3.5"
          >
            <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/b430c61ba452b38b9bfa6db51948b2e6f2142658?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="download" />
          </div>
          <span class="text-sm font-bold text-slate-500">Export Candidates</span>
        </div>
      </div>
      <div
        class="flex z-0 items-start px-16 py-6 w-full max-md:px-5 max-md:max-w-full"
      >
        <div
          class="flex flex-wrap flex-1 shrink gap-10 justify-between items-center p-3 w-full bg-white rounded-xl border border-blue-200 border-solid basis-0 min-w-60 max-md:max-w-full"
        >
          <div
            class="flex gap-3 items-center self-stretch my-auto w-80 min-w-60"
          >
            <span class="self-stretch my-auto text-xs font-bold text-blue-400">ACTIVE JOB:</span>
            <div
              class="flex flex-1 shrink gap-3 justify-between items-center self-stretch px-4 py-2 my-auto bg-white rounded-lg border border-blue-200 border-solid basis-0"
            >
              <span class="self-stretch my-auto text-sm font-bold text-slate-500">Senior Developer – Yangon</span>
              <div
                class="flex justify-center items-center self-stretch my-auto w-3 min-h-3"
              >
                <img class="flex relative self-stretch my-auto w-3" src="https://api.builder.io/api/v1/image/assets/TEMP/2733e5a3a402131beed18a843b5128bc78e7f78d?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="chevron-down" />
              </div>
            </div>
          </div>
          <div
            class="flex flex-wrap gap-4 items-center self-stretch my-auto min-w-60 max-md:max-w-full"
          >
            <div
              class="flex gap-2 items-center self-stretch px-3 py-2 my-auto rounded-lg border border-blue-200 border-solid w-[200px]"
            >
              <div
                class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
              >
                <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/18572d2d0a1d0a505c55f26640fe28e0cdde17a2?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="search" />
              </div>
              <span class="self-stretch my-auto text-sm text-blue-400">Search candidates...</span>
            </div>
            <div
              class="flex gap-2 items-center self-stretch px-3 py-1.5 my-auto bg-blue-200 rounded-3xl"
            >
              <span class="self-stretch my-auto text-xs font-semibold text-slate-500"></span>
              <div
                class="flex justify-center items-center self-stretch my-auto w-2.5 min-h-2.5"
              >
                <img class="flex relative self-stretch my-auto w-2.5" src="https://api.builder.io/api/v1/image/assets/TEMP/06e8d95d7c5b1a8dc4beadaafbe9e5f9addaae95?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="chevron-down" />
              </div>
            </div>
            <div
              class="flex gap-2 items-center self-stretch px-3 py-1.5 my-auto bg-blue-200 rounded-3xl"
            >
              <span class="self-stretch my-auto text-xs font-semibold text-slate-500"></span>
              <div
                class="flex justify-center items-center self-stretch my-auto w-2.5 min-h-2.5"
              >
                <img class="flex relative self-stretch my-auto w-2.5" src="https://api.builder.io/api/v1/image/assets/TEMP/06e8d95d7c5b1a8dc4beadaafbe9e5f9addaae95?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="chevron-down" />
              </div>
            </div>
            <div
              class="flex gap-2 items-center self-stretch px-3 py-1.5 my-auto bg-blue-200 rounded-3xl"
            >
              <span class="self-stretch my-auto text-xs font-semibold text-slate-500"></span>
              <div
                class="flex justify-center items-center self-stretch my-auto w-2.5 min-h-2.5"
              >
                <img class="flex relative self-stretch my-auto w-2.5" src="https://api.builder.io/api/v1/image/assets/TEMP/06e8d95d7c5b1a8dc4beadaafbe9e5f9addaae95?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="chevron-down" />
              </div>
            </div>
            <div
              class="flex gap-2 items-center self-stretch px-3 py-1.5 my-auto bg-blue-200 rounded-3xl"
            >
              <span class="self-stretch my-auto text-xs font-semibold text-slate-500"></span>
              <div
                class="flex justify-center items-center self-stretch my-auto w-2.5 min-h-2.5"
              >
                <img class="flex relative self-stretch my-auto w-2.5" src="https://api.builder.io/api/v1/image/assets/TEMP/06e8d95d7c5b1a8dc4beadaafbe9e5f9addaae95?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="chevron-down" />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div
        class="flex z-0 flex-wrap gap-5 px-16 pb-24 w-full max-md:px-5 max-md:max-w-full"
      >
        <div
          class="flex flex-col flex-1 shrink basis-0 min-w-60"
        >
          <div
            class="flex flex-col w-full font-bold whitespace-nowrap"
          >
            <div
              class="flex w-full rounded-lg bg-slate-500 min-h-1"></div>
            <div
              class="flex gap-10 justify-between items-center p-3 w-full bg-white border-r border-b border-l border-blue-200 border-solid"
            >
              <span class="self-stretch my-auto text-sm text-neutral-800">APPLIED</span>
              <div
                class="flex items-start self-stretch px-2 py-0.5 my-auto text-xs bg-blue-200 rounded-xl text-slate-500"
              >
                <span class="">12</span>
              </div>
            </div>
          </div>
          <div
            class="flex flex-col flex-1 p-2.5 mt-4 w-full rounded-xl bg-blue-200 bg-opacity-40"
          >
            <div
              class="flex flex-col p-4 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">AKM</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Aung Kyaw Moe</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">92% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Sr. Developer at Wave Money</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/bade29a21d05d223a9899483c41b956cf379ec5b?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
            <div
              class="flex flex-col p-4 mt-3 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">HTW</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Hla Thi Win</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">88% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Frontend Developer</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/af0e5ae6bc93067b3781770238f3563741da7fcf?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
            <div
              class="flex flex-col p-4 mt-3 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">ZMO</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Zaw Min Oo</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">85% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Fullstack at Grab Myanmar</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/c118ce26f931a5885212fd4d6dbdad602df658bc?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
            <div
              class="flex justify-center items-center p-3 mt-3 w-full text-sm font-bold text-blue-400 bg-white rounded-lg border border-blue-200 border-dashed"
            >
              <span class="self-stretch my-auto">+10 more candidates</span>
            </div>
          </div>
        </div>
        <div
          class="flex flex-col flex-1 shrink basis-0 min-w-60"
        >
          <div
            class="flex flex-col w-full font-bold whitespace-nowrap"
          >
            <div
              class="flex w-full bg-blue-400 rounded-lg min-h-1"></div>
            <div
              class="flex gap-10 justify-between items-center p-3 w-full bg-white border-r border-b border-l border-blue-200 border-solid"
            >
              <span class="self-stretch my-auto text-sm text-neutral-800">SHORTLISTED</span>
              <div
                class="flex items-start self-stretch px-2 py-0.5 my-auto text-xs bg-blue-200 rounded-xl text-slate-500"
              >
                <span class="">5</span>
              </div>
            </div>
          </div>
          <div
            class="flex flex-col flex-1 p-2.5 mt-4 w-full rounded-xl bg-blue-200 bg-opacity-40"
          >
            <div
              class="flex flex-col p-4 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">MSK</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Mya Su Khin</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">95% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Lead Dev at KBZ</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/800cf435fdcf9c90e22229b2865f407f679335b1?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
            <div
              class="flex flex-col p-4 mt-3 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">KN</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Ko Naing</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">84% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Jr. Developer</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/9ef057bd2d5b5e9b6667d0c821e7859f30c6e5dd?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
            <div
              class="flex flex-col p-4 mt-3 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">TZH</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Than Zaw Htun</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">81% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Backend at Ooredoo</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/7e75ded93b0c7be0d1d9ee804fc7bd9d62752a35?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div
          class="flex flex-col flex-1 shrink basis-0 min-w-60"
        >
          <div
            class="flex flex-col w-full font-bold whitespace-nowrap"
          >
            <div
              class="flex w-full bg-blue-300 rounded-lg min-h-1"></div>
            <div
              class="flex gap-10 justify-between items-center p-3 w-full bg-white border-r border-b border-l border-blue-200 border-solid"
            >
              <span class="self-stretch my-auto text-sm text-neutral-800">INTERVIEW</span>
              <div
                class="flex items-start self-stretch px-2 py-0.5 my-auto text-xs bg-blue-200 rounded-xl text-slate-500"
              >
                <span class="">3</span>
              </div>
            </div>
          </div>
          <div
            class="flex flex-col flex-1 p-2.5 mt-4 w-full rounded-xl bg-blue-200 bg-opacity-40"
          >
            <div
              class="flex flex-col p-4 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">EMC</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Ei Mon Cho</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs bg-blue-200 rounded text-slate-500"
                  >
                    <span class="">79% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">DevOps at Telenor</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/267cf5417fc3518ab5c347314cf6ae8711538f5c?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
            <div
              class="flex flex-col p-4 mt-3 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">NLA</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Nay Lin Aung</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">81% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Mobile Dev at YOMA</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/2834cf81211f840bf742c1f4a66b7ae1900e8a48?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
            <div
              class="flex flex-col p-4 mt-3 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">TP</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Thet Paing</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">85% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Fullstack Developer</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/039e86bed0948abbfce08db75fb56c9f88d88969?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div
          class="flex flex-col flex-1 shrink basis-0 min-w-60"
        >
          <div
            class="flex flex-col w-full font-bold whitespace-nowrap"
          >
            <div
              class="flex w-full bg-green-600 rounded-lg min-h-1"></div>
            <div
              class="flex gap-10 justify-between items-center p-3 w-full bg-white border-r border-b border-l border-blue-200 border-solid"
            >
              <span class="self-stretch my-auto text-sm text-neutral-800">HIRED</span>
              <div
                class="flex items-start self-stretch px-2 py-0.5 my-auto text-xs bg-blue-200 rounded-xl text-slate-500"
              >
                <span class="">1</span>
              </div>
            </div>
          </div>
          <div
            class="flex flex-col flex-1 justify-center p-2.5 mt-4 w-full rounded-xl bg-blue-200 bg-opacity-40"
          >
            <div
              class="flex flex-col p-4 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">WM</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Win Maw</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs text-green-600 rounded bg-green-600 bg-opacity-10"
                  >
                    <span class="">96% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Senior Software Architect</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/ea05717506b503cd3de0409b22cdf66057e7a98b?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div
          class="flex flex-col flex-1 shrink basis-0 min-w-60"
        >
          <div
            class="flex flex-col w-full font-bold whitespace-nowrap"
          >
            <div
              class="flex w-full bg-red-500 rounded-lg min-h-1"></div>
            <div
              class="flex gap-10 justify-between items-center p-3 w-full bg-white border-r border-b border-l border-blue-200 border-solid"
            >
              <span class="self-stretch my-auto text-sm text-neutral-800">REJECTED</span>
              <div
                class="flex items-start self-stretch px-2 py-0.5 my-auto text-xs bg-blue-200 rounded-xl text-slate-500"
              >
                <span class="">2</span>
              </div>
            </div>
          </div>
          <div
            class="flex flex-col flex-1 p-2.5 mt-4 w-full rounded-xl bg-blue-200 bg-opacity-40"
          >
            <div
              class="flex flex-col p-4 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">KS</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Kyaw Swar</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs bg-blue-200 rounded text-slate-500"
                  >
                    <span class="">62% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Jr. Developer</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/317d526d1d3bb04bb883cfc9edde1e431682b0ac?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
            <div
              class="flex flex-col p-4 mt-3 w-full bg-white rounded-lg border border-blue-200 border-solid"
            >
              <div
                class="flex gap-3 items-center w-full font-bold"
              >
                <div
                  class="flex justify-center items-center self-stretch my-auto w-10 h-10 text-sm whitespace-nowrap bg-blue-200 rounded-3xl min-h-10 text-slate-500"
                >
                  <span class="self-stretch my-auto">SM</span>
                </div>
                <div
                  class="flex flex-col flex-1 shrink self-stretch my-auto basis-0"
                >
                  <span class="text-sm text-neutral-800">Su Myat</span>
                  <div
                    class="flex items-start self-start px-1.5 py-0.5 mt-1 text-xs bg-blue-200 rounded text-slate-500"
                  >
                    <span class="">58% MATCH</span>
                  </div>
                </div>
              </div>
              <span class="mt-3 text-sm text-blue-400">Intern</span>
              <div
                class="flex mt-3 w-full bg-blue-200 min-h-px"></div>
              <div
                class="flex gap-10 justify-between items-center mt-3 w-full"
              >
                <div
                  class="flex items-start self-stretch px-3 py-1.5 my-auto text-xs font-bold rounded-md border border-solid border-slate-500 text-slate-500"
                >
                  <span class="">View CV</span>
                </div>
                <div
                  class="flex justify-center items-center self-stretch px-2 my-auto bg-white rounded-md border border-blue-200 border-solid h-[30px] w-[30px]"
                >
                  <div
                    class="flex justify-center items-center self-stretch my-auto w-3.5 min-h-3.5"
                  >
                    <img class="flex relative self-stretch my-auto w-3.5" src="https://api.builder.io/api/v1/image/assets/TEMP/317d526d1d3bb04bb883cfc9edde1e431682b0ac?placeholderIfAbsent=true&apiKey=a5f12cc7a3c0436da3d4e385ac31c487" alt="mail" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div
        class="flex absolute inset-x-0 bottom-0 z-0 flex-wrap gap-10 justify-between items-center px-16 py-8 w-full h-20 bg-white border-t border-blue-200 border-solid min-h-20 max-md:px-5 max-md:max-w-full"
      >
        <div
          class="flex flex-wrap gap-10 items-center self-stretch my-auto text-sm min-w-60 max-md:max-w-full"
        >
          <div
            class="flex gap-2 items-center self-stretch my-auto"
          >
            <span class="self-stretch my-auto text-blue-400">Total Applicants:</span>
            <span class="self-stretch my-auto font-bold text-slate-500">23 Candidates</span>
          </div>
          <div
            class="flex gap-2 items-center self-stretch my-auto"
          >
            <span class="self-stretch my-auto text-blue-400">Avg Match:</span>
            <span class="self-stretch my-auto font-bold text-slate-500">81%</span>
          </div>
          <div
            class="flex gap-2 items-center self-stretch my-auto"
          >
            <span class="self-stretch my-auto text-blue-400">Time to Hire:</span>
            <span class="self-stretch my-auto font-bold text-slate-500">18 days</span>
          </div>
        </div>
        <div
          class="flex gap-3 items-center self-stretch my-auto text-xs text-blue-400 min-w-60"
        >
          <span class="self-stretch my-auto">© 2026 NDK Recruiter Portal · Live Status Updates</span>
        </div>
      </div>
    </div>
</template>
