# OSA System — Design System

Derived from OSA's existing branded social graphics (navy/gold folder-file motif). Built for Blade + Tailwind CSS.

---

## 1. Color Palette

| Token | Hex | Usage |
|---|---|---|
| `navy-900` | `#0B1930` | Darkest background, header/hero panels |
| `navy-800` | `#142A4D` | Gradient mid-stop, dark card backgrounds |
| `navy-700` | `#1B3868` | Gradient top-stop (lighter blue) |
| `slate-500` | `#5C77A6` | Folder-tab accent, secondary borders/icons |
| `slate-200` | `#C9D6EA` | Muted text on navy backgrounds |
| `gold-500` | `#D4AF37` | Accent ring, badges, highlights — use sparingly |
| `gold-700` | `#7A5C14` | Text-on-gold-tint (badges/pills) |
| `paper` | `#FFFFFF` | Card/document surfaces |
| `paper-muted` | `#F4F6FA` | Page background, subtle card fill |
| `ink-900` | `#16233F` | Body text on white/paper surfaces |

Status colors (Tailwind defaults are fine — keep semantic, not brand):
- Success: `green-600` / `green-50`
- Warning / physical-pending: `amber-600` / `amber-50`
- Danger / revision needed: `red-600` / `red-50`
- Info / in review: `blue-600` / `blue-50`

### Tailwind config extension

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        navy: {
          900: '#0B1930',
          800: '#142A4D',
          700: '#1B3868',
        },
        slate: {
          500: '#5C77A6',
          200: '#C9D6EA',
        },
        gold: {
          500: '#D4AF37',
          700: '#7A5C14',
        },
        paper: {
          DEFAULT: '#FFFFFF',
          muted: '#F4F6FA',
        },
        ink: {
          900: '#16233F',
        },
      },
      fontFamily: {
        sans: ['Inter', 'Poppins', 'ui-sans-serif', 'system-ui'],
        display: ['Montserrat', 'ui-sans-serif'],
        wordmark: ['"Playfair Display"', 'serif'],
      },
      backgroundImage: {
        'navy-gradient': 'linear-gradient(180deg, #1B3868 0%, #0B1930 100%)',
      },
    },
  },
};
```

Load `Inter` and `Montserrat` (and `Playfair Display` only if the official wordmark/logo is reproduced) via `fonts.googleapis.com` in the Blade layout `<head>`.

---

## 2. Typography

| Role | Font | Weight/style | Usage |
|---|---|---|---|
| Wordmark | Playfair Display, italic | 400 italic | Official "Ateneo de Zamboanga University" lockup only — not used in app UI, reserved for print exports (PDF letterhead) |
| Display / headline | Montserrat | 800–900, uppercase, tight tracking | Page heroes, empty states, print headers — sparingly, not every page title |
| Eyebrow label | Montserrat | 600, uppercase, letter-spacing 0.05em, 11–12px | Small tags like status badges, section kickers |
| File reference | Inter or Playfair italic | italic, 12–13px, gold-500 | Reference/tracking codes (e.g. request reference numbers), echoing "File no. 04" |
| UI heading (h1–h3) | Inter | 500–600 | Standard page/section headings |
| Body | Inter | 400, `text-sm`/`text-base` | Default UI copy |

Sentence case for all standard UI copy and buttons; reserve uppercase display styling for hero banners and print-style headers only — don't uppercase every heading, or the branded feel becomes noise instead of accent.

---

## 3. Core Components (Blade + Tailwind recipes)

### 3.1 App header / hero panel
Navy gradient background, gold ring logo mark, used on dashboard headers and print-style summary pages.

```html
<div class="bg-navy-gradient rounded-xl px-6 py-5 text-white">
  <div class="flex items-center gap-2 mb-3">
    <span class="w-7 h-7 rounded-full border-2 border-gold-500 flex items-center justify-center">
      <x-icon name="feather" class="w-3.5 h-3.5 text-gold-500" />
    </span>
    <span class="text-sm font-medium">Office of Student Affairs</span>
  </div>
  <p class="text-xs italic text-gold-500 tracking-wide mb-1">Ref. {{ $request->reference_code }}</p>
  <h1 class="text-xl font-extrabold uppercase tracking-wide">{{ $request->title }}</h1>
</div>
```

### 3.2 File / document card
The paperclip-and-folder-tab motif maps directly onto document/checklist items — reuse it as the primary "document card" component across the Document Checklist & Upload Center.

```html
<div class="relative pt-3.5">
  <div class="absolute top-0 left-4 w-16 h-4 bg-slate-500 rounded-t-md"></div>
  <div class="relative bg-paper border border-slate-200 rounded-md p-4">
    <div class="flex justify-between items-start">
      <div>
        <p class="text-sm font-medium text-ink-900">{{ $document->label }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ $document->is_physical ? 'Physical' : 'Digital' }}</p>
      </div>
      <x-icon name="paperclip" class="w-4 h-4 text-slate-500 rotate-12" />
    </div>
    <x-status-pill :status="$document->status" />
  </div>
</div>
```

### 3.3 Status pill
Maps directly to `CHECKLIST_ITEMS.status` / `ACTIVITY_REQUESTS.status` from the Architecture doc.

```html
@php
  $map = [
    'verified'  => 'bg-green-50 text-green-700',
    'submitted' => 'bg-blue-50 text-blue-700',
    'pending'   => 'bg-amber-50 text-amber-700',
    'physical'  => 'bg-amber-50 text-amber-700',
    'denied'    => 'bg-red-50 text-red-700',
  ];
@endphp
<span class="inline-block mt-2 text-[11px] font-medium px-2.5 py-1 rounded-full {{ $map[$status] }}">
  {{ $label }}
</span>
```

### 3.4 Eyebrow badge (gold outline)
For light accents — academic year tags, small callouts. Use gold outline, not gold fill, to keep it an accent rather than a dominant color.

```html
<span class="text-[11px] font-semibold uppercase tracking-wide px-3 py-1 rounded-full border border-gold-500 text-gold-700">
  AY {{ $academicYear }}
</span>
```

### 3.5 Buttons
- Primary: `bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium`
- Secondary: `bg-transparent border border-slate-500 text-navy-900 hover:bg-paper-muted rounded-lg px-4 py-2 text-sm font-medium`
- Gold is never used as a button fill — reserved for accents/badges only, matching how it's used sparingly (ring, small text) in the source graphics.

---

## 4. Application Guidance

- **Dashboards** (Org, Moderator, OSA Admin, OSA Director from the Architecture doc) use `paper-muted` page background, white cards, navy headers only for section heroes — not on every panel, to avoid visual heaviness.
- **Document Checklist & Upload Center** (Architecture §5.2) is the natural home for the file-card component (§3.2) — each `CHECKLIST_ITEMS` row renders as one card, physical items get the amber "physical" pill instead of an upload button.
- **Print/PDF exports** (reference slips, OSA Form 3 printouts, `PdfExportService` from the Architecture doc) are the only place the italic wordmark font and full navy-gradient hero should appear full-bleed, mirroring the original social graphics most closely.
- Keep gold strictly as an accent (rings, small text, thin borders) — never a large fill — so it reads as premium/official rather than loud.
