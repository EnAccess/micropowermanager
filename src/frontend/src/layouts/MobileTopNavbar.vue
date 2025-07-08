<template>
  <div>
    <div class="md-layout md-gutter md-size-100" style="background: #424040">
      <div class="md-layout-item md-size-15">
        <md-button
          class="md-icon-button"
          @click="showSidebar"
          v-if="!mobileSidebarVisible"
        >
          <md-icon style="color: white">menu</md-icon>
        </md-button>
        <md-button
          class="md-icon-button"
          @click="hideSidebar"
          v-if="mobileSidebarVisible"
        >
          <md-icon style="color: white">keyboard_arrow_left</md-icon>
        </md-button>
      </div>
      <div class="md-layout-item md-size-85">
        <div class="md-layout-item md-layout md-gutter nav-menu">
          <md-menu md-direction="bottom-end" md-size="medium" class="menu-item">
            <md-button class="md-dense nav-button" md-menu-trigger>
              <md-icon class="c-white">add_location_alt</md-icon>
              <small>{{ $tc("words.location", 2) }}</small>
            </md-button>
            <md-menu-content>
              <md-menu-item disabled>
                <span class="mobile-sub-menu-text">
                  {{ $tc("words.location", 2) }}
                </span>
                <md-icon>add_location_alt</md-icon>
              </md-menu-item>
              <md-divider></md-divider>
              <md-menu-item @click="replaceRoute('/locations/add-cluster')">
                <span class="mobile-sub-menu-text">
                  {{ $tc("menu.subMenu.addCluster") }}
                </span>
              </md-menu-item>
              <md-menu-item @click="replaceRoute('/locations/add-mini-grid')">
                <span class="mobile-sub-menu-text">
                  {{ $tc("menu.subMenu.addMiniGrid") }}
                </span>
              </md-menu-item>
              <md-menu-item @click="replaceRoute('/locations/add-village')">
                <span class="mobile-sub-menu-text">
                  {{ $tc("menu.subMenu.addVillage") }}
                </span>
              </md-menu-item>
            </md-menu-content>
          </md-menu>
          <md-menu class="menu-item" md-direction="bottom-end" md-size="medium">
            <md-button class="md-dense nav-button" md-menu-trigger>
              <md-icon class="c-white">settings</md-icon>
              <small class="mobile-menu-text">
                {{ $tc("menu.subMenu.Settings") }}
              </small>
            </md-button>
            <md-menu-content>
              <md-menu-item disabled>
                <span>{{ $tc("menu.subMenu.Settings") }}</span>
                <md-icon>settings</md-icon>
              </md-menu-item>
              <md-divider></md-divider>
              <md-menu-item @click="replaceRoute('/settings/configuration')">
                <span class="mobile-sub-menu-text">
                  {{ $tc("menu.subMenu.Config") }}
                </span>
              </md-menu-item>
              <md-menu-item @click="replaceRoute('/settings/user-management')">
                <span class="mobile-sub-menu-text">
                  {{ $tc("phrases.userManagement") }}
                </span>
              </md-menu-item>
              <md-menu-item disabled="">
                <span class="mobile-sub-menu-text">
                  {{ $tc("words.connection") }}
                </span>
                <md-icon>cast</md-icon>
              </md-menu-item>
              <md-menu-item
                @click="replaceRoute('/settings/connection-groups')"
              >
                <span class="mobile-sub-menu-text">
                  {{ $tc("words.group", 2) }}
                </span>
              </md-menu-item>
              <md-menu-item @click="replaceRoute('/settings/connection-types')">
                <span class="mobile-sub-menu-text">
                  {{ $tc("words.type", 2) }}
                </span>
              </md-menu-item>
            </md-menu-content>
          </md-menu>
          <md-menu class="menu-item" md-direction="bottom-end" md-size="medium">
            <md-button class="nav-button md-dense" md-menu-trigger>
              <md-icon class="c-white">person</md-icon>
              <small class="mobile-menu-text">
                {{ adminName }}
              </small>
              <md-icon class="c-white">keyboard_arrow_down</md-icon>
            </md-button>
            <md-menu-content>
              <div class="author-card">
                <div class="md-layout">
                  <md-icon class="md-size-2x">account_circle</md-icon>
                </div>
                <div class="md-layout md-alignment-center">
                  <span class="mobile-sub-menu-text">
                    {{ adminName }}
                  </span>
                </div>
                <hr />
              </div>
              <md-menu-item
                class="mobile-sub-menu-text"
                @click="replaceRoute('/profile')"
              >
                <span class="mobile-sub-menu-text">
                  {{ $tc("words.profile") }}
                </span>
              </md-menu-item>
              <md-menu-item @click="logout()">
                <span class="mobile-sub-menu-text">Log Out</span>
              </md-menu-item>
            </md-menu-content>
          </md-menu>
        </div>
      </div>
    </div>
    <div class="md-layout md-gutter md-size-100">
      <breadcrumb />
    </div>
  </div>
</template>

<script>
import Breadcrumb from "../shared/Breadcrumb"

export default {
  name: "MobileTopNavBar",
  components: { Breadcrumb },
  data() {
    return {
      open: false,
      mobileSidebarVisible: false,
    }
  },
  watch: {
    $route() {
      this.hideSidebar()
    },
  },
  methods: {
    logout() {
      this.$store.dispatch("auth/logOut").then(() => {
        this.$router.replace("/login")
      })
    },
    showSidebar() {
      this.mobileSidebarVisible = true
      this.$sidebar.displaySidebar(true)
    },
    hideSidebar() {
      this.mobileSidebarVisible = false
      this.$sidebar.displaySidebar(false)
    },
    replaceRoute(route) {
      this.$router.replace(route)
    },
  },
  computed: {
    adminName() {
      return this.$store.getters["auth/getAuthenticateUser"].name
    },
  },
}
</script>

<style scoped>
@media screen and (max-width: 500px) {
  .mobile-menu-text {
    display: none;
  }
}

.nav-menu {
  right: 0;
  float: right;
}

.mobile-sub-menu-text {
  font-size: smaller;
}
</style>
