# F3 - ODAF Studio Visual Designer

**Status:** 🚧 In Progress  
**Phase:** Design & Planning  
**Target:** M5 Milestone

---

## 🎯 Goal

Transform ODAF Studio from a **generic table editor** (current) into a **visual metadata designer** that provides:

- Intuitive UI for creating applications, forms, and fields
- Visual form builder (drag-drop fields)
- Workflow designer (visual state machine)
- LOV designer with preview
- Real-time preview of generated forms
- No-code / low-code experience

---

## 📊 Current State (Studio Data Manager)

### ✅ **What We Have:**
- Generic CRUD for all tables (TableIntrospector + TableDataManager)
- FK dropdowns (auto-detected)
- Enum/flag dropdowns (CHECK constraints)
- Compile & Activate button
- Admin-only access

### ❌ **What's Missing (F3 Requirements):**
- **Visual form builder** - Drag-drop fields, live preview
- **Application wizard** - Step-by-step app creation
- **Field property editor** - Visual widget for field settings
- **LOV designer** - Create LOV with preview
- **Workflow designer** - Visual state machine editor
- **Layout designer** - Column layout, tabs, sections
- **Validation designer** - Visual rule builder
- **Permission designer** - Matrix view for RBAC

---

## 🗺️ F3 Feature Map

### **Priority 1: Form Designer** (Core UX improvement)

**Goal:** Replace table editing with visual form builder

**Features:**
1. **Application Dashboard**
   - Card-based app list
   - Quick stats (forms, fields, users)
   - Recent activity

2. **Form Builder Canvas**
   - Drag-drop fields from palette
   - Live preview (split view)
   - Field reordering
   - Property panel (right sidebar)

3. **Field Property Editor**
   - Visual widget selection (dropdown, not text)
   - LOV assignment (dropdown with preview)
   - Validation rules (checkboxes + inputs)
   - Layout settings (width, order, visibility)

4. **Field Palette**
   - Field types: Text, Number, Date, Email, LOV, File, etc.
   - Drag to canvas
   - Pre-configured templates (Name, Email, Phone)

---

### **Priority 2: LOV Designer** (High value)

**Goal:** Visual LOV creation with preview

**Features:**
1. **LOV Type Selector**
   - Cards for STATIC / SQL / VIEW
   - Wizard-style flow

2. **STATIC LOV Editor**
   - Key-value grid (inline edit)
   - Add/remove rows
   - Import from CSV
   - Preview dropdown

3. **SQL LOV Editor**
   - SQL editor with syntax highlight
   - Query tester (run query, show results)
   - Column mapper (value/label)
   - Preview dropdown

4. **Parametric LOV**
   - Parameter field selector
   - Dependency visualizer
   - Test with sample values

---

### **Priority 3: Workflow Designer** (Visual state machine)

**Goal:** Visual workflow builder (not table editor)

**Features:**
1. **State Canvas**
   - Drag-drop states
   - Connect with transitions
   - Visual flow (like draw.io)

2. **Transition Editor**
   - Action name
   - Guard conditions
   - Role permissions
   - Notification triggers

3. **Preview & Test**
   - Simulate workflow
   - Show current state
   - Test transitions

---

### **Priority 4: Permission Designer** (RBAC Matrix)

**Goal:** Visual permission assignment

**Features:**
1. **Matrix View**
   - Rows: Roles
   - Columns: Permissions (by module/form)
   - Checkboxes for quick assign

2. **Role Editor**
   - Create/edit roles
   - User assignment
   - Permission groups

---

## 🏗️ Technical Architecture

### **New Components:**

```
/studio
  /designer
    - ApplicationDashboard.php (Livewire)
    - FormBuilder.php (Livewire)
    - FieldEditor.php (Livewire)
    - LovDesigner.php (Livewire)
    - WorkflowDesigner.php (Livewire)
    - PermissionMatrix.php (Livewire)
  /services
    - FormBuilderService.php
    - LovBuilderService.php
    - WorkflowBuilderService.php
```

