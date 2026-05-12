import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { useForm, Controller } from 'react-hook-form';
import Select from 'react-select';
import CreatableSelect from 'react-select/creatable';

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

const BASE = (window.__APP_URL__ ?? '').replace(/\/$/, '');
const api = (path) => `${BASE}${path}`;

async function getJson(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`Request failed: ${res.status}`);
    return res.json();
}

/* react-select gets Tailwind via classNames so the menu inherits the
   wider form's look. */
const selectClassNames = {
    control: ({ isFocused, isDisabled }) =>
        [
            'min-h-[44px] !rounded-lg !border !bg-white text-sm transition',
            isFocused ? '!border-indigo-500 !shadow-[0_0_0_3px_rgba(99,102,241,0.15)]'
                      : '!border-slate-200 hover:!border-slate-300',
            isDisabled ? '!bg-slate-50 cursor-not-allowed' : '',
        ].join(' '),
    valueContainer: () => '!px-3',
    placeholder:    () => '!text-slate-400',
    indicatorSeparator: () => '!hidden',
    menu:    () => '!rounded-lg !border !border-slate-200 !shadow-lg !overflow-hidden',
    option:  ({ isFocused, isSelected }) =>
        [
            'text-sm !px-3 !py-2 cursor-pointer',
            isSelected ? '!bg-indigo-600 !text-white'
            : isFocused ? '!bg-indigo-50 !text-slate-900'
                        : '!text-slate-700',
        ].join(' '),
};

/* ------------------------------------------------------------------ */
/*  Component                                                          */
/* ------------------------------------------------------------------ */

