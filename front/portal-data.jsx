// Portal-specific data — university notices, programs, important info

const UNIVERSITY = {
  name: "Sardar Vallabhbhai National College",
  nameHi: "सरदार वल्लभभाई राष्ट्रीय महाविद्यालय",
  short: "SVNC",
  estd: 1956,
  location: "Anand, Gujarat 388120",
  accreditation: "NAAC A++ · NIRF Rank #42",
  ugc: "Recognised u/s 2(f) & 12B of UGC Act, 1956",
};

const NOTICES = {
  Latest: [
    { date: "27", mo: "May", title: "Round-2 Counselling Schedule for Undergraduate Programmes 2026-27", tag: "new", category: "Admissions", file: "PDF · 412 KB" },
    { date: "26", mo: "May", title: "Provisional Merit List — B.Sc. (Hons.) Computer Science published", tag: "new", category: "Admissions", file: "PDF · 1.2 MB" },
    { date: "25", mo: "May", title: "Last Date Extended for Online Application Submission — 31 May 2026", tag: "imp", category: "Notice", file: "PDF · 220 KB" },
    { date: "24", mo: "May", title: "Important: Document verification through DigiLocker — Mandatory from this session", tag: "imp", category: "Notice", file: "PDF · 380 KB" },
    { date: "22", mo: "May", title: "Walk-in Counselling Schedule for Vocational & Skill-Enhancement Programmes", tag: "new", category: "Admissions", file: "PDF · 540 KB" },
    { date: "20", mo: "May", title: "Revised Fee Structure for Hostel & Mess for AY 2026-27", tag: null, category: "Notice", file: "PDF · 290 KB" },
    { date: "18", mo: "May", title: "Constitution of Anti-Ragging Committee for AY 2026-27", tag: null, category: "Notice", file: "PDF · 180 KB" },
    { date: "15", mo: "May", title: "Orientation Programme for Newly Admitted Students — Schedule", tag: null, category: "Notice", file: "PDF · 420 KB" },
  ],
  Admissions: [
    { date: "27", mo: "May", title: "Round-2 Counselling Schedule for Undergraduate Programmes 2026-27", tag: "new", category: "Admissions", file: "PDF · 412 KB" },
    { date: "26", mo: "May", title: "Provisional Merit List — B.Sc. (Hons.) Computer Science published", tag: "new", category: "Admissions", file: "PDF · 1.2 MB" },
    { date: "26", mo: "May", title: "Provisional Merit List — B.A. (Hons.) Economics with Data Science", tag: "new", category: "Admissions", file: "PDF · 980 KB" },
    { date: "25", mo: "May", title: "List of Approved Documents for Verification — Round-2", tag: null, category: "Admissions", file: "PDF · 260 KB" },
    { date: "23", mo: "May", title: "Information Brochure 2026-27 — All Undergraduate Programmes", tag: null, category: "Admissions", file: "PDF · 4.8 MB" },
    { date: "22", mo: "May", title: "Walk-in Counselling Schedule for Vocational & Skill-Enhancement Programmes", tag: "new", category: "Admissions", file: "PDF · 540 KB" },
    { date: "18", mo: "May", title: "Reservation Matrix & Category-wise Seat Distribution", tag: null, category: "Admissions", file: "PDF · 320 KB" },
  ],
  Examination: [
    { date: "26", mo: "May", title: "Date Sheet — Semester End Examinations, Even Sem 2025-26", tag: "new", category: "Examination", file: "PDF · 680 KB" },
    { date: "24", mo: "May", title: "Re-Evaluation Form for Backlog Papers — Last Date 15 Jun", tag: "imp", category: "Examination", file: "PDF · 180 KB" },
    { date: "20", mo: "May", title: "Centre List for Practical Examinations — UG Programmes", tag: null, category: "Examination", file: "PDF · 240 KB" },
    { date: "16", mo: "May", title: "Examination Form Submission — Procedural Notice", tag: null, category: "Examination", file: "PDF · 160 KB" },
  ],
  Recruitment: [
    { date: "25", mo: "May", title: "Recruitment of Assistant Professors (Various Departments) — Advt. No. 14/2026", tag: "new", category: "Recruitment", file: "PDF · 720 KB" },
    { date: "22", mo: "May", title: "Walk-in Interview for Guest Faculty — Mathematics & Statistics", tag: null, category: "Recruitment", file: "PDF · 280 KB" },
    { date: "18", mo: "May", title: "Recruitment of Non-Teaching Staff — Junior Office Assistants", tag: null, category: "Recruitment", file: "PDF · 340 KB" },
  ],
};

