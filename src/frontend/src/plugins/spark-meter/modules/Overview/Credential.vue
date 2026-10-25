<template>
  <div>
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
      class="Credential-Form"
    >
      <md-card>
        <md-card-header>
          <div class="md-subhead">
            Configure your SparkMeter credentials to manage meters through
            MicroPowerManager.
          </div>
        </md-card-header>
        <md-card-content>
          <div class="md-layout md-gutter my-layout">
            <div class="md-layout-item">
              <md-field
                :class="{ 'md-invalid': errors.has('Credential-Form.apiKey') }"
              >
                <label for="apiKey">API Key</label>
                <md-input
                  id="apiKey"
                  name="apiKey"
                  v-model="credentialService.credential.apiKey"
                  v-validate="'required'"
                />
                <span class="md-error">
                  {{ errors.first("Credential-Form.apiKey") }}
                </span>
              </md-field>
              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.apiSecret'),
                }"
              >
                <label for="apiSecret">API Secret</label>
                <md-input
                  id="apiSecret"
                  name="apiSecret"
                  type="password"
                  v-model="credentialService.credential.apiSecret"
                  v-validate="'required'"
                />
                <span class="md-error">
                  {{ errors.first("Credential-Form.apiSecret") }}
                </span>
              </md-field>

              <div
                class="advanced-toggle"
                @click="showAdvanced = !showAdvanced"
              >
                <md-icon>
                  {{
                    showAdvanced
                      ? "keyboard_arrow_down"
                      : "keyboard_arrow_right"
                  }}
                </md-icon>
                <span>Advanced Options</span>
              </div>
              <div v-if="showAdvanced">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.apiUrl'),
                  }"
                >
                  <label for="apiUrl">SparkMeter API URL</label>
                  <md-input
                    id="apiUrl"
                    name="apiUrl"
                    v-model="credentialService.credential.apiUrl"
                    v-validate="'required'"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.apiUrl") }}
                  </span>
                </md-field>
              </div>
            </div>

            <div class="md-layout-item md-small-size-100">
              <div class="info-box">
                <h3>How to get your credentials:</h3>
                <ol>
                  <li>
                    The API key and secret are provided by SparkMeter. Save
                    them, then sync sites from the Sites page.
                  </li>
                  <li>
                    On the Sites page, edit each site and add its ThunderCloud
                    API token. It is found on a ThunderCloud user with the "api"
                    role, under API Credentials.
                  </li>
                  <li>
                    Only change the Advanced Options if SparkMeter gave you a
                    different API URL.
                  </li>
                </ol>
              </div>
            </div>
          </div>

          <div class="md-layout md-gutter status-row">
            <div class="md-layout-item md-small-hide"></div>
            <div class="md-layout-item">
              <md-card
                class="md-elevation-1 status-card"
                :class="{
                  'md-primary': status.state === 'success',
                  'md-accent': status.state === 'error',
                }"
              >
                <md-card-content>
                  <div>
                    <strong>Status:</strong>
                    {{ status.label }}
                  </div>
                  <div>{{ status.message }}</div>
                </md-card-content>
                <md-progress-bar md-mode="indeterminate" v-if="loading" />
              </md-card>
            </div>
            <div class="md-layout-item md-small-hide"></div>
          </div>
        </md-card-content>
        <md-card-actions>
          <md-button
            class="md-raised md-accent"
            type="button"
            :disabled="loading"
            @click="testCredentials"
          >
            Test Credentials
          </md-button>
          <md-button
            class="md-raised md-primary"
            type="submit"
            :disabled="loading"
          >
            {{ $tc("words.save") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </form>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"
import { SiteService } from "../../services/SiteService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      siteService: new SiteService(),
      showAdvanced: false,
      loading: false,
      testResult: null,
    }
  },
  mounted() {
    this.load()
  },
  computed: {
    status() {
      if (this.testResult) {
        return this.testResult
      }
      if (!this.credentialService.credential.isAuthenticated) {
        return {
          state: "error",
          label: "Not connected",
          message:
            "Could not authenticate. Check the API key, secret and API URL, or use Test Credentials to see why.",
        }
      }
      if (this.siteService.list.length === 0) {
        return {
          state: "idle",
          label: "Action needed",
          message: "Sync sites from the Sites page.",
        }
      }
      const siteWithoutToken = this.siteService.list.find(
        (site) => !site.thundercloudToken,
      )
      if (siteWithoutToken) {
        return {
          state: "idle",
          label: "Action needed",
          message: `Add the ThunderCloud API token for ${siteWithoutToken.name} on the Sites page.`,
        }
      }
      const failingSite = this.siteService.list.find(
        (site) => !site.isAuthenticated,
      )
      if (failingSite) {
        return {
          state: "error",
          label: "Error",
          message: `ThunderCloud rejected the URL or API token for ${failingSite.name}. Edit it on the Sites page.`,
        }
      }
      return {
        state: "success",
        label: "Connected",
        message: "SparkMeter and ThunderCloud are ready to use.",
      }
    },
  },
  methods: {
    async load() {
      this.testResult = null
      await Promise.all([
        this.credentialService.getCredential(),
        this.siteService.getSites(),
      ])
    },
    async testCredentials() {
      const isValid = await this.$validator.validateAll("Credential-Form")
      if (!isValid) {
        return
      }
      this.loading = true
      try {
        const result = await this.credentialService.checkCredential()
        this.testResult = result.valid
          ? {
              state: "success",
              label: "Success",
              message: "Credentials are valid. Save them to apply.",
            }
          : {
              state: "error",
              label: "Error",
              message: `Invalid credentials: ${result.message}`,
            }
      } catch (e) {
        this.testResult = { state: "error", label: "Error", message: e.message }
      } finally {
        this.loading = false
      }
    },
    async submitCredentialForm() {
      const isValid = await this.$validator.validateAll("Credential-Form")
      if (!isValid) {
        return
      }
      this.loading = true
      try {
        await this.credentialService.updateCredential()
        await this.load()
        this.alertNotify(
          this.status.state === "error" ? "error" : "success",
          this.status.message,
        )
        EventBus.$emit("SparkMeter")
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
@media (max-width: 960px) {
  .my-layout {
    flex-direction: column-reverse;
  }
}

.status-row {
  margin-top: 1.5rem;
}

.advanced-toggle {
  display: flex;
  align-items: center;
  cursor: pointer;

  .md-icon {
    margin: 0;
  }
}

.info-box {
  padding: 1.5rem;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 4px solid #4caf50;

  h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.1rem;
    margin-bottom: 1rem;
  }

  ol {
    padding-left: 1.5rem;
    margin-bottom: 1rem;

    li {
      margin-bottom: 0.5rem;
      line-height: 1.5;
    }
  }
}

.md-subhead {
  margin-top: 0.5rem;
  font-size: 0.9rem;
}
</style>