function ContactDetailsForm({ action, csrf, backUrl, initial = {} }) {
    const {
        register, handleSubmit, control, watch, setValue, formState: { errors, isSubmitting },
    } = useForm({
        defaultValues: {
            mobile_number:   initial.mobile_number   ?? '',
            email:           initial.email           ?? '',
            country:         null,
            country_code:    initial.country_code ?? '',
            region:          null,
            province:        null,
            city:            null,
            barangay:        null,
            zip_code:        initial.zip_code ?? '',
            address_line_1:  initial.address_line_1 ?? '',
            address_line_2:  initial.address_line_2 ?? '',
        },
    });

    const [countries, setCountries] = useState([]);
    const [regions,   setRegions]   = useState([]);
    const [provinces, setProvinces] = useState([]);
    const [cities,    setCities]    = useState([]);
    const [barangays, setBarangays] = useState([]);

    const country  = watch('country');
    const region   = watch('region');
    const province = watch('province');
    const city     = watch('city');

    const isPh = country?.value === 'PH';
    const regionLabel = isPh ? 'Region' : 'State / Province';

    /* Initial data: countries */
    useEffect(() => {
        getJson(api('/api/address/countries')).then((rows) => {
            setCountries(rows);
            if (initial.country) {
                const match = rows.find((r) => r.value === initial.country);
                if (match) setValue('country', match);
            }
        }).catch(() => setCountries([]));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    /* Country -> dial code + load regions, clear children */
    useEffect(() => {
        if (!country) {
            setValue('country_code', '');
            setRegions([]); setProvinces([]); setCities([]); setBarangays([]);
            return;
        }
        setValue('country_code', country.dialCode ?? '');
        setValue('region',   null);
        setValue('province', null);
        setValue('city',     null);
        setValue('barangay', null);
        setProvinces([]); setCities([]); setBarangays([]);
        getJson(api(`/api/address/regions?country=${country.value}`))
            .then(setRegions).catch(() => setRegions([]));
    }, [country, setValue]);

    /* Region -> provinces (PH) or cities (non-PH); clear children. NCR and
       similar PH regions have no provinces — when the provinces list comes
       back empty we transparently load cities directly by region. */
    useEffect(() => {
        setValue('province', null);
        setValue('city',     null);
        setValue('barangay', null);
        setProvinces([]); setCities([]); setBarangays([]);

        if (!country || !region) return;

        if (isPh) {
            getJson(api(`/api/address/provinces?region=${encodeURIComponent(region.value)}`))
                .then((rows) => {
                    setProvinces(rows);
                    if (!rows || rows.length === 0) {
                        // Region with no provinces (e.g. NCR) -> jump straight to cities.
                        return getJson(api(`/api/address/cities?country=PH&region=${encodeURIComponent(region.value)}`))
                            .then(setCities).catch(() => setCities([]));
                    }
                })
                .catch(() => setProvinces([]));
        } else {
            getJson(api(`/api/address/cities?country=${country.value}&region=${encodeURIComponent(region.value)}`))
                .then(setCities).catch(() => setCities([]));
        }
    }, [region, country, isPh, setValue]);

    /* Province -> cities (PH) */
    useEffect(() => {
        if (!isPh) return;
        setValue('city',     null);
        setValue('barangay', null);
        setCities([]); setBarangays([]);
        if (!province) return;
        getJson(api(`/api/address/cities?country=PH&province=${encodeURIComponent(province.value)}`))
            .then(setCities).catch(() => setCities([]));
    }, [province, isPh, setValue]);

    /* City -> barangays (PH) */
    useEffect(() => {
        if (!isPh) return;
        setValue('barangay', null);
        setBarangays([]);
        if (!city) return;
        getJson(api(`/api/address/barangays?city=${encodeURIComponent(city.value)}`))
            .then(setBarangays).catch(() => setBarangays([]));
    }, [city, isPh, setValue]);

    /* City changes -> try to auto-fill ZIP. PH uses code+name lookup; non-PH
       uses city name only. */
    useEffect(() => {
        if (!city) return;
        const params = new URLSearchParams();
        if (isPh && city.value) params.set('cityCode', city.value);
        if (city.label) params.set('cityName', city.label);
        getJson(api(`/api/address/zip?${params.toString()}`))
            .then((r) => {
                if (r?.zip) setValue('zip_code', r.zip, { shouldValidate: true });
            })
            .catch(() => { /* silent */ });
    }, [city, isPh, setValue]);

    /* Submit -> POST as a real form so the existing redirect chain works */
    const onValid = (data) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;

        const append = (name, value) => {
            const i = document.createElement('input');
            i.type  = 'hidden';
            i.name  = name;
            i.value = value ?? '';
            form.appendChild(i);
        };

        append('_token',           csrf);
        append('mobile_number',    data.mobile_number);
        append('email',            data.email);
        append('country',          data.country?.value ?? '');
        append('country_code',     data.country_code ?? '');
        // Submit human-readable names to the DB; the PSGC code is only used
        // internally to drive the cascade between dropdowns.
        append('region',           data.region?.label ?? '');
        append('province',         data.province?.label ?? '');
        append('city_municipality',data.city?.label ?? '');
        append('barangay',         data.barangay?.label ?? '');
        append('zip_code',         data.zip_code);
        append('address_line_1',   data.address_line_1);
        append('address_line_2',   data.address_line_2);

        document.body.appendChild(form);
        form.submit();
    };

    /* ------------------------------------------------------------------ */
    /*  Markup                                                             */
    /* ------------------------------------------------------------------ */

    const labelCls = 'text-xs font-bold tracking-wide text-slate-800 uppercase';
    const inputCls = 'w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm transition focus:border-indigo-500 focus:outline-none focus:ring-[3px] focus:ring-indigo-500/15';
    const errorCls = 'text-xs font-medium text-rose-600';

    return (
        <form onSubmit={handleSubmit(onValid)} noValidate className="space-y-6">
            {/* Phone numbers (kept native) */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div className="flex flex-col gap-2">
                    <label className={labelCls}>Mobile Number*</label>
                    <input
                        {...register('mobile_number', { required: 'Mobile number is required' })}
                        className={inputCls}
                        placeholder="0917XXXXXXX"
                    />
                    {errors.mobile_number && <p className={errorCls}>{errors.mobile_number.message}</p>}
                </div>
                <div className="flex flex-col gap-2">
                    <label className={labelCls}>Email Address*</label>
                    <input
                        type="email"
                        {...register('email', {
                            required: 'Email is required',
                            pattern: { value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Enter a valid email' },
                        })}
                        className={inputCls}
                        placeholder="you@example.com"
                    />
                    {errors.email && <p className={errorCls}>{errors.email.message}</p>}
                </div>
            </div>

            <h4 className="mt-8 border-b border-slate-100 pb-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                Residential Address
            </h4>

            {/* Country + dial code */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div className="flex flex-col gap-2">
                    <label className={labelCls}>Country*</label>
                    <Controller
                        control={control}
                        name="country"
                        rules={{ required: 'Country is required' }}
                        render={({ field }) => (
                            <Select
                                {...field}
                                options={countries}
                                placeholder="Select country…"
                                isClearable
                                classNames={selectClassNames}
                            />
                        )}
                    />
                    {errors.country && <p className={errorCls}>{errors.country.message}</p>}
                </div>
                <div className="flex flex-col gap-2">
                    <label className={labelCls}>Country Code</label>
                    <input
                        {...register('country_code')}
                        readOnly
                        className={`${inputCls} bg-slate-50 cursor-not-allowed`}
                        placeholder="+__"
                    />
                </div>
            </div>

            {/* Region + Province (Province only when PH) */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div className="flex flex-col gap-2">
                    <label className={labelCls}>{regionLabel}*</label>
                    <Controller
                        control={control}
                        name="region"
                        rules={{ required: `${regionLabel} is required` }}
                        render={({ field }) => {
                            const Cmp = isPh ? Select : CreatableSelect;
                            return (
                                <Cmp
                                    {...field}
                                    options={regions}
                                    isDisabled={!country}
                                    placeholder={
                                        country
                                            ? (isPh ? `Select ${regionLabel.toLowerCase()}…`
                                                   : `Type your ${regionLabel.toLowerCase()} and press Enter`)
                                            : 'Pick a country first'
                                    }
                                    isClearable
                                    classNames={selectClassNames}
                                    formatCreateLabel={(input) => `Use "${input}"`}
                                    onCreateOption={(input) => {
                                        const opt = { value: input, label: input };
                                        field.onChange(opt);
                                    }}
                                    noOptionsMessage={() => isPh ? 'No options available' : 'Type to enter a value'}
                                />
                            );
                        }}
                    />
                    {errors.region && <p className={errorCls}>{errors.region.message}</p>}
                </div>

                {isPh && provinces.length > 0 && (
                    <div className="flex flex-col gap-2">
                        <label className={labelCls}>Province*</label>
                        <Controller
                            control={control}
                            name="province"
                            rules={{ required: provinces.length > 0 ? 'Province is required' : false }}
                            render={({ field }) => (
                                <Select
                                    {...field}
                                    options={provinces}
                                    isDisabled={!region}
                                    placeholder={region ? 'Select province…' : 'Pick a region first'}
                                    isClearable
                                    classNames={selectClassNames}
                                />
                            )}
                        />
                        {errors.province && <p className={errorCls}>{errors.province.message}</p>}
                    </div>
                )}
            </div>

            {/* City + Barangay (Barangay only when PH) */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div className="flex flex-col gap-2">
                    <label className={labelCls}>City / Municipality*</label>
                    <Controller
                        control={control}
                        name="city"
                        rules={{ required: 'City / municipality is required' }}
                        render={({ field }) => {
                            const Cmp = isPh ? Select : CreatableSelect;
                            return (
                                <Cmp
                                    {...field}
                                    options={cities}
                                    isDisabled={
                                        isPh
                                            ? (!region || (provinces.length > 0 && !province))
                                            : !region
                                    }
                                    placeholder={
                                        isPh
                                            ? (region
                                                ? (provinces.length > 0 && !province
                                                    ? 'Pick a province first'
                                                    : 'Select city/municipality…')
                                                : 'Pick a region first')
                                            : (region ? 'Type your city and press Enter' : 'Pick a region first')
                                    }
                                    isClearable
                                    classNames={selectClassNames}
                                    formatCreateLabel={(input) => `Use "${input}"`}
                                    onCreateOption={(input) => {
                                        const opt = { value: input, label: input };
                                        field.onChange(opt);
                                    }}
                                    noOptionsMessage={() => isPh ? 'No options available' : 'Type to enter a value'}
                                />
                            );
                        }}
                    />
                    {errors.city && <p className={errorCls}>{errors.city.message}</p>}
                </div>

                {isPh && (
                    <div className="flex flex-col gap-2">
                        <label className={labelCls}>Barangay*</label>
                        <Controller
                            control={control}
                            name="barangay"
                            rules={{ required: isPh ? 'Barangay is required' : false }}
                            render={({ field }) => (
                                <Select
                                    {...field}
                                    options={barangays}
                                    isDisabled={!city}
                                    placeholder={city ? 'Select barangay…' : 'Pick a city first'}
                                    isClearable
                                    classNames={selectClassNames}
                                />
                            )}
                        />
                        {errors.barangay && <p className={errorCls}>{errors.barangay.message}</p>}
                    </div>
                )}
            </div>

            {/* Zip + Address Line 1 */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div className="flex flex-col gap-2">
                    <label className={labelCls}>Zip Code*</label>
                    <input
                        {...register('zip_code', { required: 'Zip code is required' })}
                        className={inputCls}
                    />
                    {errors.zip_code && <p className={errorCls}>{errors.zip_code.message}</p>}
                </div>
                <div className="flex flex-col gap-2">
                    <label className={labelCls}>Address Line 1*</label>
                    <input
                        {...register('address_line_1', { required: 'Address Line 1 is required' })}
                        className={inputCls}
                        placeholder="House No., Street, Building"
                    />
                    {errors.address_line_1 && <p className={errorCls}>{errors.address_line_1.message}</p>}
                </div>
            </div>

            {/* Address Line 2 */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div className="flex flex-col gap-2 md:col-span-2">
                    <label className={labelCls}>Address Line 2</label>
                    <input
                        {...register('address_line_2')}
                        className={inputCls}
                        placeholder="Subdivision, Village, Phase"
                    />
                </div>
            </div>

            {/* Footer */}
            <div className="mt-10 flex items-center justify-between border-t border-slate-200 pt-5">
                {backUrl
                    ? <a href={backUrl} className="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Back</a>
                    : <span />}
                <button
                    type="submit"
                    disabled={isSubmitting}
                    className="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-500 disabled:opacity-50"
                >
                    {isSubmitting ? 'Saving…' : 'Next Step'}
                </button>
            </div>
        </form>
    );
}

/* ------------------------------------------------------------------ */
/*  Mount                                                              */
/* ------------------------------------------------------------------ */

const mount = document.getElementById('contact-details-react-root');
if (mount) {
    const props = JSON.parse(mount.dataset.props || '{}');
    createRoot(mount).render(<ContactDetailsForm {...props} />);
}