const TICKER = [
  { new: true, text: "Round-2 Counselling Schedule for UG Programmes 2026-27 released — 27 May 2026" },
  { new: true, text: "Last date extended to 31 May 2026 for online application submission" },
  { new: false, text: "Document verification through DigiLocker is mandatory from this session" },
  { new: true, text: "Provisional merit list for B.Sc. CS, B.A. Eco-DS published" },
  { new: false, text: "Information Brochure 2026-27 available for download" },
  { new: false, text: "Helpline: 1800-555-0142 · admissions@svnc.ac.in · 9:30 AM to 5:30 PM (Mon-Sat)" },
];

const ADMISSION_SCHEDULE = [
  { event: "Online Application Submission", round: "Round-1", from: "01 Apr 2026", to: "30 Apr 2026", status: "Completed" },
  { event: "Document Verification (DigiLocker)", round: "Round-1", from: "02 Apr 2026", to: "03 May 2026", status: "Completed" },
  { event: "Publication of Provisional Merit List", round: "Round-1", from: "05 May 2026", to: "—", status: "Completed" },
  { event: "Online Application Submission (Round-2)", round: "Round-2", from: "12 May 2026", to: "31 May 2026", status: "Ongoing" },
  { event: "Document Verification & Eligibility Confirmation", round: "Round-2", from: "13 May 2026", to: "02 Jun 2026", status: "Ongoing" },
  { event: "Publication of Provisional Merit List", round: "Round-2", from: "05 Jun 2026", to: "—", status: "Upcoming" },
  { event: "Seat Allocation & Fee Payment", round: "Round-2", from: "08 Jun 2026", to: "15 Jun 2026", status: "Upcoming" },
  { event: "Spot Admission Round (Vacant Seats)", round: "Spot", from: "20 Jun 2026", to: "25 Jun 2026", status: "Upcoming" },
  { event: "Commencement of Academic Session", round: "—", from: "01 Jul 2026", to: "—", status: "Upcoming" },
];

const PROGRAMMES = [
  { code: "UGCS01", name: "B.Sc. (Hons.) Computer Science", dept: "School of Computing", duration: "4 Years", credits: 160, intake: 120, applied: 1842, eligibility: "12th with PCM, Min. 75%", fee: 35000 },
  { code: "UGEC02", name: "B.A. (Hons.) Economics with Data Science", dept: "School of Social Sciences", duration: "4 Years", credits: 160, intake: 90, applied: 1104, eligibility: "12th any stream, Maths mandatory, Min. 70%", fee: 32000 },
  { code: "UGCM03", name: "B.Com. (Hons.) Finance & Accounting", dept: "School of Commerce", duration: "3 Years", credits: 120, intake: 180, applied: 2310, eligibility: "12th with Commerce or any stream, Min. 65%", fee: 28000 },
  { code: "UGBT04", name: "B.Sc. (Hons.) Biotechnology", dept: "School of Life Sciences", duration: "4 Years", credits: 160, intake: 80, applied: 967, eligibility: "12th with Biology/Maths, Min. 70%", fee: 38000 },
  { code: "UGLS05", name: "B.A. Liberal Studies", dept: "School of Humanities", duration: "3 Years", credits: 120, intake: 120, applied: 654, eligibility: "12th any stream, Min. 60%", fee: 26000 },
  { code: "VOCDM06", name: "B.Voc. Digital Media Production", dept: "School of Vocational Studies", duration: "3 Years", credits: 120, intake: 60, applied: 412, eligibility: "12th any stream, Min. 50%", fee: 30000 },
  { code: "UGCH07", name: "B.Sc. (Hons.) Chemistry", dept: "School of Physical Sciences", duration: "4 Years", credits: 160, intake: 90, applied: 612, eligibility: "12th with PCM/B, Min. 72%", fee: 34000 },
  { code: "UGPS08", name: "B.A. (Hons.) Political Science", dept: "School of Social Sciences", duration: "4 Years", credits: 160, intake: 80, applied: 489, eligibility: "12th any stream, Min. 65%", fee: 28000 },
];

