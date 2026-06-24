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
            Configure your SunKing SHS API credentials to manage devices through
            MicroPowerManager.
            <a
              href="https://sunking.com/"
              target="_blank"
              rel="noopener noreferrer"
            >
              Learn more about SunKing SHS
            </a>
          </div>
        </md-card-header>
        <md-card-content>
          <div class="md-layout md-gutter my-layout">
            <div class="md-layout-item">
              <div class="field-with-help">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.clientId'),
                  }"
                >
                  <label for="clientId">SunKing Client ID</label>
                  <md-input
                    id="clientId"
                    name="clientId"
                    v-model="credentialService.credential.clientId"
                    v-validate="'required|min:3'"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.clientId") }}
                  </span>
                </md-field>
                <md-icon class="field-help">
                  info_outline
                  <md-tooltip md-direction="top">
                    The public identifier issued by SunKing for your
                    organization. This is NOT the secret — it is safe to share
                    and identifies your account.
                  </md-tooltip>
                </md-icon>
              </div>

              <div class="field-with-help">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Credential-Form.clientSecret'),
                  }"
                >
                  <label for="clientSecret">SunKing Client Secret</label>
                  <md-input
                    id="clientSecret"
                    type="password"
                    name="clientSecret"
                    v-model="credentialService.credential.clientSecret"
                    v-validate="'required|min:3'"
                  />
                  <span class="md-error">
                    {{ errors.first("Credential-Form.clientSecret") }}
                  </span>
                </md-field>
                <md-icon class="field-help">
                  info_outline
                  <md-tooltip md-direction="top">
                    The confidential password paired with your Client ID. Keep
                    it private and never share it — do not paste the Client ID
                    here, or authentication will fail.
                  </md-tooltip>
                </md-icon>
              </div>
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
                  <div class="field-with-help">
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Credential-Form.authUrl'),
                      }"
                    >
                      <label for="authUrl">SunKing SHS Auth URL</label>
                      <md-input
                        id="authUrl"
                        name="authUrl"
                        v-model="credentialService.credential.authUrl"
                        v-validate="'required|min:3'"
                      />
                      <span class="md-error">
                        {{ errors.first("Credential-Form.authUrl") }}
                      </span>
                    </md-field>
                    <md-icon class="field-help">
                      info_outline
                      <md-tooltip md-direction="top">
                        The OAuth token endpoint used to authenticate. Only
                        change this if SunKing has given you a different URL
                        from the default.
                      </md-tooltip>
                    </md-icon>
                  </div>

                  <div class="field-with-help">
                    <md-field
                      :class="{
                        'md-invalid': errors.has('Credential-Form.apiUrl'),
                      }"
                    >
                      <label for="apiUrl">SunKing SHS API URL</label>
                      <md-input
                        id="apiUrl"
                        name="apiUrl"
                        v-model="credentialService.credential.apiUrl"
                        v-validate="'required|min:3'"
                      />
                      <span class="md-error">
                        {{ errors.first("Credential-Form.apiUrl") }}
                      </span>
                    </md-field>
                    <md-icon class="field-help">
                      info_outline
                      <md-tooltip md-direction="top">
                        The base URL for SunKing API requests. Only change this
                        if SunKing has given you a different URL from the
                        default.
                      </md-tooltip>
                    </md-icon>
                  </div>
                </div>
              </transition>
            </div>

            <div class="md-layout-item md-small-size-100">
              <div class="info-box">
                <h3>Client ID vs. Client Secret</h3>
                <ul>
                  <li>
                    The
                    <strong>Client ID</strong>
                    is the public identifier for your account.
                  </li>
                  <li>
                    The
                    <strong>Client Secret</strong>
                    is the private password paired with the Client ID. Keep it
                    confidential.
                  </li>
                </ul>
                <p class="info-warning">
                  <md-icon>warning</md-icon>
                  Both are issued by SunKing. Copy each value into its matching
                  field exactly as provided. Swapping them is the most common
                  cause of failed authentication.
                </p>
              </div>
            </div>
          </div>

          <div class="md-layout md-gutter status-row">
            <div class="md-layout-item md-small-hide"></div>
            <div class="md-layout-item">
              <md-card
                class="md-elevation-1 md-secondary status-card md-with-hover"
                :class="{
                  'md-disabled':
                    testStatus === 'idle' || testStatus === 'loading',
                  'md-primary': testStatus === 'success',
                  'md-accent': testStatus === 'error',
                }"
              >
                <md-card-content>
                  <div>
                    <strong>Status:</strong>
                    {{ statusLabel }}
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
              </md-card>
            </div>
            <div class="md-layout-item md-small-hide"></div>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
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
import { CredentialService } from "../../services/CredentialService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"

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
        this.alertNotify("success", "Authentication Successful")
        EventBus.$emit("SunKingSHS")
      } catch (e) {
        this.alertNotify("error", e.message)
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

        await this.credentialService.updateCredential()
        let success = await this.credentialService.checkCredential()

        this.testStatus = success ? "success" : "error"
        this.testStatusMessage = success
          ? ""
          : "Please check your Client ID and Client Secret."
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

.field-with-help {
  display: flex;
  align-items: center;

  .md-field {
    flex: 1;
  }

  .field-help {
    margin-left: 0.5rem;
    cursor: help;
    color: rgba(0, 0, 0, 0.4);
    font-size: 20px !important;
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

  ul {
    padding-left: 1.5rem;
    margin-bottom: 1rem;

    li {
      margin-bottom: 0.5rem;
      line-height: 1.5;
    }
  }

  .info-warning {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0;
    padding: 0.75rem;
    background-color: #fff8e1;
    border-radius: 4px;
    line-height: 1.5;
    color: #5f4300;

    .md-icon {
      color: #f9a825;
      margin: 0;
      flex-shrink: 0;
    }
  }
}

.status-row {
  margin-top: 1.5rem;
}
</style>
