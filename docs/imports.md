# Student import (CSV)

Admins with the `students.import` permission open **استيراد الطالبات** (`/admin/imports/students`).

## Workflow

1. **Upload** a CSV file (up to 2 MB and 500 rows).
2. **Validate:** every row is checked, and nothing is saved yet.
3. **Preview:** if any row has an error, every error is listed by row number (row 2 is the first data row) and the file **cannot be confirmed**. Fix the file and upload it again.
4. **Confirm:** possible only when every row is valid.
5. **Import:** the accounts are created by the queued job `App\Jobs\ImportStudents` in **one database transaction**, so either every row is imported or none is. It runs on the queue because hashing hundreds of passwords is too slow for a web request.
6. **Summary:** the import page shows `اكتمل الاستيراد` (completed) with the count, or `فشل الاستيراد` (failed) with the reason. Refresh the page while it says `جارٍ الاستيراد` (importing).

The import does **not** create schools, grades, classrooms or counselors. They must exist first.

## Columns

Headers may be in English or Arabic. A downloadable template is linked on the page.

| Column | Arabic header | Required | Notes |
|---|---|---|---|
| `student_code` | رقم الطالبة | ✅ | Latin letters, digits, `.` `-` `_`, max 32. Unique. **Not** the national ID. |
| `name` | الاسم | ✅ | |
| `school` | المدرسة | ✅ | Exact school name as stored in Tamakkun |
| `grade` | الصف | with `classroom` | Grade name in the school's **current** academic year |
| `classroom` | الفصل | optional | Classroom name inside that grade |
| `counselor` | الموجهة | optional | Counselor's username or email. Must belong to the same school. |
| `username` | اسم المستخدم | optional | Defaults to `student_code`. Unique. |
| `initial_password` | كلمة المرور | ✅ | Minimum 8 characters |

## File format

- UTF-8 is preferred. In Excel, use **Save As → CSV UTF-8**. The UTF-8 BOM that Excel adds is handled.
- Windows-1256 files (Arabic Windows "CSV") are converted automatically.
- Commas, semicolons and tabs are all detected as delimiters.

## Security and privacy

- The validated rows, including initial passwords, are stored **encrypted** (`encrypted:array` cast) in `student_imports.payload` between preview and import. They are **deleted** as soon as the import completes or fails.
- Passwords are never written to logs or audit entries.
- Before importing, uniqueness is checked again. If someone created a matching account after the preview, the whole import fails and nothing is saved.
- Each created account produces `user.created`, `user.status_changed` and `student.created` audit entries. Confirming the import adds `students.import_confirmed`.
- Imported accounts are created **active**. Students should change their password after first login (a forced change is planned).
