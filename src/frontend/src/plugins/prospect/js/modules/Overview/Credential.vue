<template>
  <div>
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
      class="Credential-Form"
    >
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="md-layout md-gutter">
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': submitted && errors.has('Credential-Form.apiUrl'),
                    }"
                  >
                    <label for="apiUrl">
                      {{ $tc("phrases.apiEndpoint") }}
                    </label>
                    <md-select
                      id="apiUrl"
                      name="apiUrl"
                      v-model="selectedEndpoint"
                      v-validate="'required'"
                    >
                      <md-option value="" disabled>Select endpoint</md-option>
                      <md-option value="installations">Installations</md-option>
                      <md-option value="payments">Payments</md-option>
                    </md-select>
                    <span class="md-error" v-if="submitted">
                      {{ errors.first("Credential-Form.apiUrl") }}
                    </span>
                  </md-field>
                </div>

                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': submitted && errors.has('Credential-Form.apiToken'),
                    }"
                  >
                    <label for="apiToken">
                      {{ $tc("phrases.apiToken") }}
                    </label>
                    <md-input
                      id="apiToken"
                      name="apiToken"
                      type="password"
                      v-model="credentialService.credential.apiToken"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error" v-if="submitted">
                      {{ errors.first("Credential-Form.apiToken") }}
                    </span>
                  </md-field>
                </div>
              </div>
            </div>

            <div class="md-layout md-gutter" style="padding: 2.5rem">
              <div
                class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-layout-item--right"
              >
                <span style="font-weight: bold">
                  Prospect Webhook URL:
                  <p class="token-value">{{ prospectWebhookUrl }}</p>
                </span>
              </div>
            </div>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <md-card-actions>
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
import { EventBus } from "@/shared/eventbus"
import { baseUrl } from "@/repositories/Client/AxiosClient"
import { mapGetters } from "vuex"
import { notify } from "@/mixins/notify"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      credentialService: new CredentialService(),
      loading: false,
      submitted: false,
      selectedEndpoint: "",
    }
  },
  mounted() {
    this.getCredential()
  },
  methods: {
    async getCredential() {
      await this.credentialService.getCredential()
      const savedUrl = this.credentialService.credential.apiUrl || ""
      if (savedUrl.endsWith("/installations") || savedUrl.includes("/installations")) {
        this.selectedEndpoint = "installations"
      } else if (savedUrl.endsWith("/payments") || savedUrl.includes("/payments")) {
        this.selectedEndpoint = "payments"
      } else {
        this.selectedEndpoint = ""
      }
    },
    async submitCredentialForm() {
      this.submitted = true
      let validator = await this.$validator.validateAll("Credential-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        this.credentialService.credential.apiUrl = this.selectedEndpoint
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Updated successfully")
        EventBus.$emit("Prospect")
      } catch (e) {
        this.alertNotify("error", "MPM failed to verify your request")
      }
      this.loading = false
    },
  },
  computed: {
    ...mapGetters({
      authUser: "auth/getAuthenticateUser",
    }),
    prospectWebhookUrl() {
      return `${baseUrl}/api/prospect/webhook/${this.authUser.companyId}`
    },
  },
}
</script>

<style lang="scss" scoped>
.md-card {
  height: 100% !important;
}

.Credential-Form {
  height: 100% !important;
}
.token-value {
  font-size: 16px;
  color: #333;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #f9f9f9;
  font-weight: normal;
}
</style>
