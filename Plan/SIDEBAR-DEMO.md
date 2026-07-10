# Sidebar Demo - Visual Walkthrough

## 🎬 Animation Demo (ASCII)

### **1. Normal View (Default 256px)**

```
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║  ┌─────────────────────┬───────────────────────────────────────┐ ║
║  │ ODAF Runtime        │ ☰  Customer List          Studio admin│ ║
║  │ ODAF Demo App       │                                       │ ║
║  ├─────────────────────┤                                       │ ║
║  │                     │                                       │ ║
║  │ ▼ Master Data       │  ┌─────────────────────────────────┐ │ ║
║  │   • Customer        │  │ Customer Table                  │ │ ║
║  │   • Product         │  ├─────┬──────────┬────────┬───────┤ │ ║
║  │                     │  │ Code│ Name     │ Email  │ Type  │ │ ║
║  │ ▼ Transaction       │  ├─────┼──────────┼────────┼───────┤ │ ║
║  │   • Sales Order     │  │C-001│ PT Maju  │ info@..│Retail │ │ ║
║  │   • Purchase Order  │  │C-002│ CV Jaya  │ admin..│Grosir │ │ ║
║  │                     │  │C-003│ UD Makmur│ cs@... │Distri │ │ ║
║  ├─────────────────────┤  └─────┴──────────┴────────┴───────┘ │ ║
║  │ Metadata • F1  256px│                                       │ ║
║  └─────────────────────┴───────────────────────────────────────┘ ║
║      ↑                                                            ║
║      └─ Resize handle di sini (hover untuk lihat)                ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

### **2. Resize Action (Dragging)**

```
╔═══════════════════════════════════════════════════════════════════╗
║  Mouse hovering resize handle...                                  ║
║                                                                   ║
║  ┌─────────────────────┐ ← Highlight!                           ║
║  │ ODAF Runtime        ║                                         ║
║  │ ODAF Demo App       ║  Cursor: ↔                             ║
║  ├─────────────────────║                                         ║
║  │                     ║                                         ║
║  │ ▼ Master Data       ║  💡 Tip: Drag left/right               ║
║  │   • Customer        ║                                         ║
║  │   • Product         ║  Min: 200px                            ║
║  │                     ║  Max: 500px                            ║
║  │ ▼ Transaction       ║                                         ║
║  │   • Sales Order     ║                                         ║
║  └─────────────────────║                                         ║
║            Blue line showing resize handle                        ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

### **3. Narrow Sidebar (200px) - More Content Space**

```
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║  ┌───────────┬─────────────────────────────────────────────────┐ ║
║  │ ODAF      │ ☰  Customer List              Studio       admin│ ║
║  │ Demo      │                                                 │ ║
║  ├───────────┤                                                 │ ║
║  │           │                                                 │ ║
║  │▼Master    │  ┌───────────────────────────────────────────┐ │ ║
║  │ •Customer │  │ Customer Table (More Space!)              │ │ ║
║  │ •Product  │  ├──────┬────────────────┬──────────┬────────┤ │ ║
║  │           │  │ Code │ Name           │ Email    │ Type   │ │ ║
║  │▼Trans.    │  ├──────┼────────────────┼──────────┼────────┤ │ ║
║  │ •Sales    │  │C-001 │ PT Maju Jaya   │ info@... │ Retail │ │ ║
║  │ •Purchase │  │C-002 │ CV Jaya Abadi  │ admin... │ Grosir │ │ ║
║  │           │  │C-003 │ UD Makmur      │ cs@...   │ Distri │ │ ║
║  ├───────────┤  └──────┴────────────────┴──────────┴────────┘ │ ║
║  │ F1   200px│                                                 │ ║
║  └───────────┴─────────────────────────────────────────────────┘ ║
║                                                                   ║
║  ✅ More horizontal space for data!                               ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

### **4. Wide Sidebar (400px) - Full Menu Text**

```
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║  ┌───────────────────────────────────────┬───────────────────────┐ ║
║  │ ODAF Runtime                          │ ☰  Customer List      │ ║
║  │ ODAF Demo Application                 │                       │ ║
║  ├───────────────────────────────────────┤                       │ ║
║  │                                       │                       │ ║
║  │ ▼ Master Data Management              │  ┌─────────────────┐ │ ║
║  │   • Customer Management               │  │ Customer Table  │ │ ║
║  │   • Product Management                │  ├──────┬──────────┤ │ ║
║  │   • Category Management               │  │ Code │ Name     │ │ ║
║  │                                       │  ├──────┼──────────┤ │ ║
║  │ ▼ Transaction Processing              │  │C-001 │ PT Maju  │ │ ║
║  │   • Sales Order Entry                 │  │C-002 │ CV Jaya  │ │ ║
║  │   • Purchase Order Entry              │  └──────┴──────────┘ │ ║
║  │   • Invoice Generation                │                       │ ║
║  │                                       │                       │ ║
║  ├───────────────────────────────────────┤                       │ ║
║  │ Metadata-driven • F1           400px  │                       │ ║
║  └───────────────────────────────────────┴───────────────────────┘ ║
║                                                                   ║
║  ✅ Full menu text visible - no truncation!                       ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

