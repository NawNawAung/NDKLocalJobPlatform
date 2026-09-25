<script setup>
import { ref } from 'vue';

const messageDraft = ref('');
const messageNotice = ref('');

function sendMessage() {
    const message = messageDraft.value.trim();
    if (!message) return;
    messageNotice.value = `Message sent: ${message}`;
    messageDraft.value = '';
}
</script>

<template>
      <div class="flex flex-col bg-slate-100 min-h-screen">
        <div class="flex top-0 z-50 justify-between items-center px-6 py-0 bg-white border border-b h-[60px]">
          <div class="flex gap-2 items-center">
            <div class="flex gap-1.5 items-center">
              <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="28" height="28" rx="6" fill="#1e3a5f"></rect><text x="14" y="19" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" font-weight="700" fill="white">NDK</text></svg>
              <span class="text-base font-bold tracking-normal text-cyan-900">NDK</span>
              <span class="px-1.5 py-0.5 ml-0.5 text-xs font-medium tracking-wider text-gray-500 uppercase bg-gray-100 rounded border border border-solid">MYANMAR</span>
            </div>
            <div class="flex gap-6 items-center ml-8 max-sm:hidden">
              <span class="pb-0.5 text-sm font-semibold text-cyan-900 border-2 border-cyan-900 cursor-pointer">Jobs</span>
              <span class="text-sm font-medium text-gray-500 cursor-pointer">Skills &amp; Badges</span>
              <span class="text-sm font-medium text-gray-500 cursor-pointer">Salary Insights</span>
            </div>
          </div>
          <div class="flex gap-3 items-center">
            <div class="flex justify-center items-center w-9 h-9 cursor-pointer">
              <i class="ti ti-search text-xl text-gray-500" />
            </div>
            <div class="flex gap-1.5 items-center px-3 py-1.5 bg-indigo-50 rounded-full cursor-pointer max-sm:hidden">
              <div class="w-2 h-2 bg-blue-500 rounded-full" />
              <span class="text-sm font-medium text-cyan-900">Recruiter Mode</span>
            </div>
            <span class="text-sm font-medium text-gray-700 cursor-pointer max-sm:hidden">Sign In</span>
            <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Post a Job</div>
          </div>
        </div>
        <div class="flex flex-1 gap-6 px-10 py-8 mx-auto w-full max-w-[1440px] max-md:px-4 max-sm:flex-col max-sm:px-2 max-sm:py-4">
          <div class="flex flex-col gap-0 max-w-[360px] min-w-80 w-[360px] max-sm:w-full max-sm:min-w-0 max-sm:max-w-full">
            <div class="flex overflow-hidden flex-col bg-white rounded-2xl shadow-sm">
              <div class="px-5 pt-5 pb-3">
                <div class="mb-3 text-lg font-bold text-gray-900">Conversations</div>
                <div class="relative mb-3">
                  <i class="ti ti-search absolute left-3 top-2/4 text-base text-gray-400 -translate-y-2/4" />
                  <input
                    type="text"
                    placeholder="Search candidates or jobs"
                    class="py-2 pr-3 pl-9 w-full text-sm text-gray-500 bg-gray-50 rounded-lg border border border-solid"
                  />
                </div>
                <div class="flex gap-2 items-center">
                  <div class="px-3.5 py-1.5 text-xs font-semibold text-white bg-cyan-900 rounded-full cursor-pointer">All</div>
                  <div class="px-3.5 py-1.5 text-xs font-medium text-gray-500 bg-white rounded-full border border border-solid cursor-pointer">Unread</div>
                  <div class="px-3.5 py-1.5 text-xs font-medium text-gray-500 bg-white rounded-full border border border-solid cursor-pointer">Interview Invites</div>
                </div>
              </div>
              <div class="flex flex-col">
                <div class="flex gap-3 items-start px-4 py-3.5 border-cyan-900 cursor-pointer bg-slate-100 border-[3px]">
                  <div class="flex justify-center items-center w-10 h-10 text-sm font-bold text-cyan-900 rounded-full bg-slate-300 flex-[shrink]">ZM</div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-0.5">
                      <span class="text-sm font-semibold text-gray-900">Zayar Min</span>
                      <span class="text-xs text-gray-400">10m ago</span>
                    </div>
                    <div class="mb-1">
                      <span class="px-2 py-0.5 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">Senior Python Developer</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="overflow-hidden text-xs text-gray-500 whitespace-nowrap max-w-[200px] text-ellipsis">Thank you for the response! I would love to join the c...</span>
                      <div class="ml-1 w-2 h-2 bg-blue-500 rounded-full flex-[shrink]" />
                    </div>
                  </div>
                </div>
                <div class="flex gap-3 items-start px-4 py-3.5 bg-white border-b border-gray-100 cursor-pointer">
                  <div class="flex justify-center items-center w-10 h-10 text-sm font-bold text-emerald-800 bg-emerald-100 rounded-full flex-[shrink]">HTW</div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-0.5">
                      <span class="text-sm font-semibold text-gray-900">Hla Thi Win</span>
                      <span class="text-xs text-gray-400">2h ago</span>
                    </div>
                    <div class="flex justify-between items-center mb-1">
                      <span class="px-2 py-0.5 text-xs font-semibold text-pink-800 bg-pink-100 rounded-full">UI/UX Designer</span>
                      <span class="px-2 py-0.5 text-xs font-semibold text-emerald-800 bg-emerald-100 rounded-full">INVITED</span>
                    </div>
                    <span class="block overflow-hidden text-xs text-gray-500 whitespace-nowrap text-ellipsis">Is the position open for remote work from Mandalay?</span>
                  </div>
                </div>
                <div class="flex gap-3 items-start px-4 py-3.5 bg-white border-b border-gray-100 cursor-pointer">
                  <div class="flex justify-center items-center w-10 h-10 text-sm font-bold text-violet-800 bg-violet-100 rounded-full flex-[shrink]">KZ</div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-0.5">
                      <span class="text-sm font-semibold text-gray-900">Kyaw Zeya</span>
                      <span class="text-xs text-gray-400">1d ago</span>
                    </div>
                    <div class="mb-1">
                      <span class="px-2 py-0.5 text-xs font-semibold text-violet-800 bg-violet-100 rounded-full">DevOps Engineer</span>
                    </div>
                    <span class="block overflow-hidden text-xs text-gray-500 whitespace-nowrap text-ellipsis">Let me check the interview slot tomorrow.</span>
                  </div>
                </div>
                <div class="flex gap-3 items-start px-4 py-3.5 bg-white border-b border-gray-100 cursor-pointer">
                  <div class="flex justify-center items-center w-10 h-10 text-sm font-bold text-amber-800 bg-amber-100 rounded-full flex-[shrink]">YT</div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-0.5">
                      <span class="text-sm font-semibold text-gray-900">Yuki Tanaka</span>
                      <span class="text-xs text-gray-400">2d ago</span>
                    </div>
                    <div class="mb-1">
                      <span class="px-2 py-0.5 text-xs font-semibold text-amber-800 bg-amber-100 rounded-full">Regional Sales Manager</span>
                    </div>
                    <span class="block overflow-hidden text-xs text-gray-500 whitespace-nowrap text-ellipsis">I have attached my reference letters from KBZ.</span>
                  </div>
                </div>
                <div class="flex gap-3 items-start px-4 py-3.5 bg-white cursor-pointer">
                  <div class="flex justify-center items-center w-10 h-10 text-sm font-bold text-pink-800 bg-pink-100 rounded-full flex-[shrink]">SMN</div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-0.5">
                      <span class="text-sm font-semibold text-gray-900">Su Myat Noe</span>
                      <span class="text-xs text-gray-400">3d ago</span>
                    </div>
                    <div class="mb-1">
                      <span class="px-2 py-0.5 text-xs font-semibold text-pink-800 bg-pink-100 rounded-full">Finance Assistant</span>
                    </div>
                    <span class="block overflow-hidden text-xs text-gray-500 whitespace-nowrap text-ellipsis">Could we reschedule to next Monday morning?</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="flex flex-col flex-1 gap-0 min-w-0">
            <div class="flex overflow-hidden flex-col bg-white rounded-2xl shadow-sm">
              <div class="flex justify-between items-center px-6 py-5 border-b border-gray-100">
                <div class="flex gap-3.5 items-center">
                  <div class="flex justify-center items-center w-11 h-11 text-sm font-bold text-cyan-900 rounded-full bg-slate-300 flex-[shrink]">ZM</div>
                  <div class="flex flex-col">
                    <div class="flex gap-2.5 items-center">
                      <span class="text-base font-bold text-gray-900">Zayar Min</span>
                      <span class="px-2.5 py-1 text-xs font-bold text-emerald-800 bg-emerald-100 rounded-full">92% Match</span>
                    </div>
                    <span class="text-xs font-medium text-blue-500">Candidate for Senior Python Developer · Yangon Head Office</span>
                  </div>
                </div>
                <div class="px-5 py-2.5 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Schedule Interview</div>
              </div>
              <div class="flex justify-between items-start px-6 py-3.5 border-b border-gray-100 bg-slate-50">
                <span class="text-sm text-gray-700">Let's schedule an interview to finalize the engineering assessment values.</span>
                <div class="px-5 py-2.5 ml-4 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Schedule Interview</div>
              </div>
              <div class="flex overflow-y-auto flex-col flex-1 gap-4 px-6 py-5">
                <div class="flex justify-center">
                  <span class="px-3.5 py-1.5 text-xs font-medium text-gray-500 rounded-full bg-slate-200">Application Received · Jan 14, 2026</span>
                </div>
                <div class="flex gap-2.5 items-start">
                  <div class="flex justify-center items-center w-9 h-9 text-xs font-bold text-cyan-900 rounded-full bg-slate-300 flex-[shrink]">ZM</div>
                  <div class="flex flex-col gap-1 max-w-[60%]">
                    <div class="px-3.5 py-2.5 bg-gray-100 rounded-sm">
                      <span class="text-sm leading-normal text-gray-900">Hello! I am very interested in the Senior Python Developer position at KBZ Group. I have 5+ years of experience with Django and FastAPI.</span>
                    </div>
                    <span class="text-xs text-gray-400">10:15 AM</span>
                  </div>
                </div>
                <div class="flex gap-2.5 justify-end items-start">
                  <div class="flex flex-col gap-1 items-end max-w-[60%]">
                    <div class="px-3.5 py-2.5 bg-cyan-900 rounded-sm">
                      <span class="text-sm leading-normal text-white">Hi Zayar Min, thanks for reaching out. Your background in core financial services looks like a strong match. Are you free for an initial screening call this week?</span>
                    </div>
                    <span class="text-xs text-gray-400">11:02 AM</span>
                  </div>
                </div>
                <div class="flex justify-center">
                  <span class="px-3.5 py-1.5 text-xs font-medium text-emerald-800 bg-emerald-100 rounded-full">Interview Scheduled · Thursday, 2:00 PM (MMT)</span>
                </div>
                <div class="flex gap-2.5 items-start">
                  <div class="flex justify-center items-center w-9 h-9 text-xs font-bold text-cyan-900 rounded-full bg-slate-300 flex-[shrink]">ZM</div>
                  <div class="flex flex-col gap-1 max-w-[60%]">
                    <div class="px-3.5 py-2.5 bg-gray-100 rounded-sm">
                      <span class="text-sm leading-normal text-gray-900">Thank you for the response! I would love to join the call. I have confirmed the Google Meet invite sent to my email. See you soon!</span>
                    </div>
                    <span class="text-xs text-gray-400">11:15 AM</span>
                  </div>
                </div>
              </div>
              <div class="flex gap-2.5 items-center px-5 py-3.5 bg-white border-t border-gray-100">
                <div class="flex justify-center items-center w-9 h-9 cursor-pointer flex-[shrink]">
                  <i class="ti ti-paperclip text-xl text-gray-400" />
                </div>
                <input
                  v-model="messageDraft"
                  type="text"
                  placeholder="Write a message to Zayar Min..."
                  class="flex-1 px-3.5 py-2.5 text-sm text-gray-700 bg-gray-50 rounded-lg border border border-solid"
                  @keydown.enter.prevent="sendMessage"
                />
                <div class="flex gap-0.5 items-center px-3.5 py-2.5 bg-white rounded-lg border border border-solid cursor-pointer flex-[shrink]">
                  <span class="text-sm font-medium text-gray-700">Templates</span>
                  <i class="ti ti-chevron-down ml-1 text-sm text-gray-500" />
                </div>
                <button type="button" class="px-5 py-2.5 text-sm font-semibold text-white bg-cyan-900 rounded-lg flex-[shrink]" @click="sendMessage">Send</button>
              </div>
              <p v-if="messageNotice" class="px-5 pb-3 text-sm text-emerald-700" role="status">{{ messageNotice }}</p>
            </div>
          </div>
        </div>
        <div class="pt-10 mt-auto">
          <div class="px-10 py-6 bg-white border border-t max-sm:px-4">
            <div class="flex justify-between items-center mx-auto max-w-[1440px] max-sm:flex-col max-sm:gap-4 max-sm:items-start">
              <div class="flex gap-2 items-center">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="22" height="22" rx="5" fill="#1e3a5f"></rect><text x="11" y="15" text-anchor="middle" font-family="Inter,sans-serif" font-size="8" font-weight="700" fill="white">NDK</text></svg>
                <span class="text-sm font-bold text-cyan-900">NDK</span>
                <span class="text-sm text-gray-500">· Myanmar's Premier Job Platform</span>
              </div>
              <div class="flex gap-6 items-center max-sm:flex-wrap max-sm:gap-3">
                <span class="text-sm text-gray-500 cursor-pointer">About NDK</span>
                <span class="text-sm text-gray-500 cursor-pointer">Privacy Policy</span>
                <span class="text-sm text-gray-500 cursor-pointer">Terms of Service</span>
                <span class="text-sm text-gray-500 cursor-pointer">Contact Support</span>
              </div>
            </div>
            <div class="mt-4 text-xs text-center text-gray-400">© 2026 NDK Job Platform. Connecting Myanmar's brightest talent with top employers in Yangon, Mandalay, and beyond.</div>
          </div>
        </div>
      </div>
</template>
