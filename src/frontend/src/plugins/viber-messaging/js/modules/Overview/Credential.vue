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
                      'md-invalid': errors.has('Credential-Form.apiToken'),
                    }"
                  >
                    <label for="apiToken">
                      {{ $tc("phrases.apiToken") }}
                    </label>
                    <md-input
                      id="apiToken"
                      name="apiToken"
                      v-model="credentialService.credential.apiToken"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.apiToken") }}
                    </span>
                  </md-field>
                </div>

                <div
                  v-if="credentialService.credential.deepLink"
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <p>
                    {{ $tc("phrases.distributeThis") }}
                  </p>
                  <br />
                  <span style="font-weight: bold">
                    {{ credentialService.credential.deepLink }}
                  </span>
                </div>
              </div>
            </div>

            <div
              class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-50"
            >
              <div class="md-layout md-gutter" style="display: grid">
                <div class="md-layout-item md-size-100">
                  <div
                    v-if="credentialService.credential.hasWebhookCreated"
                    class="authorize-div"
                  >
                    <img src="@/assets/images/authorized.png" />
                    <label style="padding-left: 2rem !important">
                      {{ $tc("words.authorized") }}
                    </label>
                  </div>

                  <div
                    v-if="!credentialService.credential.hasWebhookCreated"
                    class="authorize-div"
                  >
                    <img src="@/assets/images/unauthorized.png" />
                    <label style="padding-left: 2rem !important">
                      {{ $tc("words.unauthorized") }}
                    </label>
                  </div>
                </div>
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
        this.alertNotify("success", "Token updated successfully")
        EventBus.$emit("Viber Messaging")
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
</style>
