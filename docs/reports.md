# Reports

Counselors open **التقارير** (reports) at `/counselor/reports` (their assigned students); admins at `/admin/reports` (all students). Both need `reports.view`. Every report can be viewed on screen or exported as **PDF** or **CSV**, all from the same data (`App\Services\ReportService`).

| Report | Key | Contents |
|---|---|---|
| تقدّم الطالبات | `student-progress` | Name, code, class, completion %, last activity, follow-up status, open alerts |
| تقدّم الفصول | `class-progress` | Per class: students, average completion, booked, inactive, average best Qudurat |
| درجات القدرات | `qudurat-scores` | Latest, best, target, gap and improvement per student |
| درجات التحصيلي | `tahsili-scores` | The same for Tahsili |
| التحسّن | `improvement` | Previous → latest result per exam, sorted by improvement |
| الاختبارات القادمة | `upcoming-exams` | Booked upcoming exams, soonest first |
| لم يحجزن | `not-booked` | Students missing a booking, and for which exam |
| غير النشطات | `inactive` | No learning activity for `inactivity_days` |
| إنجاز المحتوى | `content-completion` | Per visible content item: how many students completed it, and the % |
| المشاركة في التحدي | `challenge-participation` | Challenge answers in the last 30 days: total, correct, % correct |

All figures come from the same services as the dashboards (`StudentRosterService`, `StudentProgressService`, `ExamProgressService`), so reports always match what is on screen.

## PDF

- Rendered with **mPDF** (`App\Services\PdfRenderer`), landscape A4, **RTL**.
- Font: **IBM Plex Sans Arabic**, bundled in `resources/fonts`. It was converted losslessly from IBM's official `@ibm/plex-sans-arabic` package, is licensed under the SIL OFL 1.1 (see `resources/fonts/OFL-IBMPlexSansArabic.txt`), and is configured in `config/reports.php`. PDFs **never** load Bunny Fonts or any remote font.
- mPDF writes temporary files to `storage/app/mpdf-temp`, which must be writable by the site user.
- Each PDF footer marks the report as confidential.

## CSV

- UTF-8 **with BOM**, so Excel shows Arabic correctly.
- Cells that start with `=`, `+`, `-`, `@`, tab or CR are prefixed with `'` to prevent spreadsheet formula injection. Signed numbers such as `+11` are left as they are.

## Audit

Every export writes `report.exported` to `audit_logs` (report key, format and row count). Report contents are never stored.

## Future

Reports are generated within the request; for a school-sized roster this takes well under a second. If a deployment grows to thousands of students, move PDF generation to a queued job that emails a link.
