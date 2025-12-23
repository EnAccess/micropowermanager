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
        All required associated files to complete the installation of the
        software as well as of its associated apps (Agent App and Customer
        Registration App), can be found below:
      </p>
      <div class="router-box">
        <p>You can download our applications' .apk files from Github</p>
        <p>
          <a
            href="https://github.com/EnAccess/micropowermanager-agent-app/releases/download/v1.6.1/micropowermanager-agent-app-v1.6.1-release.apk"
          >
            Agent App
          </a>
          -
          <a
            href="https://github.com/EnAccess/micropowermanager-customer-registration-app/releases/download/v1.5.0/micropowermanager-customer-registration-app-v1.5.0-release.apk"
          >
            Customer Registration App
          </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { config } from "@/config"
export default {
  name: "WelcomePage",
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
  margin-top: 1rem;
}

.router-box p {
  width: 100%;
  margin-top: 8px;
  padding: 4px;
}
</style>