### **Frontend Stack:**

- **Livewire 3** - For reactive components
- **Alpine.js** - For drag-drop & interactions
- **Sortable.js** - For field reordering
- **CodeMirror** - For SQL editor
- **Tailwind CSS** - For styling
- **Heroicons** - For icons

---

## 📋 Implementation Phases

### **Phase 3.1: Application Dashboard** (Week 1)

**Deliverables:**
- Card-based app list
- Quick actions (Edit, Compile, Deploy)
- Stats dashboard
- Recent changes log

**Routes:**
```
/studio/designer           → ApplicationDashboard
/studio/designer/app/{id}  → App Overview
```

---

### **Phase 3.2: Form Builder** (Week 2-3)

**Deliverables:**
- Visual form canvas
- Drag-drop field palette
- Live preview
- Field property editor
- Save to metadata

**Routes:**
```
/studio/designer/form/{pageId}  → FormBuilder
```

**UI Mockup:**
```
┌────────────────────────────────────────────────────────────┐
│ Form Builder: Customer Form                     [Save] [▶] │
├─────────────┬──────────────────────────┬────────────────────┤
│ Field Types │ Canvas                   │ Properties         │
├─────────────┤                          │                    │
│ □ Text      │  ┌─ Customer Form ─────┐│ Field: Email       │
│ □ Number    │  │ [Kode Customer]     ││ ├─ Label           │
│ □ Email     │  │ [Nama Customer]     ││ │  Email Address   │
│ □ Date      │  │ [Email]         ←───┼┤ ├─ Type            │
│ □ LOV       │  │ [Batas Kredit]      ││ │  EMAIL           │
│ □ Checkbox  │  │ [Tipe] ▼            ││ ├─ Required        │
│ □ File      │  │                     ││ │  ☑ Yes           │
│             │  └─────────────────────┘│ ├─ Validation      │
│             │                          │ │  [+ Add Rule]    │
│             │                          │ └─ LOV             │
│             │                          │    [ -- none -- ] ▼│
└─────────────┴──────────────────────────┴────────────────────┘
```

---

### **Phase 3.3: LOV Designer** (Week 4)

**Deliverables:**
- Visual LOV type selector
- STATIC LOV grid editor
- SQL LOV editor with tester
- Preview panel

**Routes:**
```
/studio/designer/lov       → LOV List
/studio/designer/lov/new   → LOV Designer
/studio/designer/lov/{id}  → Edit LOV
```

**UI Mockup:**
```
┌────────────────────────────────────────────────────────────┐
│ LOV Designer: Customer Type                      [Save]    │
├──────────────────────────────────────────────────────┬─────┤
│ Type: ◉ STATIC  ○ SQL  ○ VIEW                       │     │
├──────────────────────────────────────────────────────┤     │
│ Values:                                              │ Pre-│
│ ┌────────┬──────────────┬────┐                      │ view│
│ │ Value  │ Label        │ ×  │                      │     │
│ ├────────┼──────────────┼────┤                      │ ┌───┤
│ │ RETAIL │ Retail       │ ×  │                      │ │[Rt│
│ │ WHOLES │ Wholesale    │ ×  │                      │ │[Wh│
│ │ DISTR  │ Distributor  │ ×  │                      │ │[Di│
│ │ AGENT  │ Agent        │ ×  │                      │ │[Ag│
│ └────────┴──────────────┴────┘                      │ └───┤
│ [+ Add Row]  [Import CSV]                           │     │
└──────────────────────────────────────────────────────┴─────┘
```

---

### **Phase 3.4: Workflow Designer** (Week 5-6)

**Deliverables:**
- Visual state canvas
- Drag-drop states
- Transition editor
- Flow simulation

**Routes:**
```
/studio/designer/workflow     → Workflow List
/studio/designer/workflow/new → Workflow Designer
/studio/designer/workflow/{id}→ Edit Workflow
```

