# Content sources and copyright

Tamakkun is a **follow-up and organisation platform, not a republishing platform**.

## Rules

- **Link only to official, public or authorised sources.** Never copy paid or proprietary content, re-host paid videos, scrape protected course areas or mirror books and PDFs.
- **Never fabricate URLs.** A source or link URL is entered only after checking it on the provider's official site. Until then, it stays empty.
- **No implied partnership.** Listing a source (for example المعاصر) only identifies where a resource comes from. Content pages show a short disclaimer.
- **Embed only when allowed.** Videos are embedded only from whitelisted players (YouTube via `youtube-nocookie.com`, and Vimeo). Admins paste a normal video URL, and iframe HTML is never accepted. CSP `frame-src` allows only those players.
- **Verify ETEC links before production.** `important_links` starts empty; the admin adds official links after verifying them and ticks **مصدر رسمي** only for official bodies.

## Seeded structure (migration `2026_10_06_000002`)

These records come from the stakeholder brief. Admins can rename, reorder or hide them, and the migration never overwrites edits.

| Type | Records |
|---|---|
| Sources (URL = NULL) | المعاصر · المنصف · المفكر · هيئة تقويم التعليم والتدريب |
| Quantitative categories | الأعداد، الكسور، النسب، النسبة المئوية، النسب والتناسب، المتوسط، الأسس والجذور، المعادلات، السرعة والزمن والمسافة، العمل والإنجاز، الاحتمالات، الإحصاء، الهندسة، المساحات، المحيط، الزوايا، المسائل اللفظية الكمية |
| Verbal categories | استيعاب المقروء، التناظر اللفظي، إكمال الجمل، الخطأ السياقي، الارتباط والاختلاف، المفردات، العلاقات بين الكلمات |
| Tahsili subjects | الرياضيات، الفيزياء، الكيمياء، الأحياء |

## Intended progression (quantitative)

`تأسيس → تدريب → إتقان → مراجعة` (`ContentStage`).

The brief's preferred mapping is المعاصر → تأسيس, المنصف → تدريب, and المفكر → إتقان / مراجعة / extra practice. This mapping is applied **per content item** by the admin (each item has a source and a stage), not hard-coded.

Do not claim that المعاصر, المنصف or المفكر provide verbal material unless that has been verified.

## Open items

- The official URL for **المفكر** was not confirmed in the brief, so it stays empty.
- Current official ETEC service links (registration, results, individual services) must be verified live before production.

## Imported القدرات content (migration `2026_10_13_000001`)

The team's own Google Site [العب وتدرب قدرات وتحصيلي](https://sites.google.com/view/play-training-qudrat-tahsyle) is the source of the real Qudurat content. Every item links to where it already lives (YouTube, Wordwall, Quizalize, Google/Microsoft Forms, Google Drive). Nothing is re-hosted on Tamakkun.

- **Data file:** `database/data/qudurat-content.php`, one row per item (slug, section, category, title, description, type, stage, URL, order). Slugs are `qudurat-q-NNN` (كمي) and `qudurat-v-NNN` (لفظي).
- **Placement:** each item is in the category it is about. For example, geometry lessons, games and tests are all under الهندسة. Items that mix several skills go to «نماذج وتجميعات محلولة», «اختبارات شاملة ومحاكية» or «مراجع وتجميعات». Course activities stay with the lesson they follow.
- **Order inside a category:** course and summary videos, then games, then e-tests, then files, each in the site's own order.
- **Types and stages:** YouTube → مقطع فيديو, embedded (تأسيس, or تدريب for game and activity solutions). Games and activities → تدريب, opened in a new tab. E-tests → تدريب type at stage إتقان. Drive files (PDFs and two MP4s that Drive does not allow us to embed) → رابط خارجي at stage مراجعة (the MP4s at تأسيس).
- **Not imported:** tiles on the site that have no link yet (for example حل هندسة 11–15 and حل كمي 17–20, still being recorded), and «هندسة 7» in the course, which points to the same video as «هندسة 6».
- **Tests:** the import is skipped while unit tests run, so feature tests keep their own fixtures. `QuduratContentImportTest` runs it explicitly.

