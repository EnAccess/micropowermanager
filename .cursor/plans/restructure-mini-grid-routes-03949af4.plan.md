<!-- 03949af4-ca3a-4720-b787-615eb4092417 5ecdb199-cae5-46c6-91d9-1d52781cedc3 -->
# Restructure Mini-Grid Routes to Match Agents Pattern

## Overview

Restructure Mini-Grid routes to match the Agents pattern with a top-level sidebar item and expandable submenu, moving from `/dashboards/mini-grid` to `/mini-grids`.

## Changes

### 1. Restructure Routes in `ExportedRoutes.js`

- **Remove** the existing Mini-Grid route at `dashboards/mini-grid` (lines 346-386)
- **Add** a new top-level route matching the Agents pattern:
  - Top-level route: `path: ""` with `sidebar.enabled: true, name: "Mini-Grid", icon: "bolt"`
  - Child route: `path: "mini-grids"` with `sidebar.enabled: true, name: "Overview"`
  - Overview page: `path: ""` under mini-grids (uses `MiniGridOverviewPage`)
  - Detail page: `path: ":id"` under mini-grids (uses `MiniGridDetailPage`)
  - Update breadcrumb links from `/dashboards/mini-grid` to `/mini-grids`

### 2. Update Navigation Paths

Update all route references from `/dashboards/mini-grid` to `/mini-grids`:

- **`src/frontend/src/pages/Dashboard/MiniGrid/index.vue`** (line 29)
  - Change: `/dashboards/mini-grid/` → `/mini-grids/`

- **`src/frontend/src/modules/MiniGrid/Dashboard.vue`** (line 421)
  - Change: `/dashboards/mini-grid/` → `/mini-grids/`

- **`src/frontend/src/modules/Village/AddVillage.vue`** (line 303)
  - Change: `/dashboards/mini-grid/${id}` → `/mini-grids/${id}`

- **`src/frontend/src/modules/Map/DashboardMap.vue`** (line 128)
  - Change: `/dashboards/mini-grid` → `/mini-grids`

- **`src/frontend/src/modules/Map/ClusterMap.vue`** (line 208)
  - Change: `/dashboards/mini-grid` → `/mini-grids`

## Route Structure Comparison

**Before:**

```
Dashboards (sidebar)
  └── Mini-Grid → /dashboards/mini-grid
      ├── Overview → /dashboards/mini-grid
      └── Detail → /dashboards/mini-grid/:id
```

**After (matching Agents):**

```
Mini-Grid (sidebar, top-level)
  └── Overview → /mini-grids
      ├── Overview → /mini-grids
      └── Detail → /mini-grids/:id
```

### To-dos

- [ ] Restructure Mini-Grid routes in ExportedRoutes.js: remove old route, add top-level route matching Agents pattern
- [ ] Update route in pages/Dashboard/MiniGrid/index.vue from /dashboards/mini-grid/ to /mini-grids/
- [ ] Update route in modules/MiniGrid/Dashboard.vue from /dashboards/mini-grid/ to /mini-grids/
- [ ] Update redirect path in modules/Village/AddVillage.vue from /dashboards/mini-grid/ to /mini-grids/
- [ ] Update route reference in modules/Map/DashboardMap.vue from /dashboards/mini-grid to /mini-grids
- [ ] Update route reference in modules/Map/ClusterMap.vue from /dashboards/mini-grid to /mini-grids