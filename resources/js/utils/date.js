// Human date formatter for the admissions portal.
//
// Default output: DD MMM YYYY (e.g. "01 Apr 2026")
// With withTime: DD MMM YYYY, HH:MM (24h, IST locale) (e.g. "01 Apr 2026, 14:30")
//
// Accepts ISO strings, Date instances, YYYY-MM-DD, null, undefined.
// Returns '—' for empty / unparseable inputs.

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const pad = (n) => String(n).padStart(2, '0');

const parse = (value) => {
    if (value === null || value === undefined || value === '') return null;
    if (value instanceof Date) return isNaN(value.getTime()) ? null : value;
    const d = new Date(value);
    return isNaN(d.getTime()) ? null : d;
};

export const formatDate = (value, { withTime = false, dash = '—' } = {}) => {
    const d = parse(value);
    if (!d) return dash;

    const day = pad(d.getDate());
    const month = MONTHS[d.getMonth()];
    const year = d.getFullYear();

    if (!withTime) {
        return `${day} ${month} ${year}`;
    }

    return `${day} ${month} ${year}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

export const formatDateTime = (value, opts = {}) => formatDate(value, { ...opts, withTime: true });

// Convenience for relative-style ("12 May 2026" without zero padding).
export const formatDateLong = (value) => {
    const d = parse(value);
    if (!d) return '—';
    return `${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}`;
};