To add items the site gains later, add them in the admin area. To re-import from the site, extend the data file with new slugs and add a new migration; never change an existing slug.

## Imported التحصيلي – الرياضيات content (migration `2026_10_14_000001`)

From the التحصيلي pages of the same Google Site. Rows are in `database/data/tahsili-math-content.php` (slugs `tahsili-math-NNN`), under the subject الرياضيات.

- **Chapters:** the 24 curriculum chapters, named «<grade> – الفصل <n>: <name>» (أول، ثاني، ثالث ثانوي), then تجميعات تحصيلي 1447هـ, تجميعات تحصيلي 1446هـ, اختبارات إلكترونية شاملة, مبادرة مهارات التعامل مع أسئلة التحصيلي (جسم) and مراجع وتجميعات. The site left ثالث ثانوي's sixth chapter untitled. It is named «الإحداثيات القطبية والأعداد المركبة» after its lessons. The site labels ثاني ثانوي's fifth chapter «الفصل الرابع» twice. It is numbered correctly here.
- **Inside a curriculum chapter:** the «دورة فن التحصيلي» topic (course lessons, activities and activity solutions for that chapter), then one topic per lesson (its game, then its solution video), then «اختبار إلكتروني» for ثالث ثانوي chapters. ثاني and أول ثانوي e-tests cover the whole grade, so they sit in «اختبارات إلكترونية شاملة».
- **Stages:** course lessons and جسم videos → تأسيس. Games, solutions, activities and models → تدريب. E-tests → إتقان. Files → مراجعة.
- **Not imported:** tiles with no link, and links the site reuses for a different item. The 1447 solutions for models 9–12 reuse the videos of 7 and 8. 1446 «حل نموذج 1» is the video of model 15. «حل الدراسات المسحية» is the التحليل الإحصائي video. The «التبرير الاستنتاجي» game is the same quiz as «العبارات الشرطية». «دورة منصة تميز» is week 2 of جسم.
- **Files:** most PDFs under مراجع وتجميعات are full Tahsili packs (all four subjects), not math only.

## Free sample tests (migration `2026_10_15_000001`)

Twelve free sample tests (Google Forms) listed on a test-preparation store's public page. The store sells courses and is **not** a partner, so it is not recorded as a source and its own pages are not linked. Only the free form links are. Rows are in `database/data/sample-tests-content.php`.

- Placement follows each form's own title, not the store's label: «اختبار كمي (2)–(5)» are geometry tests 1–4 and sit under الهندسة. «اختبار كمي (1)» is a general test under «اختبارات شاملة ومحاكية». The verbal tests go to their skill category.
- The two placement tests («اختبار تحديد المستوى») open «اختبارات شاملة ومحاكية» in each section. The others follow the category's last e-test.
- Several of these forms ask for a name and a phone number. The external-link warning (below) covers this.

## External-link warning

Every place that sends a student to another website shows `<x-external-link-notice>`: the content page above «فتح المصدر», the «روابط مهمة» page and the public resources page. It tells students not to enter real personal data (phone, ID, address, passwords), to use a dummy phone number such as 0500000000 if one is required, and that a first name is enough. On the link lists it adds that sites marked «مصدر رسمي» (for example قياس) need real details. Embedded YouTube/Vimeo videos do not show it, because they ask for nothing.

## تجميعات المنصف 1500 سؤال (migration `2026_10_17_000001`)

The free PDF edition on Google Drive (its file title reads «المنصف 1500 (مجاني)»), linked as the first item of القدرات الكمي ← مراجع وتجميعات, with the seeded source المنصف.

## Not imported: «تأسيس أينشتاين» playlist re-upload

The YouTube playlist «تاسيس قدرات اينشتاين من الصفر» (57 lectures) is uploaded by the channel «قدراتي مهاراتي», not by أينشتاين. Its descriptions also point students to "leaked" courses on Telegram. Under the rule "never re-host or link re-uploads of paid courses" above, it is left out until the course is available from أينشتاين's own channel or with the owner's permission.
