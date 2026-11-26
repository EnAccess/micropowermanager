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
            Sell electricity tokens for Calin meters on your own Calin API V2
            (AMR) system. When a customer pays, a kWh token is created
            automatically. You'll need your system's address and the user ID and
            password Calin gave you.
          </div>
        </md-card-header>
        <md-card-content>
          <md-field
            :class="{
              'md-invalid': errors.has('Credential-Form.apiUrl'),
            }"
          >
            <label for="apiUrl">API URL</label>
            <md-input
              id="apiUrl"
              name="apiUrl"
              v-model="credentialService.credential.apiUrl"
              v-validate="'required|url'"
              placeholder="http://203.0.113.10:8080"
            />
            <md-icon class="field-help">
              help_outline
              <md-tooltip md-direction="left">
                The web address of your Calin system, as Calin gave it to you.
              </md-tooltip>
            </md-icon>
            <span class="md-error">
              {{ errors.first("Credential-Form.apiUrl") }}
            </span>
          </md-field>
          <md-field
            :class="{
              'md-invalid': errors.has('Credential-Form.userId'),
            }"
          >
            <label for="userId">User ID</label>
            <md-input
              id="userId"
              name="userId"
              v-model="credentialService.credential.userId"
              v-validate="'required'"
            />
            <span class="md-error">
              {{ errors.first("Credential-Form.userId") }}
            </span>
          </md-field>
          <md-field
            :class="{
              'md-invalid': errors.has('Credential-Form.password'),
            }"
          >
            <label for="password">Password</label>
            <md-input
              id="password"
              name="password"
              type="password"
              v-model="credentialService.credential.password"
              v-validate="'required'"
            />
            <span class="md-error">
              {{ errors.first("Credential-Form.password") }}
            </span>
          </md-field>
          <div class="advanced-toggle" @click="showAdvanced = !showAdvanced">
            <md-icon>
              {{
                showAdvanced ? "keyboard_arrow_down" : "keyboard_arrow_right"
              }}
            </md-icon>
            <span>Advanced options</span>
          </div>
          <md-field v-if="showAdvanced">
            <label for="company">Company (optional)</label>
            <md-input
              id="company"
              name="company"
              v-model="credentialService.credential.company"
            />
            <md-icon class="field-help">
              help_outline
              <md-tooltip md-direction="left">
                Only needed if your Calin system asks for a company name when
                you log in.
              </md-tooltip>
            </md-icon>
          </md-field>
          <div class="md-layout md-gutter">
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

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      loading: false,
      showAdvanced: false,
      testStatus: "idle", // idle | loading | success | error
      testStatusMessage: "",
    }
  },
  async mounted() {
    await this.credentialService.getCredential()
    this.showAdvanced = Boolean(this.credentialService.credential.company)
  },
  methods: {
    async submitCredentialForm() {
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.credentialService.updateCredential()
        this.alertNotify(
          "success",
          "Calin accepted the login. Credentials saved",
        )
        EventBus.$emit("CalinMeterV2")
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
      }
    },
    async testCredentials() {
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        this.testStatus = "loading"
        await this.credentialService.checkCredential()
        this.testStatus = "success"
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
.md-field.md-has-password ::v-deep .md-toggle-password {
  right: 32px;
}

.advanced-toggle {
  display: flex;
  align-items: center;
  min-height: 48px;
  padding-top: 16px;
  margin: 4px 0 24px;
  font-size: 16px;
  cursor: pointer;
  user-select: none;

  .md-icon {
    margin: 0;
  }
}
</style>
