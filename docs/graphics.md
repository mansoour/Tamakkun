# Graphics brief

Every graphic the site can show, with its file name, size and look. The site already works without them: until a file exists, a soft lavender tile with an icon is shown in its place. To add a graphic, save it under the exact path below. It appears on the next page load, with no code change.

## Style (applies to every image)

- **Audience:** Saudi female secondary-school students (Grades 10–12) preparing for the Qudurat and Tahsili exams. Tone: calm, encouraging, modern, studious.
- **Style:** flat 2D vector illustration with soft gradients and gentle shadows, rounded shapes, generous empty space, clean edges. No photorealism, no 3D clay, no clutter.
- **People:** if people appear, they are female students in modest dress: a black abaya and a hijab or headscarf. Use simple, friendly faces or faceless figures. Never show male figures, real people, logos or brand names.
- **Text:** **no text, letters or numbers inside any image** (the site adds Arabic text in HTML). Math symbols such as + − × ÷ √ π, triangles, graphs and charts are fine as decorative shapes.
- **Culture:** neutral Saudi school setting. Books, notebooks, laptop, phone, calendar, trophy, lightbulb, stars and a graduation cap are good props.
- **Background:** transparent (PNG or WebP with alpha) unless stated otherwise.
- **Format:** WebP (preferred) or PNG, sRGB, under 300 KB each where possible.

## Colour palette

| Token | Hex | Use |
|---|---|---|
| brand-900 | `#382A5B` | deepest shade, outlines, dark accents |
| brand-800 | `#4B377A` | dark fills |
| brand-700 | `#5F4699` | primary dark |
| brand-600 | `#7458B5` | **primary** (main purple) |
| brand-500 | `#8A70C6` | mid purple |
| brand-400 | `#9B83D1` | light purple, gradient end |
| brand-300 | `#C9B9E8` | soft lavender |
| brand-100 | `#EFE9F8` | very light lavender fills |
| canvas | `#F7F4FB` | page background |
| ink | `#302B3A` | dark neutral (hair, abaya, outlines) |
| accent (optional, sparingly) | `#F5B841` warm gold, `#5BC0A8` mint | stars, checkmarks, success |

Brand gradient: 135°, from `#7458B5` to `#9B83D1`.

## Files

All illustration paths are relative to `public/images/illustrations/`.

| # | File | Size (px) | Where it shows | Background | Description / prompt |
|---|---|---|---|---|---|
| 1 | `hero.webp` | 1200 × 1000 | Home page, top banner (sits on the purple gradient) | Transparent | A female Saudi student in abaya and hijab sitting cross-legged with a laptop, surrounded by floating study icons (open book, lightbulb, checkmark, small bar chart, math symbols, star). Light colours (white, lavender `#EFE9F8`, gold accents) so it stands out on a purple background. |
| 2 | `path-quantitative.webp` | 800 × 600 | Home, card «القدرات – الكمي» | Transparent or `#F7F4FB` | Quantitative reasoning: geometric shapes (triangle, circle, cube), a ruler, a calculator, a simple graph, floating math symbols. Purple palette. |
| 3 | `path-verbal.webp` | 800 × 600 | Home, card «القدرات – اللفظي» | Transparent or `#F7F4FB` | Verbal reasoning: an open book, speech bubbles, connected word cards (shapes only, no letters), a magnifying glass. Purple palette. |
| 4 | `path-tahsili.webp` | 800 × 600 | Home, card «التحصيلي» | Transparent or `#F7F4FB` | Achievement test (math): a stack of textbooks, a graduation cap, a protractor, a function graph, a beaker as a small accent. Purple palette. |
| 5 | `how-it-works.webp` | 900 × 900 | Home, section «كيف تبدئين؟» | Transparent | A winding path or staircase with four milestones (sign-up card → book → game controller/puzzle piece → trophy), with a small female student figure climbing towards the trophy. |
| 6 | `counselor.webp` | 400 × 400 | Home, card «للموجهة الطلابية» | Transparent | A female school counselor (abaya, hijab) holding a tablet showing a simple progress chart, friendly and supportive. |
| 7 | `supervision.webp` | 400 × 400 | Home, supervision card (on dark purple `#382A5B`) | Transparent | An emblem: a shield or badge with a checkmark and a small star or laurel, in light lavender, white and gold so it reads on a dark purple card. No text. |
| 8 | `auth-side.webp` | 960 × 720 | Login and register pages, purple side panel (desktop) | Transparent | A female student at a desk with a notebook and laptop, a calendar with a marked date and a small target/goal icon. Light colours for a purple background. |

Other images:

| # | Path | Size (px) | Purpose | Notes |
|---|---|---|---|---|
| 9 | `public/images/og-image.png` | 1200 × 630 | Link preview when the site is shared (WhatsApp, X, Telegram) | Solid brand gradient background (`#7458B5` → `#9B83D1`), illustration on one side, empty space on the right for the logo. The image AI should add no text. You can add the platform name «تمكّن» yourself afterwards, in Alexandria ExtraBold. |
| 10 | `public/icons/icon-512.png` | 512 × 512 | App icon (home screen, manifest) | Square, solid `#7458B5` background, simple white mark (graduation cap or an abstract "rising step"), with 20% safe padding (maskable). |
| 11 | `public/icons/icon-192.png` | 192 × 192 | App icon (small) | Same design as #10, scaled down. |
| 12 | `public/icons/apple-touch-icon.png` | 180 × 180 | iPhone home screen | Same design as #10, no transparency. |
| 13 | `public/favicon.ico` | 32 × 32 (+16 × 16) | Browser tab | Same mark as #10, simplified. |

Items 10–13 replace files that already exist. Keep the same names.

## Checklist before adding a file

1. The exact file name and folder from the tables above.
2. The size matches (or the same aspect ratio, at least that large).
3. No text or letters in the image.
4. Transparent background where the table says so.
5. Under ~300 KB (compress at squoosh.app if needed).
