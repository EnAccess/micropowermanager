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
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.publicKey'),
                    }"
                  >
                    <label for="publicKey">
                      {{ $tc("phrases.publicKey") }}
                    </label>
                    <md-input
                      id="publicKey"
                      name="publicKey"
                      v-model="credentialService.credential.publicKey"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.publicKey") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.webhookSecret'),
                    }"
                  >
                    <label for="webhookSecret">
                      {{ $tc("phrases.webhookSecret") }}
                    </label>
                    <md-input
                      id="webhookSecret"
                      name="webhookSecret"
                      v-model="credentialService.credential.webhookSecret"
                      v-validate="'required|min:3'"
                      type="password"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.webhookSecret") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.callbackUrl'),
                    }"
                  >
                    <label for="callbackUrl">
                      {{ $tc("phrases.callbackUrl") }}
                    </label>
                    <md-input
                      id="callbackUrl"
                      name="callbackUrl"
                      v-model="credentialService.credential.callbackUrl"
                      v-validate="'required|url'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.callbackUrl") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field
                    :class="{
                      'md-invalid': errors.has('Credential-Form.merchantName'),
                    }"
                  >
                    <label for="merchantName">
                      {{ $tc("phrases.merchantName") }}
                    </label>
                    <md-input
                      id="merchantName"
                      name="merchantName"
                      v-model="credentialService.credential.merchantName"
                      v-validate="'required|min:2'"
                    />
                    <span class="md-error">
                      {{ errors.first("Credential-Form.merchantName") }}
                    </span>
                  </md-field>
                </div>
                <div
                  class="md-layout-item md-xlarge-size-100 md-large-size-50 md-medium-size-50 md-small-size-50"
                >
                  <md-field>
                    <label for="environment">
                      {{ $tc("phrases.environment") }}
                    </label>
                    <md-select
                      id="environment"
                      name="environment"
                      v-model="credentialService.credential.environment"
                    >
                      <md-option value="test">{{ $tc("phrases.test") }}</md-option>
                      <md-option value="live">{{ $tc("phrases.live") }}</md-option>
                    </md-select>
                  </md-field>
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
      try {
        await this.credentialService.getCredential()
      } catch (error) {
        this.alertError("Failed to get credential")
      }
    },
    async submitCredentialForm() {
      const validation = await this.$validator.validateAll("Credential-Form")
      if (!validation) {
        return
      }

      this.loading = true
      try {
        await this.credentialService.updateCredential()
        this.alertSuccess("Credential updated successfully")
        EventBus.$emit("credential-updated")
      } catch (error) {
        this.alertError("Failed to update credential")
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.Credential-Form {
  padding: 1rem;
}
</style>
