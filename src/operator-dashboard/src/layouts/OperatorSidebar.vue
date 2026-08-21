<template>
  <aside class="sidebar">
    <div class="sidebar__brand">
      <div class="sidebar__logo">
        <img :src="logo" alt="MicroPowerManager" />
      </div>
      <div class="sidebar__brand-text">
        <div class="sidebar__brand-name">MPM Operations</div>
        <div class="sidebar__brand-meta">Powered by MPM</div>
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

    <div class="sidebar__footer">
      <span class="material-icons sidebar__icon">account_circle</span>
      <span>{{ $tc("words.operator") }}</span>
    </div>
  </aside>
</template>

<script>
import logo from "@/assets/images/mpm-logo.png"

export default {
  name: "OperatorSidebar",
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
.sidebar {
  width: 230px;
  flex: none;
  background: $ops-shell-sidebar;
  position: sticky;
  top: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: auto;
  box-shadow: 2px 0 12px rgba(0, 0, 0, 0.25);
}

.sidebar__brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 20px 18px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar__logo {
  width: 38px;
  height: 38px;
  flex: none;
  border-radius: 5px;
  background: $brand-white;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5px;

  img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }
}

.sidebar__brand-text {
  line-height: 1.3;
}

.sidebar__brand-name {
  color: $brand-white;
  font-weight: 400;
  font-size: 14.5px;
}

.sidebar__brand-meta {
  color: $ops-shell-text-faint;
  font-size: 11px;
  font-weight: 300;
}

.sidebar__nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 14px 10px;
}

.sidebar__link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: $ops-radius-control;
  cursor: pointer;
  font-size: 13.5px;
  color: $brand-white;

  &:hover {
    background: rgba(200, 200, 200, 0.2);
    color: $brand-white;
  }

  &.active {
    background: $brand-primary;
    box-shadow: 0 4px 12px rgba(27, 117, 186, 0.35);
  }
}

.sidebar__icon {
  font-size: 20px;
}

.sidebar__pill {
  margin-left: auto;
  background: rgba(255, 255, 255, 0.14);
  color: #e0e0e0;
  font-size: 11px;
  font-weight: 500;
  padding: 1px 8px;
  border-radius: $ops-radius-pill;
}

.sidebar__footer {
  margin-top: auto;
  padding: 14px 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  gap: 10px;
  color: #c9c9c9;
  font-size: 12.5px;
  font-weight: 300;
}
</style>