const RESERVATION_MATRIX = [
  { cat: "Unreserved (UR)", pct: 40, seats: 48, relax: "—" },
  { cat: "Other Backward Classes (OBC-NCL)", pct: 27, seats: 33, relax: "3%" },
  { cat: "Scheduled Castes (SC)", pct: 15, seats: 18, relax: "5%" },
  { cat: "Scheduled Tribes (ST)", pct: 7.5, seats: 9, relax: "5%" },
  { cat: "Economically Weaker Sections (EWS)", pct: 10, seats: 12, relax: "—" },
  { cat: "Persons with Benchmark Disabilities (PwBD)", pct: 5, seats: 6, relax: "5%" },
  { cat: "Kashmiri Migrants / Wards of Defence", pct: "Supernumerary", seats: 4, relax: "5%" },
];

const QUICK_LINKS = [
  { label: "Information Brochure", icon: "book" },
  { label: "Fee Structure", icon: "money" },
  { label: "Reservation Policy", icon: "list" },
  { label: "Programmes Offered", icon: "cap" },
  { label: "Eligibility Criteria", icon: "check" },
  { label: "Refund Policy", icon: "money" },
  { label: "DigiLocker Setup", icon: "lock" },
  { label: "ABC Account Setup", icon: "id" },
  { label: "Helpdesk", icon: "phone" },
  { label: "Prospectus 2026-27", icon: "doc" },
  { label: "Sample Application", icon: "edit" },
  { label: "Past Year Cut-offs", icon: "chart" },
];

const STUDENT_PROFILE = {
  name: "Aarav Kumar Patel",
  applicationNo: "SVNC/UG/2026/039812",
  abcId: "1234-5678-9012-3456",
  digilocker: "linked",
  email: "aarav.patel@gmail.com",
  mobile: "+91 98245-19987",
  category: "OBC-NCL",
  gender: "Male",
  dob: "12 August 2008",
  aadhaar: "5421 •••• 3214",
  nationality: "Indian",
  religion: "Hindu",
  bloodGroup: "B+",
  state: "Gujarat",
  district: "Ahmedabad",
  pincode: "380015",
  address: "B-204, Sapphire Heights, Off SG Road, Bodakdev, Ahmedabad — 380015",
  fatherName: "Sh. Rajesh Kumar Patel",
  fatherOccupation: "Business",
  fatherIncome: "₹ 6,80,000 per annum",
  motherName: "Smt. Meena R. Patel",
  motherOccupation: "Homemaker",
  guardianMobile: "+91 98244-19988",
  emergencyContact: "+91 98244-19988",
  board12: "Gujarat Secondary & Higher Secondary Education Board (GSHSEB)",
  board12Year: "2026",
  board12Stream: "Science (PCM with Computer Science)",
  board12Pct: 88.4,
  board12School: "Delhi Public School, Bopal, Ahmedabad",
  board12Roll: "A12-8843-21",
  board10: "Central Board of Secondary Education (CBSE)",
  board10Year: "2024",
  board10Pct: 92.6,
  programmeApplied: "B.Sc. (Hons.) Computer Science",
  programmeCode: "UGCS01",
  programmePreference: "1st",
  minorPreference: "Mathematics",
};

