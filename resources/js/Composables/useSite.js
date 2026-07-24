import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/** Institutional identity from `site_settings`, shared by HandleInertiaRequests. */
export function useSite() {
    const page = usePage();

    const site = computed(() => page.props.site || {});
    const s = (key, fallback = '') => site.value[key] || fallback;

    const activeSession = computed(() => page.props.active_session || null);
    const sessionCode = computed(() => activeSession.value?.code || '');

    const portalTagline = computed(() =>
        ['Online Admissions', sessionCode.value ? `Session ${sessionCode.value}` : null]
            .filter(Boolean)
            .join(' · '),
    );

    return {
        site,
        s,
        activeSession,
        sessionCode,
        portalTagline,

        portalName: computed(() => s('portal_name')),
        collegeName: computed(() => s('college_name')),
        collegeShort: computed(() => s('college_short')),
        estdYear: computed(() => s('estd_year')),
        cityState: computed(() => s('city_state')),
        naacGrade: computed(() => s('naac_grade')),
        ugcStatus: computed(() => s('ugc_status')),
        helplinePhone: computed(() => s('helpline_phone')),
        helplineEmail: computed(() => s('helpline_email')),
    };
}
