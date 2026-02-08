Feature	Old VBS	Current Status	Details
Duplicate detection	ParentOuEmailExiste()	❓ Not found	Checking if parent (by name+email) already received gifts in 2023/2024
Time slot allocation	GénérerCréneaux()	❓ Not found	Auto-allocating parents to 15-min time slots (10 people per slot)
Gift pickup emails	EnvoyerEmailsRetraitCadeaux()	⚠️ Partial	Emails with specific pickup times/dates not found
Zone/Gift area signs	CreerAffichesZonesCadeaux()	❓ Not found	Creating numbered zone signs (e.g., "1–10" with gift list)
Address PDF	GenererAdressesPDF()	❓ Not found	Standalone PDF of parent addresses for verification


# Missing Features from Old VBS Macro

This document lists all features and mechanics from the old Excel VBS macro system that are **not yet implemented** in the new Laravel application.

---

## 🔴 Critical Missing Features

### 1. **Duplicate Detection System** (Historical Comparison)

**Purpose:** Prevent families from receiving gifts multiple times across seasons.

**Old Implementation:** `ParentOuEmailExiste()` function
- Compared parent names + email against historical data (2023, 2024 sheets)
- If parent received gifts in **both** 2023 **and** 2024, marked as excluded
- Exclusion reason: "reçu déjà 2x" (already received 2x)

**Current Status:** ❌ NOT IMPLEMENTED

**Required Data:**
- Access to previous season data (2023, 2024, etc.)
- Ability to compare: `first_name + last_name` or `email`
- Flag/status: `exclusion_reason` or similar

**Todo:**
- [ ] Create migration to store exclusion status per family/season
- [ ] Build duplicate detection logic comparing against previous seasons
- [ ] Add field: `families.excluded_reason` (nullable)
- [ ] Create admin command/interface to mark families as excluded

---

### 2. **Gift Pickup Time Slot System**

**Purpose:** Allocate families to specific time slots for gift pickup with automatic load balancing.

**Old Implementation:** `EnvoyerEmailsRetraitCadeaux()` + `GénérerCréneaux()` functions

**Mechanics:**
- Generate 15-minute time slots between start and end times
- Example: Saturday 14:00-18:00 → slots at 14:00, 14:15, 14:30... 18:00
- Allocate **max 10 families per time slot**
- Auto-assign next family to next available slot
- Create Outlook draft emails with assigned time slot

**Parameters:**

**Current Status:** ❌ NOT IMPLEMENTED

**Required Database Changes:**
- [ ] Create `pickup_slots` table with fields:
  - `id`, `season_id`, `slot_date`, `slot_start_time`, `slot_end_time`, `capacity`, `current_count`
- [ ] Add to `families` table:
  - `pickup_slot_id` (nullable, foreign key to pickup_slots)
- [ ] Add to `seasons` table:
  - `pickup_start_date`, `pickup_end_date`
  - `pickup_address`, `pickup_venue`

**Todo:**
- [ ] Create models: `PickupSlot`
- [ ] Build slot generation logic (time-based)
- [ ] Implement slot assignment algorithm (round-robin)
- [ ] Create admin interface to:
  - Configure pickup dates/times
  - View slot capacity
  - Manually reassign families
- [ ] Add family view to show assigned time slot

---

### 3. **Gift Pickup Notification Email**

**Purpose:** Send families their assigned time slot for gift pickup.

**Old Implementation:** Email template in `EnvoyerEmailsRetraitCadeaux()`

**Template Content:**

**Variables:**
- `[nom]` - Family first + last name
- `[date]` - Pickup date (e.g., "samedi 20 décembre")
- `[heure]` - Time range (e.g., "14h00 et 14h15")

**Current Status:** ⚠️ PARTIAL (Basic email system exists, but not integrated with time slots)

**Todo:**
- [ ] Create `GiftPickupNotificationMail` class
- [ ] Integrate with time slot assignment
- [ ] Make email template configurable (admin settings)
- [ ] Add bulk email sending functionality

---

### 4. **Gift Zone/Area Signs (Affiches)**

**Purpose:** Create printed signs for organizing gifts into numbered zones during event.

**Old Implementation:** `CreerAffichesZonesCadeaux()` function

