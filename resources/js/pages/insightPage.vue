<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';

const explorerFilters = reactive({ keyword: '', title: '', experience_level: '', location: '', category_id: '', currency: '' });
const salaryExplorer = ref(null);
const salaryExplorerLoading = ref(true);
const salaryExplorerError = ref('');
const comparisonType = ref('title');
const comparisonFirst = ref('');
const comparisonSecond = ref('');
const personalSalary = ref('');
let explorerTimer;
let explorerController;
let explorerRequest = 0;

const comparisonOptions = computed(() => {
    const options = salaryExplorer.value?.options;
    if (!options) return [];
    return comparisonType.value === 'category'
        ? options.categories.map((category) => ({ value: String(category.id), label: category.name }))
        : options.titles.map((title) => ({ value: title, label: title }));
});
const comparisonMaximum = computed(() => Math.max(0, ...(salaryExplorer.value?.comparison ?? []).map(({ summary }) => summary.median_salary ?? summary.maximum_salary ?? 0)));
const personalSalaryResult = computed(() => {
    const entered = Number(personalSalary.value);
    const summary = salaryExplorer.value?.summary;
    if (!Number.isFinite(entered) || entered <= 0 || !summary?.sample_size || summary.minimum_salary === null || summary.maximum_salary === null) return null;
    const position = entered < summary.minimum_salary ? 'below' : entered > summary.maximum_salary ? 'above' : 'within';
    return { position, sampleSize: summary.sample_size };
});