### **5. Collapsing (Hide Animation)**

```
Step 1: Click ☰ icon
╔═══════════════════════════════════════════════════════════════════╗
║  ┌─────────────────────┬───────────────────────────────────────┐ ║
║  │ ODAF Runtime        │ [☰] ← Clicked!                       │ ║
║  │ ODAF Demo App       │                                       │ ║
║  └─────────────────────┴───────────────────────────────────────┘ ║
╚═══════════════════════════════════════════════════════════════════╝

Step 2: Sidebar sliding left (Animation 100ms)
╔═══════════════════════════════════════════════════════════════════╗
║  ┌──────────┬────────────────────────────────────────────────────┐ ║
║  │ ODAF R...│ [☰]                                               │ ║
║  │ ODAF D...│                                                    │ ║
║  └──────────┴────────────────────────────────────────────────────┘ ║
║       ↑ Fading out...                                             ║
╚═══════════════════════════════════════════════════════════════════╝

Step 3: Sidebar hidden, floating button appears
╔═══════════════════════════════════════════════════════════════════╗
║ ┌──┐                                                              ║
║ │▶ │ ← Floating button                                           ║
║ └──┘                                                              ║
║    ┌─────────────────────────────────────────────────────────────┐ ║
║    │ [☰]  Customer List                    Studio          admin│ ║
║    │                                                             │ ║
║    │                                                             │ ║
║    │              FULL WIDTH CONTENT AREA                        │ ║
║    │                                                             │ ║
║    │  ┌────────────────────────────────────────────────────────┐│ ║
║    │  │ Customer Table (Full Width!)                           ││ ║
║    │  ├────────┬────────────────────┬─────────────┬────────────┤│ ║
║    │  │ Code   │ Name               │ Email       │ Type       ││ ║
║    │  ├────────┼────────────────────┼─────────────┼────────────┤│ ║
║    │  │ C-001  │ PT Maju Jaya       │ info@maju.. │ Retail     ││ ║
║    │  │ C-002  │ CV Jaya Abadi      │ admin@jaya..│ Wholesale  ││ ║
║    │  └────────┴────────────────────┴─────────────┴────────────┘│ ║
║    │                                                             │ ║
║    └─────────────────────────────────────────────────────────────┘ ║
║                                                                   ║
║  ✅ Maximum space for content!                                    ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

### **6. Expanding Back (Show Animation)**

```
Step 1: Click ▶ floating button
╔═══════════════════════════════════════════════════════════════════╗
║ ┌──┐                                                              ║
║ │[▶]│ ← Clicked!                                                  ║
║ └──┘                                                              ║
╚═══════════════════════════════════════════════════════════════════╝