**Mechanics:**
- Group gifts into zones of 10 gifts per page
- Generate zone number range (e.g., "1–10", "11–20")
- List all gifts in zone with format: `{ID} : {GIFT_WISH}`
- Output as Word document for printing
- Large title text (100pt), smaller content (25pt)

**Example Output:**

**Current Status:** ❌ NOT IMPLEMENTED

**Required Data:**
- Gift ID/identifier
- Gift wish/description
- Sequential ordering

**Todo:**
- [ ] Create `ZoneSignsExport` or similar service
- [ ] Implement PDF generation (using Barryvdh or similar)
- [ ] Add admin interface to generate zone signs
- [ ] Make zone size (10 gifts) configurable
- [ ] Implement sorting/grouping logic

---

### 5. **Address Verification PDF**

**Purpose:** Generate PDF listing all parent addresses for verification before gift pickup event.

**Old Implementation:** `GenererAdressesPDF()` function

**Content per Family:**

**Format:**
- A4 portrait
- 2cm margins on all sides
- One page per family
- One family per page

**Current Status:** ❌ NOT IMPLEMENTED

**Required Data:**
- Family first/last name
- Address
- Postal code
- City

**Todo:**
- [ ] Create `AddressVerificationExport` or similar service
- [ ] Implement PDF generation with proper formatting
- [ ] Add admin interface to generate/download PDF
- [ ] Verify all required address fields exist in database

---

### 6. **Email Preview in Word Document**

**Purpose:** Preview all gift pickup emails before sending.

**Old Implementation:** `PrévisualiserEmailsDansWord()` function

**Features:**
- Generate Word document with all emails
- Shows: Family ID + Full email content
- One email per page with page breaks
- Also populates journal with email metadata

**Current Status:** ❌ NOT IMPLEMENTED

**Todo:**
- [ ] Create `EmailPreviewExport` service
- [ ] Add admin interface to preview emails before sending
- [ ] Implement bulk preview generation

---

## 📊 Data Structure Issues

### Missing Database Fields

| Feature | Table | Missing Field(s) | Type | Notes |
|---------|-------|-----------------|------|-------|
| Duplicate Detection | `families` | `excluded_reason` | string/nullable | Reason for exclusion |
| Duplicate Detection | `families` | `excluded_at` | timestamp/nullable | When marked as excluded |
| Time Slots | `families` | `pickup_slot_id` | foreignId | Link to assigned slot |
| Time Slots | `seasons` | `pickup_start_date` | date | When pickup begins |
| Time Slots | `seasons` | `pickup_end_date` | date | When pickup ends |
| Time Slots | `seasons` | `pickup_address` | text/nullable | Pickup venue address |
| Time Slots | `seasons` | `pickup_venue` | text/nullable | Pickup venue name |
| Anonymity | `children` | `anonymous` | boolean | Already exists? |

---

## 📋 Summary of Missing Subroutines/Functions

| VBS Function | Purpose | Status |
|--------------|---------|--------|
| `RemplirCartes()` | Restructure data + duplicate check + PDF generation | ⚠️ Partial |
| `ParentOuEmailExiste()` | Duplicate detection | ❌ Missing |
| `GenererAdressesPDF()` | Address verification PDF | ❌ Missing |
| `EnvoyerEmailsRetraitCadeaux()` | Time slot assignment + email creation | ⚠️ Partial |
| `GénérerCréneaux()` | Time slot generation | ❌ Missing |
| `PrévisualiserEmailsDansWord()` | Email preview in Word | ❌ Missing |
| `CreerAffichesZonesCadeaux()` | Zone signs generation | ❌ Missing |

---

## 🎯 Implementation Priority

### Phase 1 (High Priority)
1. **Time Slot System** - Core functionality for gift pickup
2. **Gift Pickup Email** - Notify families of pickup times
3. **Duplicate Detection** - Prevent repeated gift distribution

### Phase 2 (Medium Priority)
1. **Zone Signs (Affiches)** - Organize event logistics
2. **Address PDF** - Verification before event

### Phase 3 (Low Priority)
1. **Email Preview** - Nice-to-have before bulk sending

---

## 📝 Notes

- All features were hardcoded for **December 2024** dates in old system
- New system should make dates/times **configurable per season**
- Consider moving from Outlook draft creation to proper email queue system
- PDF generation should use modern PHP libraries (DOMPDF, Barryvdh) instead of Word interop
