<template>
  <div class="welcome">
    <div class="content">
      <div class="title">
        Welcome to
        <span class="title highlight">MicroPowerManager</span>
      </div>

      <p class="cloud-description">
        This is the cloud version of the MicroPowerManager. It is a web
        application that allows you to manage your MiniGrids easily.
      </p>
      <div class="md-layout md-gutter">
        <div class="md-layout-item">
          <md-button class="md-accent md-raised" to="/register">
            <md-icon>person_add</md-icon>
            Register
          </md-button>
        </div>
        <div class="md-layout-item">
          <md-button class="md-primary md-raised" to="/login">
            <md-icon>login</md-icon>
            {{ $tc("phrases.signIn") }}
          </md-button>
        </div>
      </div>

      <p class="cloud-description">
        The companion mobile app for agents and customer registration is
        available on Google Play and the App Store:
      </p>
      <div class="router-box">
        Download Field App
        <div class="app-links">
          <a
            href="https://play.google.com/store/apps/details?id=io.micropowermanager.fieldapp"
            target="_blank"
            rel="noopener noreferrer"
          >
            <img
              class="store-badge"
              alt="Get it on Google Play"
              :src="googlePlayBadge"
            />
          </a>
          <a
            href="https://apps.apple.com/ng/app/mpm-field-app/id6809758728"
            target="_blank"
            rel="noopener noreferrer"
          >
            <img
              class="store-badge"
              alt="Download on the App Store"
              :src="appStoreBadge"
            />
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import appStoreBadge from "@/assets/icons/app-store.svg"
import googlePlayBadge from "@/assets/icons/google-store.svg"
import { config } from "@/config.js"
export default {
  name: "WelcomePage",
  data() {
    return {
      appStoreBadge: appStoreBadge,
      googlePlayBadge: googlePlayBadge,
    }
  },
  created() {
    // In the Demo environment the company registration page is hidden
    if (config.mpmEnv === "demo") {
      this.$router.push("/login")
    }
  },
}
</script>

<style lang="scss" scoped>
.welcome {
  background: linear-gradient(
    to right,
    $brand-background-dark,
    $brand-background
  );
  display: flex;
  flex-direction: column;
  align-items: center;

  width: 100vw;
  min-height: 100vh;
}

.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 720px;
  text-align: center;
  margin-top: 18rem;
  padding: 0 1rem;
}

.title {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 1rem;
}

.cloud-description {
  text-align: center;
  padding: 15px;
  font-size: 16px;
  font-weight: 500;
}

.highlight {
  color: $brand-secondary;
}

.router-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin-top: 1rem;
}

.app-links {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}

.store-badge {
  display: block;
  height: 32px;
  width: auto;
}

@media (max-width: 768px) {
  .content {
    margin-top: 4rem;
  }

  .title {
    font-size: 1.5rem;
  }
}
</style>
