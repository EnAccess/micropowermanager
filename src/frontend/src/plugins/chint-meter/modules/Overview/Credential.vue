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
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
            >
              <div class="md-layout md-gutter">
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.username'),
                    }"
                  >
                    <label for="username">User Name</label>
                    <md-input
                      id="username"
                      name="username"
                      v-model="credentialService.credential.userName"
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
                      'md-invalid': errors.has('Credential-Form.userSecret'),
                    }"
                  >
                    <label for="apiKey">User Password</label>
                    <md-input
                      id="userSecret"
                      type="password"
                      name="userSecret"
                      v-model="credentialService.credential.userPassword"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.userSecret") }}
                    </span>
                  </md-field>
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
        await this.credentialService.updateCredential()
        this.alertNotify("success", "Authentication Successful")
        EventBus.$emit("Chint Meter")
      } catch (e) {
        this.alertNotify("error", e.message)
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
</style>
