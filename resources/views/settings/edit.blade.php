<x-management-layout title="تنظیمات کلینیک">
    <x-page-header title="هویت و تنظیمات کلینیک" description="نام پزشک و برندینگ از اینجا مرکزی می‌شود. فیلد خالی یعنی استفاده از پیش‌فرض تأییدشده یا مقدار تعیین‌نشده. آمار یا ادعاهای پزشکی ساختگی وارد نکنید." />

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-labelledby="identity-heading">
            <h2 id="identity-heading" class="text-base font-semibold text-slate-900">هویت کلینیک</h2>
            <p class="mt-1 text-sm text-slate-600">نام نمایشی عمومی پزشک باید دقیقاً «دکتر هومان شفاعی» باشد مگر کلینیک خلاف آن را تأیید کند.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <x-text-field name="clinic_name" label="نام نمایشی کلینیک" :value="$values['clinic_name']" placeholder="کلینیک ارتودنسی دکتر هومان شفاعی" />
                <x-text-field name="doctor_display_name" label="نام نمایشی پزشک" :value="$values['doctor_display_name']" placeholder="دکتر هومان شفاعی" />
                <x-text-field name="doctor_title" label="عنوان حرفه‌ای" :value="$values['doctor_title']" placeholder="متخصص ارتودنسی" />
                <x-text-field name="contact_phone" label="تلفن تماس" :value="$values['contact_phone']" />
                <div class="md:col-span-2">
                    <x-text-field name="clinic_address" label="آدرس کلینیک" :value="$values['clinic_address']" />
                </div>
                <div class="md:col-span-2">
                    <label class="block space-y-1" for="clinic_intro">
                        <span class="text-sm font-medium">متن معرفی تأییدشده</span>
                        <textarea id="clinic_intro" name="clinic_intro" rows="3" maxlength="1000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">{{ old('clinic_intro', $values['clinic_intro']) }}</textarea>
                    </label>
                </div>
                <div class="md:col-span-2">
                    <label class="block space-y-1" for="booking_display_note">
                        <span class="text-sm font-medium">توضیح رزرو آنلاین برای بیمار</span>
                        <textarea id="booking_display_note" name="booking_display_note" rows="3" maxlength="1000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">{{ old('booking_display_note', $values['booking_display_note']) }}</textarea>
                    </label>
                    <p class="mt-1 text-xs text-slate-500">باید روشن کند که درخواست آنلاین نیاز به تأیید پذیرش دارد.</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-labelledby="brand-heading">
            <h2 id="brand-heading" class="text-base font-semibold text-slate-900">رنگ و دارایی‌های بصری</h2>
            <p class="mt-1 text-sm text-slate-600">مسیر لوگو/پرتره فقط فایل تأییدشده داخل public است. بدون فایل تأییدشده، هویت تایپوگرافی استفاده می‌شود.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <x-text-field name="primary_color" label="رنگ اصلی (هگز)" :value="$values['primary_color']" placeholder="#0B1220" />
                <x-text-field name="accent_color" label="رنگ تأکید (هگز)" :value="$values['accent_color']" placeholder="#55E6D0" />
                <x-text-field name="logo_path" label="مسیر نسبی لوگو" :value="$values['logo_path']" placeholder="branding/logo.svg" />
                <x-text-field name="doctor_portrait_path" label="مسیر نسبی پرتره پزشک" :value="$values['doctor_portrait_path']" placeholder="branding/doctor.jpg" />
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-labelledby="ops-heading">
            <h2 id="ops-heading" class="text-base font-semibold text-slate-900">عملیات و سیاست</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <x-text-field name="timezone" label="منطقه زمانی کلینیک" :value="$values['timezone']" placeholder="فقط پس از تأیید، مانند Asia/Tehran" />
                <x-text-field name="currency" label="واحد پول" :value="$values['currency']" placeholder="تعیین نشده" />
                <x-select-field
                    name="booking_confirmation_mode"
                    label="سیاست تأیید نوبت آنلاین"
                    :options="collect($modes)->mapWithKeys(fn ($mode) => [$mode->value => $mode->label()])->all()"
                    :value="$values['booking_confirmation_mode']"
                    placeholder="هنوز انتخاب نشده"
                />
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">منطقه زمانی فعلی پیکربندی برنامه {{ $applicationTimezone }} است و تا تأیید کلینیک به‌عنوان واقعیت کسب‌وکار ثبت نمی‌شود.</p>
        </section>

        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700">ذخیره تنظیمات</button>
    </form>
</x-management-layout>
