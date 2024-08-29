<template>
  <header
    class="site-header header-s1 is-sticky"
    style="position: fixed; width: 100%; z-index: 10"
  >
    <div class="navbar navbar-primary">
      <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" routerLink="/">
          <img
            class="logo"
            alt="logo"
            src="https://www.inensus.com/images/logo.png"
          />
        </a>

        <!-- MainNav -->
        <nav
          class="navbar-collapse collapse"
          id="mainnav"
          style="margin-top: 0.5rem"
        >
          <ul class="navbar-settings-list">
            <li>
              <md-menu
                class="settings-menu"
                md-size="medium"
                :md-offset-x="120"
                :md-offset-y="-30"
                :md-active.sync="toggleCard"
              >
                <md-button class="btn-menu md-raised" md-menu-trigger>
                  <md-icon style="color: white">settings</md-icon>
                </md-button>
                <md-menu-content class="menu-content m-pad">
                  <md-menu-item class="menu-item">
                    <router-link :to="{ path: '/profile' }" class="link">
                      <md-icon>person</md-icon>
                      {{ $tc("words.profile") }}
                    </router-link>
                  </md-menu-item>
                  <md-menu-item>
                    <router-link
                      :to="{
                        path: '/profile/management',
                      }"
                      class="link"
                    >
                      <md-icon>people</md-icon>
                      {{ $tc("phrases.userManagement") }}
                    </router-link>
                  </md-menu-item>
                </md-menu-content>
              </md-menu>
            </li>
            <li>
              <button class="btn-close md-raised" @click="logout()">
                <md-icon style="color: white">exit_to_app</md-icon>
              </button>
            </li>
          </ul>
        </nav>
        <!-- #end MainNav -->
      </div>
    </div>
  </header>
</template>

<script>
export default {
  data() {
    return {
      open: false,
      toggleCard: false,
    }
  },
  methods: {
    logout() {
      this.$router.replace("/login")
    },
    toggle() {
      this.toggleCard = !this.toggleCard
    },
  },
}
</script>

<style lang="css" scoped>
.site-header .navbar {
  padding: 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
  background-color: #3d3c3f;
}

.site-header .navbar .container .navbar-brand {
  margin-left: 1rem;
  background-color: #3d3c3f;
}

.site-header .navbar-brand {
  height: auto;
  padding: 0;
}

.site-header .navbar-brand img {
  padding-top: 0.1rem;
  width: auto;
  max-width: 100%;
  max-height: 50px;
}

.site-header .navbar-header .quote-btn {
  display: none;
}

.site-header .navbar-collapse {
  padding: 0;
  background-color: transparent;
  color: #3c3c3c;
}

.navbar-nav {
  width: 100%;
}

.navbar-nav > li:hover > a {
  color: #fb3c4a;
  background: transparent;
}

.navbar-nav > li > a {
  background: transparent;
  text-transform: uppercase;
  color: inherit;
  font-family: "Quicksand", sans-serif;
  font-weight: 700;
  font-size: 14px;
  line-height: 20px;
  padding: 14px 0;
  margin: 0 16px;
  transition:
    background 350ms,
    color 350ms,
    border-color 350ms;
  color: #f2e9e1;
}

.navbar-nav > li.active > a,
.navbar-nav > li > a:hover,
.navbar-nav > li > a:focus {
  color: #fb3c4a;
  background: transparent;
}

.navbar-nav > li > .dropdown-menu .nav-opened .dropdown-menu,
.navbar-nav > li > .dropdown-menu .dropdown-menu {
  top: 0;
  margin-top: 0;
}

.navbar-nav > li.nav-opened > .dropdown-menu,
.navbar-nav > li:hover > .dropdown-menu {
  top: 100%;
  margin-top: 0;
  opacity: 1;
  visibility: visible;
}

.navbar-nav > li.quote-btn {
  margin-left: 12px;
  float: right;
}

.navbar-nav > li.quote-btn .btn {
  padding: 9px 24px 8px;
  font-size: 14px;
  font-weight: 700;
}

.navbar-nav .label {
  margin-top: -3px;
  vertical-align: middle;
  display: inline-block;
  font-family: "Quicksand", sans-serif;
  margin-left: 5px;
  text-transform: uppercase;
  padding: 3px 10px 4px;
  -ms-border-radius: 20px;
  border-radius: 20px;
}

.site-header .navbar-nav > li > a {
  padding-top: 35px;
  padding-bottom: 35px;
}

.site-header .navbar-brand {
  margin-top: 20px;
  margin-bottom: 20px;
  margin-left: 0;
  z-index: 6;
}

.site-header .navbar-collapse {
  float: right;
  width: auto;
}

.site-header .single-slide {
  min-height: 670px;
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center center;
}

.site-header.is-transparent {
  color: #fff;
}
.m-pad {
  top: 6vh !important;
  margin-right: 5vh !important;
  transition-duration: 1000ms;
}

.site-header.is-transparent .topbar,
.site-header.is-transparent .navbar {
  background: transparent;
  position: absolute;
  width: 100%;
  z-index: 9;
  color: #fff;
}

.site-header.is-transparent .topbar + .navbar {
  margin-top: 43px;
}

.site-header.is-transparent .topbar {
  border-color: rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.1);
}

.site-header.is-transparent .topbar a:not(.btn):hover {
  color: #fb3c4a;
}

.site-header.is-transparent .topbar .social li a {
  color: #fb3c4a;
}

.site-header.is-transparent .topbar .social li a:hover {
  color: #fff;
}

.site-header.is-transparent .navbar-nav > li > a:not(.btn):after {
  content: "";
  position: absolute;
  left: 0;
  bottom: 28px;
  width: 100%;
  height: 2px;
  background-color: #fff;
  opacity: 0;
  transition: all 0.4s;
}

.btn-close {
  background-color: #fb3c4a;
  border: 1.5px solid #fb3c4a;
  height: 100% !important;
  cursor: pointer;
  /* margin-top: 0.7rem;
  margin-right: 1rem; */
}
.btn-menu {
  min-width: 0px !important;
  height: 100% !important;
  background-color: #fb3c4a !important;
}
.navbar-settings-list {
  margin: auto;
  display: inline-flex;

  padding-bottom: -5rem;
  align-items: center;
}

.navbar-settings-list > li {
  padding-right: 1rem;
  min-height: 30px;
}
.settings-menu {
  height: 30px;
}

.menu-content .link {
  padding: 5px;
  text-decoration: none;
  color: #3d3b3f;
}
.menu-content .link:hover {
  color: #3d3b3f;
}
.menu-content .menu-item:nth-child(1) {
  border-bottom: 0.5px solid #75727ae0;
}
.menu-content .link .active {
  color: #3d3b3f;
}
</style>
