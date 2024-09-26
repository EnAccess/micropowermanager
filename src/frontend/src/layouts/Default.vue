<template>
  <div>
    <div class="wrapper" :class="{ 'nav-open': $sidebar.showSidebar }">
      <side-bar />
      <div class="main-panel">
        <top-navbar class="top-nav-bar"></top-navbar>
        <mobile-top-navbar class="mobile-top-nav-bar"></mobile-top-navbar>
        <div class="content">
          <slot />
        </div>
        <footer-bar />
      </div>
    </div>

    <md-dialog
      :md-active.sync="active"
      :md-close-on-esc="false"
      :md-click-outside-to-close="false"
    >
      <md-dialog-title>
        {{ $tc("phrases.expireSession") }}
      </md-dialog-title>
      <md-dialog-content>
        {{
          $tc("phrases.expireSessionLabel", 2, {
            expires_in: expires_in,
          })
        }}
        <br />
        {{ $tc("phrases.expireSessionLabel", 1) }}
      </md-dialog-content>

      <md-dialog-actions>
        <md-button
          class="md-primary md-raised"
          @click="extendToken"
          :disabled="confirmed"
        >
          {{ $tc("words.confirm") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
    <tail-wizard :show-wizard="showWizard" :tail="tail" />
    <password-protection />
  </div>
</template>
<script>
import FooterBar from "../layouts/FooterBar.vue"
import { EventBus } from "@/shared/eventbus"
import TopNavbar from "./TopNavbar.vue"
import SideBar from "@/modules/Sidebar/SideBar"
import MobileTopNavbar from "./MobileTopNavbar"
import TailWizard from "@/shared/TailWizard"
import { mapGetters } from "vuex"
import PasswordProtection from "@/shared/PasswordProtection"

export default {
  name: "default",
  components: {
    PasswordProtection,
    TopNavbar,
    FooterBar,
    SideBar,
    MobileTopNavbar,
    TailWizard,
  },
  created() {
    if (this.status !== "") {
      const tail = JSON.parse(this.registrationTail.tail)
      for (const tailElement of tail) {
        if (tailElement.adjusted === false && !this.isWizardShown) {
          this.showWizard = true
        }
      }
      this.tail = tail.filter((x) => x.adjusted === false && x.tag !== null)
    }
  },
  mounted() {
    //register the time extender
    EventBus.$on("ask.for.extend", this.showExtender)
    EventBus.$on("session.end", this.logout)
  },
  data: () => ({
    active: false,
    showed: false,
    confirmed: false,
    expires_in: null,
    sidebarBackground: "green",
    sidebarBackgroundImage: null,
    showWizard: false,
    tail: [],
  }),
  methods: {
    showExtender(val) {
      this.expires_in = val
      if (this.showed === true) {
        return
      }
      this.showed = true
      this.active = true
    },
    extendToken() {
      this.confirmed = true
      location.reload()
    },
    logout() {
      this.$store.commit("registrationTail/SET_IS_WIZARD_SHOWN", false)
      this.$store.dispatch("auth/logOut").then(() => {
        this.$router.replace("/login")
      })
    },
  },
  computed: {
    ...mapGetters({
      status: "auth/getStatus",
      registrationTail: "registrationTail/getTail",
      isWizardShown: "registrationTail/getIsWizardShown",
    }),
  },
}
</script>

<style lang="css" scoped>
.container {
  padding: 1rem;
}

@media screen and (min-width: 992px) {
  .sidebar {
    width: 8%;
    min-width: 200px;
  }

  .main-panel {
    width: 92%;
    max-width: calc(100% - 200px);
  }
}

@media screen and (min-width: 1370px) {
  .sidebar {
    width: 10%;
    min-width: 230px;
  }

  .main-panel {
    width: 90%;
    max-width: calc(100% - 230px);
  }
}

@media screen and (min-width: 1800px) {
  .sidebar {
    width: 15%;
    min-width: 260px;
  }

  .main-panel {
    width: 85%;
    max-width: calc(100% - 260px);
  }
}

@media screen and (max-width: 991px) {
  .top-nav-bar {
    display: none;
  }
}

@media screen and (min-width: 992px) {
  .mobile-top-nav-bar {
    display: none;
  }
}
</style>