function salaryAmount(value, currency = salaryExplorer.value?.filters.currency || 'MMK') {
    return value === null || value === undefined ? '—' : `${currency} ${Number(value).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
}

function salaryRange(job) {
    const minimum = job.salary_min ? salaryAmount(job.salary_min, job.currency) : null;
    const maximum = job.salary_max ? salaryAmount(job.salary_max, job.currency) : null;
    if (minimum && maximum) return `${minimum} – ${maximum} / month`;
    if (minimum) return `From ${minimum} / month`;
    if (maximum) return `Up to ${maximum} / month`;
    return 'Salary not disclosed';
}

function scheduleSalaryExplorerLoad() {
    clearTimeout(explorerTimer);
    explorerTimer = setTimeout(loadSalaryExplorer, 250);
}

async function loadSalaryExplorer() {
    const requestId = ++explorerRequest;
    explorerController?.abort();
    explorerController = new AbortController();
    salaryExplorerLoading.value = true;
    salaryExplorerError.value = '';

    const params = new URLSearchParams();
    Object.entries(explorerFilters).forEach(([key, value]) => { if (value !== '') params.set(key, value); });
    if (comparisonFirst.value && comparisonSecond.value && comparisonFirst.value !== comparisonSecond.value) {
        params.set('compare_by', comparisonType.value);
        params.set('compare_a', comparisonFirst.value);
        params.set('compare_b', comparisonSecond.value);
    }

    try {
        const response = await fetch(`/api/salary-insights?${params}`, {
            headers: { Accept: 'application/json' },
            signal: explorerController.signal,
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Salary information could not be loaded.');
        if (requestId !== explorerRequest) return;
        salaryExplorer.value = payload;
        if (!explorerFilters.currency && payload.filters.currency) explorerFilters.currency = payload.filters.currency;

        if (!comparisonFirst.value || !comparisonSecond.value || comparisonFirst.value === comparisonSecond.value) {
            const values = comparisonOptions.value.map((option) => option.value);
            if (values.length >= 2) {
                comparisonFirst.value = values[0];
                comparisonSecond.value = values[1];
            }
        }
    } catch (error) {
        if (error.name !== 'AbortError' && requestId === explorerRequest) salaryExplorerError.value = error.message || 'Salary information could not be loaded.';
    } finally {
        if (requestId === explorerRequest) salaryExplorerLoading.value = false;
    }
}

function resetSalaryFilters() {
    Object.assign(explorerFilters, { keyword: '', title: '', experience_level: '', location: '', category_id: '', currency: '' });
    comparisonType.value = 'title';
    comparisonFirst.value = '';
    comparisonSecond.value = '';
}

watch(explorerFilters, scheduleSalaryExplorerLoad);
watch([comparisonType, comparisonFirst, comparisonSecond], scheduleSalaryExplorerLoad);
watch(comparisonType, () => {
    const values = comparisonOptions.value.map((option) => option.value);
    comparisonFirst.value = values[0] ?? '';
    comparisonSecond.value = values[1] ?? '';
});
onMounted(loadSalaryExplorer);
onUnmounted(() => {
    clearTimeout(explorerTimer);
    explorerController?.abort();
});
const grossMonthlyPay = ref(1500000);
const directHousingValue = ref(0);
const hasTaxDependentSpouse = ref(false);
const taxDependentChildren = ref(0);
const monthlyLifeInsurance = ref(0);
const estimateNetPay = computed(() => {
    const gross = Math.max(0, Number(grossMonthlyPay.value) || 0);
    const housing = Math.min(gross, Math.max(0, Number(directHousingValue.value) || 0));
    const ssb = Math.min(gross * 0.02, 6000);
    const annualTaxableBeforeRelief = Math.max(0, (gross - housing - ssb) * 12);
    const basicRelief = Math.min(annualTaxableBeforeRelief * 0.2, 10000000);
    const dependentRelief = (hasTaxDependentSpouse.value ? 1000000 : 0) + Math.max(0, Number(taxDependentChildren.value) || 0) * 500000;
    const annualInsuranceRelief = Math.max(0, Number(monthlyLifeInsurance.value) || 0) * 12;
    const taxable = Math.max(0, annualTaxableBeforeRelief - basicRelief - dependentRelief - annualInsuranceRelief);
    const bands = [
        { cap: 2000000, rate: 0 }, { cap: 10000000, rate: 0.05 }, { cap: 30000000, rate: 0.1 },
        { cap: 50000000, rate: 0.15 }, { cap: 70000000, rate: 0.2 }, { cap: Infinity, rate: 0.25 },
    ];
    let taxDue = 0;
    let previousCap = 0;
    for (const band of bands) {
        taxDue += Math.max(0, Math.min(taxable, band.cap) - previousCap) * band.rate;
        previousCap = band.cap;
        if (taxable <= band.cap) break;
    }
    const pit = taxDue / 12;
    return { gross, housing, ssb, pit, net: gross - ssb - pit };
});

const tcoeGrossMonthly = ref(1500000);
const tcoeInjuryRate = ref(1);
const tcoeTenureYears = ref(3);
const severanceReserveMonths = computed(() => {
    const years = Number(tcoeTenureYears.value) || 0;
    if (years < 0.5) return 0;
    if (years < 1) return 0.5;
    if (years < 2) return 1;
    if (years < 3) return 1.5;
    if (years < 4) return 3;
    if (years < 6) return 4;
    if (years < 8) return 5;
    if (years < 10) return 6;
    if (years < 20) return 8;
    if (years < 25) return 10;
    return 13;
});
const estimateTcoe = computed(() => {
    const gross = Math.max(0, Number(tcoeGrossMonthly.value) || 0);
    const employerSsb = Math.min(gross * 0.03, 9000);
    const injury = gross * Math.max(0, Number(tcoeInjuryRate.value) || 0) / 100;
    const dailySalary = gross / 26;
    const leaveDays = 10 + 6 + 30 + 16;
    const leaveReserve = dailySalary * leaveDays / 12;
    const severanceReserve = gross * severanceReserveMonths.value / 12;
    return { gross, employerSsb, injury, leaveReserve, severanceReserve, total: gross + employerSsb + injury + leaveReserve + severanceReserve };
});

const complianceDailyWage = ref(7800);
const isBelowMinimumWage = computed(() => Number(complianceDailyWage.value) > 0 && Number(complianceDailyWage.value) < 7800);
const formatMmk = (value) => `MMK ${Math.round(value || 0).toLocaleString('en-US')}`;

const allowanceHistory = [
    { date: '1 October 2023', amount: 'MMK 1,000', detail: 'First additional daily allowance' },
    { date: '1 August 2024', amount: 'MMK 1,000', detail: 'Second additional daily allowance' },
    { date: '1 October 2025', amount: 'MMK 1,000', detail: 'Third additional daily allowance; Notification No. 1/2025 was issued on 14 October 2025' },
];

const packageComponents = [
    { title: 'Base salary', text: 'The contractual wage for the role, stated separately from allowances and benefits.' },
    { title: 'Recurring allowances', text: 'For example, cost-of-living, housing, and transport support. State eligibility and payment terms clearly.' },
    { title: 'Conditional and variable pay', text: 'Attendance premiums, incentives, and bonuses may depend on conditions and should be described separately.' },
    { title: 'Other benefits', text: 'Non-cash benefits and foreign-currency arrangements should show their value, payment currency, and terms.' },
];

const taxBrackets = [
    { range: 'MMK 1 – 2,000,000', rate: '0%' },
    { range: 'MMK 2,000,001 – 10,000,000', rate: '5%' },
    { range: 'MMK 10,000,001 – 30,000,000', rate: '10%' },
    { range: 'MMK 30,000,001 – 50,000,000', rate: '15%' },
    { range: 'MMK 50,000,001 – 70,000,000', rate: '20%' },
    { range: 'Above MMK 70,000,000', rate: '25%' },
];

const personalReliefs = [
    'Basic relief: 20% of income, capped at MMK 10,000,000 per year',
    'Qualifying dependent parent: MMK 1,000,000 each',
    'Qualifying spouse: MMK 1,000,000',
    'Qualifying child: MMK 500,000 each',
    'Qualifying life-insurance premiums and employee social-security contributions',
];

const severanceBands = [
    { tenure: '6 months to under 1 year', salary: '0.5 month' },
    { tenure: '1 year to under 2 years', salary: '1 month' },
    { tenure: '2 years to under 3 years', salary: '1.5 months' },
    { tenure: '3 years to under 4 years', salary: '3 months' },
    { tenure: '4 years to under 6 years', salary: '4 months' },
    { tenure: '6 years to under 8 years', salary: '5 months' },
    { tenure: '8 years to under 10 years', salary: '6 months' },
    { tenure: '10 years to under 20 years', salary: '8 months' },
    { tenure: '20 years to under 25 years', salary: '10 months' },
    { tenure: '25 years or more', salary: '13 months' },
];
</script>

<template>
    <section class="bg-slate-50">
        <div class="bg-gradient-to-br from-blue-50 via-white to-slate-100 px-5 py-12 sm:px-8 sm:py-16">
            <div class="mx-auto max-w-6xl">
                <p class="text-sm font-bold uppercase tracking-[0.12em] text-[var(--brand-primary)]">Compensation intelligence</p>
                <div class="mt-3 flex flex-col justify-between gap-5 md:flex-row md:items-end">
                    <div class="max-w-3xl">
                        <h1 class="text-3xl font-bold tracking-tight text-[var(--brand-ink)] sm:text-4xl">Myanmar Salary Insights</h1>
                        <p class="mt-3 text-base leading-7 text-slate-600">Understand how pay packages are structured and how the current minimum-wage framework affects daily compensation.</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-800">Wage: 1 Oct 2025 · Tax law: 1 Apr 2026</span>
                </div>
                <div class="mt-7 flex flex-wrap gap-2 text-xs font-medium text-slate-600">
                    <span class="rounded-full bg-white px-3 py-1.5 ring-1 ring-slate-200">Package structure</span>
                    <span class="rounded-full bg-white px-3 py-1.5 ring-1 ring-slate-200">Minimum wage</span>
                    <span class="rounded-full bg-white px-3 py-1.5 ring-1 ring-slate-200">Allowances</span>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-6xl space-y-8 px-5 py-9 sm:px-8 sm:py-12">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="market-structure-title">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Part 1 · Market context</p>
                    <h2 id="market-structure-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Look at the full compensation package</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Inflation and currency movements can reduce the purchasing power of a salary stated only as a fixed MMK amount. Comparing offers is more useful when the base wage, recurring allowances, conditional pay, and benefits are shown as separate components.</p>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <article v-for="component in packageComponents" :key="component.title" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h3 class="font-semibold text-slate-900">{{ component.title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ component.text }}</p>
                    </article>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-5">
                        <h3 class="font-semibold text-blue-950">An illustrative package mix</h3>
                        <p class="mt-2 text-sm leading-6 text-blue-950/80">The 60–70% base and 30–40% supplemental split supplied for this page is a directional illustration. It is not a statutory rule or a measured NDK salary survey result. Actual mixes vary by employer, role, and contract.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-900">Show foreign-currency terms clearly</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Some international, technology, and remote roles may quote compensation in a foreign currency. Show the agreed payment currency and conversion terms separately; exchange rates change, so a fixed USD-to-MMK comparison can quickly become outdated.</p>
                    </div>
                </div>

                <p class="mt-5 rounded-lg border-l-4 border-blue-500 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">COLA, housing, and transport support are often recurring allowances rather than variable pay. Attendance premiums and incentives may be conditional. Classify each item by its actual payment terms.</p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="minimum-wage-title">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Part 2 · Statutory reference</p>
                        <h2 id="minimum-wage-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Minimum wage and daily allowances</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">For covered employers, the current eight-hour daily total is MMK 7,800: the MMK 4,800 base rate plus MMK 3,000 in cumulative additional allowances.</p>
                    </div>
                    <div class="shrink-0 rounded-xl bg-[var(--brand-ink)] px-5 py-4 text-white">
                        <p class="text-xs font-medium text-blue-100">Current combined daily amount</p>
                        <p class="mt-1 text-2xl font-bold">MMK 7,800</p>
                        <p class="mt-1 text-xs text-blue-100">8-hour workday · base plus allowances</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 p-5">
                        <p class="text-sm font-medium text-slate-500">Base minimum wage</p>
                        <p class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">MMK 4,800</p>
                        <p class="mt-1 text-sm text-slate-600">Per 8-hour workday · MMK 600 per hour</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-5">
                        <p class="text-sm font-medium text-slate-500">Cumulative additional allowances</p>
                        <p class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">MMK 3,000</p>
                        <p class="mt-1 text-sm text-slate-600">Three daily allowances of MMK 1,000 each</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-5">
                        <p class="text-sm font-medium text-slate-500">Coverage note</p>
                        <p class="mt-2 text-base font-semibold text-[var(--brand-ink)]">Generally applies to employers with 10 or more workers</p>
                        <p class="mt-1 text-sm text-slate-600">Small and family-owned businesses with fewer than 10 workers are excluded under the cited notification. Check the applicable rules for a specific workplace.</p>
                    </div>
                </div>

                <div class="mt-7">
                    <h3 class="font-semibold text-slate-900">Allowance history</h3>
                    <ol class="mt-3 grid gap-3 md:grid-cols-3">
                        <li v-for="item in allowanceHistory" :key="item.date" class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--brand-primary)]">{{ item.date }}</p>
                            <p class="mt-2 text-lg font-bold text-[var(--brand-ink)]">{{ item.amount }}</p>
                            <p class="mt-1 text-sm leading-5 text-slate-600">{{ item.detail }}</p>
                        </li>
                    </ol>
                </div>

                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <h3 class="font-semibold text-amber-950">Training and probation are different stages</h3>
                    <p class="mt-2 text-sm leading-6 text-amber-950/80">The Rules allow at least 50% of the minimum wage for up to three months of pre-probation training in a factory or workshop when a worker has not yet met the required skill or production standard. During the probationary period, at least 75% is payable. Seventy-five percent of the MMK 4,800 base is MMK 3,600. This is the base component only; determine allowance entitlement separately under the applicable notification and employment terms.</p>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-semibold text-slate-800">Sources and further reading</h3>
                    <ul class="mt-2 flex flex-col gap-2 text-sm text-[var(--brand-primary)] sm:flex-row sm:flex-wrap sm:gap-x-5">
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://servicetrade.gov.mm/horizontal/rule-detail/minimum-wage-rules" target="_blank" rel="noopener noreferrer">Myanmar Minimum Wage Rules</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.lincolnmyanmar.com/wp-content/uploads/2025/10/Minimum-wage-notification-2025.pdf" target="_blank" rel="noopener noreferrer">Notification No. 1/2025 (convenience translation)</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.tilleke.com/insights/myanmar-sets-new-minimum-wage/55/" target="_blank" rel="noopener noreferrer">Legal update on the 2025 daily allowance</a></li>
                    </ul>
                    <p class="mt-3 text-xs leading-5 text-slate-500">This page is general information, not legal or payroll advice. A payroll estimate also needs taxpayer-specific information, current social-security contribution rules, foreign-currency treatment, and employer-specific details.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="overtime-title">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Part 3 · Overtime</p>
                    <h2 id="overtime-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Calculate overtime from the applicable wage basis</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">The MMK 3,000 in additional minimum-wage allowances is excluded from overtime calculations. For a worker paid the MMK 4,800 statutory base, the standard hourly overtime example is MMK 1,200.</p>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-5">
                        <p class="text-sm font-semibold text-blue-950">Statutory minimum-wage example</p>
                        <p class="mt-3 rounded-lg bg-white px-4 py-3 text-center font-mono text-sm font-semibold text-[var(--brand-ink)]">(MMK 4,800 ÷ 8 hours) × 2 = MMK 1,200 / OT hour</p>
                        <p class="mt-3 text-sm leading-6 text-blue-950/80">The double rate is calculated on the base wage. Do not add the MMK 3,000 daily allowance to this base for the minimum-wage OT calculation.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-5">
                        <p class="text-sm font-semibold text-slate-900">Daily-wage workers paid above the minimum</p>
                        <p class="mt-3 rounded-lg bg-slate-50 px-4 py-3 text-center font-mono text-sm font-semibold text-slate-800">(Daily wage × 6 ÷ applicable weekly hours) × 2</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">The applicable weekly-hours denominator depends on the sector and work pattern. ILO guidance distinguishes this standing-order formula from the MMK 4,800 ÷ 8-hour formula for workers paid the statutory minimum.</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <article class="rounded-xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-900">Factories</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">For non-continuous factory work, overtime generally begins after 8 hours in a day or 44 hours in a week. The ILO guide describes limits of 3 overtime hours on weekdays, 5 on Saturday, and 20 per week for this category. Some continuous-work schedules have different thresholds.</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-900">Shops and establishments</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">The ILO guide describes overtime after 8 hours a day or 48 hours a week, with a limit of 12 overtime hours per week (16 in exceptional cases). Confirm the rules for the specific workplace and job category.</p>
                    </article>
                </div>

                <p class="mt-5 rounded-lg border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">A 10-hour factory attendance span can mean 8 net working hours plus 2 hours of rest; it is not a universal cap on total hours including overtime. Work on weekly rest days and public holidays has separate treatment under the Leave and Holidays Act. Do not assume time off in lieu can replace statutory payment without checking the applicable rule.</p>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-semibold text-slate-800">Overtime sources</h3>
                    <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-[var(--brand-primary)]">
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.ilo.org/sites/default/files/wcmsp5/groups/public/%40asia/%40ro-bangkok/%40ilo-yangon/documents/publication/wcms_634853.pdf" target="_blank" rel="noopener noreferrer">ILO Guide to Myanmar Labour Law · worker FAQs</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.tilleke.com/insights/myanmar-sets-new-minimum-wage/55/" target="_blank" rel="noopener noreferrer">2025 allowance and overtime base summary</a></li>
                    </ul>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="pit-title">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Part 4 · Personal income tax</p>
                    <h2 id="pit-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Annual salary tax, reliefs, and withholding</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Salary income up to MMK 4,800,000 per year is exempt. Salary income can include wages, bonuses, awards, fees, commissions, and other qualifying benefits, not just base salary. When annual salary exceeds the exemption threshold, applicable reliefs are deducted before progressive rates are applied.</p>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-900">Progressive rate schedule</h3>
                            <p class="mt-1 text-xs text-slate-600">Bands apply to taxable income after allowable reliefs—not directly to gross salary.</p>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white text-xs uppercase tracking-wide text-slate-500">
                                <tr><th scope="col" class="px-4 py-3 font-semibold">Annual taxable income</th><th scope="col" class="px-4 py-3 text-right font-semibold">Rate</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="bracket in taxBrackets" :key="bracket.range" class="border-t border-slate-100">
                                    <td class="px-4 py-3 text-slate-700">{{ bracket.range }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-[var(--brand-ink)]">{{ bracket.rate }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <aside class="rounded-xl border border-blue-100 bg-blue-50/70 p-5">
                        <h3 class="font-semibold text-blue-950">Common personal reliefs</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-5 text-blue-950/80">
                            <li v-for="relief in personalReliefs" :key="relief" class="flex gap-2"><i class="ti ti-check text-blue-700" aria-hidden="true" /><span>{{ relief }}</span></li>
                        </ul>
                    </aside>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-900">Employer withholding</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">The employer estimates annual salary tax and withholds it when salary is disbursed, remitting it in installments. Individual tax status and relief eligibility can change the result.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-900">Housing is not one single tax treatment</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Employer-owned housing or accommodation leased and provided directly for an employee may be excluded from salary income. Cash housing allowances and payments to cover an employee’s rent are taxable. Keep the arrangement and supporting documents clear.</p>
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-semibold text-slate-800">Tax sources</h3>
                    <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-[var(--brand-primary)]">
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://presoffministry.gov.mm/en/node/28603" target="_blank" rel="noopener noreferrer">Union Tax Law 2026 · effective 1 April 2026</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://ird.gov.mm/tax-knowledge/taxes/individual" target="_blank" rel="noopener noreferrer">Internal Revenue Department · individual income tax</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.lincolnmyanmar.com/wp-content/uploads/2023/07/Interpretation-Statement-1-2023x.pdf" target="_blank" rel="noopener noreferrer">IRD Interpretation Statement 1/2023 (convenience translation)</a></li>
                    </ul>
                    <p class="mt-3 text-xs leading-5 text-slate-500">This page is general information, not legal or tax advice. Actual withholding depends on tax residency, income type, current tax year, and eligible reliefs. Verify individual cases with the IRD or a qualified Myanmar tax adviser.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="ssb-title">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Part 5 · Social security</p>
                    <h2 id="ssb-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">SSB contributions and insured benefits</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">For covered employment, the standard Health and Social Care Fund contribution is shared between employee and employer and is calculated up to the MMK 300,000 monthly insurable wage ceiling.</p>
                </div>

                <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th scope="col" class="px-4 py-3 font-semibold">Contribution</th><th scope="col" class="px-4 py-3 font-semibold">Rate</th><th scope="col" class="px-4 py-3 text-right font-semibold">Monthly maximum</th></tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-slate-100"><th scope="row" class="px-4 py-3 font-medium text-slate-700">Employee</th><td class="px-4 py-3 text-slate-700">2%</td><td class="px-4 py-3 text-right font-semibold text-[var(--brand-ink)]">MMK 6,000</td></tr>
                            <tr class="border-t border-slate-100"><th scope="row" class="px-4 py-3 font-medium text-slate-700">Employer</th><td class="px-4 py-3 text-slate-700">3%</td><td class="px-4 py-3 text-right font-semibold text-[var(--brand-ink)]">MMK 9,000</td></tr>
                            <tr class="border-t border-slate-100 bg-blue-50/60"><th scope="row" class="px-4 py-3 font-semibold text-blue-950">Combined standard contribution</th><td class="px-4 py-3 font-semibold text-blue-950">5%</td><td class="px-4 py-3 text-right font-bold text-blue-950">MMK 15,000</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-5">
                        <h3 class="font-semibold text-blue-950">Employment injury insurance is assessed separately</h3>
                        <p class="mt-2 text-sm leading-6 text-blue-950/80">The Social Security Rules provide for an employer-funded Employment Injury Benefit Fund. Its contribution can depend on the prescribed rate and workplace risk. Do not automatically add a flat 1% premium to every employer’s 3% contribution without confirming the current SSB assessment.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-900">Sickness benefit is not annual paid leave</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Eligible insured workers may receive an SSB cash benefit of 60% of average wages for up to 26 weeks of incapacity, subject to contribution history and medical certification. This is a social-security benefit, not 26 weeks of employer-paid sick leave each year.</p>
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-semibold text-slate-800">SSB sources</h3>
                    <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-[var(--brand-primary)]">
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.ssb.gov.mm/Portal/assets/attachment/The-Social-Security-Rules-English%20Version.pdf" target="_blank" rel="noopener noreferrer">Social Security Rules · contribution and sickness benefit provisions</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://ssb.gov.mm/Portal/assets/attachment/Social-Security-Law-2012-English%20Version.pdf" target="_blank" rel="noopener noreferrer">Social Security Law 2012</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.csostat.gov.mm/Content/PublicationAndRelease/2024/Glossary.htm" target="_blank" rel="noopener noreferrer">Central Statistical Organization · SSB benefit summary</a></li>
                    </ul>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="leave-severance-title">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Part 6 · Leave and termination</p>
                    <h2 id="leave-severance-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Leave entitlements and severance reference</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Leave rights can come from employment law, the employment contract, or SSB insurance benefits. Keep those sources distinct when comparing total compensation.</p>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Earned leave</h3><p class="mt-2 text-sm leading-6 text-slate-600">10 days per year after qualifying service; leave may be taken consecutively or separately.</p></article>
                    <article class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Casual leave</h3><p class="mt-2 text-sm leading-6 text-slate-600">6 paid days per year under the government investment guide.</p></article>
                    <article class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Maternity and paternity</h3><p class="mt-2 text-sm leading-6 text-slate-600">The cited guide lists 14 weeks maternity leave (with an additional 4 weeks for twins) and up to 15 days paternity leave. SSB cash benefits have insurance eligibility and payment rules.</p></article>
                    <article class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Sick leave and SSB</h3><p class="mt-2 text-sm leading-6 text-slate-600">The 26-week figure above is the maximum SSB sickness cash-benefit period for eligible insured workers. Employer leave entitlements and any additional contract benefits should be checked separately.</p></article>
                    <article class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Public holidays</h3><p class="mt-2 text-sm leading-6 text-slate-600">The government declares public or gazette holidays; the number can vary by year. Use the applicable annual calendar rather than treating 16 as a fixed number.</p></article>
                    <article class="rounded-xl border border-slate-200 p-5"><h3 class="font-semibold text-slate-900">Working on a public holiday</h3><p class="mt-2 text-sm leading-6 text-slate-600">The government guide states employees required to work on a public holiday are paid twice the ordinary rate and the living allowance. Confirm the current rules for the relevant workplace.</p></article>
                </div>

                <div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-900">Severance schedule</h3>
                            <p class="mt-1 text-xs text-slate-600">Reference schedule under Notification No. 84/2015</p>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white text-xs uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-4 py-3 font-semibold">Continuous service</th><th scope="col" class="px-4 py-3 text-right font-semibold">Salary equivalent</th></tr></thead>
                            <tbody>
                                <tr v-for="band in severanceBands" :key="band.tenure" class="border-t border-slate-100"><td class="px-4 py-3 text-slate-700">{{ band.tenure }}</td><td class="px-4 py-3 text-right font-semibold text-[var(--brand-ink)]">{{ band.salary }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <aside class="h-fit rounded-xl border border-amber-200 bg-amber-50 p-5">
                        <h3 class="font-semibold text-amber-950">Check eligibility and calculation basis</h3>
                        <p class="mt-2 text-sm leading-6 text-amber-950/80">Severance depends on the reason and circumstances of termination. This table is a reference schedule; it does not by itself determine eligibility or the salary basis for an individual case.</p>
                    </aside>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-semibold text-slate-800">Leave and severance sources</h3>
                    <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-[var(--brand-primary)]">
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.dica.gov.mm/wp-content/uploads/2024/12/mig_2023_20240129.pdf" target="_blank" rel="noopener noreferrer">Myanmar Investment Guide · leave and holidays</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://tradefordecentwork.ilo.org/wp-content/uploads/2024/09/Myanmar-Labour-Laws-and-COVID-19-FAQ.pdf" target="_blank" rel="noopener noreferrer">ILO labour-law FAQ · severance schedule</a></li>
                        <li><a class="underline underline-offset-2 hover:text-[var(--brand-primary-hover)]" href="https://www.mol.gov.mm/laws-and-regulations/" target="_blank" rel="noopener noreferrer">Ministry of Labour · laws and regulations</a></li>
                    </ul>
                    <p class="mt-3 text-xs leading-5 text-slate-500">This section is a general reference. Verify leave, insurance eligibility, public-holiday dates, and severance calculations with the Ministry of Labour, SSB, or a qualified Myanmar employment adviser.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="salary-explorer-title">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Live platform data</p>
                        <h2 id="salary-explorer-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Salary Explorer</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Explore published job listings that disclose a valid monthly salary. Figures update from matching records and are separated by currency; they are advertised ranges, not independently verified compensation outcomes.</p>
                    </div>
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500" @click="resetSalaryFilters">Reset filters</button>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="text-sm font-medium text-slate-700">Job title or keyword
                        <input v-model="explorerFilters.keyword" type="search" maxlength="120" placeholder="Search advertised job titles" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                    </label>
                    <label class="text-sm font-medium text-slate-700">Specific job title
                        <select v-model="explorerFilters.title" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="">All titles</option><option v-for="title in salaryExplorer?.options.titles ?? []" :key="title" :value="title">{{ title }}</option></select>
                    </label>
                    <label class="text-sm font-medium text-slate-700">Experience level
                        <select v-model="explorerFilters.experience_level" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="">All experience levels</option><option value="entry">Entry</option><option value="junior">Junior</option><option value="mid">Mid</option><option value="senior">Senior</option><option value="lead">Lead</option><option value="executive">Executive</option></select>
                    </label>
                    <label class="text-sm font-medium text-slate-700">Location
                        <input v-model="explorerFilters.location" type="search" maxlength="120" placeholder="Township, region, or location" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                    </label>
                    <label class="text-sm font-medium text-slate-700">Job category
                        <select v-model="explorerFilters.category_id" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="">All categories</option><option v-for="category in salaryExplorer?.options.categories ?? []" :key="category.id" :value="String(category.id)">{{ category.name }}</option></select>
                    </label>
                    <label class="text-sm font-medium text-slate-700">Salary currency
                        <select v-model="explorerFilters.currency" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option v-for="currency in salaryExplorer?.options.currencies ?? []" :key="currency" :value="currency">{{ currency }}</option></select>
                    </label>
                </div>

                <div v-if="salaryExplorerLoading" class="mt-6 rounded-xl border border-blue-100 bg-blue-50 px-4 py-5 text-sm text-blue-900" role="status">Loading salary data…</div>
                <div v-else-if="salaryExplorerError" class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-5 text-sm text-red-800" role="alert">{{ salaryExplorerError }} <button type="button" class="ml-2 font-semibold underline" @click="loadSalaryExplorer">Try again</button></div>
                <template v-else-if="salaryExplorer">
                    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Listings used</p><p class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">{{ salaryExplorer.summary.sample_size }}</p><p class="mt-1 text-xs text-slate-500">Published listings with valid salary values</p></article>
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estimated minimum</p><p class="mt-2 text-xl font-bold text-[var(--brand-ink)]">{{ salaryAmount(salaryExplorer.summary.minimum_salary) }}</p><p class="mt-1 text-xs text-slate-500">Lowest advertised range floor</p></article>
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estimated maximum</p><p class="mt-2 text-xl font-bold text-[var(--brand-ink)]">{{ salaryAmount(salaryExplorer.summary.maximum_salary) }}</p><p class="mt-1 text-xs text-slate-500">Highest advertised range ceiling</p></article>
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Median advertised salary</p><p class="mt-2 text-xl font-bold text-[var(--brand-ink)]">{{ salaryExplorer.summary.median_available ? salaryAmount(salaryExplorer.summary.median_salary) : 'Insufficient data' }}</p><p class="mt-1 text-xs text-slate-500">Requires at least {{ salaryExplorer.summary.minimum_median_sample }} matching listings</p></article>
                    </div>

                    <div v-if="salaryExplorer.summary.sample_size" class="mt-6 grid gap-5 lg:grid-cols-2">
                        <section class="rounded-xl border border-slate-200 p-5" aria-labelledby="salary-distribution-title">
                            <div class="flex items-start justify-between gap-3"><div><h3 id="salary-distribution-title" class="font-semibold text-slate-900">Advertised salary distribution</h3><p class="mt-1 text-xs text-slate-500">Buckets use each listing’s range midpoint, or its single disclosed bound.</p></div><span class="text-xs font-medium text-slate-500">{{ salaryExplorer.filters.currency }} / month</span></div>
                            <div class="mt-5 space-y-3"><div v-for="(bucket, index) in salaryExplorer.distribution" :key="index" class="grid grid-cols-[112px_minmax(0,1fr)_32px] items-center gap-3 text-xs"><span class="truncate text-slate-600">{{ salaryAmount(bucket.minimum) }}{{ bucket.minimum !== bucket.maximum ? `–${Number(bucket.maximum).toLocaleString('en-US', { maximumFractionDigits: 0 })}` : '' }}</span><div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-700 transition-[width] duration-200" :style="{ width: `${salaryExplorer.summary.sample_size ? bucket.count / salaryExplorer.summary.sample_size * 100 : 0}%` }" /></div><span class="text-right font-semibold text-slate-700">{{ bucket.count }}</span></div></div>
                        </section>

                        <section class="rounded-xl border border-slate-200 p-5" aria-labelledby="salary-comparison-title">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 id="salary-comparison-title" class="font-semibold text-slate-900">Compare two roles or categories</h3><p class="mt-1 text-xs text-slate-500">Both comparisons use the selected currency and filters.</p></div><select v-model="comparisonType" aria-label="Compare by title or category" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm"><option value="title">Job title</option><option value="category">Category</option></select></div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2"><label class="text-xs font-medium text-slate-600">First selection<select v-model="comparisonFirst" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-800"><option value="">Choose one</option><option v-for="option in comparisonOptions" :key="`a-${option.value}`" :value="option.value">{{ option.label }}</option></select></label><label class="text-xs font-medium text-slate-600">Second selection<select v-model="comparisonSecond" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-800"><option value="">Choose one</option><option v-for="option in comparisonOptions" :key="`b-${option.value}`" :value="option.value">{{ option.label }}</option></select></label></div>
                            <div v-if="salaryExplorer.comparison.length === 2" class="mt-4 space-y-4"><div v-for="item in salaryExplorer.comparison" :key="item.value"><div class="flex justify-between gap-3 text-xs"><span class="truncate font-semibold text-slate-700">{{ comparisonOptions.find((option) => option.value === item.value)?.label ?? item.value }}</span><span class="shrink-0 text-slate-500">{{ item.summary.sample_size }} listings</span></div><div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-sky-600" :style="{ width: `${comparisonMaximum && item.summary.median_salary ? item.summary.median_salary / comparisonMaximum * 100 : 0}%` }" /></div><p v-if="item.summary.sample_size" class="mt-1 text-xs text-slate-600">Median: {{ item.summary.median_available ? salaryAmount(item.summary.median_salary) : 'Insufficient data' }} · Range: {{ salaryAmount(item.summary.minimum_salary) }} – {{ salaryAmount(item.summary.maximum_salary) }}</p><p v-else class="mt-1 text-xs text-slate-500">No matching listings for this selection.</p></div></div>
                            <p v-else class="mt-4 text-sm text-slate-500">Choose two different values to compare records.</p>
                        </section>
                    </div>
                    <div v-else class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center"><h3 class="font-semibold text-slate-800">No matching salary listings</h3><p class="mt-2 text-sm text-slate-600">Try another filter. Statistics appear only when published listings contain a positive salary amount.</p></div>

                    <section v-if="salaryExplorer.summary.sample_size" class="mt-5 rounded-xl border border-blue-100 bg-blue-50/70 p-5" aria-labelledby="personal-salary-title">
                        <h3 id="personal-salary-title" class="font-semibold text-blue-950">Compare an expected salary</h3>
                        <p class="mt-1 text-sm leading-5 text-blue-900/80">Enter a monthly amount to see where it falls against the current explorer filters. This describes advertised data and does not judge whether an offer is fair.</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,280px)_1fr] sm:items-end"><label class="text-sm font-medium text-slate-700">Your expected monthly salary ({{ salaryExplorer.filters.currency }})<input v-model.number="personalSalary" type="number" min="1" step="1000" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /></label><p v-if="personalSalaryResult" class="rounded-lg bg-white px-4 py-3 text-sm text-slate-700">This amount is <strong>{{ personalSalaryResult.position }}</strong> the observed advertised range, based on {{ personalSalaryResult.sampleSize }} matching listings.</p><p v-else class="text-sm text-slate-500">Enter a positive amount to compare it with the available range.</p></div>
                    </section>

                    <section class="mt-6" aria-labelledby="salary-related-jobs-title"><div class="flex items-center justify-between gap-3"><h3 id="salary-related-jobs-title" class="font-semibold text-slate-900">Related job listings</h3><span class="text-xs text-slate-500">{{ salaryExplorer.jobs.length }} shown</span></div><div v-if="salaryExplorer.jobs.length" class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3"><article v-for="job in salaryExplorer.jobs" :key="job.id" class="rounded-xl border border-slate-200 p-4"><h4 class="font-semibold text-slate-900">{{ job.title }}</h4><p class="mt-1 text-xs text-slate-500">{{ job.category || 'Category not specified' }} · {{ job.location }}</p><p class="mt-3 text-sm font-semibold text-[var(--brand-ink)]">{{ salaryRange(job) }}</p><a :href="`/#details?job=${job.id}`" class="mt-3 inline-flex text-sm font-semibold text-blue-800 hover:underline">View job</a></article></div><p v-else class="mt-3 text-sm text-slate-500">No matching listings are available.</p></section>
                    <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500">Method: only published, unexpired listings with positive, internally consistent salary bounds are included. A range’s midpoint represents that listing for the median and distribution; a single bound represents itself. Minimum and maximum cards use the disclosed range floor and ceiling. Median is shown only for {{ salaryExplorer.summary.minimum_median_sample }} or more records. Monthly units follow the platform’s job-posting form. Currency conversion is not performed.</p>
                </template>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="gross-net-calculator-title">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Interactive estimate · job seekers</p>
                    <h2 id="gross-net-calculator-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Gross-to-net monthly pay calculator</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">This estimate annualizes monthly taxable salary for the April–March tax year, applies the displayed relief assumptions, then shows monthly PIT and SSB. Directly provided housing is excluded from taxable salary in this estimate. Verify relief eligibility and current tax treatment before using it for payroll.</p>
                </div>
                <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-medium text-slate-700">Gross monthly pay (MMK)<input v-model.number="grossMonthlyPay" type="number" min="0" step="10000" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /></label>
                        <label class="text-sm font-medium text-slate-700">Direct employer housing value (included in gross)<input v-model.number="directHousingValue" type="number" min="0" step="10000" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /></label>
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm text-slate-700 sm:col-span-2"><input v-model="hasTaxDependentSpouse" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500" /> Include qualifying spouse relief (MMK 1,000,000 annually)</label>
                        <label class="text-sm font-medium text-slate-700">Qualifying dependent children<input v-model.number="taxDependentChildren" type="number" min="0" step="1" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /></label>
                        <label class="text-sm font-medium text-slate-700">Eligible life-insurance premium (MMK/month)<input v-model.number="monthlyLifeInsurance" type="number" min="0" step="1000" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /></label>
                    </div>
                    <aside class="rounded-xl bg-[var(--brand-ink)] p-5 text-white">
                        <h3 class="font-semibold">Estimated monthly breakdown</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-blue-100">Gross pay</dt><dd class="font-semibold">{{ formatMmk(estimateNetPay.gross) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-blue-100">Employee SSB</dt><dd class="font-semibold">− {{ formatMmk(estimateNetPay.ssb) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-blue-100">Estimated PIT</dt><dd class="font-semibold">− {{ formatMmk(estimateNetPay.pit) }}</dd></div>
                            <div class="border-t border-blue-700 pt-3"><div class="flex justify-between gap-3"><dt class="font-semibold">Estimated take-home</dt><dd class="text-lg font-bold">{{ formatMmk(estimateNetPay.net) }}</dd></div></div>
                        </dl>
                        <p class="mt-4 text-xs leading-5 text-blue-100">The SSB line is capped at MMK 6,000. Reliefs are simplified and subject to eligibility; this is not a payroll filing calculation.</p>
                    </aside>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="tcoe-calculator-title">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Interactive estimate · employers</p>
                    <h2 id="tcoe-calculator-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Total cost of employment planner</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Estimate recurring employer contributions and monthly planning reserves. Leave days are shown as a reserve assumption, not an additional statutory payment on top of paid salary; actual costing depends on work pattern, contract, and payroll policy.</p>
                </div>
                <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-medium text-slate-700">Gross monthly compensation (MMK)<input v-model.number="tcoeGrossMonthly" type="number" min="0" step="10000" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /></label>
                        <label class="text-sm font-medium text-slate-700">Employment injury rate (%)<input v-model.number="tcoeInjuryRate" type="number" min="0" max="1.5" step="0.1" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /><span class="mt-1 block text-xs font-normal text-slate-500">Starts at the 1% basic rate; confirm the SSB assessed rate.</span></label>
                        <label class="text-sm font-medium text-slate-700 sm:col-span-2">Expected continuous service (years)<input v-model.number="tcoeTenureYears" type="number" min="0" max="40" step="0.5" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /></label>
                    </div>
                    <aside class="rounded-xl bg-[var(--brand-ink)] p-5 text-white">
                        <h3 class="font-semibold">Estimated monthly employer cost</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-blue-100">Gross compensation</dt><dd class="font-semibold">{{ formatMmk(estimateTcoe.gross) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-blue-100">Employer SSB (3%, capped)</dt><dd class="font-semibold">+ {{ formatMmk(estimateTcoe.employerSsb) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-blue-100">Injury contribution assumption</dt><dd class="font-semibold">+ {{ formatMmk(estimateTcoe.injury) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-blue-100">Leave planning reserve</dt><dd class="font-semibold">+ {{ formatMmk(estimateTcoe.leaveReserve) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-blue-100">Severance reserve ({{ severanceReserveMonths }} months over 12)</dt><dd class="font-semibold">+ {{ formatMmk(estimateTcoe.severanceReserve) }}</dd></div>
                            <div class="border-t border-blue-700 pt-3"><div class="flex justify-between gap-3"><dt class="font-semibold">Estimated total</dt><dd class="text-lg font-bold">{{ formatMmk(estimateTcoe.total) }}</dd></div></div>
                        </dl>
                        <p class="mt-4 text-xs leading-5 text-blue-100">Planning model assumes 10 annual, 6 casual, 30 medical, and 16 public holiday days and 26 working days per month. Actual statutory eligibility and yearly holiday declarations vary.</p>
                    </aside>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="compliance-alert-title">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--brand-primary)]">Employer guardrail · manual estimate</p>
                    <h2 id="compliance-alert-title" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">Minimum wage and payroll reminders</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Check an offered daily rate against the current minimum-wage total for covered workplaces. This on-page check is informational and does not yet run automatically when employers submit a job posting.</p>
                </div>
                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-5">
                        <label class="text-sm font-medium text-slate-700">Offered daily wage (MMK)<input v-model.number="complianceDailyWage" type="number" min="0" step="100" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" /></label>
                        <p v-if="isBelowMinimumWage" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">Warning: below MMK 7,800 per 8-hour day. Verify coverage and the applicable minimum wage notification.</p>
                        <p v-else class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">At or above the MMK 7,800 daily reference. Confirm workplace coverage and wage components.</p>
                    </div>
                    <div class="space-y-3">
                        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4"><h3 class="font-semibold text-blue-950">Overtime reference</h3><p class="mt-1 text-sm leading-6 text-blue-950/80">The supplied statutory minimum-wage example uses MMK 4,800 ÷ 8 = MMK 600 per hour; at 200%, that is MMK 1,200 per overtime hour. Do not calculate OT from the combined MMK 7,800 floor.</p></div>
                        <div class="rounded-xl border border-slate-200 p-4"><h3 class="font-semibold text-slate-900">Payroll records and wage statements</h3><p class="mt-1 text-sm leading-6 text-slate-600">Give workers a clear itemized wage statement as a sound payroll practice and maintain required wage, attendance, and deduction records. Tax Administration Law business records generally have a seven-year retention period; Payment of Wages Rules require specified wage-payment records for at least 12 months. Avoid treating these as the same retention rule.</p></div>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 pt-4 text-sm text-[var(--brand-primary)]">
                    <a class="underline underline-offset-2" href="https://servicetrade.gov.mm/horizontal/rule-detail/social-security-rules" target="_blank" rel="noopener noreferrer">SSB Rules · injury contribution rates</a>
                    <a class="underline underline-offset-2" href="https://servicetrade.gov.mm/horizontal/law-detail/tax-administration-law" target="_blank" rel="noopener noreferrer">Tax Administration Law · record retention</a>
                    <a class="underline underline-offset-2" href="https://www.lincolnmyanmar.com/wp-content/uploads/2023/06/Payment-of-Wages-Rules.pdf" target="_blank" rel="noopener noreferrer">Payment of Wages Rules · payment records</a>
                    <a class="underline underline-offset-2" href="https://servicetrade.gov.mm/horizontal/law-detail/the-minimum-wage-law-2013" target="_blank" rel="noopener noreferrer">Minimum Wage Law</a>
                </div>
            </section>

        </div>
    </section>
</template>