**UI Mockup:**
```
┌────────────────────────────────────────────────────────────┐
│ Workflow Designer: Customer Approval            [Save]     │
├────────────────────────────────────────────────────────────┤
│ Canvas:                                                    │
│                                                            │
│    ┌─────────┐                                            │
│    │ DRAFT   │───SUBMIT──→┌──────────────┐               │
│    └─────────┘            │PENDING_APPR  │               │
│                           └──────────────┘               │
│                             │         │                   │
│                          APPROVE   REJECT                 │
│                             │         │                   │
│                             ↓         ↓                   │
│                        ┌────────┐ ┌──────┐               │
│                        │APPROVED│ │DRAFT │               │
│                        └────────┘ └──────┘               │
│                                                            │
│ Selected: SUBMIT transition                                │
│ ├─ From: DRAFT                                            │
│ ├─ To: PENDING_APPROVAL                                   │
│ ├─ Roles: [ ] ADMIN [✓] USER                             │
│ └─ Notify: [✓] Send notification                         │
└────────────────────────────────────────────────────────────┘
```

---

## 🎨 UI/UX Principles

### **1. Progressive Disclosure**
- Simple for common tasks
- Advanced features hidden until needed
- Wizards for complex flows

### **2. Immediate Feedback**
- Live preview
- Validation on blur
- Success/error toasts

### **3. Consistency**
- Same patterns across designers
- Predictable interactions
- Clear visual hierarchy

### **4. Escape Hatches**
- "View as JSON" for power users
- "Edit in table mode" fallback
- Export/import for version control

---

## 🔄 Migration Strategy

### **Coexistence:**
- Studio Data Manager (current) remains as "Advanced Mode"
- Visual Designer (new) becomes default
- Toggle between modes: "Switch to Table Editor"

### **Data:**
- No schema changes needed
- Same metadata tables
- Same compilation process

### **User Training:**
- In-app tutorial (first time)
- Tooltips & help text
- Video demos

---

## 🎯 Success Metrics

### **Adoption:**
- % users using Visual Designer vs Table Editor
- Time to create new form (before/after)
- Errors during metadata authoring (before/after)

### **Quality:**
- Metadata validation errors (should decrease)
- Compilation failures (should decrease)
- Support tickets (should decrease)

### **Productivity:**
- Forms created per day
- Average time per form
- User satisfaction (survey)

---

## 🚀 Quick Start (Phase 3.1 - This Week)

**Goal:** Build Application Dashboard as entry point

**Tasks:**
1. Create `ApplicationDashboard` Livewire component
2. Show app list as cards (not table)
3. Add quick stats per app
4. Add "Create New App" wizard
5. Link to Form Builder (stub for now)

**Success Criteria:**
- Dashboard loads < 1s
- Apps displayed as cards
- Click app → navigate to Form Builder (placeholder)
- "Create New App" wizard works

---

## 📚 References

### **Inspiration:**
- **Retool** - Form builder
- **Airtable** - Field types & LOV
- **n8n** - Workflow designer
- **OutSystems** - Low-code platform
- **Directus** - Metadata-driven CMS

### **Technical:**
- Livewire docs: https://livewire.laravel.com
- Sortable.js: https://sortablejs.github.io/Sortable/
- CodeMirror: https://codemirror.net/

---

## ✅ Next Actions

**This Session:**
1. ✅ Create F3 plan document
2. 🚧 Create ApplicationDashboard component
3. 🚧 Design card-based UI
4. 🚧 Add app stats
5. 🚧 Wire up navigation

**This Week:**
- Complete Phase 3.1 (Dashboard)
- Design Form Builder mockup
- Prototype drag-drop interaction

**This Month:**
- Complete Form Builder (Phase 3.2)
- Complete LOV Designer (Phase 3.3)
- Beta test with real users

---

**Let's start with Phase 3.1: Application Dashboard!** 🚀