Step 2: Sidebar sliding in from left (Animation 200ms)
╔═══════════════════════════════════════════════════════════════════╗
║  ┌──────────┬────────────────────────────────────────────────────┐ ║
║  │ ODAF R...│                                                    │ ║
║  │ ODAF D...│  Sliding in...                                     │ ║
║  └──────────┴────────────────────────────────────────────────────┘ ║
╚═══════════════════════════════════════════════════════════════════╝

Step 3: Sidebar fully visible (Back to saved width)
╔═══════════════════════════════════════════════════════════════════╗
║  ┌─────────────────────┬───────────────────────────────────────┐ ║
║  │ ODAF Runtime        │ [☰]  Customer List              admin │ ║
║  │ ODAF Demo App       │                                       │ ║
║  ├─────────────────────┤                                       │ ║
║  │ ▼ Master Data       │  Content area (adjusted)              │ ║
║  │   • Customer        │                                       │ ║
║  │   • Product         │                                       │ ║
║  └─────────────────────┴───────────────────────────────────────┘ ║
║                                                                   ║
║  ✅ Width restored to last saved value (256px)                    ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

### **7. Scrollable Menu (Long List)**

```
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║  ┌─────────────────────┬───────────────────────────────────────┐ ║
║  │ ODAF Runtime        │ [☰]  Dashboard                        │ ║
║  │ ODAF Demo           │                                       │ ║
║  ├─────────────────────┤                                       │ ║
║  │                     │▲ Scroll up                            │ ║
║  │ ▼ Master Data       │█ ← Custom scrollbar                   │ ║
║  │   • Customer        │█   (thin, themed)                     │ ║
║  │   • Product         │█                                      │ ║
║  │   • Category        │                                       │ ║
║  │   • Supplier        │                                       │ ║
║  │   • Warehouse       │  [Content Area]                       │ ║
║  │                     │                                       │ ║
║  │ ▼ Transaction       │                                       │ ║
║  │   • Sales Order     │                                       │ ║
║  │   • Purchase Order  │                                       │ ║
║  │   • Invoice         │█                                      │ ║
║  │   • Payment         │█ ← Scrollbar                          │ ║
║  │   • Receipt         │▼ Scroll down                          │ ║
║  ├─────────────────────┤                                       │ ║
║  │ F1            256px │                                       │ ║
║  └─────────────────────┴───────────────────────────────────────┘ ║
║                                                                   ║
║  ✅ Long menus automatically scrollable!                          ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

## 🎯 Key Features Demonstrated

1. **Resize** - Drag handle untuk adjust width (200-500px)
2. **Collapse** - Hide sidebar untuk full-width content
3. **Expand** - Show sidebar dengan saved width
4. **Scroll** - Custom scrollbar untuk menu panjang
5. **Persist** - Settings auto-save ke localStorage
6. **Smooth** - Animations untuk professional feel

---

## 💡 Pro Tips

### **Resize Shortcut:**
- **Quick narrow:** Drag ke min (200px) untuk compact view
- **Quick wide:** Drag ke max (500px) untuk full text
- **Reset:** Double-click handle (future) untuk default 256px

### **Toggle Shortcut:**
- Click hamburger icon (☰) in header
- OR click floating arrow (▶) when hidden
- Keyboard: `Ctrl+B` (future enhancement)

### **Workflow Tips:**
1. **Data entry heavy:** Narrow sidebar (200px) → more form space
2. **Navigation heavy:** Wide sidebar (400px) → full menu text
3. **Presentation mode:** Hide sidebar → full screen content
4. **Development:** Wide sidebar → see all menu structure

---

## 🎬 Try It Live!

```
http://localhost:8080/app/ODAF_DEMO
Login: admin / password

1. Hover mouse di tepi kanan sidebar
2. Lihat blue highlight muncul
3. Drag left/right untuk resize
4. Click ☰ untuk hide
5. Refresh page → settings tetap!
```

🎉 **Interactive demo ready!**

