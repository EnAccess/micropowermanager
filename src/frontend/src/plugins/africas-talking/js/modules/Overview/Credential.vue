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
                      'md-invalid': errors.has('Credential-Form.apiKey'),
                    }"
                  >
                    <label for="apiKey">
                      {{ $tc("phrases.apiKey") }}
                    </label>
                    <md-input
                      id="apiKey"
                      name="apiKey"
                      v-model="credentialService.credential.apiKey"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.apiKey") }}
                    </span>
                  </md-field>
                </div>

                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.username'),
                    }"
                  >
                    <label for="username">
                      {{ $tc("words.username") }}
                    </label>
                    <md-input
                      id="username"
                      name="username"
                      v-model="credentialService.credential.username"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.username") }}
                    </span>
                  </md-field>
                </div>

                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.apiToken'),
                    }"
                  >
                    <label for="shortCode">
                      {{ $tc("phrases.shortCode") }}
                    </label>
                    <md-input
                      id="shortCode"
                      name="shortCode"
                      v-model="credentialService.credential.shortCode"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.shortCode") }}
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
                  Incoming Messages URL:
                  <p class="token-value">{{ incomingMessagesUrl }}</p>
                </span>
              </div>

              <div
                class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-layout-item--right"
              >
                <span style="font-weight: bold">
                  Delivery Reports URL:
                  <p class="token-value">{{ deliveryReportsUrl }}</p>
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

export default {
  name: "Credential",
  data() {
    return {
      credentialService: new CredentialService(),
      loading: false,
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
        this.alertNotify("success", "Updated successfully")
        EventBus.$emit("Africas Talking")
      } catch (e) {
        this.alertNotify("error", "MPM failed to verify your request")
      }
      this.loading = false
    },
    alertNotify(type, message) {
      this.$notify({
        group: "notify",
        type: type,
        title: type + " !",
        text: message,
      })
    },
  },
  computed: {
    ...mapGetters({
      authUser: "auth/getAuthenticateUser",
    }),
    incomingMessagesUrl() {
      return `${baseUrl}/api/africas-talking/callback/${this.authUser.companyId}/incoming-messages`
    },
    deliveryReportsUrl() {
      return `${baseUrl}/api/africas-talking/callback/${this.authUser.companyId}/delivery-reports`
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
