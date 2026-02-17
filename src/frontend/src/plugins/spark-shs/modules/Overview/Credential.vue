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
            Configure your Spark SHS API credentials to manage devices through
            MicroPowerManager.
            <a
              href="https://sparkenergy.io/"
              target="_blank"
              rel="noopener noreferrer"
            >
              Learn more about Spark SHS
            </a>
          </div>
        </md-card-header>
        <md-card-content>
          <div class="md-layout md-gutter my-layout">
            <div class="md-layout-item">
              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.clientId'),
                }"
              >
                <label for="clientId">Spark SHS client_id</label>
                <md-input
                  id="clientId"
                  name="clientId"
                  v-model="credentialService.credential.clientId"
                  v-validate="'required|min:3'"
                  placeholder="Your Spark SHS client_id"
                />
                <span class="md-error">
                  {{ errors.first("Credential-Form.clientId") }}
                </span>
              </md-field>

              <md-field
                :class="{
                  'md-invalid': errors.has('Credential-Form.clientSecret'),
                }"
              >
                <label for="clientSecret">Spark SHS client_secret</label>
                <md-input
                  id="clientSecret"
                  name="clientSecret"
                  v-model="credentialService.credential.clientSecret"
                  v-validate="'required|min:3'"
                  placeholder="Your Spark SHS client_secret"
                  type="password"
                />
                <span class="md-error">
                  {{ errors.first("Credential-Form.clientSecret") }}
                </span>
              </md-field>
              <div
                class="advanced-toggle"
                @click="showAdvanced = !showAdvanced"
              >
                <md-icon
                  class="toggle-icon"
                  :class="{ expanded: showAdvanced }"
                >
                  {{
                    showAdvanced
                      ? "keyboard_arrow_down"
                      : "keyboard_arrow_right"
                  }}
                </md-icon>

                <span>Advanced Options</span>
              </div>

              <!-- Advanced Fields (Collapsible) -->
              <transition name="expand">
                <div v-if="showAdvanced" class="advanced-section">
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.authUrl'),
                    }"
                  >
                    <label for="clientSecret">Spark SHS Auth URL</label>
                    <md-input
                      id="authUrl"
                      name="authUrl"
                      v-model="credentialService.credential.authUrl"
                      v-validate="'required|min:3'"
                      placeholder="Your Spark SHS auth_url"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.authUrl") }}
                    </span>
                  </md-field>

                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.apiUrl'),
                    }"
                  >
                    <label for="clientSecret">Spark SHS API URL</label>
                    <md-input
                      id="apiUrl"
                      name="apiUrl"
                      v-model="credentialService.credential.apiUrl"
                      v-validate="'required|min:3'"
                      placeholder="Your Spark SHS api_url"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.apiUrl") }}
                    </span>
                  </md-field>
                </div>
              </transition>
            </div>

            <div class="md-layout-item md-small-size-100">
              <div class="info-box">
                <h3>How to get your credentials:</h3>
                <ol>
                  <li>
                    Log into the
                    <a
                      href="https://platform.ruralspark.com/"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      Spark Portal
                    </a>
                    and confirm you can see devices.
                  </li>
                  <li>
                    Credentials (client_id and client_secret) are provided by
                    SparkSHS support.
                  </li>
                </ol>
              </div>
            </div>
          </div>

          <div class="md-layout md-gutter">
            <div class="md-layout-item md-small-hide"></div>
            <div class="md-layout-item">
              <md-card
                class="md-elevation-1 md-secondary status-card md-with-hover"
                :class="{
                  'md-disabled': testStatus === 'idle',
                  'md-disabled': testStatus === 'loading',
                  'md-primary': testStatus === 'success',
                  'md-accent': testStatus === 'error',
                }"
              >
                <md-card-content>
                  <div>
                    <strong>Status:</strong>
                    <span
                      :class="{
                        idle: testStatus === 'idle',
                        loading: testStatus === 'loading',
                        success: testStatus === 'success',
                        error: testStatus === 'error',
                      }"
                    >
                      {{ statusLabel }}
                    </span>
                  </div>

                  <div v-if="testStatus === 'idle'">
                    Credentials not tested yet.
                  </div>

                  <div v-if="testStatus === 'loading'">
                    Testing credentials…
                  </div>

                  <div v-if="testStatus === 'success'">
                    Credentials are valid and ready to use.
                  </div>

                  <div v-if="testStatus === 'error'">
                    Invalid credentials:
                    {{ testStatusMessage }}
                  </div>
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
            @click="testCredentials"
          >
            Test Credentials
          </md-button>
          <md-button class="md-raised md-primary" type="submit">
            {{ $tc("words.save") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </form>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService"
import { notify } from "@/mixins/notify"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      showAdvanced: false,
      loading: false,
      testStatus: "idle", // idle | loading | success | error
      testStatusMessage: "",
    }
  },
  mounted() {
    this.getCredential()
  },
  methods: {
    async getCredential() {
      await this.credentialService.getCredential()
    },
    async submitCredentialForm() {
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Credentials updated successfully")
      } catch (e) {
        this.alertNotify("error", "Failed to update credentials")
      }
      this.loading = false
    },
    async testCredentials() {
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }

      try {
        this.loading = true
        this.testStatus = "loading"

        let success = await this.credentialService.checkCredential()

        this.loading = false
        this.testStatus = success ? "success" : "error"
        this.testStatusMessage = success
          ? ""
          : "Please check your client_id and client_secret."
      } catch (e) {
        this.testStatus = "error"
        this.testStatusMessage = e.message
      } finally {
        this.loading = false
      }
    },
  },
  computed: {
    statusLabel() {
      switch (this.testStatus) {
        case "loading":
          return "Testing"
        case "success":
          return "Success"
        case "error":
          return "Error"
        default:
          return "Not tested"
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

  a {
    color: #4caf50;
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }
}

.md-subhead {
  margin-top: 0.5rem;
  font-size: 0.9rem;

  a {
    color: #4caf50;
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }
}
</style>
