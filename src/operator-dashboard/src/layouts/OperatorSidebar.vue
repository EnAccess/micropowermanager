<template>
  <aside class="sidebar" :class="{ 'sidebar--open': open }">
    <div class="sidebar__brand">
      <img :src="logo" alt="MicroPowerManager" class="sidebar__logo" />
      <div class="sidebar__brand-name">
        MPM Operations
        <br />
        <small>Powered by MPM</small>
      </div>
    </div>

    <nav class="sidebar__nav">
      <router-link :to="{ name: 'overview' }" class="sidebar__link" exact>
        <span class="material-icons sidebar__icon">dashboard</span>
        <span>{{ $tc("words.overview") }}</span>
      </router-link>
      <router-link :to="{ name: 'tenants' }" class="sidebar__link">
        <span class="material-icons sidebar__icon">supervisor_account</span>
        <span>{{ $tc("words.tenants") }}</span>
        <span class="sidebar__pill tabular">{{ tenantsTotal }}</span>
      </router-link>
    </nav>
  </aside>
</template>

<script>
import logo from "@/assets/images/mpm-logo.png"

export default {
  name: "OperatorSidebar",
  props: {
    open: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return { logo }
  },
  computed: {
    tenantsTotal() {
      return this.$store.getters["operatorDashboard/tenantsTotal"]
    },
  },
}
</script>

<style lang="scss" scoped>
// Widths follow src/frontend/src/layouts/Default.vue.
.sidebar {
  width: 200px;
  flex: none;
  background: $ops-shell-sidebar;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow: auto;
  z-index: 30;
  box-shadow: $ops-shadow-sidebar;
}

// Below desktop the sidebar is an off-canvas drawer, as in src/frontend.
@media screen and (max-width: #{$ops-breakpoint-desktop - 1px}) {
  .sidebar {
    position: fixed;
    left: 0;
    width: 230px;
    transform: translateX(-100%);
    transition: transform 0.33s;
    box-shadow: none;
  }

  .sidebar--open {
    transform: translateX(0);
    box-shadow: $ops-shadow-sidebar;
  }
}

@media screen and (min-width: $ops-breakpoint-desktop) {
  .sidebar {
    width: 8%;
    min-width: 200px;
  }
}

@media screen and (min-width: 1370px) {
  .sidebar {
    width: 10%;
    min-width: 230px;
  }
}

@media screen and (min-width: 1800px) {
  .sidebar {
    width: 15%;
    min-width: 260px;
  }
}

.sidebar__brand {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 0 15px;
  padding: 15px 0 15px 1rem;
  border-bottom: 1px solid rgba(180, 180, 180, 0.3);
}

.sidebar__logo {
  max-width: 64px;
  max-height: 64px;
}

.sidebar__brand-name {
  color: $brand-white;
  font-weight: bold;
  line-height: 1.5em;
}

.sidebar__nav {
  display: flex;
  flex-direction: column;
  padding: 10px;
}

.sidebar__link {
  display: flex;
  align-items: center;
  margin-left: 5px;
  padding: 10px 15px;
  border-radius: $ops-radius-control;
  color: $brand-white;
  font-size: 0.8rem;
  font-weight: 400;

  &:hover {
    background: rgba(200, 200, 200, 0.2);
    color: $brand-white;
  }

  &.active {
    background: $ops-shell-sidebar-active;
    border-right: 5px solid $brand-primary-dark;
  }
}

.sidebar__icon {
  width: 25px;
  margin-right: 10px;
  font-size: 24px;
}

.sidebar__pill {
  margin-left: auto;
  padding: 0 8px;
  border-radius: $ops-radius-chip;
  background: rgba(255, 255, 255, 0.14);
  font-size: $font-caption;
}
</style>
