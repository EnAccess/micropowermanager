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
                      'md-invalid': errors.has('Credential-Form.api_key'),
                    }"
                  >
                    <label for="api_key">API Key</label>
                    <md-input
                      id="api_key"
                      name="api_key"
                      type="password"
                      v-model="credentialService.credential.apiKey"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.api_key") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.api_secret'),
                    }"
                  >
                    <label for="api_secret">Api Secret</label>
                    <md-input
                      id="api_secret"
                      name="api_secret"
                      type="password"
                      v-model="credentialService.credential.apiSecret"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.api_secret") }}
                    </span>
                  </md-field>
                </div>
              </div>
            </div>

            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="md-layout md-gutter" style="display: grid">
                <div class="md-layout-item md-size-100">
                  <div
                    v-if="credentialService.credential.isAuthenticated"
                    class="authorize-div"
                  >
                    <img src="@/assets/images/authorized.png" />
                    <label style="padding-left: 2rem !important">
                      Authorized
                    </label>
                  </div>

                  <div
                    v-if="!credentialService.credential.isAuthenticated"
                    class="authorize-div"
                  >
                    <img src="@/assets/images/unauthorized.png" />
                    <label style="padding-left: 2rem !important">
                      Unauthorized
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <md-card-actions>
          <md-button class="md-raised md-primary" type="submit">Save</md-button>
        </md-card-actions>
      </md-card>
    </form>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"

export default {
  name: "Credential",
  mixins: [notify],
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
        const updatedData = await this.credentialService.updateCredential()
        this.alertNotify(updatedData.alert.type, updatedData.alert.message)
        EventBus.$emit("Spark Meter")
      } catch (e) {
        this.alertNotify("error", "MPM failed to verify your request")
      }
      this.loading = false
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

.authorize-div {
  max-width: 30%;
  margin: auto;
  margin-top: 1rem;
}
</style>
