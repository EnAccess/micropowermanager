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
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.merchantId'),
                    }"
                  >
                    <label for="merchantId">
                      {{ $tc("phrases.merchantId") }}
                    </label>
                    <md-input
                      id="merchantId"
                      name="merchantId"
                      v-model="credentialService.credential.merchantId"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.merchantId") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.secretKey'),
                    }"
                  >
                    <label for="secretKey">
                      {{ $tc("phrases.secretKey") }}
                    </label>
                    <md-input
                      id="secretKey"
                      name="secretKey"
                      v-model="credentialService.credential.secretKey"
                      v-validate="'required|min:3'"
                      type="password"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.secretKey") }}
                    </span>
                  </md-field>
                </div>

                <div
                  v-if="credentialService.credential.paymentUrl"
                  class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
                >
                  <p>{{ $tc("phrases.pleaseShareThis") }}</p>
                  <br />
                  <a
                    target="_blank"
                    :href="credentialService.credential.paymentUrl"
                  >
                    {{ credentialService.credential.paymentUrl }}
                  </a>
                  <br />
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
        this.alertNotify("success", "Credentials updated successfully")
        EventBus.$emit("WaveMoney")
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