const APP_TABLE = [
  { sno: 1, no: "SVNC/UG/2026/039812", name: "Aarav Kumar Patel", prog: "UGCS01 — B.Sc. CS", cat: "OBC-NCL", pct: 88.40, gender: "M", state: "Gujarat", docs: "8/8", fee: "Paid", status: "Verified" },
  { sno: 2, no: "SVNC/UG/2026/039811", name: "Diya Krishnan", prog: "UGEC02 — B.A. Eco-DS", cat: "UR", pct: 94.20, gender: "F", state: "Kerala", docs: "8/8", fee: "Paid", status: "Verified" },
  { sno: 3, no: "SVNC/UG/2026/039810", name: "Rohan Mehta", prog: "UGCM03 — B.Com.", cat: "UR", pct: 91.00, gender: "M", state: "Maharashtra", docs: "6/8", fee: "Paid", status: "Pending" },
  { sno: 4, no: "SVNC/UG/2026/039809", name: "Sanvi Reddy", prog: "UGBT04 — B.Sc. Biotech", cat: "SC", pct: 86.70, gender: "F", state: "Telangana", docs: "7/8", fee: "Paid", status: "Submitted" },
  { sno: 5, no: "SVNC/UG/2026/039808", name: "Vivaan Iyer", prog: "UGCS01 — B.Sc. CS", cat: "EWS", pct: 89.50, gender: "M", state: "Tamil Nadu", docs: "8/8", fee: "Paid", status: "Verified" },
  { sno: 6, no: "SVNC/UG/2026/039807", name: "Ananya Sharma", prog: "UGLS05 — B.A. Lib.S", cat: "UR", pct: 78.20, gender: "F", state: "Rajasthan", docs: "5/8", fee: "Unpaid", status: "Draft" },
  { sno: 7, no: "SVNC/UG/2026/039806", name: "Kabir Singh", prog: "VOCDM06 — B.Voc. DM", cat: "OBC-NCL", pct: 72.80, gender: "M", state: "Punjab", docs: "8/8", fee: "Paid", status: "Verified" },
  { sno: 8, no: "SVNC/UG/2026/039805", name: "Meera Joshi", prog: "UGCS01 — B.Sc. CS", cat: "UR", pct: 92.60, gender: "F", state: "Gujarat", docs: "8/8", fee: "Refund", status: "Rejected" },
  { sno: 9, no: "SVNC/UG/2026/039804", name: "Aryan Kapoor", prog: "UGCM03 — B.Com.", cat: "OBC-NCL", pct: 85.30, gender: "M", state: "Delhi", docs: "8/8", fee: "Paid", status: "Verified" },
  { sno: 10, no: "SVNC/UG/2026/039803", name: "Ishita Banerjee", prog: "UGEC02 — B.A. Eco-DS", cat: "SC", pct: 81.40, gender: "F", state: "West Bengal", docs: "6/8", fee: "Paid", status: "Pending" },
  { sno: 11, no: "SVNC/UG/2026/039802", name: "Aditya Rao", prog: "UGBT04 — B.Sc. Biotech", cat: "UR", pct: 88.00, gender: "M", state: "Karnataka", docs: "8/8", fee: "Paid", status: "Verified" },
  { sno: 12, no: "SVNC/UG/2026/039801", name: "Tara Nair", prog: "UGLS05 — B.A. Lib.S", cat: "ST", pct: 79.80, gender: "F", state: "Kerala", docs: "7/8", fee: "Paid", status: "Submitted" },
];

const FEE_HEADS = [
  { head: "Application & Processing Fee", paid: 1500, due: 0, status: "Paid", challan: "CHN-2026-22841", date: "12-05-2026" },
  { head: "Admission Confirmation Fee", paid: 5000, due: 0, status: "Paid", challan: "CHN-2026-22847", date: "20-05-2026" },
  { head: "Tuition Fee — Sem-1", paid: 0, due: 17500, status: "Due", challan: "—", date: "30-06-2026" },
  { head: "Examination & Library Fee — Sem-1", paid: 0, due: 4200, status: "Due", challan: "—", date: "30-06-2026" },
  { head: "Caution Money (Refundable)", paid: 0, due: 5000, status: "Due", challan: "—", date: "30-06-2026" },
  { head: "Identity Card / Smart Card", paid: 0, due: 350, status: "Due", challan: "—", date: "30-06-2026" },
  { head: "Hostel Fee (Optional)", paid: 0, due: 18000, status: "Optional", challan: "—", date: "30-06-2026" },
  { head: "Mess Fee (Optional)", paid: 0, due: 13500, status: "Optional", challan: "—", date: "30-06-2026" },
];

const MERIT_LIST = [
  { rank: 1, no: "SVNC/UG/2026/039811", name: "Diya Krishnan", cat: "UR", marks: 94.20, prog: "UGCS01", tiebreak: "Math: 99", status: "Allocated" },
  { rank: 2, no: "SVNC/UG/2026/039805", name: "Meera Joshi", cat: "UR", marks: 92.60, prog: "UGCS01", tiebreak: "Math: 98", status: "Withdrawn" },
  { rank: 3, no: "SVNC/UG/2026/039810", name: "Rohan Mehta", cat: "UR", marks: 91.00, prog: "UGCS01", tiebreak: "Math: 96", status: "Allocated" },
  { rank: 4, no: "SVNC/UG/2026/039808", name: "Vivaan Iyer", cat: "EWS", marks: 89.50, prog: "UGCS01", tiebreak: "Math: 95", status: "Allocated" },
  { rank: 5, no: "SVNC/UG/2026/039812", name: "Aarav Kumar Patel", cat: "OBC-NCL", marks: 88.40, prog: "UGCS01", tiebreak: "Math: 92", status: "Allocated" },
  { rank: 6, no: "SVNC/UG/2026/039802", name: "Aditya Rao", cat: "UR", marks: 88.00, prog: "UGCS01", tiebreak: "Math: 91", status: "Allocated" },
  { rank: 7, no: "SVNC/UG/2026/039809", name: "Sanvi Reddy", cat: "SC", marks: 86.70, prog: "UGCS01", tiebreak: "Math: 90", status: "Waitlist" },
  { rank: 8, no: "SVNC/UG/2026/039804", name: "Aryan Kapoor", cat: "OBC-NCL", marks: 85.30, prog: "UGCS01", tiebreak: "Math: 88", status: "Waitlist" },
  { rank: 9, no: "SVNC/UG/2026/039803", name: "Ishita Banerjee", cat: "SC", marks: 81.40, prog: "UGCS01", tiebreak: "Math: 84", status: "Waitlist" },
  { rank: 10, no: "SVNC/UG/2026/039801", name: "Tara Nair", cat: "ST", marks: 79.80, prog: "UGCS01", tiebreak: "Math: 82", status: "Waitlist" },
];

const DOC_LIST = [
  { name: "10th (Secondary) Marksheet & Certificate", required: true, status: "Verified", file: "10th-cert.pdf", size: "1.2 MB", digilocker: true },
  { name: "12th (Higher Secondary) Marksheet & Certificate", required: true, status: "Verified", file: "12th-cert.pdf", size: "1.4 MB", digilocker: true },
  { name: "Aadhaar Card", required: true, status: "Verified", file: "aadhaar.pdf", size: "880 KB", digilocker: true },
  { name: "Passport Size Photograph (Recent)", required: true, status: "Verified", file: "photo.jpg", size: "210 KB" },
  { name: "Signature (Scanned)", required: true, status: "Verified", file: "signature.jpg", size: "98 KB" },
  { name: "Category / Caste Certificate (OBC-NCL)", required: true, status: "Pending", file: "obc-ncl.pdf", size: "1.1 MB" },
  { name: "Income Certificate (For EWS / Scholarship)", required: false, status: "Not Uploaded", file: null },
  { name: "Migration Certificate (Non-Gujarat Boards)", required: false, status: "Not Uploaded", file: null },
  { name: "Domicile Certificate", required: false, status: "Not Uploaded", file: null },
];

Object.assign(window, {
  UNIVERSITY, NOTICES, TICKER, ADMISSION_SCHEDULE, PROGRAMMES, RESERVATION_MATRIX,
  QUICK_LINKS, STUDENT_PROFILE, APP_TABLE, FEE_HEADS, MERIT_LIST, DOC_LIST,
});
